<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kept independent of a specific `penilaian` (formal/informal) row: VATM
        // validation is assessed once per CPMK per asesor, before the jenis-specific
        // scoring forms (F-ASR-04 precedes F-ASR-07 in the assessment flow).
        Schema::create('validasi_cpmk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_mk_id')->constrained('pengajuan_mk')->cascadeOnDelete();
            $table->foreignId('asesor_id')->constrained('asesor')->cascadeOnDelete();
            $table->foreignId('cpmk_id')->constrained('cpmk')->cascadeOnDelete();
            $table->boolean('valid')->default(false);
            $table->boolean('asli')->default(false);
            $table->boolean('terkini')->default(false);
            $table->boolean('memadai')->default(false);
            $table->timestamps();

            $table->unique(['pengajuan_mk_id', 'asesor_id', 'cpmk_id'], 'validasi_cpmk_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validasi_cpmk');
    }
};
