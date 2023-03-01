<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Payment;

use GuzzleHttp;
class ProcessUpdatePaymentAPI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $sdate;
    private $edate;
    private $address;
    private $user_id;

    public function __construct($s, $e, $addr, $u)
    {
        //
        $this->sdate = $s;
        $this->edate = $e;
        $this->address = $addr;
        $this->user_id = $u;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        $url ='https://www.heliumtracker.io/api/hotspots/' . $this->address . '/rewards_total';

        $client = new GuzzleHttp\Client();

        $response = $client->request('GET', $url, [
            'headers' => [
                // 'User-Agent' => $_SERVER['HTTP_USER_AGENT'],
                "Api-Key" => "taFGg81X8z2LSUY8T41u2g"
            ],
            'form_params' => [
                'start_date' => $this->sdate->format('Y-m-dTH:i:s'),
                'end_date'=> $this->edate->format('Y-m-dTH:i:s')
            ]
        ]);

        $hotspot_status = json_decode($response->getBody()->getContents());
        $key = $this->edate->format('Y-m-01');

        $payment = Payment::where('user_id', '=', $this->user_id)->where('during', '=', $key)->first();
        
        if($payment) {
            $payment->amount += $hotspot_status->total->total;
            $payment->save();
        }
        else {
            $pay = new Payment();
            $pay->user_id = $this->user_id;
            $pay->during = $key;
            $pay->amount = $hotspot_status->total->total;
            $pay->status_id = 1;
            $pay->random = $this->generateRandomString(6);

            $pay->save();
        }
    }

    private function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
