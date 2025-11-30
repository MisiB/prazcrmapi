<?php

namespace App\implementation\services;

use App\Interfaces\repositories\isuspenseInterface;
use App\Interfaces\services\isuspenseService;
use App\Interfaces\repositories\icustomerInterface;
use App\Interfaces\repositories\invoiceInterface;

class _suspenseService implements isuspenseService
{
    /**
     * Create a new class instance.
     */
    protected $repository;
    protected $customerrepo;
    protected $invoicerepo; 
    public function __construct(isuspenseInterface $repository,icustomerInterface $customerrepo,invoiceInterface $invoicerepo)
    {
        $this->repository = $repository;
        $this->customerrepo = $customerrepo;
        $this->invoicerepo = $invoicerepo;
    }
    public function getpendingsuspensewallets(){
        return $this->repository->getpendingsuspensewallets();
    }
    public function create(array $data){
        return $this->repository->create($data);
    }
    public function createmonthlysuspensewallets($month,$year){
        return $this->repository->createmonthlysuspensewallets($month,$year);
    }
    public function getmonthlysuspensewallets($month,$year){
        return $this->repository->getmonthlysuspensewallets($month,$year);
    }
    public function getsuspensewallet($regnumber){
        return $this->repository->getsuspensewallet($regnumber);
    }
    public function getsuspense($id){
        return $this->repository->getsuspense($id);
    }
    public function getsuspensestatement($customer_id){
        return $this->repository->getsuspensestatement($customer_id);
    }
    public function getwalletbalance($regnumber,$accounttype,$currency){
        return $this->repository->getwalletbalance($regnumber,$accounttype,$currency);
    }
    public function deductwallet($regnumber,$invoice_id,$accounttype,$currency,$amount,$receiptnumber){
        return $this->repository->deductwallet($regnumber,$invoice_id,$accounttype,$currency,$amount,$receiptnumber);
    }
    public function suspenseutilization($data){
    
        //get customer by regnumber
        $customer = $this->customerrepo->getCustomerByRegnumber($data['regnumber']);
        
        if(!$customer){
            return ['status'=>'ERROR','message'=>'Account not found'];
        }
        //get invoice by invoice number
        $invoice = $this->invoicerepo->getInvoiceByInvoiceNumber($data['invoice_number']);
        if(!$invoice){
            return ['status'=>'ERROR','message'=>'Invoice not found'];
        }
        
        // Check invoice status
        if(strtoupper($invoice->status) == 'PAID'){
            return ['status'=>'ERROR','message'=>'Invoice already settled'];
        }
        
        //get wallet balance - extract numeric value from array
        $walletbalanceResult = $this->repository->getwalletbalance($customer->regnumber,$data['accounttype'],$invoice->currency->name);

        // Check if there was an error from getwalletbalance
        if(isset($walletbalanceResult['status']) && $walletbalanceResult['status'] === 'ERROR'){
            return $walletbalanceResult;
        }

        // Extract balance and remove formatting (commas) for numeric comparison
        $walletbalance = (float) str_replace(',', '', $walletbalanceResult['balance']);

        // FIX: Remove commas from invoice amount before comparison
        $invoiceAmount = (float) str_replace(',', '', $invoice->amount);

        if($invoiceAmount > $walletbalance){
            return ['status'=>'ERROR','message'=>"Insufficient funds in wallet of type ".$data['accounttype']." using currency ".$invoice->currency->name."invoice amount: ".$invoiceAmount." wallet balance: ".$walletbalance."account type: ".$data['accounttype']." currency: ".$invoice->currency->name];
        }
        
        //get pendingsuspense - we NEED these records to exist!
        $pendingsuspense = $this->repository->getpendingsuspense($customer->regnumber,$data['accounttype'],$invoice->currency->name);
        if(count($pendingsuspense) == 0){  // FIX: Changed from >0 to ==0
            return ['status'=>'ERROR','message'=>"Insufficient funds in wallet of type ".$data['accounttype']." using currency ".$invoice->currency->name];
        }
        
        /// check invoice balance
        $invoiceAmount = (float) str_replace(',', '', $invoice->amount);
        $invoicebalance = $invoiceAmount - (float)$invoice->receipts->sum('amount');
        if($invoicebalance <= 0){
            $response = $this->invoicerepo->markInvoiceAsPaid($invoice->invoicenumber);
            if($response['status']=='ERROR'){
                return $response;
            }
            return ['status' => 'SUCCESS', 'message' => 'Invoice successfully settled'];
        }
        
        //create suspenseutilization
        $balanceDue = $invoicebalance; // Initialize balanceDue
        foreach($pendingsuspense as $suspense){
            // Break early if balance is fully satisfied
            if($balanceDue <= 0){
                break;
            }
            
            $availableBalance = round(round($suspense->amount,2) - round($suspense->suspenseutilizations->sum('amount'), 2), 2);
            
            if($availableBalance <= 0){
                $suspense->status = "UTILIZED";
                $suspense->save();
                continue; // Skip to next suspense record
            }
            
            // Determine how much to utilize from this suspense
            $amountToUtilize = min($balanceDue, $availableBalance);
            
            // Create suspense utilization
            $response = $this->repository->createSuspenseutilization($suspense->id, $invoice->id, $amountToUtilize, $data['receiptnumber']);
            if($response['status']=='ERROR'){
                return $response;
            }
            
            // Update balance due
            $balanceDue = $balanceDue - $amountToUtilize;
            
            // Update suspense status if fully utilized
            if(round($availableBalance - $amountToUtilize, 2) <= 0){
                $suspense->status = "UTILIZED";
                $suspense->save();
            }
            
            // Check if invoice is fully settled (only check when balance due is satisfied)
            if($balanceDue <= 0){
                // Refresh invoice to get updated receipts
                $invoice = $this->invoicerepo->getInvoiceDetails($invoice->id);
                $invoiceAmount = (float) str_replace(',', '', $invoice->amount);
                $invoicebalance = $invoiceAmount - (float)$invoice->receipts->sum('amount');
                
                if($invoicebalance <= 0){
                    $response = $this->invoicerepo->markInvoiceAsPaid($invoice->invoicenumber);
                    if($response['status']=='SUCCESS'){                    
                        return ['status'=>'SUCCESS','message'=>'Invoice successfully settled'];
                    }
                }
                break; // Exit loop since balance is satisfied
            }
        }

        // Final check if invoice is fully settled after processing all suspense records
        $invoice = $this->invoicerepo->getInvoiceDetails($invoice->id);
        $invoiceAmount = (float) str_replace(',', '', $invoice->amount);
        $finalBalance = $invoiceAmount - (float)$invoice->receipts->sum('amount');

        if($finalBalance <= 0){
            $response = $this->invoicerepo->markInvoiceAsPaid($invoice->invoicenumber);
            if($response['status']=='SUCCESS'){
                return ['status'=>'SUCCESS','message'=>'Invoice successfully settled'];
            }
        }

        // Check if there's still balance due after using all available suspense
        if($balanceDue > 0){
            return ['status'=>'ERROR','message'=>"Insufficient funds in wallet of type ".$data['accounttype']." using currency ".$invoice->currency->name." to fully settle invoice. Remaining balance: ".$balanceDue];
        }

        return ['status'=>'SUCCESS','message'=>'Wallet utilization processed'];
    }
}
