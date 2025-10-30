<?php

namespace App\Imports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class InvoicesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Log every row that Excel is reading
        Log::info('Reading Excel row: ', $row);

        // Skip if essential fields are empty
        if (empty($row['client_id']) || empty($row['invoice_number'])) {
            Log::warning('Skipping row due to missing required fields.', $row);
            return null;
        }

        // Log before inserting into DB
        Log::info('Inserting invoice: ', $row);

        return new Invoice([
            'client_id' => $row['client_id'],
            'invoice_number' => $row['invoice_number'],
            'invoice_date' => $row['invoice_date'],
            'total_amount_eur' => $row['total_amount_eur'],
            'total_with_vat' => $row['total_with_vat'],
            'total_amount_all' => $row['total_amount_all'],
            'currency' => $row['currency'],
            'base_currency' => $row['base_currency'],
        ]);
    }
}
