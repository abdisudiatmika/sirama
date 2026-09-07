<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penilaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_mk_id')->constrained('pengajuan_mk')->cascadeOnDelete();
            $table->foreignId('asesor_id')->constrained('asesor')->cascadeOnDelete();
            $table->enum('jenis', ['formal', 'informal']);
            $table->unsignedTinyInteger('nilai');
            $table->text('catatan_reviewer')->nullable();
            $table->text('catatan_kesenjangan')->nullable();
            // Cached combined result for this asesor+pengajuan_mk pair (§6.1 formula),
            // recomputed and mirrored onto every jenis row whenever either is saved —
            // avoids recalculating on every recap read (§7.2).
            $table->unsignedTinyInteger('nilai_akhir')->nullable();
            $table->timestamps();

            $table->unique(['pengajuan_mk_id', 'asesor_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian');
    }
};
