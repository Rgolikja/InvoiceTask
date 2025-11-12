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
        $request = new Request([
            'data' => [
                [
                    'client_name' => 'Test Client',
                    'client_code' => 'TC123',
                    'invoice_number' => 'INV-001',
                    'invoice_date' => now(),
                    'currency' => 'EUR',
                    'base_currency' => 'ALL',
                    'items' => [
                        [
                            'product_name' => 'Product 1',
                            'unit' => 'pcs',
                            'quantity' => 2,
                            'unit_price' => 500,
                            'total_price' => 1000,
                            'vat_amount' => 200,
                            'description' => 'Test Description'
                        ]
                    ]
                ]
            ]
        ]);
        $this->importService->importExcelData($request);
        $this->assertDatabaseHas('clients', [
            'name' => 'Test Client',
            'code' => 'TC123'
        ]);

        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV-001',
            'currency' => 'EUR',
            'base_currency' => 'ALL'
        ]);
        $this->assertDatabaseHas('invoice_items', [
            'product_name' => 'Product 1',
            'quantity' => 2,
            'unit_price' => 500,
            'total_price' => 1000,
            'vat_amount' => 200,
            'description' => 'Test Description'
        ]);



    }
}
