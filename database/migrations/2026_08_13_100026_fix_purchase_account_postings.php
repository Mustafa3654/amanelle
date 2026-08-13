<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Previous versions increased both accounts. Move each non-cancelled
        // credit posting from +amount to -amount exactly once.
        foreach (DB::table('purchase_invoices')->where('status', '!=', 'cancelled')->get() as $invoice) {
            DB::table('accounts')->where('id', $invoice->credit_account_id)->decrement('balance', $invoice->total * 2);
        }
    }

    public function down(): void
    {
        foreach (DB::table('purchase_invoices')->where('status', '!=', 'cancelled')->get() as $invoice) {
            DB::table('accounts')->where('id', $invoice->credit_account_id)->increment('balance', $invoice->total * 2);
        }
    }
};
