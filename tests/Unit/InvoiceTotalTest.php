<?php

namespace Tests\Unit;

use App;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Tests\TestCase;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\InvoiceItem;

class InvoiceTotalTest extends TestCase
{
    use RefreshDatabase;

    public function test_does_it_calculate_totals_correctly()
    {

        //krijojme nje klient dhe fature


        $client = Client::create([
            'name' => 'Test Name',
            'code' => 'AA111'
        ]);
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-111',
            'invoice_date' => now(),
            'total_amount_eur' => 0,
            'total_with_vat' => 0,
            'total_amount_all' => 0,
            'currency' => 'EUR',
            'base_currency' => 'ALL',
        ]);

        //shtojme items
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
            [
                'product_name' => 'Fletore',
                'unit' => 'pcs',
                'quantity' => 1,
                'unit_price' => 70,
                'total_price' => 70,
                'vat_amount' => 14,
                'description' => 'Fleotre 1 cope',
            ],
        ]);

        //llogarisim totalin manualisht
        $expectedTotal = $invoice->items->sum('total_price');

        $invoice->update([
            'total_amount_eur' => $expectedTotal
        ]);
        //check nese totalet jan te sakta
        $this->assertEquals(1170, $expectedTotal);
        $this->assertEquals(1170, $invoice->fresh()->total_amount_eur);
    }
    public function test_empty_invoices()
    {
        $client = Client::create([
            'name' => 'Empty Name',
            'code' => 'AA000'
        ]);
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-000',
            'invoice_date' => now(),
            'total_amount_eur' => 0,
            'total_with_vat' => 0,
            'total_amount_all' => 0,
            'currency' => 'EUR',
            'base_currency' => 'ALL',
        ]);

        $expectedTotal = $invoice->items->sum('total_price');

        $this->assertEquals(0, $expectedTotal);
        $this->assertEquals(0, $invoice->fresh()->total_amount_eur);
    }
}
