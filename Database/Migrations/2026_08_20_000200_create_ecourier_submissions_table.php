<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Audit trail and idempotency guard for documents sent to eCourier. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecourier_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('invoice_number');
            $table->string('channel');
            $table->string('status')->default('pending');
            $table->string('document_id')->nullable();
            $table->string('e2e_message_uuid')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'invoice_id']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecourier_submissions');
    }
};
