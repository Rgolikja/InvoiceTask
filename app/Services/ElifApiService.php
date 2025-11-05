<?php
//do bejme lidhjen me api e elifit, funksionete login etc
namespace App\Services;
use App\Models\Invoice;


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
        //do dergojm post request tek login.php ne api te elifit me guzzle
        //Request()->dergon metoden post, url https://elif12.2rmlab.com/live/api/login.php dhe nje array me body qe do permbaj username dhe password

        try {
            $response = $this->client->request('POST', 'login.php', [
                'json' => [
                    'username' => $this->username,
                    'password' => $this->password,
                ],
            ]);
            $data = json_decode($response->getBody(), true);

            //duhet te kontrollojm nese kthehet token ne response
            if (isset($data['body']['UserInfo']['token'])) {
                return $data['body']['UserInfo']['token']; //kthehet dhe token dhe userinfo
            }
            return ['error' => 'Login fail', 'response' => $data];

        } catch (\Exception $e) {
            //ktu do kapim dhe kthejm erroret e api ose nga network
            Log::error('Elif Login error ' . $e->getMessage());

            return $data;
        }
    }
    //funksioni fiscalize per te fiskalizuar nje invoice me api te Elif, do marr si parametra invoice te cilin do fiskalizojm dhe token qe na vjen nga login,
//formati qe do i dergohet api do jet si formati tek dokumentimi i post sales.php
    public function fiscalize(Invoice $invoice, string $token)
    {
        try {
            //na duhen te dhenat qe kemi ruajtur per ServerConfig
            $serverConfig = json_encode([
                "Url_API" => env('ELIF_BASE_URL'),
                "DB_Config" => env('ELIF_DB_CONFIG'),
                "Company_DB_Name" => env('ELIF_COMPANY_DB_NAME'),
                "HardwareId" => env('ELIF_HARDWARE_ID'),
                "UserInfo" => [
                    "user_id" => env('ELIF_USER_ID'),
                    "username" => env('ELIF_USERNAME'),
                    "password" => env('ELIF_PASSWORD'),
                    "token" => $token,//token from login function
                ],

            ]);

            //na duhen te dhenat e invoice

            $details = $invoice->items->map(function ($item) {
                return [
                    "sales_invoice_header_id" => null,
                    "sales_invoice_detail_id" => null,
                    "item_code" => $item->product_name,
                    "item_name" => $item->product_name,
                    "item_barcode" => null,
                    "item_total_with_tax_reporting_currency" => number_format($item->total_price, 2, '.', ''),
                    "item_type_id" => 1,
                    "item_price_without_tax" => number_format($item->unit_price, 2, '.', ''),
                    "item_price_with_tax" => number_format($item->unit_price, 2, '.', ''),
                    "item_sales_tax_percentage" => 0,
                    "item_total_without_tax" => number_format($item->total_price, 2, '.', ''),
                    "item_quantity" => $item->quantity,
                    "item_total_with_tax" => number_format($item->total_price, 2, '.', ''),
                    "item_total_tax" => number_format($item->vat_amount ?? 0, 2, '.', ''),
                    "item_unit_id" => 21,
                    "tax_rate_id" => 2,
                    "item_id" => $item->id,
                    "cmd" => "insert"
                ];
            })->toArray();


            //now create the full sales invoice 
            $salesInvoice = [
                "body" => [
                    "cmd" => "insert",
                    "sales_date" => now()->format('Y-m-d H:i:s'),
                    "customer_name" => $invoice->client->name ?? 'Unknown',
                    "exchange_rate" => 1,
                    "city_id" => 1,
                    "automatic_payment_method_id" => 0,
                    'currency_id' => 1,
                    "warehouse_id" => 1,
                    "customer_id" => $invoice->client->id ?? 1,
                    "sales_document_serial" => "",
                    "paid_amount" => number_format($invoice->total_amount_eur ?? 0, 2, '.', ''),
                    "customer_tax_id" => "SKA",
                    "cash_register_id" => 9,
                    "fiscal_delay_reason_type" => null,
                    "fiscal_invoice_type_id" => 4,
                    "fiscal_profile_id" => 1,
                    "details" => $details,
                ],
                "IsEncrypted" => false,
                "ServerConfig" => $serverConfig,
                "App" => "web",
                "Language" => "sq-AL"
            ];
            //dergojm requestin post tek endpointi sales.php dhe e kovertojm nga json ne array qe tbehet e lexueshme
            $response = $this->client->post('sales.php', ['json' => $salesInvoice]);
            $data = json_decode($response->getBody(), true);


            //mbasi e dergojm requesting na duhet te kthehet qr_code qe fiskalizimi esht kryer
            if (isset($data['body'][0]['qrcode_url']) && $data['body'][0]['qrcode_url']) {
                return [
                    'message' => 'Invoice Fiscalized',
                    'qrcode_url' => $data['body'][0]['qrcode_url'],
                    'response' => $data
                ];
            }
            //nese nuk ka qrcode fiskalizimi seshte kryer

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


}