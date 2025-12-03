<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Requests\VerifyCustomerRequest;
use App\Interfaces\services\icustomerInterface;

class CustomerController extends Controller
{
    protected $customerService;
    public function __construct(icustomerInterface $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index(){
        return $this->customerService->getall();
    }

    public function getbyregnumber($regnumber){
        return $this->customerService->getcustomerbyregnumber($regnumber);
    }

    public function createcustomer(CreateCustomerRequest $request){
         /// validate request
      $validated = $request->validated();
      //return $validated['regnumber'];
        return $this->customerService->createcustomer([
            'name' => $validated['name'],
            'regnumber' => $validated['regnumber'],
            'type' => $validated['type'],
            'country' => array_key_exists('country', $validated) ? $validated['country'] : "",
            'business_type' => array_key_exists('business_type', $validated) ? $validated['business_type'] : null,
        ]);
    }

    public function verifycustomer(VerifyCustomerRequest $request){
        $validated = $request->validated();
        return $this->customerService->verifycustomer([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'regnumber' => isset($request['regnumber']) ? $request['regnumber'] : null,
        ]);
    }

    public function updatecustomer(UpdateCustomerRequest $request){

        $validated = $request->validated();
        return $this->customerService->updatecustomer([
            'prnumber' => $validated['prnumber'],
            'oldname' => $validated['oldname'],
            'newname' => $validated['newname'],
            'business_type' => $validated['business_type'] ?? null,
        ]);
    }
}
