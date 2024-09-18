<?php

namespace Tests\Unit;

use Tests\TestCase;

class TreeGetApiTest extends TestCase
{
    private $apiUrl = '/api/v1/tree/get';

    /**
     * Check Api with empty form data
     * validation
     */
    public function testTreeGetApiWithEmptyFormData()
    {
        $response = $this->call('POST', $this->apiUrl, []);
        $this->assertEquals(302, $response->status());
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testTreeGetApiWithEmptyValues()
    {
        $response = $this->call('POST', $this->apiUrl, ['topic_num' => '', 'asofdate' => '', 'algorithm' => '', 'update_all' => '']);
        $this->assertEquals(302, $response->status());
    }

    /**
     * Check Api with correct values
     */
    public function testStoreApiWithCorrectValues()
    {
        $response = $this->call('POST', $this->apiUrl, ['topic_num' => 238, 'asofdate' => time(), 'algorithm' => 'blind_popularity', 'update_all' => 0]);
        $this->assertEquals(200, $response->status());
    }
}
