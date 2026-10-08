<?php
namespace Tests\Feature;
use App\Models\City;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class GeoApiTest extends TestCase {
 use RefreshDatabase;
 public function test_country_endpoints_work(): void {
  $country=Country::create(['name'=>'Saudi Arabia','name_en'=>'Saudi Arabia','iso2'=>'SA','iso3'=>'SAU','numeric_code'=>'682']);
  $this->getJson('/api/countries')->assertOk()->assertJsonPath('data.0.iso2','SA');
  $this->getJson('/api/countries/'.$country->id)->assertOk()->assertJsonPath('iso3','SAU');
 }
 public function test_city_search_supports_name_fields(): void {
  $country=Country::create(['name'=>'Saudi Arabia','name_en'=>'Saudi Arabia','iso2'=>'SA','iso3'=>'SAU']);
  City::create(['id'=>1,'country_id'=>$country->id,'name'=>'الرياض','name_en'=>'Riyadh','ascii_name'=>'Riyadh','latitude'=>24.7136,'longitude'=>46.6753,'feature_code'=>'PPLC','alternate_names'=>'Ar-Riyad,الرياض']);
  $this->getJson('/api/cities/search?q=Riyadh')->assertOk()->assertJsonPath('data.0.id',1);
  $this->getJson('/api/cities/search?q=الرياض')->assertOk()->assertJsonPath('data.0.id',1);
 }
 public function test_nearby_search_returns_distance(): void {
  $country=Country::create(['name'=>'Saudi Arabia','name_en'=>'Saudi Arabia','iso2'=>'SA','iso3'=>'SAU']);
  City::create(['id'=>1,'country_id'=>$country->id,'name'=>'Riyadh','name_en'=>'Riyadh','ascii_name'=>'Riyadh','latitude'=>24.7136,'longitude'=>46.6753,'feature_code'=>'PPLC']);
  $this->getJson('/api/cities/nearby?lat=24.7136&lng=46.6753&radius=5')->assertOk()->assertJsonPath('data.0.id',1);
 }
}