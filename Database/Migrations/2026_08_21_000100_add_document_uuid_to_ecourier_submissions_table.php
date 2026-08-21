<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records the idempotency key sent with each submission, so a duplicate
 * delivery can be traced back to the retry that caused it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecourier_submissions', function (Blueprint $table) {
            $table->uuid('document_uuid')->nullable()->after('channel');
        });
    }

    public function down(): void
    {
        Schema::table('ecourier_submissions', function (Blueprint $table) {
            $table->dropColumn('document_uuid');
        });
    }
};
