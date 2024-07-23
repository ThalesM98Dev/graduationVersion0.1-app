<?php

namespace App\Helpers;

class ResponseHelper
{
    public static function success($data = [], $message = 'success', $status = 200)
    {
        $response = array(
            'success' => true,
            'message' => $message,
            'data' => $data,
        );
        return response()->json($response, $status);
    }

    public static function error($data = [], $message = 'error', $status = 400)
    {
        $response = [
            'success' => false,
            'message' => $message,
            'data' => $data,
            'status' => $status
        ];
        return response()->json($response, $status);
    }
}
