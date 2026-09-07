<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_bukti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_mk_id')->constrained('pengajuan_mk')->cascadeOnDelete();
            $table->foreignId('bukti_id')->constrained('bukti')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['pengajuan_mk_id', 'bukti_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_bukti');
    }
};
