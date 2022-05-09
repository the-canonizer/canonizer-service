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
        $response = $this->call('POST', '/api/v1/topic/getAll', ['page_number'=>'','page_size'=>'','algorithm'=>'','namespace_id'=>'', 'asofdate'=>'','search'=>'','user_email'=>'']);
        $this->assertEquals(422, $response->status());
    }

    /**
     * Check Api with correct values without asof
     */
    public function testWithCorrectValuesWithoutFilter()
    {
        print sprintf("Test with correct values without filter");
        $response = $this->call('POST', '/api/v1/topic/getAll', ['page_number'=>1,'page_size'=>20,'algorithm'=>'blind_popularity','namespace_id'=>1, 'asofdate'=>time(),'user_email'=>'']);
        $this->assertEquals(422, $response->status());
    }

    /**
     * Check Api with wrong values
     * Not found error 404
     * Namespace = 0 there is no data against namespace 0
     */
    public function testWithWrongValuesWithoutFilter()
    {
        print sprintf("Test with correct values without filter");
        $response = $this->call('POST', '/api/v1/topic/getAll', ['asof'=>'default','page_number'=>1,'page_size'=>20,'algorithm'=>'blind_popularity','namespace_id'=>0, 'asofdate'=>time(),'user_email'=>'abcxyz.com']);
        $this->assertCount(0, $response['data']['topic']);
    }

    /**
     * Check Api with correct values with filter search
     */
    public function testWithCorrectValuesWithFilterSearch()
    {
        print sprintf("Test with correct values with filter");
        $response = $this->call('POST', '/api/v1/topic/getAll', ['asof'=>'default','page_number'=>1,'page_size'=>20,'algorithm'=>'blind_popularity','namespace_id'=>1, 'asofdate'=>time(),'search'=>'Hard', 'filter'=>1.7,'user_email'=>'']);
        $this->assertEquals(200, $response->status());
    }

     /**
     * Check Api with correct values without search and filter
     */
    public function testWithCorrectValuesWithoutFilterSearch()
    {
        print sprintf("Test with correct values with filter");
        $response = $this->call('POST', '/api/v1/topic/getAll', ['asof'=>'default','page_number'=>1,'page_size'=>20,'algorithm'=>'blind_popularity','namespace_id'=>1, 'asofdate'=>time(),'user_email'=>'']);
        $this->assertEquals(200, $response->status());
    }

     /**
     * Check Json structure
     */
    public function checkApiJSONStructure()
    {
        print sprintf("Test with correct values with filter");
        $response = $this->call('POST', '/api/v1/topic/getAll', ['asof'=>'default','page_number'=>1,'page_size'=>20,'algorithm'=>'blind_popularity','namespace_id'=>1, 'asofdate'=>time()]);
        $this->seeJsonStructure(
            ['status_code',
             'message',
             'error',
             'data'=>['topic', 'number_of_pages'],
            ]
       );
    }
}
