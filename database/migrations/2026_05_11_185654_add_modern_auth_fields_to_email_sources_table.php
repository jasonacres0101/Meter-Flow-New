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
        Schema::table('email_sources', function (Blueprint $table) {
            $table->string('auth_type')->default('basic')->after('provider');
            $table->string('oauth_tenant_id')->nullable()->after('password');
            $table->string('oauth_client_id')->nullable()->after('oauth_tenant_id');
            $table->text('oauth_client_secret')->nullable()->after('oauth_client_id');
            $table->string('oauth_scope')->nullable()->after('oauth_client_secret');
            $table->text('oauth_access_token')->nullable()->after('oauth_scope');
            $table->text('oauth_refresh_token')->nullable()->after('oauth_access_token');
            $table->timestamp('oauth_token_expires_at')->nullable()->after('oauth_refresh_token');
            $table->string('oauth_status')->default('not_connected')->after('oauth_token_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_sources', function (Blueprint $table) {
            $table->dropColumn([
                'auth_type',
                'oauth_tenant_id',
                'oauth_client_id',
                'oauth_client_secret',
                'oauth_scope',
                'oauth_access_token',
                'oauth_refresh_token',
                'oauth_token_expires_at',
                'oauth_status',
            ]);
        });
    }
};
