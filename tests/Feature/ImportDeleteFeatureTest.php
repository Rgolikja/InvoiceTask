<?php

namespace Tests\Feature;

use Psy\Readline\Hoa\EventListens;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Import;

class ImportDeleteFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_import_successfully()
    {
        $this->withoutMiddleware();

        $import = Import::create([
            'file_name' => 'update.xlsx',
            'file_path' => '/storage/app/public/update.xlsx',
            'status' => 'pending',
            'rows_total' => 100,
            'rows_imported' => 50,
            'error_message' => null,
        ]);
        $response = $this->deleteJson("/api/imports/{$import->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('imports', ['id' => $import->id]);
    }
}