<?php

class TreeGetApiTest extends TestCase
{
    /**
     * Check Api with empty form data
     * validation
     */
    public function testTreeGetApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $response = $this->call('POST', '/api/v1/tree/get', []);
        $this->assertEquals(422, $response->status());
    }

     /**
     * Check Api with empty values
     * validation
     */
    public function testTreeGetApiWithEmptyValues()
    {
        print sprintf("Test with empty values");
        $response = $this->call('POST', '/api/v1/tree/get', ['topic_num'=>'','asofdate'=>'','algorithm'=>'','update_all'=>'']);
        $this->assertEquals(422, $response->status());
    }

    /**
     * Check Api with correct values
     */
    public function testStoreApiWithCorrectValues()
    {
        print sprintf("Test with correct values");
        $response = $this->call('POST', '/api/v1/tree/get', ['topic_num'=>238,'asofdate'=>time(),'algorithm'=>'blind_popularity','update_all'=>0]);
        $this->assertEquals(200, $response->status());
    }
}
