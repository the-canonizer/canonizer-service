<?php

class TreeStoreApiTest extends TestCase
{
    /**
     * Check Api with empty form data
     * validation
     */
    public function testStoreApiWithEmptyFormData()
    {
        $this->post('/api/v1/tree/store', [],['X-Api-Token' => env('API_TOKEN')]);
        $this->assertEquals(422, $this->response->status());
    }

     /**
     * Check Api with empty values
     */
    public function testStoreApiWithEmptyValues()
    {
        $this->post('/api/v1/tree/store', ['topic_num'=>'','asofdate'=>'','algorithm'=>'','update_all'=>''],['X-Api-Token' => env('API_TOKEN')]);
        $this->assertEquals(422, $this->response->status());
    }

    /**
     * Check Api with correct values
     */
    public function testStoreApiWithCorrectValues()
    {
        $this->post('/api/v1/tree/store', ['topic_num'=>238,'asofdate'=>time(),'algorithm'=>'blind_popularity','update_all'=>0],['X-Api-Token' => env('API_TOKEN')]);
        $this->assertEquals(200, $this->response->status());
    }
}
