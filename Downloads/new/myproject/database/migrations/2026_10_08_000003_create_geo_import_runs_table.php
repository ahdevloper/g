<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('geo_import_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 100);
            $table->string('source_version', 100)->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->string('license', 255)->nullable();
            $table->unsignedInteger('countries_imported')->default(0);
            $table->unsignedBigInteger('cities_imported')->default(0);
            $table->unsignedInteger('countries_missing')->default(0);
            $table->unsignedBigInteger('duplicate_countries')->default(0);
            $table->unsignedBigInteger('duplicate_cities')->default(0);
            $table->unsignedBigInteger('invalid_coordinates')->default(0);
            $table->string('status', 30)->default('running')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('geo_import_runs'); }
};