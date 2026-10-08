<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('name_en', 200);
            $table->char('iso2', 2)->unique();
            $table->char('iso3', 3)->unique();
            $table->char('numeric_code', 3)->nullable()->index();
            $table->char('continent', 2)->nullable()->index();
            $table->string('region', 100)->nullable()->index();
            $table->string('subregion', 100)->nullable()->index();
            $table->string('capital', 200)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->string('phone_code', 64)->nullable();
            $table->char('currency', 3)->nullable()->index();
            $table->char('flag_code', 2)->nullable()->index();
            $table->string('source', 100)->default('GeoNames');
            $table->string('source_version', 100)->nullable();
            $table->string('license', 255)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('countries'); }
};