<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Import;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\Interfaces\ImportServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportService implements ImportServiceInterface
{
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
            $sheets = Excel::toCollection(null, storage_path('app/public/' . $path));
            if ($sheets->isEmpty()) {
                throw new \Exception('Excel file empty');
            }

            $rows = $sheets->first();
            $rowsTotal = $rows->count();

            $currentInvoice = null;
            $currentClient = null;
            $headerFound = false;

            DB::beginTransaction();

            foreach ($rows as $row) {
                $r = is_array($row) ? $row : $row->toArray();
                $values = array_map(fn($v) => trim((string) $v), $r);
                if (count(array_filter($values)) === 0) {
                    continue;
                }

                $line = implode(' ', $values);

                // Detect invoice start
                if (str_contains($line, 'Nr: FSH')) {
                    preg_match('/Nr:\s*(FSH\d+)/', $line, $nrMatch);
                    preg_match('/Date dokumenti:\s*([\d\/]+)/', $line, $dateMatch);
                    preg_match('/Monedha:\s*(\w+)/', $line, $currMatch);

                    $invoiceNumber = $nrMatch[1] ?? null;
                    $date = isset($dateMatch[1]) ? date('Y-m-d', strtotime(str_replace('/', '-', $dateMatch[1]))) : null;
                    $currency = $currMatch[1] ?? 'EUR';

                    if (!$invoiceNumber) {
                        continue;
                    }

                    // Save previous invoice items done
                    $currentInvoice = Invoice::firstOrCreate(
                        ['invoice_number' => $invoiceNumber],
                        ['invoice_date' => $date, 'currency' => $currency, 'base_currency' => 'ALL']
                    );

                    $headerFound = false; // Reset header detection for new invoice
                    continue;
                }

                // Detect client info
                if (str_contains($line, 'Klienti') && str_contains($line, 'Emri')) {
                    preg_match('/Emri\s*:\s*(.*)/', $line, $nameMatch);
                    $clientName = $nameMatch[1] ?? 'Unknown';

                    $currentClient = Client::firstOrCreate(['name' => $clientName]);

                    if ($currentInvoice) {
                        $currentInvoice->update(['client_id' => $currentClient->id]);
                    }
                    continue;
                }

                // Detect product header
                if (!$headerFound && isset($values[0]) && stripos($values[0], 'Kase Telefoni') === false && stripos($values[0], 'Përshkrimi') !== false) {
                    $headerFound = true;
                    continue;
                }

                // Detect end of invoice (Shuma pa TVSH)
                if (str_contains($line, 'Shuma pa TVSH')) {
                    $headerFound = false; // Done with items for this invoice
                    $currentInvoice = null; // Reset current invoice
                    continue;
                }

                // Add product items if header found
                if ($headerFound && $currentInvoice && !empty($values[0])) {

                    // Map Excel columns correctly
                    $productName = $values[0] ?? 'Unknown';
                    $unit = $values[1] ?? null;
                    $quantity = $this->toDecimal($values[2] ?? 0);
                    $unitPrice = $this->toDecimal($values[3] ?? 0);
                    $totalPrice = $this->toDecimal($values[4] ?? 0);
                    $vatAmount = $this->toDecimal($values[5] ?? 0);
                    $totalWithVat = $this->toDecimal($values[6] ?? 0);
                    $description = $values[7] ?? null;

                    // Ensure quantity is at least 0 to avoid NOT NULL error
                    if ($quantity === null)
                        $quantity = 0;

                    InvoiceItem::create([
                        'invoice_id' => $currentInvoice->id,
                        'product_name' => $productName,
                        'unit' => $unit,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'vat_amount' => $vatAmount,
                        'description' => $description,
                    ]);

                    $rowsImported++;
                }
            }

            DB::commit();

            $import->update([
                'status' => empty($errors) ? 'completed' : 'failed',
                'rows_total' => $rowsTotal,
                'rows_imported' => $rowsImported,
                'error_message' => empty($errors) ? null : implode("\n", $errors),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error('Import failed: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }

        return $import;
    }

    protected function toDecimal($value)
    {
        if ($value === null || $value === '')
            return 0;

        $s = str_replace([' ', "\u{00A0}"], '', (string) $value);

        if (substr_count($s, ',') > 0 && substr_count($s, '.') > 0) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (substr_count($s, ',') > 0 && substr_count($s, '.') === 0) {
            $s = str_replace(',', '.', $s);
        }

        $s = preg_replace('/[^\d\.\-]/', '', $s);

        return $s === '' || $s === '.' || $s === '-' ? 0 : (float) $s;
    }

    // Optional CRUD helpers
    public function deleteImport(int $id)
    {
        $import = Import::findOrFail($id);
        if (Storage::disk('public')->exists($import->file_path)) {
            Storage::disk('public')->delete($import->file_path);
        }
        $import->delete();
        return response()->json(['deleted' => true]);
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
        return response()->json(['deleted_all' => true]);
    }

    public function getAllImports(int $perPage = 10)
    {
        return Import::paginate($perPage);
    }

    public function getImportById(int $id)
    {
        return Import::findOrFail($id);
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
}
