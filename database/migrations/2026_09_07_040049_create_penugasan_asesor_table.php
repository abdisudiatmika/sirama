<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasan_asesor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->foreignId('asesor_id')->constrained('asesor')->cascadeOnDelete();
            $table->unsignedTinyInteger('urutan');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            $table->unique(['mahasiswa_id', 'urutan']);
            $table->unique(['mahasiswa_id', 'asesor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penugasan_asesor');
    }
};
