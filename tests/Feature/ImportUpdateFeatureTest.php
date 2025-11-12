<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Import;

class ImportUpdateFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_import_status(): void
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

        $response = $this->putJson("/api/imports/{$import->id}", [
            'status' => 'completed',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('imports', [
            'id' => $import->id,
            'status' => 'completed',
        ]);
    }
}