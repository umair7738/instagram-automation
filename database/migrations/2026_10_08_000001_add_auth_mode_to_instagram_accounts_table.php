<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('instagram_accounts', 'auth_mode')) {
            Schema::table('instagram_accounts', function (Blueprint $table) {
                $table->string('auth_mode')->default('facebook_login')->after('access_token');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('instagram_accounts', 'auth_mode')) {
            Schema::table('instagram_accounts', function (Blueprint $table) {
                $table->dropColumn('auth_mode');
            });
        }
    }
};
