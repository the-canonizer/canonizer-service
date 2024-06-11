<?php

class TreeGetApiTest extends TestCase
{
    /**
     * Check Api with empty form data
     * validation
     */
    public function testTreeGetApiWithEmptyFormData()
    {
        $response = $this->call('POST', '/api/v1/tree/get', []);
        $this->assertEquals(422, $response->status());
    }

    /**
     * Check Api with empty values
     * validation
     */
    public function testTreeGetApiWithEmptyValues()
    {
        $response = $this->call('POST', '/api/v1/tree/get', ['topic_num' => '', 'asofdate' => '', 'algorithm' => '', 'update_all' => '']);
        $this->assertEquals(422, $response->status());
    }

    /**
     * Check Api with correct values
     */
    public function testStoreApiWithCorrectValues()
    {
        $response = $this->call('POST', '/api/v1/tree/get', ['topic_num' => 238, 'asofdate' => time(), 'algorithm' => 'blind_popularity', 'update_all' => 0, "view" => "\$QWZhYWNiVmhtMTE2ekt3Vg\$iIc0UGbTCDIgCXXwpHnzXA"]);
        $this->assertEquals(200, $response->status());
    }

    public function testWithCorrectValuesForValidResponseStructure()
    {
        $response = $this->call('POST', '/api/v1/tree/get', ['topic_num' => 238, 'asofdate' => time(), 'algorithm' => 'blind_popularity', 'update_all' => 0]);
        $response->assertJsonStructure([
            "code",
            "success",
            'data' => [
                0 => [
                    '1' => [
                        "topic_id",
                        "camp_id",
                        "title",
                        "review_title",
                        "link",
                        "review_link",
                        "score",
                        "full_score",
                        "submitter_nick_id",
                        "created_date",
                        "is_valid_as_of_time",
                        "is_disabled",
                        "is_one_level",
                        "is_archive",
                        "direct_archive",
                        "subscribed_users",
                        "support_tree",
                        "children",
                        "collapsedTreeCampIds",
                        "camp_views"
                    ]
                ]
            ],
        ]);
    }
}
