<?php

namespace Tests\Unit;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Import;
use App\Services\ImportService;
use Illuminate\Http\Request;


class ImportModelTest extends TestCase
{
    use RefreshDatabase;


    protected ImportService $importService;


    public function setUp(): void
    {
        parent::setUp();
        $this->importService = app(ImportService::class);
    }

    public function test_updates_import_status_correctly()
    {
        $import = Import::create([
            'file_name' => 'test.xlsx',
            'file_path' => '/storage/app/public/imports/test.xlsx',
            'status' => 'pending',
            'rows_total' => 10,
            'rows_imported' => 5,
            'error_message' => null,
        ]);

        $request = new Request([
            'status' => 'completed'
        ]);

        $this->importService->updateImport($import->id, $request);

        $this->assertDatabaseHas('imports', [
            'id' => $import->id,
            'status' => 'completed'
        ]);
    }

    public function test_delete_import()
    {

        $import = Import::create([
            'file_name' => 'delete.xlsx',
            'file_path' => '/storage/app/public/imports/delete.xlsx',
            'status' => 'failed',
            'rows_total' => 5,
            'rows_imported' => 0,
            'error_message' => 'Test error',
        ]);


        $this->importService->deleteImport($import->id);

        $this->assertDatabaseMissing('imports', ['id' => $import->id]);

    }
}
