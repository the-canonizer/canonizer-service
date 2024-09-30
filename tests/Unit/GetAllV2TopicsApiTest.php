<?php

use Illuminate\Support\Carbon;
use Tests\TestCase;

class GetAllV2TopicsApiTest extends TestCase
{
    private $apiURl = '/api/v2/topic/getAll';

    /**
     * Check Api with empty form data
     * validation
     */
    public function testGetAllTopicsApiWithEmptyFormData()
    {
        $response = $this->postJson($this->apiURl, []);
        $this->assertEquals(422, $response->status());
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testGetAllTopicsApiWithEmptyValues()
    {
        $response = $this->postJson($this->apiURl, ['page_number' => '', 'page_size' => '', 'algorithm' => '', 'namespace_id' => '', 'asofdate' => '', 'search' => '', 'user_email' => '']);
        $this->assertEquals(422, $response->status());
    }

    /**
     * Check Api with correct values without asof
     */
    public function testWithCorrectValuesWithoutFilter()
    {
        $response = $this->postJson($this->apiURl, ['page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'user_email' => '']);
        $this->assertEquals(422, $response->status());
    }

    /**
     * Check Api with wrong values
     * Not found error 404
     * Namespace = 0 there is no data against namespace 0
     */
    public function testWithWrongValuesWithoutFilter()
    {
        $response = $this->postJson($this->apiURl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 0, 'asofdate' => time(), 'user_email' => 'abcxyz.com']);
        $this->assertCount(0, $response['data']['topic']);
    }

    /**
     * Check Api with correct values with filter search
     */
    public function testWithCorrectValuesWithFilterSearch()
    {
        $response = $this->postJson($this->apiURl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'search' => 'Hard', 'filter' => 1.7, 'user_email' => '']);
        $this->assertEquals(200, $response->status());
    }

    /**
     * Check Api with correct values with special characters in filter search
     */
    public function testWithCorrectValuesWithSpecialCharactersInFilterSearch()
    {
        $response = $this->postJson($this->apiURl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'search' => '!@#', 'filter' => 1.7, 'user_email' => '']);
        $this->assertEquals(200, $response->status());
        $response = $this->postJson($this->apiURl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'search' => '$%^', 'filter' => 1.7, 'user_email' => '']);
        $this->assertEquals(200, $response->status());
        $response = $this->postJson($this->apiURl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'search' => '&*(', 'filter' => 1.7, 'user_email' => '']);
        $this->assertEquals(200, $response->status());
        $response = $this->postJson($this->apiURl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'search' => ')_+', 'filter' => 1.7, 'user_email' => '']);
        $this->assertEquals(200, $response->status());
        $response = $this->postJson($this->apiURl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'search' => '[]%', 'filter' => 1.7, 'user_email' => '']);
        $this->assertEquals(200, $response->status());
    }

    /**
     * Check Api by passing search as integer values
     */
    public function testSearchWithNumericValue()
    {
        $response = $this->postJson($this->apiURl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'search' => 4645, 'filter' => 1.7, 'user_email' => '']);
        $this->assertEquals('The search field must be a string.', $response['errors']['search'][0]);
    }

    /**
     * Check Api with correct values without search and filter
     */
    public function testWithCorrectValuesWithoutFilterSearch()
    {
        $response = $this->postJson($this->apiURl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'user_email' => '']);
        $this->assertEquals(200, $response->status());
    }

    /**
     * Check Response Structure with correct values
     */
    public function testWithCorrectValuesForValidResponseStructure()
    {
        $response = $this->postJson($this->apiURl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'user_email' => '']);
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
                'total_count',
            ],
        ]);
    }

    public function testWithCorrectValuesForValidResponseStructureInDatabase()
    {
        $response = $this->postJson($this->apiURl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => Carbon::now()->subDays(2)->timestamp, 'user_email' => '']);
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
                'total_count',
            ],
        ]);
    }

    public function testWithCorrectValuesForValidResponseStructureForBrowsePage()
    {
        $response = $this->postJson($this->apiURl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'user_email' => '', 'page' => 'browse']);
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
                        'tree_structure' => [
                            '1' => [
                                'review_title',
                                'support_tree',
                            ],
                        ],
                        'statement',
                    ],
                ],
                'total_count',
            ],
        ]);
    }

    public function testWithCorrectValuesForValidResponseStructureInDatabaseForBrowsePage()
    {
        $response = $this->postJson($this->apiURl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => Carbon::now()->subDays(2)->timestamp, 'user_email' => '', 'page' => 'browse']);
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
                        'tree_structure' => [
                            '1' => [
                                'review_title',
                                'support_tree',
                            ],
                        ],
                        'statement',
                    ],
                ],
                'total_count',
            ],
        ]);
    }

    /**
     * Check Api with wrong type of tags in it
     * validation check
     */
    public function testGetAllTopicsApiWithInvalidDataOfTags()
    {
        $response = $this->postJson($this->apiURl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 20, 'algorithm' => 'blind_popularity', 'namespace_id' => 1, 'asofdate' => time(), 'user_email' => '', 'topic_tags' => 'wrong-data']);
        $this->assertEquals(422, $response->status());
    }

    /**
     * Check Api with wrong array of tags
     * validation check
     */
    public function testGetAllTopicsApiWithWrongTags()
    {
        $response = $this->postJson($this->apiURl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 5, 'algorithm' => 'blind_popularity', 'namespace_id' => '', 'asofdate' => time(), 'user_email' => '', 'topic_tags' => ['a', 'v']]);
        $this->assertEquals(422, $response->status());
    }

    /**
     * Check Api with empty array of tags
     */
    public function testGetAllTopicsApiWithEmptyTags()
    {
        $response = $this->postJson($this->apiURl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 5, 'algorithm' => 'blind_popularity', 'namespace_id' => '', 'asofdate' => time(), 'user_email' => '', 'topic_tags' => []]);
        $this->assertEquals(200, $response->status());
    }

    /**
     * Check Api with valid tags
     */
    public function testGetAllTopicsApiWithValidTags()
    {
        $response = $this->postJson($this->apiURl, ['asof' => 'default', 'page_number' => 1, 'page_size' => 5, 'algorithm' => 'blind_popularity', 'namespace_id' => '', 'asofdate' => time(), 'user_email' => '', 'topic_tags' => [4, 5]]);
        $this->assertEquals(200, $response->status());
    }
}
