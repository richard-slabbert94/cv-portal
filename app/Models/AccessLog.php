<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['access_code_id', 'ip', 'user_agent'])]
class AccessLog extends Model
{
    public const UPDATED_AT = null;
}
