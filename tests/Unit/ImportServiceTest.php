<?php

namespace Tests\Unit;
use Illuminate\Http\Request;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\ImportService;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\User;
use App\Models\InvoiceItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Psy\Readline\Hoa\EventListens;

class ImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ImportService $importService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importService = app(ImportService::class);
    }
    /**
     * @test
     */
    public function creating_invoices_and_clients(): void
    {
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
        $invoice->items()->create([

            'product_name' => 'Laps',
            'unit' => 'pcs',
            'quantity' => 1,
            'unit_price' => 1000,
            'total_price' => 1000,
            'vat_amount' => 200,
            'description' => 'Laps 1 cope',


        ]);
        Storage::fake('local');
        $file = UploadedFile::fake()->create(
            'test.xlsx',
            100,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'

        );
        $request = new Request();
        $request->files->set('file', $file);
        $response = $this->importService->importExcelData($request);

        $this->assertDatabaseHas('clients', [
            'name' => 'Test Name',
            'code' => 'AA111'
        ]);

        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV-111',
            'currency' => 'EUR',
            'base_currency' => 'ALL'
        ]);
        $this->assertDatabaseHas('invoice_items', [
            'product_name' => 'Laps',
            'unit' => 'pcs',
            'quantity' => 1,
            'unit_price' => 1000,
            'total_price' => 1000,
            'vat_amount' => 200,
            'description' => 'Laps 1 cope',
        ]);



    }
}
