<?php

namespace App\Http\Controllers;

use App\Http\Requests\EpaymentRequest;
use App\Interfaces\services\iepaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
class EpaymentController extends Controller
{
    protected $service;

    public function __construct(iepaymentService $service)
    {
        $this->service = $service;
    }

    public function checkinvoice($invoicenumber, Request $request)
    {
        $token = $request->header('AUTHORIZATION');
        $removedtoken = str_replace('"', ' ', $token);
        $newtoken = str_replace('Bearer ', ' ', $removedtoken);
        $final = Str::trim($newtoken);

        return $this->service->checkinvoice(['token' => $final, 'invoicenumber' => $invoicenumber]);
    }

    public function posttransaction(Request $request)
    {
        Log::info("posttransaction:".json_encode($request->all()));
        $validator = Validator::make($request->all(), [
            'initiationId' => 'required',
            'TransactionDate' => 'required',
            'Reference' => 'required',
            'Amount' => 'required',
            'Currency' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $token = $request->header('AUTHORIZATION');
        $removedtoken = str_replace('"', ' ', $token);
        $newtoken = str_replace('Bearer ', ' ', $removedtoken);
        $final = Str::trim($newtoken);
        Log::info("final:".$final);
        return $this->service->posttransaction(['token' => $final, 'initiationId' => $request['initiationId'],
            'TransactionDate' => $request['TransactionDate'],
            'Reference' => $request['Reference'],
            'Amount' => $request['Amount'],
            'Currency' => $request['Currency']]);
    }
}
