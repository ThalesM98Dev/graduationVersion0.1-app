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
            'day' => ['required', 'string'],
            'departure_time' => ['required', 'string'],
            'arrival_time' => ['required', 'string'],
            'go_price' => ['required'],
            'round_trip_price' => ['required', 'numeric'],
            'semester_go_price' => ['required', 'numeric'],
            'semester_round_trip_price' => ['required', 'numeric'],
            'go_points' => ['required', 'numeric'],
            'round_trip_points' => ['required', 'numeric'],
            'semester_go_points' => ['required', 'numeric'],
            'semester_round_trip_points' => ['required', 'numeric'],
            'stations' => ['required', 'array'],
            'stations.*name' => ['required', 'string'],
            'stations.*in_time' => ['required', 'string'],
            'stations.*out_time' => ['required', 'string'],
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
