<?php

class GetAllTopicsApiTest extends TestCase
{
    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetAllTopicsApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $response = $this->call('POST', '/api/v1/topic/getAll', []);
        $this->assertEquals(422, $response->status());
    }

     /**
     * Check Api with empty values
     * validation
     */
    public function testGetAllTopicsApiWithEmptyValues()
    {
        print sprintf("Test with empty values");
        $response = $this->call('POST', '/api/v1/topic/getAll', ['page_number'=>'','page_size'=>'','algorithm'=>'','namespace_id'=>'', 'asofdate'=>'','search'=>'']);
        $this->assertEquals(422, $response->status());
    }

    /**
     * Check Api with correct values
     */
    public function testWithCorrectValuesWithoutFilter()
    {
        print sprintf("Test with correct values without filter");
        $response = $this->call('POST', '/api/v1/topic/getAll', ['page_number'=>1,'page_size'=>20,'algorithm'=>'blind_popularity','namespace_id'=>1, 'asofdate'=>1643210702,'search'=>'Hard']);
        $this->assertEquals(200, $response->status());
    }

    /**
     * Check Api with wrong values
     * Not found error 404
     * Namespace = 0 there is no data against namespace 0
     */
    public function testWithWrongValuesWithoutFilter()
    {
        print sprintf("Test with correct values without filter");
        $response = $this->call('POST', '/api/v1/topic/getAll', ['page_number'=>1,'page_size'=>20,'algorithm'=>'blind_popularity','namespace_id'=>0, 'asofdate'=>1643210702,'search'=>'Hard']);
        $this->assertEquals(404, $response['status_code']);
    }

    /**
     * Check Api with correct values with filter
     */
    public function testWithCorrectValuesWithFilter()
    {
        print sprintf("Test with correct values with filter");
        $response = $this->call('POST', '/api/v1/topic/getAll', ['page_number'=>1,'page_size'=>20,'algorithm'=>'blind_popularity','namespace_id'=>1, 'asofdate'=>1643210702,'search'=>'Hard', 'filter'=>1.7]);
        $this->assertEquals(200, $response->status());
    }
}
