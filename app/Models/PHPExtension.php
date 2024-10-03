<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PHPExtension extends Model
{
    use HasFactory;
    protected $table = 'user_php_extensions';
    protected $fillable = ['name', 'is_enabled', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function enable()
    {
        $this->is_enabled = true;
        $this->save();
    }

    public function disable()
    {
        $this->is_enabled = false;
        $this->save();
    }

    public function isEnabled()
    {
        return $this->is_enabled;
    }
}