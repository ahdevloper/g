<?php
namespace App\Services;
use App\Models\Country;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;
use ZipArchive;
class GeoNamesImporter {
 private const BASE='https://download.geonames.org/export/dump/';
 private const M49='https://unstats.un.org/unsd/methodology/m49/overview/';
 private const LICENSE='CC BY 4.0';
 private array $stats=['status'=>'running','countries_imported'=>0,'cities_imported'=>0,'countries_missing'=>0,'duplicate_countries'=>0,'duplicate_cities'=>0,'invalid_coordinates'=>0];
 public function run(bool $keep=false): array {
  $run=DB::table('geo_import_runs')->insertGetId(['source'=>'GeoNames','license'=>self::LICENSE,'imported_at'=>now(),'status'=>'running','created_at'=>now(),'updated_at'=>now()]);
  $dir=storage_path('app/geo-source'); File::ensureDirectoryExists($dir);
  try {
   $ci=$this->download('countryInfo.txt',$dir.'/countryInfo.txt'); $all=$this->download('allCountries.zip',$dir.'/allCountries.zip'); $m49=$this->parseM49($this->getText(self::M49));
   $version=$all['last_modified']?:now()->toDateString(); $this->stats['source_version']=$version;
   $countries=$this->parseCountryInfo($ci['path'],$m49,$dir.'/allCountries.zip');
   DB::transaction(function()use($countries,$all,$dir){ DB::table('cities')->delete(); DB::table('countries')->delete(); $this->importCountries($countries); $ids=Country::pluck('id','iso2')->mapWithKeys(fn($id,$iso)=>[strtoupper($iso)=>$id])->all(); $this->importPlaces($all['path'],$ids); },600);
   $this->stats['status']='completed';
   DB::table('geo_import_runs')->where('id',$run)->update(['source_version'=>$version,'last_updated_at'=>now(),'license'=>self::LICENSE,'countries_imported'=>$this->stats['countries_imported'],'cities_imported'=>$this->stats['cities_imported'],'countries_missing'=>$this->stats['countries_missing'],'duplicate_countries'=>$this->stats['duplicate_countries'],'duplicate_cities'=>$this->stats['duplicate_cities'],'invalid_coordinates'=>$this->stats['invalid_coordinates'],'status'=>'completed','metadata'=>json_encode(['m49_source'=>self::M49,'dataset'=>'allCountries.zip']),'updated_at'=>now()]);
   if(!$keep)File::deleteDirectory($dir);
  }catch(Throwable $e){ DB::table('geo_import_runs')->where('id',$run)->update(['status'=>'failed','metadata'=>json_encode(['error'=>$e->getMessage()]),'updated_at'=>now()]); throw $e; }
  return $this->stats;
 }
 private function download(string $name,string $path):array {
  $r=(new Client(['timeout'=>1800,'connect_timeout'=>60]))->request('GET',self::BASE.$name,['sink'=>$path,'http_errors'=>true]);
  $h=$r->getHeaderLine('Last-Modified'); return ['path'=>$path,'last_modified'=>$h?date('Y-m-d',strtotime($h)):null];
 }
 private function getText(string $url):string { return (string)(new Client(['timeout'=>120,'connect_timeout'=>30]))->get($url)->getBody(); }
 private function parseCountryInfo(string $path,array $m49,string $zipPath):array {
  $rows=[];$seen=[];$h=fopen($path,'rb'); if(!$h)throw new RuntimeException('Unable to read countryInfo.txt');
  while(($line=fgets($h))!==false){ if($line===''||$line[0]==='#')continue; $c=explode("\t",rtrim($line,"\r\n")); if(count($c)<17)continue;
   $iso2=strtoupper(trim($c[0])); if($iso2===''||isset($seen[$iso2])){if($iso2!=='')$this->stats['duplicate_countries']++;continue;} $seen[$iso2]=1;$iso3=strtoupper(trim($c[1]));$n=str_pad(trim($c[2]),3,'0',STR_PAD_LEFT);$m=$m49[$iso3]??[];
   $rows[]=['name'=>trim($c[4]),'name_en'=>trim($c[4]),'iso2'=>$iso2,'iso3'=>$iso3,'numeric_code'=>$n,'continent'=>trim($c[8])?:null,'region'=>$m['region']??null,'subregion'=>$m['subregion']??null,'capital'=>trim($c[5])?:null,'latitude'=>null,'longitude'=>null,'timezone'=>null,'phone_code'=>trim($c[12])?:null,'currency'=>trim($c[10])?:null,'flag_code'=>strtolower($iso2),'source'=>'GeoNames','source_version'=>$this->stats['source_version']??null,'license'=>self::LICENSE,'created_at'=>now(),'updated_at'=>now()];
  } fclose($h);
  $cap=[];$z=new ZipArchive(); if($z->open($zipPath)!==true)throw new RuntimeException('Unable to open allCountries.zip');$s=$z->getStream($z->getNameIndex(0));
  while(($line=fgets($s))!==false){$c=explode("\t",rtrim($line,"\r\n"));if(count($c)<19||($c[6]??'')!=='P')continue;$iso=strtoupper($c[8]??'');if(!isset($seen[$iso]))continue;if(in_array($c[7]??'', ['PPLC','PPLA','PPLA2','PPLA3','PPLA4'],true))$cap[$iso]=['lat'=>$c[4]??null,'lng'=>$c[5]??null,'timezone'=>$c[17]??null];}
  fclose($s);$z->close(); foreach($rows as &$r){$x=$cap[$r['iso2']]??null;if($x&&is_numeric($x['lat'])&&is_numeric($x['lng'])){$r['latitude']=(float)$x['lat'];$r['longitude']=(float)$x['lng'];$r['timezone']=$x['timezone']?:null;}}unset($r);return $rows;
 }
 private function parseM49(string $html):array {
  $map=[];preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is',$html,$rows);foreach($rows[1]??[] as $row){preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is',$row,$cells);$cells=array_map(fn($v)=>trim(html_entity_decode(strip_tags($v),ENT_QUOTES|ENT_HTML5,'UTF-8')),$cells[1]??[]);if(count($cells)<10)continue;$iso=strtoupper($cells[10]??'');if(!preg_match('/^[A-Z]{3}$/',$iso)||isset($map[$iso]))continue;$map[$iso]=['region'=>$cells[3]??null,'subregion'=>$cells[5]??null];}return $map;
 }
 private function importCountries(array $rows):void {foreach(array_chunk($rows,100)as$chunk){DB::table('countries')->insert($chunk);$this->stats['countries_imported']+=count($chunk);}}
 private function importPlaces(string $zipPath,array $countryIds):void {
  $z=new ZipArchive();if($z->open($zipPath)!==true)throw new RuntimeException('Unable to open allCountries.zip');$s=$z->getStream($z->getNameIndex(0));if(!$s)throw new RuntimeException('Unable to read GeoNames text stream');$batch=[];
  while(($line=fgets($s))!==false){$c=explode("\t",rtrim($line,"\r\n"));if(count($c)<19||($c[6]??'')!=='P')continue;$id=(int)($c[0]??0);$cc=strtoupper(trim($c[8]??''));if($id<=0||!isset($countryIds[$cc]))continue;$lat=$c[4]??null;$lng=$c[5]??null;if(!is_numeric($lat)||!is_numeric($lng)||(float)$lat<-90||(float)$lat>90||(float)$lng<-180||(float)$lng>180){$this->stats['invalid_coordinates']++;continue;}
   $batch[]=['id'=>$id,'country_id'=>$countryIds[$cc],'name'=>mb_substr(trim($c[1]??''),0,200),'name_en'=>mb_substr(trim($c[1]??''),0,200),'ascii_name'=>mb_substr(trim($c[2]??''),0,200),'latitude'=>(float)$lat,'longitude'=>(float)$lng,'population'=>max(0,(int)($c[14]??0)),'feature_code'=>trim($c[7]??''),'admin1'=>trim($c[10]??'')?:null,'admin2'=>trim($c[11]??'')?:null,'timezone'=>trim($c[17]??'')?:null,'elevation'=>is_numeric($c[15]??null)?(int)$c[15]:null,'alternate_names'=>trim($c[3]??'')?:null,'source_updated_at'=>preg_match('/^\d{4}-\d{2}-\d{2}$/',$c[18]??'')?$c[18]:null,'created_at'=>now(),'updated_at'=>now()];
   if(count($batch)>=2000){$inserted=DB::table('cities')->insertOrIgnore($batch);$this->stats['cities_imported']+=$inserted;$this->stats['duplicate_cities']+=count($batch)-$inserted;$batch=[];}
  }
  if($batch){$inserted=DB::table('cities')->insertOrIgnore($batch);$this->stats['cities_imported']+=$inserted;$this->stats['duplicate_cities']+=count($batch)-$inserted;}fclose($s);$z->close();$this->stats['countries_missing']=max(0,count($countryIds)-Country::whereIn('id',array_values($countryIds))->count());
 }
}