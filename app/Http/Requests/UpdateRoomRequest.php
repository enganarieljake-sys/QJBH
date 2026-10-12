<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
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
        $room = $this->route('room');

        return [
            'room_number' => ['required', 'string', 'max:50', Rule::unique('rooms', 'room_number')->ignore($room)],
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $room = $this->route('room');

            if (! $room || $this->input('status') === 'under maintenance') {
                return;
            }

            if ($room->activeTenants()->count() > (int) $this->input('capacity')) {
                $validator->errors()->add('capacity', 'Capacity cannot be lower than the current active tenant count.');
            }
        });
    }
}
