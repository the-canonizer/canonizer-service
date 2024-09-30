<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemoveTopicsRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return match ($this->method()) {
            'POST' => [
                'topic_numbers' => 'required|array',
            ],
            'GET' => [
                'topic_numbers' => 'required|array',
            ],
            default => [],
        };
    }
}
