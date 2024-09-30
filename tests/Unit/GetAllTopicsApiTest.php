<?php

use Illuminate\Support\Carbon;
use Tests\TestCase;

class GetAllTopicsApiTest extends TestCase
{
    private $apiUrl = '/api/v1/topic/getAll';

    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetAllTopicsApiWithEmptyFormData()
    {
        $response = $this->postJson($this->apiUrl, []);
        $this->assertEquals(422, $response->status());
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testGetAllTopicsApiWithEmptyValues()
    {
        $response = $this->postJson($this->apiUrl, ['page_number' => '', 'page_size' => '', 'algorithm' => '', 'namespace_id' => '', 'asofdate' => '', 'search' => '', 'user_email' => '']);
        $this->assertEquals(422, $response->status());
    }

    /**
     * Check Api with correct values without asof
     */
    public function testWithCorrectValuesWithoutFilter()
    {
        $response = $this->postJson($this->apiUrl, ['page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'user_email' => '']);
        $this->assertEquals(422, $response->status());
    }

    /**
     * Check Api with wrong values
     * Not found error 404
     * Namespace = 0 there is no data against namespace 0
     */
    public function testWithWrongValuesWithoutFilter()
    {
        $response = $this->postJson($this->apiUrl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 0, 'asofdate' => time(), 'user_email' => 'abcxyz.com']);
        $this->assertCount(0, $response['data']['topic']);
    }

    /**
     * Check Api with correct values with filter search
     */
    public function testWithCorrectValuesWithFilterSearch()
    {
        $response = $this->postJson($this->apiUrl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'search' => 'Hard', 'filter' => 1.7, 'user_email' => '']);
        $this->assertEquals(200, $response->status());
    }

    /**
     * Check Api with correct values with special characters in filter search
     */
    public function testWithCorrectValuesWithSpecialCharactersInFilterSearch()
    {
        $response = $this->postJson($this->apiUrl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'search' => '!@#', 'filter' => 1.7, 'user_email' => '']);
        $this->assertEquals(200, $response->status());
        $response = $this->postJson($this->apiUrl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'search' => '$%^', 'filter' => 1.7, 'user_email' => '']);
        $this->assertEquals(200, $response->status());
        $response = $this->postJson($this->apiUrl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'search' => '&*(', 'filter' => 1.7, 'user_email' => '']);
        $this->assertEquals(200, $response->status());
        $response = $this->postJson($this->apiUrl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'search' => ')_+', 'filter' => 1.7, 'user_email' => '']);
        $this->assertEquals(200, $response->status());
        $response = $this->postJson($this->apiUrl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'search' => '[]%', 'filter' => 1.7, 'user_email' => '']);
        $this->assertEquals(200, $response->status());
    }

    /**
     * Check Api by passing search as integer values
     */
    public function testSearchWithNumericValue()
    {
        $response = $this->postJson($this->apiUrl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'search' => 4645, 'filter' => 1.7, 'user_email' => '']);
        $this->assertEquals('The search field must be a string.', $response['errors']['search'][0]);
    }

    /**
     * Check Api with correct values without search and filter
     */
    public function testWithCorrectValuesWithoutFilterSearch()
    {
        $response = $this->postJson($this->apiUrl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'user_email' => '']);
        $this->assertEquals(200, $response->status());
    }

    /**
     * Check Response Structure with correct values
     */
    public function testWithCorrectValuesForValidResponseStructure()
    {
        $response = $this->postJson($this->apiUrl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'user_email' => '']);
        $response->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                'topic' => [
                    '*' => [
                        'submitter_nick_id',
                        'namespace_id',
                        'topic_score',
                        'topic_full_score',
                        'topic_id',
                        'topic_name',
                        'tree_structure',
                        'as_of_date',
                        'camp_views',
                    ],
                ],
            ],
        ]);
    }

    public function testWithCorrectValuesForValidResponseStructureInDatabase()
    {
        $response = $this->postJson($this->apiUrl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => Carbon::now()->subDays(2)->timestamp, 'user_email' => '']);
        $response->assertJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => [
                'topic' => [
                    '*' => [
                        'submitter_nick_id',
                        'namespace_id',
                        'score',
                        'topic_score',
                        'topic_full_score',
                        'topic_id',
                        'topic_name',
                        'tree_structure',
                        'as_of_date',
                    ],
                ],
            ],
        ]);
    }
}
