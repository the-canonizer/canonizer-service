<?php

namespace App\Helpers;

use App\Models\v1\Camp;
use App\Models\v1\Nickname;
use App\Models\v1\Person;
use App\Models\v1\TopicView;
use Carbon\Carbon;

class Helpers
{
    public static function getStartOfTheDay($dateTime)
    {
        return Carbon::parse($dateTime)->startOfDay()->timestamp;
    }

    public static function getNickNamesByEmail($email)
    {
        try {
            $user = (new Person)->where('email', $email)->first();
            if (! empty($user)) {
                return (new Nickname)->where('user_id', $user->id)->orderBy('nick_name', 'ASC')->pluck('id')->toArray();
            } else {
                return [];
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function renderParentsCampTree($topic_num, $camp_num)
    {
        $camp = Camp::where([
            'camp_num' => $camp_num,
            'topic_num' => $topic_num,
            'grace_period' => 0,
            'objector_nick_id' => null,
        ])->orderBy('submit_time', 'desc')->first();

        if (! $camp) {
            return [];
        }

        if ($camp && is_null($camp->parent_camp_num)) {
            return [$camp->camp_num];
        }

        return array_merge([$camp->camp_num], self::renderParentsCampTree($topic_num, $camp->parent_camp_num));
    }

    public static function getCampViewsByDate(int $topic_num, int $camp_num = 1, ?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        return TopicView::where('topic_num', $topic_num)
            ->when($camp_num > 1, fn ($query) => $query->where('camp_num', $camp_num))
            ->when(
                $startDate && $endDate,
                fn ($query) => $query->whereBetween('created_at', [$startDate->startOfDay()->timestamp, $endDate->endOfDay()->timestamp]),
                fn ($query) => $query->when(
                    $endDate,
                    fn ($query) => $query->where('created_at', '<=', $endDate->endOfDay()->timestamp),
                    fn ($query) => $query->when(
                        $startDate,
                        fn ($query) => $query->where('created_at', '>=', $startDate->startOfDay()->timestamp),
                    )
                )
            )->sum('views');
    }
}
