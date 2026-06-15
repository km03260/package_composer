<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Device-matching table used by the dfa (double-authentication) checks of both
 * the local ("Connexion hors SSO") and QR login flows.
 *
 * Mirrors the SSO server's DeviceMatching model. The model uses
 * `const UPDATED_AT = 'created_at'`, so the table tracks a single `created_at`
 * timestamp (no `updated_at`).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_matching')) {
            return;
        }

        Schema::create('user_matching', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('user_agent')->nullable();
            $table->string('platform')->nullable();
            $table->string('device')->nullable();
            $table->string('version')->nullable();
            $table->text('matching_regex')->nullable();
            $table->string('ip_request')->nullable();
            $table->string('match_token')->nullable()->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_matching');
    }
};
