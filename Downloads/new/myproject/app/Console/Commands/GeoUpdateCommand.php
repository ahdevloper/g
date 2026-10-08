<?php
namespace App\Console\Commands;
use App\Services\GeoNamesImporter;
use Illuminate\Console\Command;
class GeoUpdateCommand extends Command {
 protected $signature='geo:update {--keep-downloads}';
 protected $description='Refresh the global geography database from GeoNames.';
 public function handle(GeoNamesImporter $importer): int { $stats=$importer->run((bool)$this->option('keep-downloads')); foreach($stats as $k=>$v)$this->line(sprintf('%-24s %s',$k.':',is_array($v)?json_encode($v):$v)); return ($stats['status']??'failed')==='completed'?self::SUCCESS:self::FAILURE; }
}