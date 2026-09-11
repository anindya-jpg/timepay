<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('tarif_lembur', function (Blueprint $table) {
        $table->id('id_tarif');
        $table->decimal('tarif_per_jam', 15, 2)->default(0);
        $table->timestamp('created_at')->useCurrent();
    });
}

public function down(): void
{
    Schema::dropIfExists('tarif_lembur');
}
};
