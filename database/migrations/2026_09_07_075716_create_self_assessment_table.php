<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_assessment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_mk_id')->constrained('pengajuan_mk')->cascadeOnDelete();
            $table->foreignId('cpmk_id')->constrained('cpmk')->cascadeOnDelete();
            $table->enum('status', ['mampu', 'belum_mampu'])->nullable();
            $table->timestamps();

            $table->unique(['pengajuan_mk_id', 'cpmk_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_assessment');
    }
};
