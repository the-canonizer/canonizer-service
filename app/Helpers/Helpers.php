<?php

namespace App\Helpers;

use App\Library\General;
use App\Model\v1\{Nickname, Person};
use Carbon\Carbon;

class Helpers
{
    public static function getStartOfTheDay($dateTime)
    {
        return Carbon::parse($dateTime)->startOfDay()->timestamp;
    }

    public static function getNickNamesByEmail($email) {
        try {
            $user = (new Person())->where('email', $email)->first();
            if (!empty($user))  {
                $encode = General::canon_encode($user->id);
                $nicknames = (new Nickname())->where('owner_code', $encode)->orderBy('nick_name', 'ASC')->pluck('id')->toArray();
                return $nicknames;
            } else {
                return [];
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
