<?php

namespace losted\SSO\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Broker extends Model
{
    use SoftDeletes;

    protected $table = 'sso_brokers';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'broker_id', 'broker_secret',
    ];

}
