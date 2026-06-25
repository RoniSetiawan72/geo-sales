<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prospecting_job_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospecting_job_id')->constrained('prospecting_jobs')->onDelete('cascade');

            // Data Inti Lokasi Bisnis
            $table->string('business_name');
            $table->text('address_text')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('website_url')->nullable();

            // Data Rating & Review
            $table->decimal('rating', 3, 2)->nullable()->default(0.00);
            $table->integer('review_count')->nullable()->default(0);

            // Data Geometris Spasial (PostGIS) & Desimal biasa untuk fallback
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->geometry('geom', 'POINT', 4326)->nullable(); // Kolom PostGIS

            $table->timestamps();

            // Indexing spasial untuk optimasi query radius jarak terdekat
            $table->spatialIndex('geom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospecting_job_results');
    }
};
