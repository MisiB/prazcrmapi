<?php

namespace App\implementation\services;

use App\Interfaces\repositories\icustomerInterface;
use App\Interfaces\services\icustomerInterface as servicecustomerInterface;

class _customerService implements servicecustomerInterface
{
    protected $customerrepo;
    public function __construct(icustomerInterface $customerrepo)
    {
        $this->customerrepo = $customerrepo;
    }
    public function getall(){
        return $this->customerrepo->getall();
    }

    public function getcustomerbyregnumber($regnumber){
     $customer = $this->customerrepo->getcustomerbyregnumber($regnumber);
     if ($customer == null) {
        return [
            'status' => 'ERROR',
            'message' => 'Account not found'
        ];
    }
     $array = [  "id"=>$customer->id,
        "regnumber"=>$customer->regnumber,
        "name"=>$customer->name,
        "type"=>$customer->type,
        "bankTransaction"=>null,
        "invoice"=>null,
        "suspense"=>null,
        "onlinepayments"=> null,
        "baddebts"=>null,
        "dateCreated"=>"2019-12-01T00:00:00+00:00",
        "dateUpdated"=>"2021-07-09T00:00:00+00:00",
        "dateDeleted"=>null];
        return $array;
    }
    public function createcustomer($data){
        // Check by regnumber (most unique identifier)
        $check_customer_regnumber = $this->customerrepo->getCustomerByRegnumber($data['regnumber']);
        if($check_customer_regnumber != null){
            return [
                "status"=>"ERROR",
                "message"=>"Account already exists with registration number :".$check_customer_regnumber->regnumber,
                "data"=>[
                   "regnumber"=> $check_customer_regnumber->regnumber, 
                   "name"=>$check_customer_regnumber->name
                ]
            ]; 
        }
        
        //Check by name
        $check_customer_name = $this->customerrepo->searchname($data['name'],$data['type']);
        if($check_customer_name != null){
            return [
                "status"=>"ERROR",
                "message"=>"Account name found in previous database please use registration number  :".$check_customer_name->regnumber,
                "data"=>[
                   "regnumber"=> $check_customer_name->regnumber, 
                   "name"=>$check_customer_name->name
                ]
            ];
        }
        
        //no conflicts, create the customer
        $response = $this->customerrepo->create($data);
        if($response['status'] == "ERROR"){
            return $response;
        }else{
            $customer = $response['data'];
            return [
                "status"=>"SUCCESS",
                "message"=>"Account created with registration number :".$customer->regnumber,
                "data"=>[
                   "regnumber"=> $customer->regnumber, 
                   "name"=>$customer->name
                ]
            ];
        }
    }
    public function verifycustomer($data){
 
        if($data['regnumber'] != null){
            $checkregnumber = $this->customerrepo->getCustomerByRegnumber($data['regnumber']);
            if($checkregnumber != null){
                $normalizerequestname = $this->customerrepo->normalizename($data['name']);
                $normalizereponsename = $this->customerrepo->normalizename($checkregnumber->name);
                if($normalizerequestname == $normalizereponsename){
                    return [
                        "status"=>"SUCCESS",
                        "message"=>"REG Number and Account name matched"
                        ]; 
                }else{
                   return [
                    "status"=>"ERROR",
                    "message"=>"REG Number and Account name mismatched. REG Number belows too :".$checkregnumber->name
                    ];
                }

            }else{
               return [
                    "status"=>"ERROR",
                    "message"=>"PRAZ registration number not found"
                    ]; 
            }

        }else{
            $checkcustomername = $this->customerrepo->searchname($data['name'],$data['type']);
            if($checkcustomername != null){
              return [
                "status"=>"SUCCESS",
                "message"=>"Account name found with registration number :".$checkcustomername->regnumber
                ];
 
            }else{
                return [
                    "status"=>"SUCCESS",
                    "message"=>"Account name not found"
                ];
            }

        }
    }
    public function updatecustomer($data){
        $check_customer_regnumber = $this->customerrepo->getCustomerByRegnumber($data['prnumber']);
        if($check_customer_regnumber == null){
            return [
                "status"=>"ERROR",
                "message"=>"PR Number does not exist"
                ];
        }else{
            $normalizedsavedname = $this->customerrepo->normalizename($check_customer_regnumber->name);
            $normalizedoldname = $this->customerrepo->normalizename($data['oldname']);
            if($normalizedsavedname != $normalizedoldname){
                return [
                    "status"=>"ERROR",
                    "message"=>"PR Number and old company name does not match"
                    ];
            }else{
                $check_customer_name = $this->customerrepo->searchname($data['newname'], $check_customer_regnumber->type);
                if($check_customer_name != null){
                    return [
                        "status"=>"ERROR",
                        "message"=>"System has found Duplicate new name"
                        ];
                }else{
                    $updateData = [
                        'name' => $data['newname'],
                    ];
                    
                    // Update business_type if provided
                    if(isset($data['business_type'])){
                        $updateData['business_type'] = $data['business_type'];
                    }
                    
                    $this->customerrepo->update($updateData, $check_customer_regnumber->id);
                    return [
                        "status"=>"SUCCESS",
                        "message"=>"Account updated successfully"
                        ];
                }
            }
        }
    }
    public function deletecustomer($id){
        return $this->customerrepo->delete($id);
    }
    public function searchcustomer($needle){
        return $this->customerrepo->search($needle);
    }
}
