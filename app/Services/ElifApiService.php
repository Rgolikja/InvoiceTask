<?php
//do bejme lidhjen me api e elifit, funksionete login etc
namespace App\Services;
use App\Models\Invoice;
use App\Models\InvoiceItem;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ElifApiService
{
    protected $client;
    protected $baseUrl;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->baseUrl = env('ELIF_BASE_URL');
        $this->username = env('ELIF_USERNAME');
        $this->password = env('ELIF_PASSWORD');
        //create nje klient me base url dhe headers per json
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => ['Content-Type' => 'application/json']
        ]);
    }

    public function login()
    {
        try {
            $response = $this->client->request('POST', 'login.php', [
                'json' => [
                    'username' => $this->username,
                    'password' => $this->password,
                ],
            ]);
            $data = json_decode($response->getBody(), true);

            if (isset($data['body'][0]['token'])) {
                return $data['body'][0]['token'];
            }
            return ['error' => 'Login fail', 'response' => $data];
        } catch (\Exception $e) {
            Log::error('Elif Login error ' . $e->getMessage());
            return ['error' => 'Login exception', 'message' => $e->getMessage()];
        }
    }

    public function fiscalize(Invoice $invoice, string $token)
    {
        try {
            // ndertojm serverconfig per tu derguar
            $serverConfig = json_encode([
                "Url_API" => env('ELIF_BASE_URL'),
                "DB_Config" => env('ELIF_DB_CONFIG'),
                "Company_DB_Name" => env('ELIF_COMPANY_DB_NAME'),
                "HardwareId" => env('ELIF_HARDWARE_ID'),
                "UserInfo" => [
                    "user_id" => (int) env('ELIF_USER_ID'),
                    "username" => env('ELIF_USERNAME'),
                    "password" => null,
                    "token" => $token,
                ],
            ], JSON_UNESCAPED_SLASHES);
            $exchangeRate = 96.65;

            //ndertojme details qe do permbaj te dhenat e fatures
            $details = $invoice->items->map(function ($item) use ($invoice, $exchangeRate) {
                return [
                    "sales_invoice_header_id" => null,
                    "sales_invoice_detail_id" => null,
                    "item_code" => $invoice->invoice_number,
                    "item_name" => $item->product_name,
                    "item_barcode" => null,
                    "item_total_with_tax_reporting_currency" => number_format(round($item->total_price * $exchangeRate, 2), 2, '.', ''),
                    "item_type_id" => 1,
                    "item_price_without_tax" => number_format($item->unit_price, 2, '.', ''),
                    "item_price_with_tax" => number_format($item->unit_price, 2, '.', ''),
                    "item_sales_tax_percentage" => 0,
                    "item_total_without_tax" => number_format(round($item->total_price, 2), 2, '.', ''),
                    "item_quantity" => $item->quantity,
                    "item_total_with_tax" => number_format(round($item->total_price, 2), 2, '.', ''),
                    "item_total_tax" => number_format($item->vat_amount ?? 0, 2, '.', ''),
                    "item_unit_id" => 1,
                    "tax_rate_id" => 1,
                    "item_id" => $item->elif_item_id,
                    "is_with_tax" => 0,
                    "cmd" => "insert"
                ];
            })->toArray();

            $totalItems = round($invoice->items->sum('total_price'), 2);
            $paidAmount = number_format($totalItems, 2, '.', '');

            //  ndertojm te gjithe trupin e fatures
            $salesInvoice = [
                "body" => [
                    [
                        "cmd" => "insert",
                        "sales_date" => now()->format('Y-m-d H:i:s'),
                        "customer_name" => $invoice->client->name ?? 'Unknown',
                        "exchange_rate" => $exchangeRate,
                        "city_id" => 1,
                        "automatic_payment_method_id" => 0,
                        "currency_id" => 2,
                        "warehouse_id" => 1,
                        "customer_id" => $invoice->client->id ?? 1,
                        "sales_document_serial" => "",
                        "paid_amount" => $paidAmount,
                        "customer_tax_id" => "SKA",
                        "cash_register_id" => 10,
                        "fiscal_delay_reason_type" => null,
                        "fiscal_invoice_type_id" => 4,
                        "fiscal_profile_id" => 1,
                        "details" => $details,
                    ]
                ],
                "IsEncrypted" => false,
                "ServerConfig" => $serverConfig,
                "App" => "web",
                "Language" => "sq-AL"
            ];


            Log::info('Fiscalization validation check:', [
                'invoice_id' => $invoice->id,
                'paid_amount' => $paidAmount,
                'sum_of_items_total_price' => number_format($totalItems, 2, '.', '')
            ]);

            Log::info('Fiscalization request payload (JSON): ' . json_encode($salesInvoice, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // dergojm requestin tek sales.php
            $response = $this->client->post('sales.php', ['json' => $salesInvoice]);
            $data = json_decode($response->getBody(), true);

            // //nese cash dask sesht ber declare e deklarojm 
            if (($data['response']['status']['code'] ?? null) == 638) {
                Log::info("Cash desk not declared. declariing now");
                $this->declareCashDesk($token);

                $response = $this->client->post('sales.php', ['json' => $salesInvoice]);
                $data = json_decode($response->getBody(), true);
            }

            //  nese del sukses
            if (isset($data['body'][0]['qrcode_url']) && $data['body'][0]['qrcode_url']) {
                return [
                    'message' => 'Invoice Fiscalized',
                    'qrcode_url' => $data['body'][0]['qrcode_url'],
                    'response' => $data
                ];
            }

            //nese kemi fail
            return [
                'message' => 'Fiscalization failed',
                'response' => $data
            ];

        } catch (\Exception $e) {
            Log::error('fiscalization error ' . $e->getMessage());
            return [
                'error' => true,
                'message' => 'fiscalization failed',
                'exception' => $e->getMessage()
            ];
        }
    }


    public function declareCashDesk($token)
    {
        try {
            // ndertojm serverconfig
            $serverConfig = json_encode([
                "Url_API" => env('ELIF_BASE_URL'),
                "DB_Config" => env('ELIF_DB_CONFIG'),
                "Company_DB_Name" => env('ELIF_COMPANY_DB_NAME'),
                "HardwareId" => env('ELIF_HARDWARE_ID'),
                "UserInfo" => [
                    "user_id" => (int) env('ELIF_USER_ID'),
                    "username" => env('ELIF_USERNAME'),
                    "password" => null,
                    "token" => $token,
                ],
            ], JSON_UNESCAPED_SLASHES);

            // pergatisim body te fatures
            $body = [
                "body" => [
                    [
                        "cmd" => "insert",
                        "cash_desk_actual_balance_header_id" => null,
                        "counting_date_time" => now('Europe/Tirane')->format('Y-m-d H:i'),
                        "note" => null,
                        "balance_type" => 2,
                        "fcbc_code" => null,
                        "fiscal_delay_reason_type" => null,
                        "UUID" => null,
                        "details" => [
                            [
                                "cash_desk_actual_balance_detail_id" => null,
                                "cash_desk_actual_balance_header_id" => null,
                                "cash_desk_id" => 10,
                                "currency_id" => 2,
                                "amount" => "0",
                                "note" => null,
                                "exchange_rate" => 104.13,
                                "reporting_currency_total" => 0,
                                "current_amount" => "0",
                                "cash_transaction_header_id" => null
                            ]
                        ]
                    ]
                ],
                "IsEncrypted" => false,
                "ServerConfig" => $serverConfig,
                "App" => "web",
                "Language" => "sq-AL"
            ];

            // dergojm requestin tek cashdeskactualbalance.php
            $response = $this->client->post('cashdeskactualbalance.php', ['json' => $body]);
            $data = json_decode($response->getBody(), true);

            // Log success or failure
            if (($data['status']['code'] ?? null) == 600) {
                Log::info(" Cash desk declared successfully at " . now('Europe/Tirane'));
            } else {
                Log::warning(" Cash desk declaration failed", $data);
            }

            return $data;
        } catch (Exception $e) {
            Log::error(" Error declaring cash desk: " . $e->getMessage());
            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }




}
