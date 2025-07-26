<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'error_message',
        'endpoint',
        'status_code'
    ];

    public function getConnectionName()
    {
        if (app()->environment('testing')) {
            return config('database.default');
        }

        return 'logging_pgsql';
    }
}
