<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CheckCostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'collage_trip_id' => ['required', 'exists:collage_trips,id'],
            'points' => ['required', 'numeric'],
            'trip_type' => [
                'required',
                'in:Go,Back,Round Trip',
                function ($attribute, $value, $fail) {
                    $reservationType = request('reservation_type');
                    if ($reservationType === 'monthly' && $value !== 'Round Trip') {
                        $fail('When reservation type is monthly, trip type must be Round Trip.');
                    }
                },
            ],
            'reservation_type' => ['required', 'in:monthly,daily'],
        ];
    }


    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $transformedErrors = [];
        foreach ($errors->all() as $errorMessage) {
            $transformedErrors[] = $errorMessage;
        }
        throw new HttpResponseException(response()->json([
            'message' => 'Validation Error',
            'errors' => $transformedErrors,
        ], 422));
    }
}
