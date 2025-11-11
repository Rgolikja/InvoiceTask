<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Invoice;
use App\Services\ElifApiService;
use Exception;

class FiscalizeInvoices extends Command
{
    protected $signature = 'invoices:fiscalize';
    protected $description = 'Send created invoices to Tax Authorities every 5 minutes';


    public function handle()
    {
        Log::info('Fiscalization started at' . now());

        $service = new ElifApiService();
        $token = $service->login();

        if (isset($token['error'])) {
            Log::error('Fiscalization skipped:login failed');
            return Command::FAILURE;
        }
        $invoices = Invoice::whereNull('fiscalized_at')->get();

        if ($invoices->isEmpty()) {
            Log::info('No invoices to fiscalize');
            return Command::SUCCESS;
        }

        foreach ($invoices as $invoice) {
            try {
                $response = $service->fiscalize($invoice, $token);

                if (isset($response['qrcode_url'])) {
                    $invoice->update([
                        'fiscal_qr_url' => $response['qrcode_url'],
                        'fiscalized_at' => now(),
                        'fiscalization_response' => json_encode($response),
                    ]);
                    Log::info(" Invoice {$invoice->id} fiscalized successfully.");
                } else {
                    Log::warning(" Invoice {$invoice->id} fiscalization failed. Response: " . json_encode($response));
                }
            } catch (\Exception $e) {
                Log::error(" Fiscalization error for invoice {$invoice->id}: " . $e->getMessage());
            }

        }
        Log::info('Fiscalization with cron completed at: ' . now());
        return Command::SUCCESS;
    }
}
