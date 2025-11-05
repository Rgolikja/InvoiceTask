<?php
namespace App\Http\Controllers;
use App\Models\Invoice;
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


    public function fiscalize($invoiceId)
    {
        //marrim invoice me id specifike
        $invoice = Invoice::with('items', 'client')->findOrFail($invoiceId);

        //tani na duhet tbejm login per te marre token
        $token = $this->elifService->login();

        //nese login ben fail
        if (isset($token['error'])) {
            return response()->json([
                'success' => false,
                'message' => 'Elif Login failed',
                'details' => $token
            ], 500);
        }

        //dergojm invoice per tu fiskalizuar
        $result = $this->elifService->fiscalize($invoice, $token);

        return response()->json($result);//kthejm rezultatin e fiskalizimit
    }
}