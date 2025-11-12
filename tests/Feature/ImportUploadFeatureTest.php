<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Import;


class ImportUploadFeatureTest extends TestCase
{

    use RefreshDatabase;



    public function test_upload_file_successfully()
    {
        $this->withoutMiddleware();
        Storage::fake('public');


        $file = Import::create([
            'file_name' => 'test_import.xlsx',
            'file_path' => '/storage/app/public/test_import.xlsx',
            'status' => 'processed',
        ]);
        $response = $this->postJson('/api/imports', [
            'file' => $file,
        ]);

        $response->assertStatus(200)->assertJsonStructure(['message']);

        $this->assertDatabaseHas('imports', ['file_name' => 'test_import.xlsx']);
    }
}