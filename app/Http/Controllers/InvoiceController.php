<?php
namespace App\Http\Controllers;
use App\Models\Invoice;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['clients', 'items'])->get();
        return response()->json($invoices);
    }
    public function show($id)
    {
        $invoices = Invoice::with(['clients', 'items'])->findOrFail($id);
        return response()->json($invoices);

    }
}