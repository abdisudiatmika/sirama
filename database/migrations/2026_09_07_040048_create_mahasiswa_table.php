<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('prodi_id')->constrained('prodi')->cascadeOnDelete();
            $table->foreignId('skema_id')->nullable()->constrained('skema')->nullOnDelete();
            $table->string('nim')->unique();
            $table->unsignedSmallInteger('tahun_masuk');
            $table->enum('status_berkas', ['draft', 'diajukan', 'sedang_dinilai', 'selesai_dinilai', 'dikembalikan'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mahasiswa');
    }
};
