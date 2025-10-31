<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Import;
use App\Imports\InvoicesImport;
use App\Services\Interfaces\ImportServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportService implements ImportServiceInterface
{
    // Import excel data
    public function importExcelData(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:20480',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $path = $file->storeAs('imports', $originalName, 'public');

        $import = Import::create([
            'file_name' => $originalName,
            'file_path' => $path,
            'status' => 'processed',
        ]);

        $errors = [];
        $rowsTotal = 0;
        $rowsImported = 0;

        try {
            $sheets = Excel::toCollection(new InvoicesImport(), storage_path('app/public/' . $path));
            if ($sheets->isEmpty()) {
                throw new \Exception('Excel file empty');
            }

            $rows = $sheets->first();
            $rowsTotal = $rows->count();

            // group by invoice_number
            $groups = [];
            foreach ($rows as $row) {
                $r = $row->toArray();
                $invoiceNumber = $this->firstNotEmpty($r, ['invoice_number', 'invoice no', 'nr', 'fatura_nr']);
                $invoiceNumber = trim((string) $invoiceNumber);
                if ($invoiceNumber === '') {
                    $errors[] = 'Row skipped (no invoice_number): ' . json_encode($r);
                    continue;
                }
                $groups[$invoiceNumber][] = $r;
            }

            foreach ($groups as $invoiceNumber => $invoiceRows) {
                DB::beginTransaction();
                try {
                    $first = $invoiceRows[0];
                    $clientExternal = $this->firstNotEmpty($first, ['client_id', 'customer_id']);
                    $clientName = $this->firstNotEmpty($first, ['customer_name', 'client_name', 'client']);
                    $client = null;
                    if ($clientExternal) {
                        $client = Client::firstOrCreate(['external_id' => (string) $clientExternal], ['name' => $clientName ?: 'Unknown']);
                    } else {
                        $client = Client::firstOrCreate(['name' => $clientName ?: 'Unknown']);
                    }

                    $invoice = Invoice::firstOrCreate(
                        ['invoice_number' => $invoiceNumber],
                        [
                            'client_id' => $client->id,
                            'invoice_date' => $this->parseDate($this->firstNotEmpty($first, ['invoice_date', 'date'])),
                            'currency' => $this->firstNotEmpty($first, ['currency']),
                            'total_amount_eur' => $this->toDecimal($this->firstNotEmpty($first, ['total_amount_eur', 'total'])),
                            'total_with_vat' => $this->toDecimal($this->firstNotEmpty($first, ['total_with_vat', 'total_with_tax'])),
                            'total_amount_all' => $this->toDecimal($this->firstNotEmpty($first, ['total_amount_all'])),
                        ]
                    );

                    foreach ($invoiceRows as $r) {
                        $itemName = $this->firstNotEmpty($r, ['item_name', 'product_name', 'description']);
                        $itemCode = $this->firstNotEmpty($r, ['item_code', 'code', 'sku']);
                        $quantity = $this->toDecimal($this->firstNotEmpty($r, ['quantity', 'sasia'])) ?: 1;
                        $unitPrice = $this->toDecimal($this->firstNotEmpty($r, ['unit_price', 'cmimi'])) ?: 0;

                        if (empty($itemName) && empty($itemCode)) {
                            $errors[] = "Skipping item (no name/code) for invoice {$invoiceNumber}";
                            continue;
                        }

                        InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'item_code' => $itemCode,
                            'product_name' => $itemName,
                            'unit' => $this->firstNotEmpty($r, ['unit']),
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'total_without_tax' => $this->toDecimal($this->firstNotEmpty($r, ['total_without_tax', 'vlefta_pa_tvsh'])),
                            'total_with_tax' => $this->toDecimal($this->firstNotEmpty($r, ['total_with_tax', 'vlefta_me_tvsh'])),
                            'vat_amount' => $this->toDecimal($this->firstNotEmpty($r, ['vat_amount', 'tvsh_amount'])),
                        ]);

                        $rowsImported++;
                    }

                    DB::commit();
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $errors[] = "Invoice {$invoiceNumber} failed: " . $e->getMessage();
                }
            }

            $import->update([
                'status' => empty($errors) ? 'completed' : 'failed',
                'rows_total' => $rowsTotal,
                'rows_imported' => $rowsImported,
                'error_message' => empty($errors) ? null : implode("\n", $errors),
            ]);
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $import;
    }
    public function updateImport(int $id, Request $request)
    {
        $import = Import::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|string|in:processed,completed,failed',
        ]);
        $import->update($validated);
        return $import;
    }

    public function deleteImport(int $id)
    {
        $import = Import::findOrFail($id);

        if (Storage::disk('public')->exists($import->file_path)) {
            Storage::disk('public')->delete($import->file_path);
        }

        $import->delete();
        return true;
    }

    public function deleteAllImports()
    {
        $imports = Import::all();

        foreach ($imports as $import) {
            if (Storage::disk('public')->exists($import->file_path)) {
                Storage::disk('public')->delete($import->file_path);
            }
            $import->delete();
        }

        return true;
    }

    public function getAllImports(int $perPage = 10)
    {
        return Import::paginate($perPage);
    }
    public function getImportById(int $id)
    {
        return Import::findOrFail($id);
    }

}
