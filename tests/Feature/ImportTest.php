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

}
