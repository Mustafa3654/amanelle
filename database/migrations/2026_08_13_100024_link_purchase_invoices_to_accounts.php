<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->foreignId('debit_account_id')->nullable()->constrained('accounts')->nullOnDelete()->after('supplier_id');
            $table->foreignId('credit_account_id')->nullable()->constrained('accounts')->nullOnDelete()->after('debit_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropForeign(['debit_account_id']);
            $table->dropForeign(['credit_account_id']);
            $table->dropColumn(['debit_account_id', 'credit_account_id']);
        });
    }
};
