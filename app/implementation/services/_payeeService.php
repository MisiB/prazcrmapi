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
            if ($data->onlinepayment->status == "PAID") {
                return [
                    "status" => "success",
                    "message" => "Transaction already settled",
                    "return_url" => $data?->onlinepayment->redirecturl,
                    "uuid" => $data?->onlinepayment->uuid,
                    "data" => $data
                ];
            }
            $returnurl = config('paynowconfig.return_url') . $data->uuid;
            $checkstatus = $this->paynow->checkpaymentstatus(['type' => $data?->onlinepayment?->invoice?->inventoryitem?->type, 'currency_id' => $data?->onlinepayment->currency_id, 'pollurl' => $data->poll_url, 'returnurl' => $returnurl]);


            $status = "";
            if (strtoupper($checkstatus['status']) == "PAID" || strtoupper($checkstatus['status']) == "AWAITING DELIVERY") {
                $status = "PAID";
            } else {
                $status = strtoupper($checkstatus['status']);
            }

             $this->payeeRepository->update(['status' => $status], $uuid);
            return $this->payeeRepository->getbyuuid($uuid);

            // return $this->update(['status' => strtoupper($checkstatus['status'])], $uuid);
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
