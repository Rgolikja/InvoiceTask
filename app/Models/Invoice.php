<?php
namespace App\Models;
use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use App\Models\InvoiceItem;


class Invoice extends Model
{
    protected $fillable = [
        'client_id',
        'invoice_number',
        'invoice_date',
        'total_amount_eur',
        'total_with_vat',
        'total_amount_all',
        'currency',
        'base_currency'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }
}