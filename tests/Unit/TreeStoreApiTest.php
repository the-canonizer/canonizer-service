<?php

namespace Tests\Unit;

use Tests\TestCase;

class TreeStoreApiTest extends TestCase
{
    private $apiUrl = '/api/v1/tree/store';

    /**
     * Check Api with empty form data
     * validation
     */
    public function testStoreApiWithEmptyFormData()
    {
        $response = $this->post($this->apiUrl, [], ['X-Api-Token' => env('API_TOKEN')]);
        $this->assertEquals(302, $response->status());
    }

    /**
     * Check Api with empty values
     */
    public function testStoreApiWithEmptyValues()
    {
        $response = $this->post($this->apiUrl, ['topic_num' => '', 'asofdate' => '', 'algorithm' => '', 'update_all' => ''], ['X-Api-Token' => env('API_TOKEN')]);
        $this->assertEquals(302, $response->status());
    }

    /**
     * Check Api with correct values
     */
    public function testStoreApiWithCorrectValues()
    {
        $response = $this->post($this->apiUrl, ['topic_num' => 238, 'asofdate' => time(), 'algorithm' => 'blind_popularity', 'update_all' => 0], ['X-Api-Token' => env('API_TOKEN')]);
        $this->assertEquals(200, $response->status());
    }
}
