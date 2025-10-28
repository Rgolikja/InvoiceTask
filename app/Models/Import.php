<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    protected $fillable = [
        'file_name',
        'file_path',
        'status',
        'rows_total',
        'rows_imported',
        'error_message'

    ];



}
