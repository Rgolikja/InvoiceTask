<?php
namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;


class Kernel extends ConsoleKernel
{

    protected function schedule(Schedule $schedule)
    {

        //ktu do jet fiscalization croni yne qe do behet run cdo 5 minuta
        $schedule->call(function () {
            $service = new \App\Services\ElifApiService();

            //bejm login te elif per te marre token
            $token = $service->login();
            //nese login ben fail behet return 
            if (isset($token['error'])) {
                Log::error("Elif fiscalization skipped Login Failed");
                return;
            }
            //do marrim invoicet qe nuk jan fiskalizuar akoma
            $invoices = \App\Models\Invoice::whereNull('fiscalizet_at')->get();
            //nje loop per invoicete pa fiskalizuara
            foreach ($invoices as $invoice) {

                try {
                    //dergojm faturen tek api i fiskalizimit
                    $response = $service->fiscalize($invoice, $token);
                    //nese api kthen nje qrcode_url fiskalizimi esht sukses
                    if (isset($response['qrcode_url'])) {
                        $invoice->update([
                            'fiscal_qr_url' => $response['qrcode_url'],
                            'fiscalized_at' => now(),
                            'fiscalization_response' => json_encode($response),
                        ]);
                        Log::info("fiscalized invoice");
                    }
                } catch (Exeption $e) {
                    Log::error("Fiscalization failed");
                }
            }
        })

        //do behet cdo 5 minuta
            ->everyFiveMinutes();
    }
}