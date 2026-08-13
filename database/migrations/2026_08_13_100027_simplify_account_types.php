<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('accounts')->where('type', 'purchases')->update(['type' => 'debit']);
        DB::table('accounts')->where('type', 'payable')->update(['type' => 'credit']);
        Schema::table('accounts', function (Blueprint $table) { $table->enum('type', ['debit', 'credit'])->default('debit')->change(); });
    }

    public function down(): void
    {
        DB::table('accounts')->where('type', 'debit')->update(['type' => 'purchases']);
        DB::table('accounts')->where('type', 'credit')->update(['type' => 'payable']);
        Schema::table('accounts', function (Blueprint $table) { $table->enum('type', ['cash', 'bank', 'purchases', 'sales', 'payable', 'receivable', 'expense', 'income', 'other'])->change(); });
    }
};
