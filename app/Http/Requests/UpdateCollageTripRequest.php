<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCollageTripRequest extends FormRequest
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
            //collage Trip
            'trip_id' => ['required', 'exists:collage_trips,id'],
            'day' => ['string'],
            'departure_time' => ['string'],
            'arrival_time' => ['string'],
            'go_price' => ['numeric'],
            'round_trip_price' => ['numeric'],
            'semester_go_price' => ['numeric'],
            'semester_round_trip_price' => ['numeric'],
            'go_points' => ['numeric'],
            'round_trip_points' => ['numeric'],
            'semester_go_points' => ['numeric'],
            'semester_round_trip_points' => ['numeric'],
            'stations' => ['array'],
            'stations.*name' => ['string'],
            'stations.*in_time' => ['string'],
            'stations.*out_time' => ['string'],
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
