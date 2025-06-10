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
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->smallInteger('role_id')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->string('key')->nullable();
            $table->string('auth_token')->nullable()->unique()->after('email');
            $table->json('person')->nullable();
            $table->json('settings')->nullable();
            $table->json('statistics')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('parent_id');
            $table->dropColumn('role_id');
            $table->dropColumn('status');
            $table->dropColumn('key');
            $table->dropColumn('auth_token');
            $table->dropColumn('person');
            $table->dropColumn('settings');
            $table->dropColumn('statistics');
        });
    }
};
