<?php
namespace App\Http\Controllers;

use App\Services\ElifApiService;
use Illuminate\Http\Request;
class ElifController extends Controller
{
    protected ElifApiService $elifService;


    public function __construct(ElifApiService $elifService)
    {
        $this->elifService = $elifService;
    }
    public function login()
    {
        $data = $this->elifService->login();
        return response()->json($data);
    }
}