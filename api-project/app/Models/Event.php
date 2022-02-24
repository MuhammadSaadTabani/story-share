<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'user_id', 'created_at'];

     /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the videos of event.
     */
    public function videos()
    {
        return $this->hasMany(EventVideo::class)->select(['id', 'video']);
    }

    /**
     * Get the user of event.
     */
    public function user()
    {
        return $this->belongsTo(User::class)->select(['full_name', 'image', 'cover_image']);
    }
}
