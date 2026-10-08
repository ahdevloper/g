<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('cities', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('name_en', 200);
            $table->string('ascii_name', 200)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedBigInteger('population')->default(0);
            $table->string('feature_code', 20)->index();
            $table->string('admin1', 20)->nullable()->index();
            $table->string('admin2', 80)->nullable()->index();
            $table->string('timezone', 64)->nullable()->index();
            $table->integer('elevation')->nullable();
            $table->text('alternate_names')->nullable();
            $table->date('source_updated_at')->nullable();
            $table->timestamps();
            $table->index('country_id');
            $table->index('name');
            $table->index('name_en');
            $table->index('ascii_name');
            $table->index(['latitude', 'longitude']);
            $table->index('population');
        });
    }
    public function down(): void { Schema::dropIfExists('cities'); }
};