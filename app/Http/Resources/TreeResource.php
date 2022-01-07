<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TreeResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /* if exception happen during the tree calculation */
        if (isset($this->collection[0]['code']) && $this->collection[0]['code'] == 401) {
            return $this->collection[0];
        }

        if (count($this->collection) > 0) {
            return ["data" => $this->collection, "code" => 200, "success" => true];
        }

        if (($this->collection->isEmpty()) || !$this->collection) {
            return ["data" => [], "code" => 404, "success" => false, "error" => "Tree not found"];
        }

        return ["data" => [], "code" => 401, "success" => false, "error" => $this->collection];
    }
}
