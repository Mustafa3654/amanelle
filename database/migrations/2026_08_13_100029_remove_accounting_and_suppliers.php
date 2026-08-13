<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['debit_account_id']);
            $table->dropForeign(['credit_account_id']);
            $table->dropColumn([
                'supplier_id',
                'supplier_name',
                'due_date',
                'status',
                'debit',
                'credit',
                'debit_account',
                'credit_account',
                'debit_account_id',
                'credit_account_id',
            ]);
        });

        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('accounts');
    }

    public function down(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_number')->unique();
            $table->string('name');
            $table->string('type');
            $table->string('currency', 3)->default('USD');
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('account_number')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
