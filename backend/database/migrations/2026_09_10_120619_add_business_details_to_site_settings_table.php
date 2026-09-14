<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('business_name')->default('Mophonik')->after('heading');
            $table->string('business_phone')->nullable()->after('business_name');
            $table->string('business_email')->nullable()->after('business_phone');
            $table->text('business_address')->nullable()->after('business_email');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['business_name', 'business_phone', 'business_email', 'business_address']);
        });
    }
};
