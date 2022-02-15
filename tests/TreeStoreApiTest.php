<?php

class TreeStoreApiTest extends TestCase
{
    /**
     * Check Api with empty form data
     * validation
     */
    public function testStoreApiWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $response = $this->call('POST', '/api/v1/tree/store', []);
        $this->assertEquals(422, $response->status());
    }

     /**
     * Check Api with empty values
     */
    public function testStoreApiWithEmptyValues()
    {
        print sprintf("Test with empty values");
        $response = $this->call('POST', '/api/v1/tree/store', ['topic_num'=>'','asofdate'=>'','algorithm'=>'','update_all'=>'']);
        $this->assertEquals(422, $response->status());
    }

    /**
     * Check Api with correct values
     */
    public function testStoreApiWithCorrectValues()
    {
        print sprintf("Test with correct values");
        $response = $this->call('POST', '/api/v1/tree/store', ['topic_num'=>238,'asofdate'=>time(),'algorithm'=>'blind_popularity','update_all'=>0]);
        $this->assertEquals(200, $response->status());
    }
}
