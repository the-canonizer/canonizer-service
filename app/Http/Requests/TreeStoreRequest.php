<?php

namespace App\Http\Requests;

use Anik\Form\FormRequest;

class TreeStoreRequest extends FormRequest
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
        switch ($this->method()) {

            case 'POST': {
                    return [
                        'topic_num' => 'required|integer',
                        'asofdate' => 'required',
                        'algorithm' => 'required|string',
                        'update_all' => 'in:0,1',
                        'model_id' => 'nullable|integer',
                        'model_type' => 'nullable|string|in:topic,camp,statement',
                        'job_type' => 'nullable|string|in:live-time-job',
                        'camp_num' => 'integer'
                    ];
                    break;
                }
            case 'GET': {
                    return [
                        'topic_num' => 'required|integer',
                        'asofdate' => 'required',
                        'algorithm' => 'required|string'
                    ];
                    break;
                }
            default:
                break;
        }
    }
}
