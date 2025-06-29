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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('privacy_consent')->default(false)->comment('Согласие на обработку персональных данных');
            $table->timestamp('privacy_consent_at')->nullable()->comment('Дата согласия на обработку персональных данных');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('privacy_consent');
            $table->dropColumn('privacy_consent_at');
        });
    }
};
