<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Cache;

use App\Models\Appointment;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getBookings()
    {
        $dates = collect(Cache::get("bookings.{$this->id}") ?: []);
        if(empty($dates)){
            $existingOnes = Appointment::where('user_id', $this->id)
                ->where('completed', 0)
                ->select(['id', 'departure', 'arrival_back'])
                ->get();

            if(!empty($existingOnes)){
                $dates = $existingOnes->mapWithKeys(
                    fn($appointment) => [$appointment->id => [$appointment->departure, $appointment->arrival_back]]
                );

                Cache::put("bookings.{$this->id}", $dates);
            }
        }

        return $dates;
    }
}
