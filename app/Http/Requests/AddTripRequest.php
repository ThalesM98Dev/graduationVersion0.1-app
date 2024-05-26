<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddTripRequest extends FormRequest
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
            'price' => 'required|integer',
            'date' => 'required|date',
            'depature_hour' => 'required|date_format:H:i',
            'arrival_hour' => 'required|date_format:H:i',
            'trip_type' => 'required|in:External,Universities',
            'starting_place' => 'required|string',
            'destination_id' => 'required|exists:destinations,id',
            'bus_id' => 'required|exists:buses,id',
            'driver_id' => 'required|exists:users,id,role,Driver'
        ];
    }
}
