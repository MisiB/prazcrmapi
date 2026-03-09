<?php

namespace App\Http\Controllers;

use App\Interfaces\repositories\ipayeeInterface;
use App\Interfaces\services\izimswitchInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ZimswitchController extends Controller
{
    protected $zimswitchService;
    protected $payeeService;
    public function __construct(izimswitchInterface $zimswitchService, ipayeeInterface $payeeService)
    {
        $this->zimswitchService = $zimswitchService;
        $this->payeeService = $payeeService;
    }
    public function verify(Request $request)
    {
        $validated = Validator($request->all(), [
            "resourcepath" => "required",
            "reference" => "required"
        ]);
        if ($validated->fails()) {
            return response()->json(['status' => 'error', 'message' => $validated->errors()->first()]);
        }
        $resourcepath  = $request->resourcepath;
        $reference  = $request->reference;
        $response =  $this->zimswitchService->requestverification($resourcepath);
        if ($response == null) {
            return [
                "status" => "error",
                "message" => "zimswitch payment failed"
            ];
        } else {
            $code = $response->result->code;
            $description = $response->result->description;
            $isSuccess =
                preg_match('/^(000\.000\.|000\.100\.1|000\.[36])/', $code) ||
                preg_match('/^(000\.400\.0[^3]|000\.400\.100)/', $code);
            if ($isSuccess) {
                $details = ["status" => "PAID"];
            } else {
                $details = ["status" => "FAILED"];
            }
            $response = $this->payeeService->update($details, $reference);
            $response["message"] = $description;
            return $response;
        }
    }
}
