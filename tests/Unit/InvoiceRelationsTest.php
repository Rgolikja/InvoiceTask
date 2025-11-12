<?php

namespace Tests\Unit;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;

class InvoiceRelationsTest extends TestCase
{

    use RefreshDatabase;


    public function test_invoice_has_many_items()
    {
        $client = Client::create([
            'name' => 'Klienti',
            'code' => 'abcd'
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV1',
            'invoice_date' => now(),
            'total_amount_eur' => 100,
            'total_with_vat' => 120,
            'total_amount_all' => 120,
            'currency' => 'EUR',
            'base_currency' => 'ALL',

        ]);

        $invoice->items()->createMany([
            [
                'product_name' => 'Laps',
                'unit' => 'pcs',
                'quantity' => 1,
                'unit_price' => 1000,
                'total_price' => 1000,
                'vat_amount' => 200,
                'description' => 'Laps 1 cope',
            ],
            [
                'product_name' => 'Gome',
                'unit' => 'pcs',
                'quantity' => 2,
                'unit_price' => 50,
                'total_price' => 100,
                'vat_amount' => 20,
                'description' => 'Gome 2 cope',
            ],
        ]);

        $invoice->refresh();

        $this->assertCount(2, $invoice->items);

        $this->assertEquals('Laps', $invoice->items[0]->product_name);
        $this->assertEquals('Gome', $invoice->items[1]->product_name);

    }
}