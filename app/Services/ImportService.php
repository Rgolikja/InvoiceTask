<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Import;
use App\Services\Interfaces\ImportServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;
use Illuminate\Http\Request;

class ImportService implements ImportServiceInterface
{
    public function importExcelData($request)
    {
        $debug = [];
        $summary = [
            'invoices_created' => 0,
            'clients_linked' => 0,
            'items_created' => 0,
        ];

        DB::beginTransaction();

        try {
            if (!$request->hasFile('file')) {
                throw new Exception('No Excel file uploaded.');
            }

            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            $currentInvoice = null;
            $mode = 'searching_header';

            foreach ($rows as $index => $row) {
                $line = trim(implode('', array_map('strval', $row)));
                if ($line === '' || stripos($line, 'Regjistri') !== false)
                    continue;

                /*** 🧾 Detect invoice header ***/
                if (isset($row['A']) && stripos($row['A'], 'Nr:') !== false) {
                    $invoiceNumber = trim(str_replace('Nr:', '', ($row['A'] ?? '') . ' ' . ($row['B'] ?? '')));
                    $invoiceNumber = preg_replace('/[^A-Z0-9]/i', '', $invoiceNumber);

                    $debug[] = "Row[$index]: Detected header (A='{$row['A']}', B='{$row['B']}') → invoiceNumber='{$invoiceNumber}'";

                    $invoiceDate = null;
                    $currency = 'EUR';
                    foreach ($row as $cell) {
                        $cell = (string) $cell;
                        if (stripos($cell, 'Date dokumenti:') !== false) {
                            $invoiceDate = trim(str_replace('Date dokumenti:', '', $cell));
                        }
                        if (stripos($cell, 'Monedha:') !== false) {
                            $currency = trim(str_replace('Monedha:', '', $cell));
                        }
                    }

                    if ($invoiceNumber === '') {
                        $debug[] = "Row[$index]: Missing invoice number — skipping.";
                        $currentInvoice = null;
                        $mode = 'searching_header';
                        continue;
                    }

                    /*** 👤 Detect client info ***/
                    $clientCode = null;
                    $clientName = null;
                    for ($i = 1; $i <= 3; $i++) {
                        if (!isset($rows[$index + $i]))
                            break;
                        $nextRow = $rows[$index + $i];
                        foreach ($nextRow as $col => $value) {
                            $value = (string) $value;
                            if (stripos($value, 'Klienti:') !== false) {
                                $nextCol = chr(ord($col) + 1);
                                $clientCode = trim($nextRow[$nextCol] ?? '');
                            }
                            if (stripos($value, 'Emri:') !== false) {
                                $nextCol = chr(ord($col) + 1);
                                $clientName = trim($nextRow[$nextCol] ?? '');
                            }
                        }
                        if ($clientCode && $clientName)
                            break;
                    }

                    if (!$clientCode) {
                        $debug[] = "Row[$index]: Missing client code for {$invoiceNumber}.";
                        $currentInvoice = null;
                        $mode = 'searching_header';
                        continue;
                    }

                    $client = Client::firstOrCreate(
                        ['code' => $clientCode],
                        ['name' => $clientName ?? 'Unknown']
                    );

                    if ($client->wasRecentlyCreated) {
                        $summary['clients_linked']++;
                        $debug[] = "Created new client: {$clientCode} ({$clientName})";
                    } else {
                        $debug[] = "Found existing client: {$clientCode}";
                    }

                    $existing = Invoice::where('invoice_number', $invoiceNumber)->first();
                    if ($existing) {
                        $currentInvoice = $existing;
                        $debug[] = "Reusing existing invoice {$invoiceNumber}.";
                    } else {
                        $currentInvoice = Invoice::create([
                            'client_id' => $client->id,
                            'invoice_number' => $invoiceNumber,
                            'invoice_date' => $this->parseDate($invoiceDate) ?? now(),
                            'currency' => $currency ?: 'EUR',
                            'total_amount_eur' => null,
                            'total_with_vat' => null,
                            'total_amount_all' => null,
                        ]);
                        $summary['invoices_created']++;
                        $debug[] = "Created new invoice {$invoiceNumber}.";
                        //after invoices are created we try to fiscalize them
                        try {
                            $elifService = new \App\Services\ElifApiService();
                            $token = $elifService->login();

                            if (!isset($token['error'])) {
                                $fiscalResponse = $elifService->fiscalize($currentInvoice, $token);

                                if (isset($fiscalResponse['qrcode_url'])) {
                                    $currentInvoice->update([
                                        'fiscal_qr_url' => $fiscalResponse['qrcode_url'],
                                        'fiscalized_at' => now(),
                                        'fiscalization_response' => json_encode($fiscalResponse),
                                    ]);
                                    $debug[] = "Fiscalized Invoice {$invoiceNumber}. QR: {$fiscalResponse['qrcode_url']}";

                                } else {
                                    $debug[] = "Fiscalization Failed";
                                }
                            } else {
                                $debug[] = "Fiscalization login failed";
                            }
                        } catch (Exception $e) {
                            $debug[] = "Fiscalization exception for {$invoiceNumber}" . $e->getMessage();
                            Log::error("Fiscalization Error for {$invoiceNumber}" . $e->getMessage());
                        }

                    }

                    $mode = 'reading_items';
                    continue;
                }

                /*** 📦 Detect item rows ***/
                if ($mode === 'reading_items' && $currentInvoice) {
                    // Detect totals
                    if (stripos($line, 'Shuma me TVSH') !== false || stripos($line, 'Shuma pa TVSH') !== false) {
                        preg_match_all('/([\d\.,]+)/', $line, $matches);
                        $eurTotal = isset($matches[1][0]) ? $this->toDecimal($matches[1][0]) : 0;
                        $allTotal = isset($matches[1][1]) ? $this->toDecimal($matches[1][1]) : 0;

                        $currentInvoice->update([
                            'total_amount_eur' => $eurTotal,
                            'total_with_vat' => $eurTotal,
                            'total_amount_all' => $allTotal,
                        ]);

                        $debug[] = "💶 Updated totals for invoice {$currentInvoice->invoice_number}: EUR={$eurTotal}, ALL={$allTotal}";
                        $mode = 'searching_header';
                        continue;
                    }

                    // Detect product lines
                    if ($row['A'] && !str_starts_with($row['A'], 'Klienti:')) {
                        $productName = trim($row['A']);
                        $unit = trim($row['B'] ?? ''); // ✅ Unit column (B)
                        $values = array_values($row);
                        $numbers = [];

                        // Collect all numeric values
                        foreach ($values as $v) {
                            if (preg_match('/^\d+([.,]\d+)?$/', trim($v))) {
                                $numbers[] = $this->toDecimal($v);
                            }
                        }

                        // ✅ Map numeric columns (Layout A)
                        $quantity = $numbers[0] ?? 0;
                        $unitPrice = $numbers[1] ?? 0;
                        $totalPrice = $numbers[2] ?? 0;
                        $totalAll = $numbers[count($numbers) - 1] ?? 0; // ✅ last numeric = ALL

                        // ✅ Description is last non-numeric text
                        $description = '';
                        foreach (array_reverse($values) as $v) {
                            $v = trim((string) $v);
                            if ($v !== '' && !preg_match('/^\d+([.,]\d+)?$/', $v)) {
                                $description = $v;
                                break;
                            }
                        }

                        if ($productName === '' || $totalPrice == 0)
                            continue;

                        InvoiceItem::create([
                            'invoice_id' => $currentInvoice->id,
                            'product_name' => $productName,
                            'unit' => $unit,
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'total_price' => $totalPrice,
                            'vat_amount' => 0,
                            'description' => $description,
                        ]);

                        // ✅ Update invoice totals
                        $currentInvoice->update([
                            'total_amount_eur' => $totalPrice,
                            'total_with_vat' => $totalPrice,
                            'total_amount_all' => $totalAll,
                        ]);

                        $summary['items_created']++;
                        $debug[] = "🧩 Added '{$productName}' (Unit={$unit}, Qty={$quantity}, Price={$unitPrice}, EUR={$totalPrice}, ALL={$totalAll}, Desc='{$description}').";
                    }
                }
            }

            DB::commit();

            return [
                'message' => 'File imported successfully',
                'debug' => $debug ?: ['Import completed successfully'],
                'summary' => $summary,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Import failed: ' . $e->getMessage());
            return [
                'message' => 'Import failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function toDecimal($value)
    {
        return floatval(str_replace([',', ' '], ['.', ''], $value));
    }

    private function parseDate($value)
    {
        $date = \DateTime::createFromFormat('d/m/Y', trim($value));
        return $date ?: null;
    }

    public function updateImport(int $id, Request $request)
    {
        $import = Import::findOrFail($id);
        $import->update($request->only(['status', 'error_message']));
        return $import;
    }

    public function deleteImport(int $id)
    {
        $import = Import::findOrFail($id);
        $import->delete();
        return true;
    }

    public function getAllImports(int $perPage)
    {
        return Import::orderBy('created_at', 'desc')->paginate($perPage);
    }
}
