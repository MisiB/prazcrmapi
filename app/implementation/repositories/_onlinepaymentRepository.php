<?php

namespace App\implementation\repositories;

use App\Interfaces\repositories\invoiceInterface;
use App\Interfaces\repositories\ionlinepaymentInterface;
use App\Interfaces\repositories\isuspenseInterface;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Onlinepayment;

class _onlinepaymentRepository implements ionlinepaymentInterface
{
    /**
     * Create a new class instance.
     */
    protected $onlinepayment;

    protected $invoice;

    protected $currency;

    protected $customer;

    protected $invoicerepo;

    protected $suspenserepo;

    public function __construct(Onlinepayment $onlinepayment, Invoice $invoice, Currency $currency, Customer $customer, invoiceInterface $invoicerepo, isuspenseInterface $suspenserepo)
    {
        $this->onlinepayment = $onlinepayment;
        $this->invoice = $invoice;
        $this->currency = $currency;
        $this->customer = $customer;
        $this->invoicerepo = $invoicerepo;
        $this->suspenserepo = $suspenserepo;
    }

    public function getpayments($customer_id)
    {
        return $this->onlinepayment->with('currency')->where('customer_id', $customer_id)->paginate(10);
    }

    public function getpayment($id)
    {
        return $this->onlinepayment->with('currency', 'invoice', 'invoice.customer', 'invoice.inventoryitem')->where('id', $id)->first();
    }

    public function getpaymentbyuuid($uuid)
    {
        return $this->onlinepayment->with('currency', 'invoice.currency', 'invoice.customer', 'invoice.inventoryitem')->where('uuid', $uuid)->first();
    }

    public function initiatepayment($data)
    {
        try {
            // Check if UUID already exists
            $check = $this->onlinepayment->where('uuid', $data['uuid'])->first();
            if ($check) {
                return ['status' => 'ERROR', 'message' => 'Transaction unique ID already utilized'];
            }

             // Validate currency exists
             $currency = $this->currency->where('name', $data['currency'])->first();
             if (!$currency) {
                 return ['status' => 'ERROR', 'message' => 'Currency not found'];
             }

            // Get invoice
            $invoice = $this->invoicerepo->getInvoiceByInvoiceNumber($data['invoicenumber']);
            if ($invoice == null) {
                return ['status' => 'ERROR', 'message' => 'Invoice not found'];
            }
            if (strtoupper($invoice->status) == 'PAID') {
                return ['status' => 'ERROR', 'message' => 'Invoice already settled'];
            }
            if ($invoice->customer == null) {
                return ['status' => 'ERROR', 'message' => 'Customer account not found'];
            }
            if (strtoupper($invoice->customer->regnumber) != $data['regnumber']) {
                return ['status' => 'ERROR', 'message' => 'Customer account not found'];
            }
            // Check if invoice has currency
            if ($invoice->currency == null) {
                return ['status' => 'ERROR', 'message' => 'Currency not found'];
            }
            // Check if invoice currency matches request currency
            if (strtoupper($invoice->currency->name) != strtoupper($data['currency'])) {
                return ['status' => 'ERROR', 'message' => 'Currency Invoice different from selected currency'];
            }
            // Calculate total due 
            $totaldue = $invoice->amount - $invoice->receipts->sum('amount');
            if ($totaldue <= 0) {
                $invoice->status = 'PAID';
                $invoice->save();

                return ['status' => 'SUCCESS', 'message' => 'Invoice settled successfully'];
            }
            // Check wallet balance
            $walletbalance = $this->suspenserepo->getwalletbalance($invoice->customer->regnumber, $invoice->inventoryitem->type, $invoice->currency->name);
         
            if ($totaldue <= $walletbalance['balance']) {
                return ['status' => 'ERROR', 'message' => 'User has sufficient balance in wallet to settle invoice', 'data' => null];
            }
            // Calculate amount due after wallet balance
            $amountdue = round($totaldue - $walletbalance['balance'], 2);
            $paymentlink = config('paynowconfig.paymenturl').'/'.$data['uuid'];
            // Create online payment record
            $this->onlinepayment->create([
                'customer_id' => $invoice->customer->id,
                'uuid' => $data['uuid'],
                'currency_id' => $invoice->currency->id,
                'amount' => $amountdue,
                'email' => $data['email'],
                'invoicenumber' => $data['invoicenumber'],
                'poll_url' => '',
                'return_url' => $data['returnurl'],
                'status' => 'PENDING',
            ]);

            return ['status' => 'SUCCESS', 'message' => 'success', 'data' => ['link' => $paymentlink]];
        } catch (\Exception $e) {
            return ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    public function checkpaymentstatus($uuid)
    {
        // Load payment with currency relationship
        $payment = $this->onlinepayment->where('uuid', $uuid)->first();
        if ($payment == null) {
            return ['status' => 'ERROR', 'message' => 'Transaction not found'];
        }
        // Check if already verified
        // if (strtoupper($payment->status) == 'PAID') {
        //     return ['status' => 'ERROR', 'message' => 'Transaction already verified'];
        // }

        $status = strtoupper($payment->status);
        // Check if payment is completed (PAID or CREATED)
        if ($status == 'PAID') {
            return [
                'status' => 'SUCCESS',
                'message' => 'Payment successfully completed',
                'data' => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency->name,
                    'status' => 'PAID',
                ],
            ];
        }
 // Payment is still pending or failed
        return ['status' => 'ERROR', 'message' => 'Payment failed or pending with status: '.ucfirst($status), 'data' => [
            'id' => $payment->id,
            'amount' => $payment->amount,
            'currency' => $payment->currency->name,
            'status' => $status,
        ]];
    }

    public function update(array $data)
    {
        try {
            $this->onlinepayment->where('uuid', $data['uuid'])->update([
                'status' => $data['status'],
                'poll_url' => $data['poll_url'],
                'method' => $data['method'],

            ]);

            return ['status' => 'SUCCESS', 'message' => 'Payment updated successfully'];
        } catch (\Exception $e) {
            return ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    public function delete($id)
    {
        try {
            $this->onlinepayment->where('id', $id)->delete();

            return ['status' => 'SUCCESS', 'message' => 'Payment deleted successfully'];
        } catch (\Exception $e) {
            return ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }
}
