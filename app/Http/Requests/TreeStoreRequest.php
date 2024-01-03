<?php

namespace App\Http\Requests;

use Anik\Form\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

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
                        'topic_num' => 'required|gte:1|integer|max:'.PHP_INT_MAX.'|exists:topic,topic_num',
                        'asofdate' => 'required',
                        'algorithm' => 'required|string',
                        'update_all' => 'in:0,1',
                        'model_id' => 'nullable|integer',
                        'model_type' => 'nullable|string|in:topic,camp,statement',
                        'job_type' => 'nullable|string|in:live-time-job',
                        'event_type' => 'nullable|string',
                        'pre_LiveId' => 'nullable|string',
                        'camp_num' => 'integer|gte:1|max:' . PHP_INT_MAX,
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

    protected function statusCode(): int
    {
        return 422;
    }

    protected function messages(): array
    {
        return [
            'topic_num.exists' => 'Topic not found.',
        ];
    }

    protected function errorResponse(): ?JsonResponse
    {
        $errors = $this->validator->errors()->messages();
        if(array_key_exists("topic_num", $errors)) {
            $messageExists = in_array('The selected topic num is invalid.', $errors['topic_num']);            
        }
        // change status code to 404 when record not found...
        $statusCode = (isset($messageExists) && $messageExists) ? 404 : $this->statusCode();
    
        return response()->json([
            'code' => $statusCode,
            'message' => $this->errorMessage(),
            'error' => $this->validator->errors()->messages(),
            'data' => NULL,
            'success' => FALSE,
        ], $statusCode);
    }

    protected function validationFailed(): void
    {
        throw new ValidationException($this->validator, $this->errorResponse());
    }
}
