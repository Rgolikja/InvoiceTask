<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Import;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    // simple test /test-import
    public function test_test_import_route_works()
    {
        $response = $this->postJson('/api/test-import');
        $response->assertStatus(200)
            ->assertJson(['message' => 'Route works']);
    }

    // checking validation for upload
    public function test_post_import_requires_file()
    {
        $response = $this->postJson('/api/imports', []); // no file
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    // upload test with fake file
    public function test_upload_creates_import_and_stores_file()
    {
        // so we dont touch real disk
        Storage::fake('public');

        // make a fake facade
        Excel::shouldReceive('toCollection')->andReturn(collect([collect([])]));
        Excel::shouldReceive('import')->andReturnNull();

        // make a fake excel file
        $file = UploadedFile::fake()->create('sample.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->postJson('/api/imports', [
            'file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'import' => ['id', 'file_name', 'file_path', 'status']]);


        $this->assertDatabaseHas('imports', [
            'file_name' => $file->getClientOriginalName(),
        ]);

        //file saved in public disk in imports
        Storage::disk('public')->assertExists('imports/' . $file->getClientOriginalName());
    }
}
