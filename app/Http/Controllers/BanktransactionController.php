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

     public function create(Request $request) {
      
        $response = $this->repo->createtransaction([
            'authcode'=>$request['authcode'],
            'description'=>$request['description'],
            'trans_date'=>$request['trans_date'],
            'referencenumber'=>$request['referencenumber'],
            'source_reference'=>$request['source_reference'],
            'statement_reference'=>$request['statement_reference'],
            'amount'=>$request['amount'],
            'accountnumber'=>$request['accountnumber'],
            'currency'=>$request['currency']
           ]);
           if($response['status'] == 200 || $response['status'] == 'SUCCESS'){
            return response()->json(['description' => $response['message'], 'status' => 'success','code' => 200],200);
           }else{
            return response()->json(['description' => $response['message'], 'status' => 'error','code' => 401],401);
           }
         
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
            'SourceReference'=>$request['SourceReference'],
            'token'=>$request['token']
           ]);
           return $response;
     }
     
}
