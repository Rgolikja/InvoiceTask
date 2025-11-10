<?php
namespace App\Http\Controllers;
use App\Models\Invoice;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['client', 'items'])->get();
        return response()->json($invoices);
    }
    public function show($id)
    {
        $invoices = Invoice::with(['client', 'items'])->findOrFail($id);
        return response()->json($invoices);

    }
}