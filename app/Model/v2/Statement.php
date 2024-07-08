<?php

namespace App\Model\v2;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Statement extends Model
{
    protected $table = 'statement';

    public $timestamps = false;

    protected static $tempArray = [];

    const AGREEMENT_CAMP = "Agreement";

    /**
     * Retrieve the nickname associated with the objector.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function objectornickname()
    {
        return $this->hasOne('App\Model\Nickname', 'id', 'objector_nick_id');
    }

    /**
     * Retrieve the nickname associated with the submitter.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function submitternickname()
    {
        return $this->hasOne('App\Model\Nickname', 'id', 'submitter_nick_id');
    }

    /**
     * Retrieve the live statement based on the provided filter.
     *
     * @param array $filter The filter criteria for retrieving the live statement.
     * @return mixed The live statement based on the filter.
     */
    public static function getLiveStatement($filter = array())
    {
        if (!isset($filter['asOf'])) {
            $filter['asOf'] = 'default';
        }
        return self::liveStatementAsOfFilter($filter);
    }

    /**
     * Retrieves the live statement based on the provided filter.
     *
     * @param array $filter The filter criteria for retrieving the live statement. It should have a key 'asOf' with one of the following values: 'default', 'review', or 'bydate'.
     * @return mixed The live statement based on the filter.
     */
    private static function liveStatementAsOfFilter($filter)
    {
        $asOfFilter = [
            'default' => self::defaultAsOfFilter($filter),
            'review'  => self::reviewAsofFilter($filter),
            'bydate'  => self::byDateFilter($filter),
        ];
        return $asOfFilter[$filter['asOf']];
    }

    /**
     * Retrieves the default live statement based on the provided filter criteria.
     *
     * @param array $filter The filter criteria for retrieving the default live statement.
     * It should have the following keys:
     * - 'topicNum': The topic number.
     * - 'campNum': The camp number.
     *
     * @return \Illuminate\Database\Eloquent\Model|null The default live statement or null if not found.
     */
    public static function defaultAsOfFilter($filter)
    {
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', $filter['campNum'])
            ->where('objector_nick_id', '=', null)
            ->where('go_live_time', '<=', time())
            ->orderBy('submit_time', 'desc')
            ->first();
    }

    /**
     * Retrieves the review live statement based on the provided filter criteria.
     *
     * @param array $filter The filter criteria for retrieving the review live statement.
     * It should have the following keys:
     * - 'topicNum': The topic number.
     * - 'campNum': The camp number.
     *
     * @return \Illuminate\Database\Eloquent\Model|null The review live statement or null if not found.
     */
    public static function reviewAsofFilter($filter)
    {
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', $filter['campNum'])
            ->where('objector_nick_id', '=', null)
            ->where('grace_period', 0)
            ->orderBy('go_live_time', 'desc')
            ->first();
    }

    /**
     * Retrieves a statement by date filter.
     *
     * @param array $filter The filter criteria for retrieving the statement.
     * It should have the following keys:
     * - 'asOfDate': The date in 'Y-m-d H:i:s' format.
     * - 'topicNum': The topic number.
     * - 'campNum': The camp number.
     *
     * @return \Illuminate\Database\Eloquent\Model|null The statement or null if not found.
     */
    public static function byDateFilter($filter)
    {
        $asofdate = strtotime(date('Y-m-d H:i:s', strtotime($filter['asOfDate'])));
        return self::where('topic_num', $filter['topicNum'])
            ->where('camp_num', $filter['campNum'])
            ->where('go_live_time', '<=', $asofdate)
            ->orderBy('go_live_time', 'desc')
            ->first();
    }

    /**
     * Retrieves the live statement text for a given topic number and camp number.
     *
     * @param int $topicnum The topic number.
     * @param int $campnum The camp number.
     *
     * @return \Illuminate\Database\Eloquent\Model|null The live statement text or null if not found.
     */
    public static function getLiveStatementText(int $topicnum, int $campnum)
    {
        $statement = self::getLiveStatement([
            'topicNum' => $topicnum,
            'campNum' => $campnum,
            'asOf' => 'default',
            'asOfDate' => time()
        ]);

        if ($statement) {
            $statement = preg_replace('/[^a-zA-Z0-9_ %\.\?%&-]/s', '', self::stripTagsExcept($statement->parsed_value ?? null, ['figure', 'table']));
            return Str::of($statement)->trim()->words(30);
        }
        return null;
    }

    /**
     * Removes specified HTML tags from the input string, excluding certain tags.
     *
     * @param ?string $html The HTML string to process.
     * @param array $excludeTags An array of HTML tags to exclude from removal.
     * @return string The processed HTML string with excluded tags removed.
     */
    public static function stripTagsExcept(?string $html, array $excludeTags = []): string
    {
        if (is_null($html)) {
            return $html;
        }
        $excludeTagsPattern = implode('|', array_map(function ($tag) {
            return preg_quote($tag, '/');
        }, $excludeTags));

        // Remove the content and tags of the excluded tags
        $pattern = '/<(' . $excludeTagsPattern . ')\b[^>]*>(.*?)<\/\1>/is';
        $html = preg_replace($pattern, '', $html);

        // Strip all remaining tags
        return strip_tags($html);

        // Decode HTML entities to get the proper text
        // $cleanedText = html_entity_decode($cleanedText, ENT_QUOTES, 'UTF-8');
    }
}
