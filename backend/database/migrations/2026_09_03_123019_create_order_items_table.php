<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            // Snapshotted, so an old order still reads correctly after the product
            // is renamed, repriced or deleted.
            $table->string('product_name');
            $table->unsignedBigInteger('unit_price_kobo');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total_kobo');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
