<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('eyebrow')->default('EXPLORE');
            $table->string('heading')->default('MOPHONIK');
            $table->string('shop_url')->default('https://shop.travisscott.com/');
            $table->string('terms_url')->default('https://shop.travisscott.com/pages/terms');
            $table->string('newsletter_heading')->default('Enter email for updates');
            $table->json('menu_items')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
