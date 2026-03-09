<?php

namespace App\implementation\services;

use App\Interfaces\repositories\ipayeeInterface;
use App\Interfaces\services\ipayeeService;
use App\Interfaces\services\ipaynowInterface;

class _payeeService implements ipayeeService
{
    protected $payeeRepository;
    protected $paynow;

    public function __construct(ipayeeInterface $payeeRepository, ipaynowInterface $paynow)
    {
        $this->payeeRepository = $payeeRepository;
        $this->paynow = $paynow;
    }

    public function getbyemail($email)
    {
        return $this->payeeRepository->getbyemail($email);
    }

    public function getbyuuid($uuid)
    {
        return $this->payeeRepository->getbyuuid($uuid);
    }
    public function checkattempt($uuid)
    {
        $attempt =  $this->payeeRepository->getbyuuid($uuid);
        if ($attempt['status'] == "success") {
            $data = $attempt["data"];
            $returnurl = config('paynowconfig.return_url') . $data->uuid;
            $checkstatus = $this->paynow->checkpaymentstatus(['type' => $data?->onlinepayment?->invoice?->inventoryitem?->type, 'currency_id' => $data?->onlinepayment->currency_id, 'pollurl' => $data->poll_url, 'returnurl' => $returnurl]);


            $response = $this->payeeRepository->update(['status' => $checkstatus['status']], $uuid);
            return [
                "status" => $response['status'],
                "message" => $response['message'],
                'redirecturl' => $response["data"]?->onlinepayment->return_url
            ];
        } else {
            return [
                "status" => "error",
                "message" => $attempt['message']
            ];
        }
    }

    public function create(array $details)
    {
        return $this->payeeRepository->create($details);
    }

    public function update(array $details, $uuid)
    {
        return $this->payeeRepository->update($details, $uuid);
    }

    public function retrievepayments($email)
    {
        return $this->payeeRepository->getbyemail($email);
    }
}
