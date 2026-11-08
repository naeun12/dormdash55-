<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class adminModel extends Authenticatable
{
    // I-enable ang API tokens, model factories, ug notifications.
    use HasApiTokens, HasFactory, Notifiable;

    // I-specify ang database table nga gamiton sa admin model.
    protected $table = 'admins';

    // I-set ang primary key sa admins table.
    protected $primaryKey = 'admin_id';

    // Nagpasabot nga auto-increment ang primary key.
    public $incrementing = true;

    // Mga field nga tugotan nga ma-assign gamit ang mass assignment.
    protected $fillable = [
        'name',
        'username',
        'password',
    ];

    // Mga sensitive fields nga dili iapil sa serialized model output.
    protected $hidden = [
        'password',
        'remember_token',
    ];
}

