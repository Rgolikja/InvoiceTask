<?php
namespace Tests\Unit;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Client;
use App\Models\Invoice;


class ClientRelationsTest extends TestCase
{


    use RefreshDatabase;

    public function test_client_has_many_invoices()
    {

        $client = Client::create([
            'name' => 'Klienti',
            'code' => 'abcd'
        ]);

        $invoice1 = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV1',
            'invoice_date' => now(),
            'total_amount_eur' => 100,
            'total_with_vat' => 120,
            'total_amount_all' => 120,
            'currency' => 'EUR',
            'base_currency' => 'ALL',

        ]);
        $invoice2 = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV2',
            'invoice_date' => now(),
            'total_amount_eur' => 200,
            'total_with_vat' => 240,
            'total_amount_all' => 240,
            'currency' => 'EUR',
            'base_currency' => 'ALL',

        ]);

        $client->refresh();

        $this->assertCount(2, $client->invoices);

        $this->assertEquals('INV1', $client->invoices[0]->invoice_number);
    }
}
