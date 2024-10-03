<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Seeders\PHPExtensionSeeder;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'folder',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * if created call seeder
     */
    protected static function boot(): void
    {
        parent::boot();

        static::created(function (User $user): void {
            $user->seedPHPExtensions();
        });
    }

    /**
     * Seed PHP extensions
     */
    public function seedPHPExtensions(): void
    {
        //call seeder
        $seeder = new PHPExtensionSeeder();
        $seeder->run($this);
    }

    /**
     * Get the PHP extensions for the user.
     */

    public function phpExtensions()
    {
        return $this->hasMany(PhpExtension::class);
    }
}
