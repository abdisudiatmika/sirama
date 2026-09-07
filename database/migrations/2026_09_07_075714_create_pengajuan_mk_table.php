<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_mk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->foreignId('matakuliah_id')->constrained('matakuliah')->cascadeOnDelete();
            $table->unsignedTinyInteger('nilai_mandiri_angka');
            $table->enum('nilai_mandiri_huruf', ['A', 'AB', 'B', 'BC', 'C', 'D', 'E']);
            $table->timestamps();

            $table->unique(['mahasiswa_id', 'matakuliah_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_mk');
    }
};
