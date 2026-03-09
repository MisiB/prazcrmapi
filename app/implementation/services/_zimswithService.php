<?php

namespace App\implementation\services;

use App\Interfaces\repositories\ionlinepaymentInterface;
use App\Interfaces\services\izimswitchInterface;
use Illuminate\Support\Facades\Log;


class _zimswithService implements izimswitchInterface
{
    protected $mode;
    protected $entity_id = "";
    protected $authorization_token = "";
    protected $payment_brand = "";
    protected $test_mode = false;
    protected $oppwa_url = "";
    protected $live_oppwa_url = "";
    protected $paytype = "";
    protected $shopperurl = "";
    protected $resulturl = "";
    protected $baseurl = "";
    protected $checkouturl = "";
    protected $onlinepaymentRepository;

    public function __construct(ionlinepaymentInterface $onlinepaymentRepository)
    {
        $this->mode = config('api.zimswitch.MODE');
        if ($this->mode == 'TEST') {
            $this->entity_id = config('api.zimswitch.TEST_ENTITY_ID');
            $this->authorization_token = config('api.zimswitch.TEST_AUTHORIZATION_TOKEN');
            $this->payment_brand = config('api.zimswitch.TEST_PAYMENT_BRAND');
            $this->test_mode = true;
            $this->oppwa_url = config('api.zimswitch.TEST_OPPWA_URL');
            $this->paytype = config('api.zimswitch.PAYTYPE');
            $this->shopperurl = config('api.zimswitch.TEST_SHOPPERURL');
            $this->resulturl = config('api.zimswitch.TEST_RESULTURL');
            $this->baseurl = config('api.zimswitch.TEST_BASEURL');
            $this->checkouturl = config('api.zimswitch.TEST_CHECKOUTURL');
        } else {
            $this->entity_id = config('api.zimswitch.LIVE_ENTITY_ID');
            $this->authorization_token = config('api.zimswitch.LIVE_AUTHORIZATION_TOKEN');
            $this->payment_brand = config('api.zimswitch.LIVE_PAYMENT_BRAND');
            $this->test_mode = false;
            $this->oppwa_url = config('api.zimswitch.LIVE_OPPWA_URL');
            $this->paytype = config('api.zimswitch.PAYTYPE');
            $this->shopperurl = config('api.zimswitch.LIVE_SHOPPERURL');
            $this->resulturl = config('api.zimswitch.LIVE_RESULTURL');
            $this->baseurl = config('api.zimswitch.LIVE_BASEURL');
            $this->checkouturl = config('api.zimswitch.LIVE_CHECKOUTURL');
        }
        $this->payment_brand = config('api.zimswitch.PAYMENT_BRAND');
        $this->onlinepaymentRepository = $onlinepaymentRepository;
    }
    public function requestcheckout($data)
    {

        try {

            $amount = number_format($data['amount'], 2, '.', '');
            $currency = $data['currency'] == 'ZiG' ? 'ZWG' : $data['currency'];
            $data = "entityId=" . $this->entity_id . "&amount=" . $amount . "&currency=" . $currency . "&paymentType=" . $this->paytype;
            if ($this->mode == 'TEST') {
                if ($this->test_mode == "TEST_EXTERNAL") {
                    $data = $data . "&testMode=" . "EXTERNAL";
                } else {
                    $data = $data . "&testMode=" . "INTERNAL";
                }
            }


            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->oppwa_url);

            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array(
                    $this->authorization_token
                )
            );
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->mode == "TEST" ? false : true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $responseData = curl_exec($ch);
            //  dd($responseData);

            if (curl_errno($ch)) {
                return curl_error($ch);
            }
            curl_close($ch);

            $responseData = json_decode($responseData);
            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/zimswitch.log'),
            ])->info("Zimswitch Checkout Response:" . json_encode([
                'response' => $responseData,
                'data' => $data,
                'oppwa_url' => $this->oppwa_url,
                'authorization_token' => $this->authorization_token,
                'paytype' => $this->paytype,
                'shopperurl' => $this->shopperurl,
                'resulturl' => $this->resulturl,
            ]));

            if (is_null($responseData)) {
                return ["status" => "error", "message" => "Failed to initiate payment", "data" => null];
            }
            if ($responseData->result->code != '000.200.100') {
                return ["status" => "error", "message" => "Failed to initiate payment", "data" => null];
            }
            return ["status" => "success", "message" => "Payment initiated successfully", "data" => $this->checkouturl . $responseData->id];
        } catch (\Exception $e) {
            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/zimswitch.log'),
            ])->info("Zimswitch Checkout Failed:" . json_encode([
                'error' => $e->getMessage(),
            ]));
            return ["status" => "error", "message" => $e->getMessage(), "data" => null];
        }
    }


    public function requestverification($resourcepath)
    {
        Log::info($resourcepath);
        try {
            $url = $this->baseurl . $resourcepath . "?entityId=" . $this->entity_id;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array($this->authorization_token)
            );
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $responseData = curl_exec($ch);
            if (curl_errno($ch)) {
                return curl_error($ch);
            }
            curl_close($ch);
            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/zimswitch.log'),
            ])->info("Zimswitch response:" . $responseData);
            return json_decode($responseData);
        } catch (\Exception $e) {
            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/zimswitch.log'),
            ])->info("Zimswitch Verification Failed:" . json_encode([
                'error' => $e->getMessage(),
            ]));
            return null;
        }
    }
}
