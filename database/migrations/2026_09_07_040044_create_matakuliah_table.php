<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Local to a prodi: only reachable by that prodi's admin and students.
        Schema::create('matakuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->constrained('prodi')->cascadeOnDelete();
            $table->string('kode');
            $table->string('nama');
            $table->unsignedTinyInteger('sks')->default(2);
            $table->unsignedTinyInteger('semester')->nullable();
            $table->unsignedTinyInteger('skor_minimum')->default(66);
            $table->boolean('dapat_direkognisi')->default(true);
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();

            $table->unique(['prodi_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matakuliah');
    }
};
