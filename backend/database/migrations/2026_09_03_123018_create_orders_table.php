<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();

            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->text('delivery_address')->nullable();
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('subtotal_kobo')->default(0);
            $table->unsignedBigInteger('delivery_fee_kobo')->default(0);
            $table->unsignedBigInteger('total_kobo')->default(0);

            // Plain strings, not enums: SQLite ignores enum constraints, so a value
            // that passes locally would be rejected on MySQL.
            $table->string('status', 20)->default('pending_payment');
            $table->string('payment_status', 20)->nullable();
            $table->string('payment_reference')->nullable()->index();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
