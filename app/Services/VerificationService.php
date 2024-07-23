<?php

namespace App\Services;

class VerificationService
{

    public function sendVerificationMessage($reciverNumber, $code)
    {
        $mobile_number = '+963' . $reciverNumber;
        $params = array(
            'token' => '7pl8qqcx0ugr0lrp',
            'to' => $mobile_number,
            'body' => ' إن رمز تفعيل حسابك هو : ' . $code . ' الرجاء عدم ارساله لأحد '
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

        if ($err) {
            return $err; //error
        } else {
            return 'success';
        }
    }
}
