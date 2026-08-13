<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete()->after('id');
            $table->date('due_date')->nullable()->after('invoice_date');
            $table->enum('status', ['unpaid', 'partially_paid', 'paid', 'cancelled'])->default('unpaid')->after('total');
            $table->decimal('debit', 12, 2)->default(0)->after('total');
            $table->decimal('credit', 12, 2)->default(0)->after('debit');
            $table->string('debit_account')->default('Inventory / Purchases')->after('credit');
            $table->string('credit_account')->default('Accounts Payable')->after('debit_account');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'due_date', 'status', 'debit', 'credit', 'debit_account', 'credit_account']);
        });
    }
};
