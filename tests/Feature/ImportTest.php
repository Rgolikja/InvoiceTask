<?php

namespace Tests\Feature;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Import;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    // simple login test

    public function testLogin()
    {
        $user = \App\Models\User::factory()->create([
            'username' => 'admin',
            'password' => bcrypt('admin123'),
            'role' => 'admin'
        ]);
        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'admin123'
        ]);

        $response->assertStatus(200)->assertJsonStructure([
            'message',
            'token',
            'role',
            'user'
        ]);
    }

    public function test_api_available()
    {
        $response = $this->getJson('/api/invoices');
        $response->assertStatus(200);
    }

    public function test_import_upload()
    {
        $response = $this->postJson('/api/imports', []); //empty file 
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }
}
