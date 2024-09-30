<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimelineStoreRequest extends FormRequest
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
        switch ($this->method()) {

            case 'POST':
                return [
                    'topic_num' => 'required|integer',
                    //'asofdate' => 'required',
                    'algorithm' => 'required|string',
                    'update_all' => 'in:0,1',
                ];
                break;

            case 'GET':
                return [
                    'topic_num' => 'required|integer',
                    'algorithm' => 'required|string',
                ];
                break;

            default:
                break;
        }
    }
}
