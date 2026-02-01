<?php

namespace App\implementation\repositories;

use App\Interfaces\repositories\ibankaccountInterface;
use App\Interfaces\repositories\ibankInterface;
use App\Interfaces\repositories\ibanktransactionInterface;
use App\Interfaces\repositories\icustomerInterface;
use App\Interfaces\repositories\isuspenseInterface;
use App\Interfaces\repositories\iwallettopupInterface;
use App\Models\Bankreconciliation;
use App\Models\Bankreconciliationdata;
use App\Models\Banktransaction;
use Illuminate\Support\Facades\Log;

class _banktransactionRepository implements ibanktransactionInterface
{
    /**
     * Create a new class instance.
     */
    protected $model;

    protected $bankrepo;

    protected $customerrepo;

    protected $suspenserepo;

    protected $bankaccountrepo;

    protected $wallettopuprepo;

    protected $bankreconciliationmodel;

    protected $bankreconciliationdatamodel;

    public function __construct(Banktransaction $model, ibankInterface $bank, icustomerInterface $customer, isuspenseInterface $suspense, ibankaccountInterface $bankaccount, iwallettopupInterface $wallettopuprepo, Bankreconciliation $bankreconciliationmodel, Bankreconciliationdata $bankreconciliationdatamodel)
    {
        $this->model = $model;
        $this->bankrepo = $bank;
        $this->customerrepo = $customer;
        $this->suspenserepo = $suspense;
        $this->bankaccountrepo = $bankaccount;
        $this->wallettopuprepo = $wallettopuprepo;
        $this->bankreconciliationmodel = $bankreconciliationmodel;
        $this->bankreconciliationdatamodel = $bankreconciliationdatamodel;
    }

    public function createtransaction(array $data)
    {
        Log::channel('banktransactionlog')->info('Creating bank transaction', [
            'action' => 'create_transaction',
            'source_reference' => $data['source_reference'] ?? null,
            'referencenumber' => $data['referencenumber'] ?? null,
            'accountnumber' => $data['accountnumber'] ?? null,
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? null,
        ]);
        
        $bank = $this->bankrepo->getBankBySalt($data['authcode']);
        if ($bank == null) {
            Log::channel('banktransactionlog')->warning('Unauthorized transaction attempt', [
                'action' => 'create_transaction',
                'authcode' => $data['authcode'] ?? null,
                'source_reference' => $data['source_reference'] ?? null,
                'referencenumber' => $data['referencenumber'] ?? null,
            ]);
            return ['message' => 'Unauthorized to post transaction', 'status' => 401];
        }
        if($data['source_reference'] == null){
            Log::channel('banktransactionlog')->warning('Source reference is null, using statement reference', [
                'action' => 'create_transaction',
                'referencenumber' => $data['referencenumber'] ?? null,
                'statement_reference' => $data['statement_reference'] ?? null,
                'source_reference' => $data['source_reference'] ?? null,
            ]);
            $data['source_reference'] = $data['statement_reference'];
            return ['message' => 'Source reference is required', 'status' => 401];
        }
    
        $checktranscation = $this->model->where('sourcereference', '=', $data['source_reference'])->orWhere('statementreference', '=', $data['statement_reference'])->first();
        if ($checktranscation != null) {
            Log::channel('banktransactionlog')->info('Duplicate transaction reference', [
                'action' => 'create_transaction',
                'source_reference' => $data['source_reference'],
                'statement_reference' => $data['statement_reference'],
                'existing_transaction_id' => $checktranscation->id,
            ]);
            return ['message' => 'Reference already exists', 'status' => 200];
        }
        
        // Convert ZWG to ZiG if currency is ZWG
        if (isset($data['currency']) && strtoupper($data['currency']) === 'ZWG') {
            $data['currency'] = 'ZiG';
            Log::channel('banktransactionlog')->info('Currency converted from ZWG to ZiG', [
                'action' => 'create_transaction',
                'source_reference' => $data['source_reference'],
            ]);
        }
        
        $transaction = $this->model->create([
            'bank_id' => $bank->id,
            'referencenumber' => $data['referencenumber'],
            'sourcereference' => $data['source_reference'],
            'statementreference' => $data['statement_reference'],
            'description' => $data['description'],
            'accountnumber' => $data['accountnumber'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'regnumber' =>null,
            'transactiondate' => $data['trans_date'],
            'customer_id' => null,
            'status' => 'PENDING',
            'copied' => 0,
        ]);
       /* if ($customer != null) {
            $bankaccount = $this->bankaccountrepo->getBankAccountByBankIdAndAccountNumber($bank->id, $data['accountnumber']);
            if ($bankaccount != null) {
                $this->suspenserepo->create([
                    'customer_id' => $customer_id,
                    'sourcetype' => 'banktransaction',
                    'source_id' => $transaction->id,
                    'amount' => $data['amount'],
                    'currency' => $data['currency'],
                    'status' => 'PENDING',
                    'accountnumber' => $data['accountnumber'],
                    'type' => $bankaccount->account_type,
                    'posted' => 0,
                ]);
            }
        }*/

        Log::channel('banktransactionlog')->info('Bank transaction created successfully', [
            'action' => 'create_transaction',
            'transaction_id' => $transaction->id,
            'source_reference' => $transaction->sourcereference,
            'referencenumber' => $transaction->referencenumber,
            'status' => $transaction->status,
            'customer_id' => $transaction->customer_id,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
        ]);

        return ['message' => 'Reference number saved', 'status' => 200];
    }

    public function recallpayment($refencenumber)
    {
        Log::channel('banktransactionlog')->info('Recalling payment', [
            'action' => 'recall_payment',
            'referencenumber' => $refencenumber,
        ]);

        $transaction = $this->model->where('referencenumber', '=', $refencenumber)->first();
        if ($transaction == null) {
            Log::channel('banktransactionlog')->warning('Transaction not found for recall', [
                'action' => 'recall_payment',
                'referencenumber' => $refencenumber,
            ]);
            return ['message' => 'Transaction not found', 'status' => 'ERROR'];
        }
        if ($transaction->status == 'PENDING') {
            $transaction->status = 'RECALLED';
            $transaction->save();

            Log::channel('banktransactionlog')->info('Payment recalled successfully', [
                'action' => 'recall_payment',
                'transaction_id' => $transaction->id,
                'referencenumber' => $refencenumber,
                'previous_status' => 'PENDING',
                'new_status' => 'RECALLED',
            ]);

            return ['message' => 'Transaction recalled', 'status' => 'SUCCESS'];
        } else {
            Log::channel('banktransactionlog')->warning('Cannot recall transaction - already claimed', [
                'action' => 'recall_payment',
                'transaction_id' => $transaction->id,
                'referencenumber' => $refencenumber,
                'current_status' => $transaction->status,
            ]);
            return ['message' => 'Transaction already claimed cannot be reversed', 'status' => 'ERROR'];
        }
    }

    public function internalsearch($needle)
    {
        $transactions = $this->model->with('customer', 'bank', 'bankaccount')->where('statementreference', 'like', '%'.$needle.'%')
            ->orWhere('sourcereference', 'like', '%'.$needle.'%')
            ->orWhere('description', 'like', '%'.$needle.'%')
            ->get();

        return $transactions;
    }

    public function search($needle)
    {
        $transactions = $this->model->where('statementreference', 'like', '%'.$needle.'%')
            ->orWhere('sourcereference', 'like', '%'.$needle.'%')
            ->orWhere('description', 'like', '%'.$needle.'%')
            ->get();
        $array = [];
        if ($transactions->count() > 0) {
            foreach ($transactions as $transaction) {
                $date = str_replace('/', '-', $transaction->transactiondate);
                $newDate = date('Y-m-d', strtotime($date));
                $array[] = Collect([
                    'id' => $transaction->id,
                    'referencenumber' => $transaction->referencenumber,
                    'accountnumber' => $transaction->accountnumber,
                    'regnumber' => $transaction->regnumber,
                    'invoicenumber' => null,
                    'invoiceId' => null,
                    'bankId' => $transaction->bank_id,
                    'clientId' => null,
                    'accountId' => $transaction->customer_id,
                    'service' => null,
                    'description' => $transaction->description,
                    'transactionDate' => $newDate,
                    'statementReference' => $transaction->statementreference,
                    'sourceReference' => $transaction->sourcereference,
                    'currency' => $transaction->currency,
                    'amount' => $transaction->amount,
                    'status' => $transaction->status,
                    'account' => null,
                    'client' => null,
                    'bankaccount' => null,
                    'invoice' => null,
                    'banktransactionconv' => null,
                    'dateCreated' => $transaction->created_at,
                    'dateUpdated' => $transaction->updated_at,
                    'dateDeleted' => null,
                ]);
            }
        }

        return $array;
    }

    public function claim(array $data)
    {
        Log::channel('banktransactionlog')->info('Claiming bank transaction', [
            'action' => 'claim_transaction',
            'source_reference' => $data['SourceReference'] ?? null,
            'regnumber' => $data['regnumber'] ?? null,
        ]);

        $transaction = $this->model->where('SourceReference', '=', $data['SourceReference'])->first();
        if ($transaction == null) {
            Log::channel('banktransactionlog')->warning('Transaction not found for claim', [
                'action' => 'claim_transaction',
                'source_reference' => $data['SourceReference'] ?? null,
            ]);
            return ['message' => 'Bank transaction not found', 'status' => 'ERROR'];
        }
        if ($transaction->status == 'CLAIMED') {
            Log::channel('banktransactionlog')->warning('Transaction already claimed', [
                'action' => 'claim_transaction',
                'transaction_id' => $transaction->id,
                'source_reference' => $data['SourceReference'],
                'current_status' => $transaction->status,
            ]);
            return ['message' => 'Bank transaction already claimed', 'status' => 'ERROR'];
        }

        $bankaccount = $this->bankaccountrepo->getBankAccountByBankIdAndAccountNumber($transaction->bank_id, $transaction->accountnumber);
        if ($bankaccount == null) {
            Log::channel('banktransactionlog')->error('Bank account not found for claim', [
                'action' => 'claim_transaction',
                'transaction_id' => $transaction->id,
                'bank_id' => $transaction->bank_id,
                'accountnumber' => $transaction->accountnumber,
            ]);
            return ['message' => 'Bank account not found', 'status' => 'ERROR'];
        }
        $customer = $this->customerrepo->getCustomerByRegnumber($data['regnumber']);
        if ($customer == null) {
            Log::channel('banktransactionlog')->warning('Customer not found for claim', [
                'action' => 'claim_transaction',
                'transaction_id' => $transaction->id,
                'regnumber' => $data['regnumber'],
            ]);
            return ['message' => 'Regnumber not found', 'status' => 'ERROR'];
        }
        $suspenresponse = $this->suspenserepo->create([
            'customer_id' => $customer->id,
            'sourcetype' => 'banktransaction',
            'source_id' => $transaction->id,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'status' => 'PENDING',
            'accountnumber' => $transaction->accountnumber,
            'type' => $bankaccount->account_type,
            'posted' => 0,
        ]);
        $transaction->customer_id = $customer->id;
        $transaction->status = 'CLAIMED';
        $transaction->save();

        Log::channel('banktransactionlog')->info('Transaction claimed successfully', [
            'action' => 'claim_transaction',
            'transaction_id' => $transaction->id,
            'source_reference' => $transaction->sourcereference,
            'customer_id' => $customer->id,
            'regnumber' => $data['regnumber'],
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'suspense_id' => $suspenresponse->id ?? null,
        ]);

        return ['message' => 'Transaction successfully claimed and wallet successfully topped up', 'status' => 'SUCCESS'];
    }

    public function link(array $data)
    {
        Log::channel('banktransactionlog')->info('Linking bank transaction', [
            'action' => 'link_transaction',
            'source_reference' => $data['sourcereference'] ?? null,
            'regnumber' => $data['regnumber'] ?? null,
            'wallettopup_id' => $data['wallettopup_id'] ?? null,
        ]);

        $transaction = $this->model->where('sourcereference', '=', $data['sourcereference'])->first();
        if ($transaction == null) {
            Log::channel('banktransactionlog')->warning('Transaction not found for link', [
                'action' => 'link_transaction',
                'source_reference' => $data['sourcereference'] ?? null,
            ]);
            return ['message' => 'Bank transaction not found', 'status' => 'ERROR'];
        }
        if ($transaction->status == 'CLAIMED') {
            Log::channel('banktransactionlog')->warning('Transaction already claimed - cannot link', [
                'action' => 'link_transaction',
                'transaction_id' => $transaction->id,
                'source_reference' => $data['sourcereference'],
                'current_status' => $transaction->status,
            ]);
            return ['message' => 'Bank transaction already claimed', 'status' => 'ERROR'];
        }

        $bankaccount = $this->bankaccountrepo->getBankAccountByBankIdAndAccountNumber($transaction->bank_id, $transaction->accountnumber);
        if ($bankaccount == null) {
            Log::channel('banktransactionlog')->error('Bank account not found for link', [
                'action' => 'link_transaction',
                'transaction_id' => $transaction->id,
                'bank_id' => $transaction->bank_id,
                'accountnumber' => $transaction->accountnumber,
            ]);
            return ['message' => 'Bank account not found', 'status' => 'ERROR'];
        }
        $customer = $this->customerrepo->getCustomerByRegnumber($data['regnumber']);
        if ($customer == null) {
            Log::channel('banktransactionlog')->warning('Customer not found for link', [
                'action' => 'link_transaction',
                'transaction_id' => $transaction->id,
                'regnumber' => $data['regnumber'],
            ]);
            return ['message' => 'Regnumber not found', 'status' => 'ERROR'];
        }
        $transaction->customer_id = $customer->id;
        $transaction->status = 'CLAIMED';
        $transaction->save();
        $response = $this->wallettopuprepo->linkwallet(['id' => $data['wallettopup_id'], 'banktransaction_id' => $transaction->id]);
        if ($response['status'] == 'ERROR') {
            Log::channel('banktransactionlog')->error('Failed to link wallet', [
                'action' => 'link_transaction',
                'transaction_id' => $transaction->id,
                'wallettopup_id' => $data['wallettopup_id'],
                'error_message' => $response['message'] ?? null,
            ]);
            return ['message' => $response['message'], 'status' => 'ERROR'];
        } else {
            Log::channel('banktransactionlog')->info('Transaction linked successfully', [
                'action' => 'link_transaction',
                'transaction_id' => $transaction->id,
                'source_reference' => $transaction->sourcereference,
                'customer_id' => $customer->id,
                'regnumber' => $data['regnumber'],
                'wallettopup_id' => $data['wallettopup_id'],
            ]);
            return ['message' => $response['message'], 'status' => 'SUCCESS'];
        }
    }

    public function block($id, $status)
    {
        Log::channel('banktransactionlog')->info('Blocking/updating transaction status', [
            'action' => 'block_transaction',
            'transaction_id' => $id,
            'new_status' => $status,
        ]);

        $transaction = $this->model->find($id);
        if ($transaction == null) {
            Log::channel('banktransactionlog')->warning('Transaction not found for block', [
                'action' => 'block_transaction',
                'transaction_id' => $id,
            ]);
            return ['message' => 'Bank transaction not found', 'status' => 'ERROR'];
        }
        $previous_status = $transaction->status;
        $transaction->status = $status;
        $transaction->save();

        Log::channel('banktransactionlog')->info('Transaction status updated', [
            'action' => 'block_transaction',
            'transaction_id' => $transaction->id,
            'previous_status' => $previous_status,
            'new_status' => $status,
            'source_reference' => $transaction->sourcereference,
        ]);

        return ['message' => 'Transaction '.$status, 'status' => 'SUCCESS'];
    }

    public function getlatesttransactions()
    {
        return $this->model->whereDate('created_at', '>=', now())->orderBy('created_at', 'desc')->get();
    }

    public function gettransaction($id)
    {
        return $this->model->with('customer', 'bank', 'bankaccount', 'suspense.suspenseutilizations.invoice.inventoryitem')->find($id);
    }

    public function gettransactionbydaterange($startdate, $enddate, $bankaccount = null)
    {
        return $this->model->whereBetween('transactiondate', [$startdate, $enddate])->when($bankaccount != null, function ($query) use ($bankaccount) {
            return $query->where('accountnumber', $bankaccount);
        })->orderBy('created_at', 'desc')->get();
    }

    public function getbankreconciliations($year)
    {
        return $this->bankreconciliationmodel->with('currency', 'bankaccount', 'user')->where('year', '=', $year)->get();
    }

    public function getbankreconciliation($id)
    {
        return $this->bankreconciliationmodel->find($id);
    }

    public function extractdata($id)
    {
        Log::channel('banktransactionlog')->info('Extracting bank reconciliation data', [
            'action' => 'extract_data',
            'bankreconciliation_id' => $id,
        ]);

        $bankreconciliation = $this->bankreconciliationmodel->find($id);
        if ($bankreconciliation == null) {
            Log::channel('banktransactionlog')->warning('Bank reconciliation not found for extraction', [
                'action' => 'extract_data',
                'bankreconciliation_id' => $id,
            ]);
            return ['message' => 'Bank reconciliation not found', 'status' => 'ERROR'];
        }
        try {
            $path = storage_path('app/private/'.$bankreconciliation->filename);

            if (! file_exists($path)) {
                Log::channel('banktransactionlog')->error('File not found for extraction', [
                    'action' => 'extract_data',
                    'bankreconciliation_id' => $id,
                    'filename' => $bankreconciliation->filename,
                    'path' => $path,
                ]);
                return ['message' => 'File not found', 'status' => 'ERROR'];
            }
            $file = fopen($path, 'r');
            $i = 0;
            $records_processed = 0;
            while (($row = fgetcsv($file, null, ',')) != false) {
                if ($i > 0) {
                    $date = $row[0];
                    $description = $row[1];
                    $refencenumber = $row[2];
                    $currency = $row[3];
                    $amount = $row[4];
                    $type = $row[5];
                    $balance = $row[7];
                    $this->bankreconciliationdatamodel->create([
                        'bankreconciliation_id' => $bankreconciliation->id,
                        'tnxdate' => $date,
                        'tnxdescription' => $description,
                        'tnxreference' => $refencenumber,
                        'tnxamount' => $amount,
                        'tnxtype' => $type,
                        'balance' => $balance,
                    ]);
                    $records_processed++;
                }
                $i++;
            }
            fclose($file);
            $bankreconciliation->status = 'EXTRACTED';
            $bankreconciliation->save();

            Log::channel('banktransactionlog')->info('Data extracted successfully', [
                'action' => 'extract_data',
                'bankreconciliation_id' => $id,
                'filename' => $bankreconciliation->filename,
                'records_processed' => $records_processed,
            ]);

            return ['message' => 'Data extracted', 'status' => 'SUCCESS'];
        } catch (\Exception $e) {
            Log::channel('banktransactionlog')->error('Error extracting data', [
                'action' => 'extract_data',
                'bankreconciliation_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['message' => $e->getMessage(), 'status' => 'ERROR'];
        }
    }

    public function createbankreconciliation(array $data)
    {
        try {
            $this->bankreconciliationmodel->create($data);

            return ['message' => 'Bank reconciliation created', 'status' => 'SUCCESS'];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'status' => 'ERROR'];
        }
    }

    public function updatebankreconciliation($id, array $data)
    {
        try {
            $bankreconciliation = $this->bankreconciliationmodel->find($id);
            if ($bankreconciliation == null) {
                return ['message' => 'Bank reconciliation not found', 'status' => 'ERROR'];
            }
            if ($data['filename'] == null) {
                unset($data['filename']);
            }
            $bankreconciliation->update($data);

            return ['message' => 'Bank reconciliation updated', 'status' => 'SUCCESS'];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'status' => 'ERROR'];
        }
    }

    public function deletebankreconciliation($id)
    {
        try {
            $bankreconciliation = $this->bankreconciliationmodel->find($id);
            if ($bankreconciliation == null) {
                return ['message' => 'Bank reconciliation not found', 'status' => 'ERROR'];
            }
            $bankreconciliation->delete();

            return ['message' => 'Bank reconciliation deleted', 'status' => 'SUCCESS'];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'status' => 'ERROR'];
        }
    }

    public function syncdata($id)
    {
        Log::channel('banktransactionlog')->info('Syncing bank reconciliation data', [
            'action' => 'sync_data',
            'bankreconciliation_id' => $id,
        ]);

        $data = $this->bankreconciliationmodel->with('bankreconciliationdata')->find($id);
        $synced_count = 0;
        $not_found_count = 0;
        foreach ($data->bankreconciliationdata as $d) {
            $banktransaction = $this->model->where('sourcereference', '=', $d->tnxreference)->first();
            if ($banktransaction == null) {
                $d->status = 'NOT FOUND';
                $d->save();
                $not_found_count++;

                Log::channel('banktransactionlog')->warning('Transaction not found during sync', [
                    'action' => 'sync_data',
                    'bankreconciliation_id' => $id,
                    'reconciliation_data_id' => $d->id,
                    'tnxreference' => $d->tnxreference,
                ]);

                continue;
            }
            $d->banktransaction_id = $banktransaction->id;
            $d->status = 'SYNCED';
            $d->save();
            $synced_count++;
        }
        $data->status = 'SYNCED';
        $data->save();

        Log::channel('banktransactionlog')->info('Data synced successfully', [
            'action' => 'sync_data',
            'bankreconciliation_id' => $id,
            'total_records' => $data->bankreconciliationdata->count(),
            'synced_count' => $synced_count,
            'not_found_count' => $not_found_count,
        ]);

        return ['message' => 'Data synced', 'status' => 'SUCCESS'];
    }

    public function viewreport($id, $filterbystatus, $showdebit)
    {

        $data = $this->bankreconciliationmodel->with('bankreconciliationdata.banktransaction.customer', 'bankreconciliationdata.banktransaction.suspense.suspenseutilizations.invoice.inventoryitem')->find($id);
        if ($filterbystatus != 'ALL') {
            //  $data->bankreconciliationdata = $data->bankreconciliationdata->where("status","=",$filterbystatus);
        }
        if ($showdebit) {
            //  $data->bankreconciliationdata = $data->bankreconciliationdata->whereIn("tnxtype",["Dr","Cr"]);
        } else {
            //  $data->bankreconciliationdata = $data->bankreconciliationdata->where("tnxtype","=","Cr");
        }

        return $data;
    }

    public function gettransactions($customer_id)
    {
        return $this->model->where('customer_id', '=', "$customer_id")->get();
    }
}
