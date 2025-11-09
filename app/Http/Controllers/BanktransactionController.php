<?php

namespace App\Http\Controllers;

use App\Http\Requests\BanktransactionRequest;
use App\Http\Requests\ClaimBanktransactionRequest;
use App\Http\Requests\SearchRequest;
use App\Interfaces\services\ibanktransactionInterface;
use Illuminate\Http\Request;


class BanktransactionController extends Controller
{
     protected $repo;
     public function __construct(ibanktransactionInterface $repo)
     {
        $this->repo = $repo;
     }

     public function create(BanktransactionRequest $request) {
      
        $response = $this->repo->createtransaction([
            'authcode'=>$request['authcode'],
            'description'=>$request['description'],
            'transactiondate'=>$request['transactiondate'],
            'referencenumber'=>$request['referencenumber'],
            'sourcereference'=>$request['sourcereference'],
            'statementreference'=>$request['statementreference'],
            'amount'=>$request['amount'],
            'accountnumber'=>$request['accountnumber'],
            'currency'=>$request['currency']
           ]);
           return $response;
     }

     public function recallpayment($referencenumber)
     {
        $response = $this->repo->recalltransaction($referencenumber);
        return $response;
     }

     public function search(SearchRequest $request){
        return $this->repo->searchtransaction($request['Search']);;
     }

     public function claim(ClaimBanktransactionRequest $request){
        $response = $this->repo->claim([
            'regnumber'=>$request['regnumber'],
            'sourcereference'=>$request['sourcereference'],
            'token'=>$request['token']
           ]);
           return $response;
     }
     
}
