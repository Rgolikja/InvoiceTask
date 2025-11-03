<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_name',
        'unit',
        'quantity',
        'unit_price',
        'total_price',
        'vat_amount',
        'description'

    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

}
