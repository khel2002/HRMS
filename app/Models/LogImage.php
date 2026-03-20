<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogImage extends Model
{
    //
    protected $table = 'log_images';

    protected $fillable = [
        'log_id',
        'image_path',
        'captured_at',
        'log_type'
    ];

    public function userLogs(){
       return $this->belongsTo(UserLogs::class,'log_id');
    }
}
