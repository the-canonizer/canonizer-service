<?php

namespace App\Http\Requests;

use Anik\Form\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class TopicRequest extends FormRequest
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
                        'page_number' => 'required|integer',
                        'page_size' => 'required|integer',
                        'namespace_id' => 'required|integer',
                        'asofdate' => 'required',
                        'algorithm' => 'required|string',
                        'search' => 'nullable|string'
                    ];
                    break;
                }
            default:
                break;
        }
    }

     /**
     * format the validation response if there is error in validation.
     *
     * @return array
     */
    protected function errorResponse(): ?JsonResponse
    {

        return response()->json([
            'status_code' => 422,
            'message' => 'validation errors',
            'data' => null,
            'errors' => [
                'message' => 'The given data is invalid',
                'errors' => $this->validator->errors()->messages()
            ]
        ], $this->statusCode());
    }
}
