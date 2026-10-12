<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
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
            'room_number' => ['required', 'string', 'max:50', 'unique:rooms,room_number'],
            'floor' => ['required', 'integer', 'min:0'],
            'type' => ['required', Rule::in(Room::TYPES)],
            'capacity' => ['required', 'integer', 'min:1'],
            'monthly_rate' => ['required', 'numeric', 'min:0'],
            'electricity_rate' => ['required', 'numeric', 'min:0', 'max:1000'],
            'water_rate' => ['required', 'numeric', 'min:0', 'max:10000'],
            'status' => ['required', Rule::in(Room::STATUSES)],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
