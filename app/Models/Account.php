<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['opening_balance' => 'decimal:2', 'balance' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function debitInvoices(): HasMany { return $this->hasMany(PurchaseInvoice::class, 'debit_account_id'); }
    public function creditInvoices(): HasMany { return $this->hasMany(PurchaseInvoice::class, 'credit_account_id'); }
}
