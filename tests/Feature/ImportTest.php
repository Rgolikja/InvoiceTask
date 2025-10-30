<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
class ImportTest extends TestCase
{
    /**
     * A basic feature test example.
     */


    public function test_example(): void
    {

        $response = $this->postJson('/api/imports');
        $response->assertStatus(200);

    }

}
