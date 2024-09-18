<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;

class TopicRequest extends FormRequest
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
        return [
            'page_number' => 'required|integer',
            'page_size' => 'required|integer',
            'namespace_id' => 'nullable|integer',
            'asofdate' => 'required',
            'algorithm' => 'required|string',
            'asof' => 'required|string',
            'search' => 'nullable|string',
        ];
    }

    /**
     * format the validation response if there is error in validation.
     *
     * @return array
     */
    public function errorResponse(): ?JsonResponse
    {
        return response()->json([
            'status_code' => 422,
            'message' => 'validation errors',
            'data' => null,
            'errors' => [
                'message' => 'The given data is invalid',
                'errors' => $this->validator->errors()->messages(),
            ],
        ], $this->statusCode());
    }
}
