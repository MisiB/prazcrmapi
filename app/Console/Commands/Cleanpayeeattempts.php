<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payeeattempt;
use App\Interfaces\repositories\ipayeeInterface;
class Cleanpayeeattempts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanpayeeattempts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(ipayeeInterface $payeeInterface)
    {
        $payeeattempts = Payeeattempt::with('onlinepayment')->where('status', 'APPROVED')->get();
        if($payeeattempts->count() > 0){
            $this->info('Found ' . $payeeattempts->count() . ' approved payee attempts');
            foreach($payeeattempts as $payeeattempt){
                if($payeeattempt->onlinepayment != null){                   
                
                if($payeeattempt->onlinepayment->status == 'PENDING'){

                   $response = $payeeInterface->update(['status' => 'APPROVED'], $payeeattempt->uuid);
                   if($response['status'] == 'success'){
                    $this->info('Payment approved successfully for attempt: ' . $payeeattempt->uuid);
                   }else{
                    $this->error('Payment approval failed for attempt: ' . $payeeattempt->uuid);
                   }
                }
            }
           
        }
    }
}
}
