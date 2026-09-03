<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            // Minor units (kobo). Paystack takes the smallest currency unit, so the
            // whole app stores and calculates in it and converts once, at display.
            $table->unsignedBigInteger('price_kobo')->default(0);
            $table->string('image')->nullable();
            $table->string('image_url')->nullable();
            $table->string('unit_label')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
