<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $accounts = [
            ['account_number' => '601010001', 'name' => 'USD Purchases', 'type' => 'purchases', 'currency' => 'USD'],
            ['account_number' => '211010001', 'name' => 'USD Accounts Payable', 'type' => 'payable', 'currency' => 'USD'],
            ['account_number' => '601010002', 'name' => 'LBP Purchases', 'type' => 'purchases', 'currency' => 'LBP'],
            ['account_number' => '211010002', 'name' => 'LBP Accounts Payable', 'type' => 'payable', 'currency' => 'LBP'],
        ];

        foreach ($accounts as $account) {
            DB::table('accounts')->updateOrInsert(
                ['account_number' => $account['account_number']],
                array_merge($account, [
                    'opening_balance' => 0,
                    'balance' => 0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }
    }

    public function down(): void
    {
        DB::table('accounts')->whereIn('account_number', [
            '601010001', '211010001', '601010002', '211010002',
        ])->delete();
    }
};
