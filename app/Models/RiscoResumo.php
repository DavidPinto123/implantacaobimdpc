<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiscoResumo extends Model
{
    protected $table = 'riscos'; // apenas alias da subquery

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = [];

    // use "id" porque você já sempre gera um ROW_NUMBER() OVER () as id
    protected $primaryKey = 'id';

    protected $keyType = 'int';
}
