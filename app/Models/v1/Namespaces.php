<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Model;
use DB;

class Namespaces extends Model {

    protected $table = 'namespace';
    public $timestamps = false;

    public $fillable = ['name','parent_id'];

    public function parentNamespace(){
        return $this->belongsTo('\App\Model\Namespaces','parent_id');
    }

    public function topics(){
    	return $this->hasMany('\App\Model\Topic','namespace_id');
    }

}
