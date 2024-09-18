<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Model;

class Namespaces extends Model
{
    protected $table = 'namespace';

    public $timestamps = false;

    public $fillable = ['name', 'parent_id'];

    public function parentNamespace()
    {
        return $this->belongsTo(Namespaces::class, 'parent_id');
    }

    public function topics()
    {
        return $this->hasMany(Topic::class, 'namespace_id');
    }
}
