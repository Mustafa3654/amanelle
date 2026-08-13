<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_number')->unique();
            $table->string('name');
            $table->enum('type', ['cash', 'bank', 'purchases', 'sales', 'payable', 'receivable', 'expense', 'income', 'other']);
            $table->string('currency', 3)->default('USD');
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);
            $table->string('contact_name')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
