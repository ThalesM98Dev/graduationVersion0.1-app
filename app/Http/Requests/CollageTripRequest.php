<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CollageTripRequest extends FormRequest
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
            'trip_number' => 'required|integer|unique:trips',
            'date' => 'required|date',
            'depature_hour' => 'required',
            'back_hour' => 'required|date_format:H:i',
            'trip_type' => 'required|string',
            'starting_place' => 'required|string',
            'destination_id' => 'required|exists:destinations,id',
            'bus_id' => 'required|exists:buses,id',
            'driver_id' => 'required|exists:users,id',

            'stations' => 'required|array',
            'stations.*' => 'required|array',
            'stations.*.name' => 'required|string',
            'stations.*.arrival_dateTime' => 'required|date_format:Y-m-d H:i:s',

            'daily_price' => 'required|numeric',//daily price
            'semester_price' => 'required|numeric',

            'daily_points' => 'required|numeric',
            'semester_points' => 'required|numeric',


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
