<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimelineStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    protected function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    protected function rules(): array
    {
        return match ($this->method()) {

            'POST' => [
                'topic_num' => 'required|integer',
                'algorithm' => 'required|string',
                'update_all' => 'in:0,1'
            ],
            'GET' => [
                'topic_num' => 'required|integer',
                'algorithm' => 'required|string'
            ],
            default => [],
        };
    }
}
