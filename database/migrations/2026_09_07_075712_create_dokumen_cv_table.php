<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Link-based only — no file is ever stored on the server (§7.2).
        Schema::create('dokumen_cv', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->unique()->constrained('mahasiswa')->cascadeOnDelete();
            $table->string('url');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_cv');
    }
};
