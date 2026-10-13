<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_it_wo_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_it_wo_id')->constrained('form_it_wos')->cascadeOnDelete();
            $table->string('material')->nullable();
            $table->string('kode_pemesanan')->nullable();
            $table->decimal('jumlah', 12, 2)->nullable();
            $table->string('satuan', 50)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_it_wo_materials');
    }
};