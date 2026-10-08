<?php
namespace Database\Seeders;
use App\Services\GeoNamesImporter;
use Illuminate\Database\Seeder;
class CountrySeeder extends Seeder { public function run(): void { app(GeoNamesImporter::class)->run(); } }