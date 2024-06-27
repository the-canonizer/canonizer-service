<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TopicResource extends ResourceCollection
{
    public $resource;
    private $totalCount;

    /**
     * Constructs a new instance of the class.
     *
     * @param mixed $resource The resource to be assigned to the instance.
     * @param int $totalCount The total count of resources, defaults to 0.
     */
    public function __construct($resource, $totalCount = 0)
    {
        $this->resource = $resource;
        $this->totalCount = $totalCount;
    }

    /**
     * Converts the resource into an array representation.
     *
     * @param mixed $request The request object.
     * @return array The array representation of the resource.
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
                    'total_count' => $this->totalCount
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
