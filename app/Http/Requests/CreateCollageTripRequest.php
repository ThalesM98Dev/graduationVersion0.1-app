<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

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
            // 'stations' => ['required', 'array'],
            // 'stations.*name' => ['string'],
            // 'stations.*in_time' => ['string'],
            // 'stations.*.out_time' => ['string'],
            // 'stations.*type' => ['in:Go,Back'],


            'stations' => [
                'required',
                'array'
            ],
            'stations.*.name' => ['required', 'string'],
            'stations.*.in_time' => ['required', 'date_format:h:i A'],
            'stations.*.out_time' => [
                'prohibited_if:stations.*.type,Back',
                'required_if:stations.*.type,Go',
                // 'date_format:h:i A',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $inTime = $this->input("stations.{$index}.in_time");
                    if ($inTime && $value) {
                        $inCarbon = Carbon::createFromFormat('h:i A', $inTime);
                        $outCarbon = Carbon::createFromFormat('h:i A', $value);
                        if ($inCarbon->eq($outCarbon)) {
                            $fail("The out time must be different from the in time for station {$index}.");
                        }
                    }
                },
            ],
            'stations.*.type' => ['required', Rule::in(['Go', 'Back'])],

            'total_seats' => ['required', 'integer'],
            'driver_id' => ['required', 'exists:users,id']
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
