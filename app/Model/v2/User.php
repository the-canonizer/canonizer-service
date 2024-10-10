<?php

namespace App\Model\v2;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'person';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'middle_name', 'email', 'password', 'language', 'status', 'otp', 'provider', 'provider_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    // Define the accessor for the profile_picture_path attribute
    public function getProfilePicturePathAttribute($value)
    {
        return !is_null($value) ? urldecode(env('AWS_PUBLIC_URL') . '/' . $value) : null;
    }

    public static function getByEmail($email)
    {
        $user = User::where('email', $email)->first();
        return !empty($user) ? $user : false;
    }

    public function getNameAttribute()
    {
        return ucwords($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get user by user id
     * @param interger $id
     * @return User 
     */
    public static function getById($id)
    {
        return User::where('id', $id)->first();
    }
}
