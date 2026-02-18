<?php

namespace App\Model\v1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Statement extends Model
{

    protected $table = 'statement';
    public $timestamps = false;
    protected static $tempArray = [];

    const AGREEMENT_CAMP = "Agreement";

    // ... (rest of the file)

    /**
     * Retrieve live statements for multiple topics.
     *
     * @param array $topicIds
     * @return \Illuminate\Support\Collection
     */
    public static function getLiveStatementsByTopics($topicIds)
    {
        $latestStatements = self::select('topic_num', DB::raw('MAX(submit_time) as max_submit_time'))
            ->whereIn('topic_num', $topicIds)
            ->where('camp_num', 1)
            ->whereNull('objector_nick_id')
            ->where('go_live_time', '<=', time())
            ->groupBy('topic_num');

        // Join with the main table to get the full statement text
        $statements = self::joinSub($latestStatements, 'latest', function ($join) {
                $join->on('statement.topic_num', '=', 'latest.topic_num')
                     ->on('statement.submit_time', '=', 'latest.max_submit_time');
            })
            ->where('camp_num', 1)
            ->get(['statement.topic_num', 'statement.parsed_value', 'statement.value']);

        return $statements->mapWithKeys(function ($item) {
            $text = self::stripTagsExcept($item->parsed_value ?? $item->value ?? null);
            return [$item->topic_num => Str::of($text)->trim()];
        });
    }


    // public static function boot() { // currently this boot is un-used, and occuring issue for creating instance.

    // }

    public function objectornickname()
    {
        return $this->hasOne('App\Model\Nickname', 'id', 'objector_nick_id');
    }

    public function submitternickname()
    {
        return $this->hasOne('App\Model\Nickname', 'id', 'submitter_nick_id');
    }

    public static function getHistory($topicnum, $campnum, $filter = array())
    {

        return self::where('topic_num', $topicnum)->where('camp_num', $campnum)->latest('submit_time')->get();
    }

    public static function getAnyStatement($topicnum, $campnum, $filter = array())
    {
        return self::where('topic_num', $topicnum)->where('camp_num', $campnum)->get();
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
            $statement = self::stripTagsExcept($statement->parsed_value ?? null);
            return Str::of($statement)->trim();
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
    public static function stripTagsExcept(?string $html, array $excludeTags = ['a', 'img', 'figure', 'table', 'iframe', 'video', 'picture']): string
    {
        if (is_null($html)) {
            return '';
        }

        // Handle anchor tags separately
        $html = preg_replace_callback(
            '/<a\b[^>]*href=["\'](.*?)["\'][^>]*>(.*?)<\/a>/is',
            function ($matches) {
                $href = trim($matches[1]);
                $innerText = trim($matches[2]);

                // If inner text and href are the same, remove the tag completely
                if ($href === $innerText) {
                    return '';
                }

                // Otherwise, retain only the inner text
                return $innerText;
            },
            $html
        );

        // Pattern to match the tags and their content for removal
        $excludeTagsPattern = implode('|', array_map(function ($tag) {
            return preg_quote($tag, '/');
        }, $excludeTags));

        if (!empty($excludeTagsPattern)) {
            $pattern = '/<(' . $excludeTagsPattern . ')\b[^>]*>.*?<\/\1>/is';
            $html = preg_replace($pattern, '', $html);
        }

        // Strip all remaining tags
        $html = strip_tags($html);

        // Decode HTML entities for readable text
        return html_entity_decode($html, ENT_QUOTES, 'UTF-8');
    }
}
