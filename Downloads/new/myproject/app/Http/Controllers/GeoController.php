<?php
namespace App\Http\Controllers;
use App\Models\City;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
class GeoController extends Controller {
 public function countries(Request $request): JsonResponse {
  $perPage=min(max((int)$request->input('per_page',50),1),200); $q=trim((string)$request->input('q',''));
  $builder=Country::query()->orderBy('name_en');
  if($q!==''){ $like='%'.addcslashes($q,'%_').'%'; $builder->where(function($x)use($like){$x->where('name','like',$like)->orWhere('name_en','like',$like)->orWhere('iso2','like',$like)->orWhere('iso3','like',$like);});}
  return response()->json($builder->paginate($perPage));
 }
 public function country(Country $country): JsonResponse { return response()->json(Cache::remember('geo:country:'.$country->id,now()->addHours(6),fn()=>$country)); }
 public function countryCities(Request $request, Country $country): JsonResponse { $perPage=min(max((int)$request->input('per_page',50),1),200); return response()->json($country->cities()->orderByDesc('population')->paginate($perPage)); }
 public function cities(Request $request): JsonResponse {
  $perPage=min(max((int)$request->input('per_page',50),1),200); $builder=City::query()->with('country:id,name,name_en,iso2,iso3');
  if($request->filled('country_id'))$builder->where('country_id',(int)$request->input('country_id'));
  if($request->filled('q'))$builder->search((string)$request->input('q'));
  return response()->json($builder->orderByDesc('population')->paginate($perPage));
 }
 public function citySearch(Request $request): JsonResponse {
  $request->validate(['q'=>['required','string','min:1','max:100']]); $perPage=min(max((int)$request->input('per_page',30),1),100);
  return response()->json(City::query()->with('country:id,name,name_en,iso2,iso3')->search((string)$request->input('q'))->orderByDesc('population')->paginate($perPage));
 }
 public function nearby(Request $request): JsonResponse {
  $data=$request->validate(['lat'=>['required','numeric','between:-90,90'],'lng'=>['required','numeric','between:-180,180'],'radius'=>['nullable','numeric','min:0.1','max:500'],'per_page'=>['nullable','integer','min:1','max:100']]);
  $lat=(float)$data['lat']; $lng=(float)$data['lng']; $radius=(float)($data['radius']??25); $perPage=(int)($data['per_page']??50);
  $latDelta=$radius/111.045; $lngDelta=$radius/max(cos(deg2rad($lat))*111.045,0.0001);
  $distance='(6371 * acos(cos(radians(?))*cos(radians(latitude))*cos(radians(longitude)-radians(?))+sin(radians(?))*sin(radians(latitude))))';
  $builder=City::query()->with('country:id,name,name_en,iso2,iso3')->whereBetween('latitude',[$lat-$latDelta,$lat+$latDelta])->whereBetween('longitude',[$lng-$lngDelta,$lng+$lngDelta])->select('cities.*')->selectRaw($distance.' AS distance_km',[$lat,$lng,$lat])->having('distance_km','<=',$radius)->orderBy('distance_km');
  return response()->json($builder->paginate($perPage));
 }
}