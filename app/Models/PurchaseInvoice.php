<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['invoice_date' => 'date', 'due_date' => 'date', 'subtotal' => 'decimal:2', 'tax' => 'decimal:2', 'total' => 'decimal:2', 'debit' => 'decimal:2', 'credit' => 'decimal:2'];
    }

    public function items(): HasMany { return $this->hasMany(PurchaseInvoiceItem::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function debitAccount(): BelongsTo { return $this->belongsTo(Account::class, 'debit_account_id'); }
    public function creditAccount(): BelongsTo { return $this->belongsTo(Account::class, 'credit_account_id'); }
}
