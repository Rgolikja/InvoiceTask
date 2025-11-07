<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ElifApiService;

class DeclareCashDeskDaily extends Command
{
    protected $description = 'Automatically declare cash desk at start of day';
    protected $signature = 'app:declare-cashdesk';





    public function handle(ElifApiService $service)
    {
        $token = $service->login();

        if (is_array($token) && isset($token['error'])) {
            $this->error('Login failed');
            return;
        }
        $result = $service->declareCashDesk($token);
        if (isset($result['status']['code']) && $result['status']['code'] == 600) {
            $this->info("cash desk declared successfully");
        } else {
            $this->error('cash dask declaration failed');
        }


    }
}
