<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TopicResource extends ResourceCollection
{

    public $resource;
    private $numberOfPages;
    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
        // $this->numberOfPages = $numberOfPages;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (count(array($this->resource)) > 0) {
            return [
                'status_code' => 200,
                'message' => 'Success',
                'error' => null,
                'data' => [
                    'topic' => $this->resource,
                    // 'number_of_pages' => $this->numberOfPages
                ],
            ];
        } elseif (count(array($this->resource)) <= 0) {
            return [
                'status_code' => 404,
                'message' => "No data found",
                'data' => null,
                'errors' => [
                    'message' => 'No data found',
                    'errors' => []
                ]
            ];
        } else {
            return [
                'status_code' => 400,
                'message' => "something went wrong",
                'data' => null,
                'errors' => [
                    'message' => 'something went wrong',
                    'errors' => $this->resource
                ]
            ];
        }
    }
}
