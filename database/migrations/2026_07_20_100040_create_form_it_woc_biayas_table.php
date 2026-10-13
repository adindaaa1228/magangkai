<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_it_woc_biayas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_it_woc_id')->constrained('form_it_wocs')->cascadeOnDelete();
            $table->string('jenis_biaya')->nullable();
            $table->decimal('jumlah', 12, 2)->nullable();
            $table->string('satuan', 50)->nullable();
            $table->decimal('harga_satuan_valas', 18, 2)->nullable();
            $table->decimal('harga_satuan_rp', 18, 2)->nullable();
            $table->decimal('total_valas', 18, 2)->nullable();
            $table->decimal('total_rp', 18, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_it_woc_biayas');
    }
};