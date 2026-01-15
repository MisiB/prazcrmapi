<?php

namespace App\implementation\services;

use App\Interfaces\repositories\isuspenseInterface;
use App\Interfaces\services\isuspenseService;
use App\Interfaces\repositories\icustomerInterface;
use App\Interfaces\repositories\invoiceInterface;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
    // public function suspenseutilization($data){
    
    //     //get customer by regnumber
    //     $customer = $this->customerrepo->getCustomerByRegnumber($data['regnumber']);
        
    //     if(!$customer){
    //         return ['status'=>'ERROR','message'=>'Account not found'];
    //     }
    //     //get invoice by invoice number
    //     $invoice = $this->invoicerepo->getInvoiceByInvoiceNumber($data['invoice_number']);
    //     if(!$invoice){
    //         return ['status'=>'ERROR','message'=>'Invoice not found'];
    //     }
        
    //     // Check invoice status
    //     if(strtoupper($invoice->status) == 'PAID'){
    //         return ['status'=>'ERROR','message'=>'Invoice already settled'];
    //     }
        
    //     //get wallet balance - extract numeric value from array
    //     $walletbalanceResult = $this->repository->getwalletbalance($customer->regnumber,$data['accounttype'],$invoice->currency->name);

    //     // Check if there was an error from getwalletbalance
    //     if(isset($walletbalanceResult['status']) && $walletbalanceResult['status'] === 'ERROR'){
    //         return $walletbalanceResult;
    //     }

    //     // Extract balance and remove formatting (commas) for numeric comparison
    //     $walletbalance = (float) str_replace(',', '', $walletbalanceResult['balance']);

    //     // FIX: Remove commas from invoice amount before comparison
    //     $invoiceAmount = (float) str_replace(',', '', $invoice->amount);

    //     if($invoiceAmount > $walletbalance){
    //         return ['status'=>'ERROR','message'=>"Insufficient funds in wallet of type ".$data['accounttype']." using currency ".$invoice->currency->name];
    //     }
        
    //     //get pendingsuspense - we NEED these records to exist!
    //     $pendingsuspense = $this->repository->getpendingsuspense($customer->regnumber,$data['accounttype'],$invoice->currency->name);
    //     if(count($pendingsuspense) == 0){  // FIX: Changed from >0 to ==0
    //         return ['status'=>'ERROR','message'=>"Insufficient funds in wallet of type ".$data['accounttype']." using currency ".$invoice->currency->name];
    //     }
        
    //     /// check invoice balance
    //     $invoiceAmount = round((float) str_replace(',', '', $invoice->amount),2);
    //     $invoicebalance = round($invoiceAmount - round($invoice->receipts->sum('amount'),2),2);
    //    /* if($invoicebalance <= 0){
         
    //         if($response['status']=='ERROR'){
    //             return $response;
    //         }
    //        // return ['status' => 'SUCCESS', 'message' => 'Invoice successfully settled'];
    //     }*/
        
    //     //create suspenseutilization
    //     $balanceDue = $invoicebalance; // Initialize balanceDue
     
    //     foreach($pendingsuspense as $suspense){
    //         // Break early if balance is fully satisfied
    //         if($balanceDue <= 0){
    //             break;
    //         }

            
    //         $availableBalance = round(round($suspense->amount,2) - round($suspense->suspenseutilizations->sum('amount'), 2), 2);
            
    //         if($availableBalance <= 0){
    //             $suspense->status = "UTILIZED";
    //             $suspense->save();
    //             continue; // Skip to next suspense record
    //         }
            
    //         // Determine how much to utilize from this suspense
    //         $amountToUtilize = min($balanceDue, $availableBalance);
            
    //         // Create suspense utilization
    //         $response = $this->repository->createSuspenseutilization($suspense->id, $invoice->id, $amountToUtilize, $data['receiptnumber']);
    //         if($response['status']=='ERROR'){
    //             return $response;
    //         }
            
    //         // Update balance due
    //         $balanceDue = $balanceDue - $amountToUtilize;
            
    //         // Update suspense status if fully utilized
    //         if(round($availableBalance - $amountToUtilize, 2) <= 0){
    //             $suspense->status = "UTILIZED";
    //             $suspense->save();
    //         }
            
    //         // Check if invoice is fully settled (only check when balance due is satisfied)
    //         if($balanceDue <= 0){
    //             // Refresh invoice to get updated receipts
    //             $invoice = $this->invoicerepo->getInvoiceDetails($invoice->id);
    //             $invoiceAmount = (float) str_replace(',', '', $invoice->amount);
    //             $invoicebalance = $invoiceAmount - (float)$invoice->receipts->sum('amount');
                
    //             if($invoicebalance <= 0){
    //                 $response = $this->invoicerepo->markInvoiceAsPaid($invoice->invoicenumber);
    //                 if($response['status']=='SUCCESS'){                    
    //                     return ['status'=>'SUCCESS','message'=>'Invoice successfully settled'];
    //                 }
    //             }
    //             break; // Exit loop since balance is satisfied
    //         }
    //     }

    //     // Final check if invoice is fully settled after processing all suspense records
    //     $invoice = $this->invoicerepo->getInvoiceDetails($invoice->id);
    //     $invoiceAmount = (float) str_replace(',', '', $invoice->amount);
    //     $finalBalance = $invoiceAmount - (float)$invoice->receipts->sum('amount');

    //     if($finalBalance <= 0){

    //          // Update settled_at when invoice is successfully settled
    //          $invoice->settled_at = \Carbon\Carbon::now();
    //          $invoice->save();
             
    //         $response = $this->invoicerepo->markInvoiceAsPaid($invoice->invoicenumber);
    //         if($response['status']=='SUCCESS'){
    //             return ['status'=>'SUCCESS','message'=>'Invoice successfully settled'];
    //         }
    //     }

    //     // Check if there's still balance due after using all available suspense
    //     if($balanceDue > 0){
    //         return ['status'=>'ERROR','message'=>"Insufficient funds in wallet of type ".$data['accounttype']." using currency ".$invoice->currency->name." to fully settle invoice. Remaining balance: ".$balanceDue];
    //     }

    //     return ['status'=>'SUCCESS','message'=>'Wallet utilization processed'];
    // }

    
    public function suspenseutilization($data)
    {
        return DB::transaction(function () use ($data) {

            /* ---------------- CUSTOMER ---------------- */
            $customer = $this->customerrepo
                ->getCustomerByRegnumber($data['regnumber']);

            if(!$customer){
                return ['status'=>'ERROR','message'=>'Account not found'];
            }

            /* ---------------- INVOICE ---------------- */
            $invoice = $this->invoicerepo
                ->getInvoiceByInvoiceNumber($data['invoice_number']);

            if(!$invoice){
                return ['status'=>'ERROR','message'=>'Invoice not found'];
            }

            if(strtoupper($invoice->status) === 'PAID'){
                return ['status'=>'ERROR','message'=>'Invoice already settled'];
            }

            /* ---------------- WALLET ---------------- */
            $walletResult = $this->repository->getwalletbalance(
                $customer->regnumber,
                $invoice->inventoryitem->type,
                $invoice->currency->name
            );

            if(isset($walletResult['status']) && $walletResult['status'] === 'ERROR'){
                return $walletResult;
            }

            // KEEP SAME VARIABLES
            $walletBalance = str_replace(',', '', $walletResult['balance']);
            $invoiceAmount = str_replace(',', '', $invoice->amount);
            $paidAmount    = str_replace(',', '', $invoice->receipts->sum('amount'));

            // PRECISION FIX
            $balanceDue = bcsub($invoiceAmount, $paidAmount, 2);

            if(bccomp($balanceDue, '0', 2) <= 0){
                return ['status'=>'ERROR','message'=>'Invoice already settled'];
            }

            /* ---------------- PENDING SUSPENSE ---------------- */
            $pendingsuspense = $this->repository->getpendingsuspense(
                $customer->regnumber,
                $invoice->inventoryitem->type,
                $invoice->currency->name
            );

            if(count($pendingsuspense) == 0){
                return [
                    'status'=>'ERROR',
                    'message'=>"Insufficient funds in wallet of type ".$data['accounttype'].
                            " using currency ".$invoice->currency->name
                ];
            }

            /* ---------------- UTILIZATION LOOP ---------------- */
            foreach($pendingsuspense as $suspense){

                if(bccomp($balanceDue, '0', 2) <= 0){
                    break;
                }

                // ORIGINAL LOGIC – PRECISION SAFE
                $used = str_replace(',', '', $suspense->suspenseutilizations->sum('amount'));
                $availableBalance = bcsub($suspense->amount, $used, 2);

                if(bccomp($availableBalance, '0', 2) <= 0){
                    $suspense->status = 'UTILIZED';
                    $suspense->save();
                    continue;
                }

                $amountToUtilize = bccomp($balanceDue, $availableBalance, 2) <= 0
                    ? $balanceDue
                    : $availableBalance;

                $response = $this->repository->createSuspenseutilization(
                    $suspense->id,
                    $invoice->id,
                    $amountToUtilize,
                    $data['receiptnumber']
                );

                if($response['status'] === 'ERROR'){
                    return $response;
                }

                // PRECISION FIX
                $balanceDue = bcsub($balanceDue, $amountToUtilize, 2);

                if(bccomp(bcsub($availableBalance, $amountToUtilize, 2), '0', 2) <= 0){
                    $suspense->status = 'UTILIZED';
                    $suspense->save();
                }
            }

            /* ---------------- FINAL CHECK ---------------- */
            $invoice = $this->invoicerepo->getInvoiceDetails($invoice->id);

            $finalBalance = bcsub(
                str_replace(',', '', $invoice->amount),
                str_replace(',', '', $invoice->receipts->sum('amount')),
                2
            );

            if(bccomp($finalBalance, '0', 2) <= 0){
                $invoice->settled_at = Carbon::now();
                $invoice->save();

                $response = $this->invoicerepo
                    ->markInvoiceAsPaid($invoice->invoicenumber);

                if($response['status'] === 'SUCCESS'){
                    return ['status'=>'SUCCESS','message'=>'Invoice successfully settled'];
                }
            }

            return [
                'status'=>'ERROR',
                'message'=>"Insufficient funds in wallet of type ".$data['accounttype'].
                            " using currency ".$invoice->currency->name
                // 'message'=>"Insufficient funds in wallet of type ".$data['accounttype'].
                //         " using currency ".$invoice->currency->name.
                //         " to fully settle invoice. Remaining balance: ".$balanceDue
            ];
        }); 
    }




}
