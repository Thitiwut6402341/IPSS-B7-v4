<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class FlexModel extends Model
{
    protected $table = 'OpenTable';

    protected $primaryKey = 'ID';

    protected $allowedFields = [
        "Name",
    ];
}
