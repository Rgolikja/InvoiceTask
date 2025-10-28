<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string("invoice_number")->unique();
            $table->date('invoice_date')->nullable();
            $table->decimal('total_amount_eur', 12, 2)->nullable();
            $table->decimal('total_with_vat', 12, 2)->nullable();
            $table->decimal('total_amount_all', 12, 2)->nullable();
            $table->string('currency')->default('EUR');
            $table->string('base_currency')->default('ALL');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');

    }
}
;
