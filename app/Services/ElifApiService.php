<?php
//do bejme lidhjen me api e elifit, funksionete login etc
namespace App\Services;

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




}