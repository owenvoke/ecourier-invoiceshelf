<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-customer Peppol routing details.
 *
 * No foreign keys to host tables: a removed customer simply leaves an unused
 * row, which the module-disabled listener clears.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecourier_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('customer_id');
            $table->string('scheme');
            $table->string('identifier');
            $table->string('name')->nullable();
            $table->string('vat_id')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code', 64)->nullable();
            $table->string('country', 2)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecourier_recipients');
    }
};
