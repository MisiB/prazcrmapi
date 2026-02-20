<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetWalletByTypeRequest;
use App\Interfaces\services\isuspenseService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    protected $suspenseService;
    public function __construct(isuspenseService $suspenseService)
    {
        $this->suspenseService = $suspenseService;
    }

    public function getwallet($regnumber)
    {
        return $this->suspenseService->getsuspensewallet($regnumber);
    }
    public function getwalletbalance(Request $request)
    {
        $validated = Validator($request->all(), [
            'regnumber' => 'required',
            'type' => 'required',
            'currency' => 'required'
        ]);
        if ($validated->fails()) {
            Log::error("Validation failed for getwalletbalance: " . $validated->errors()->first());
            return response()->json(['status' => 'error', 'message' => $validated->errors()->first()]);
        }
        return $this->suspenseService->getwalletbalance($request->regnumber, $request->type, $request->currency);
    }
}
