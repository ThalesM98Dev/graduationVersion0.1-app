<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateCollageTripRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            //collage Trip
            'days' => ['required', 'array'],
            'days.*' => ['integer', 'exists:days,id'],

            'go_price' => ['required', 'numeric'],
            'round_trip_price' => ['required', 'numeric'],
            'semester_round_trip_price' => ['required', 'numeric'],

            'go_points' => ['numeric'],
            'round_trip_points' => ['numeric'],
            'semester_round_trip_points' => ['numeric'],
            //
            'required_go_points' => ['numeric'],
            'required_round_trip_points' => ['numeric'],
            'required_semester_round_trip_points' => ['numeric'],
            //
            'stations' => ['array'],
            'stations.*name' => ['string'],
            'stations.*in_time' => ['date_format:g:i A'],
            'stations.*.out_time' => ['date_format:g:i A'],
            'stations.*type' => ['in:Go,Back'],
            'total_seats' => ['required', 'integer'],
            'driver_id' => ['exists:users,id']
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $stations = $this->input('stations', []);
            foreach ($stations as $index => $station) {
                if (isset($station['in_time'], $station['out_time']) && $station['in_time'] >= $station['out_time']) {
                    $validator->errors()->add("stations.$index.out_time", 'The out_time must be after the in_time.');
                }
            }
        });
    }
    public function messages()
    {
        return [
            'stations.*.in_time.date_format' => 'The :attribute field must match the format H:i (e.g., 11:11).',
            'stations.*.out_time.date_format' => 'The :attribute field must match the format H:i (e.g., 11:11).',
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
