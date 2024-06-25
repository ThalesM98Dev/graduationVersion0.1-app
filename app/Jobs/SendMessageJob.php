<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $receiverNumber;
    protected $verificationCode;
    public function __construct($receiverNumber, $verificationCode)
    {
        $this->receiverNumber = $receiverNumber;
        $this->verificationCode = $verificationCode;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mobile_number = '+963' . $this->receiverNumber;
        $params = array(
            'token' => '7pl8qqcx0ugr0lrp',
            'to' => $mobile_number,
            'body' => ' إن رمز تفعيل حسابك هو : ' . $this->verificationCode . ' الرجاء عدم ارساله لأحد '
        );
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.ultramsg.com/instance88627/messages/chat",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        // if ($err) {
        //     return $err; //error
        // } else {
        //     return 'success';
        // }
    }
}
