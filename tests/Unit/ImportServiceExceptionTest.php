<?php

namespace Tests\Unit;
use Illuminate\Http\Request;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\ImportService;
use Exception;


class ImportServiceExceptionTest extends TestCase
{

    use RefreshDatabase;


    protected ImportService $importService;



    protected function setUp(): void
    {
        parent::setUp();
        $this->importService = app(ImportService::class);
    }


    public function test_exception_if_no_file()
    {
        $request = new Request();

        $response = $this->importService->importExcelData($request);

        $this->assertIsArray($response);
        $this->assertEquals('Import failed', $response['message']);
        $this->assertEquals('No Excel file uploaded.', $response['error']);
    }


    public function test_return_message_when_import_fails()
    {

        $request = new Request(['file' => null]);

        $response = $this->importService->importExcelData($request);

        $this->assertIsArray($response);

        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Import failed', $response['message']);

        $this->assertArrayHasKey('error', $response);
    }
}