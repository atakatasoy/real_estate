<?php

namespace App\Core\Appointment;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

use App\Core\Appointment\Exceptions\ExternalException;

use App\Models\Appointment;
use App\Models\Contact;
use App\Models\User;

class AppointmentManager {
    /**
     * API endpoint for postal code queries
     */
    static $postCodes = "api.postcodes.io/postcodes";

    /**
     * Distance matrix API url
     */
    static $maps = "https://maps.googleapis.com/maps/api/distancematrix/json";

    /**
     * @var User
     */
    private $user;

    /**
     * Postal code
     * @var string
     */
    private $from;

    /**
     * Stores the information about bookings
     * @var array
     */
    private $bookings;

    public function __construct(User $user, $from)
    {
        $this->user = $user;
        $this->from = $from;
        $this->bookings = $user->getBookings();
    }

    /**
     * Calculates distance and travel duration
     * @param string $postalCode
     */
    protected function calculate(string $postalCode)
    {
        if($result = Cache::get($postalCode)){
            return $result;
        }

        $from = $this->getPoints($this->from);
        $to = $this->getPoints($postalCode);

        return tap(
            $this->getMetrics(...$from, ...$to), 
            fn($result) => Cache::put($postalCode, $result, 60 * 60 * 24)
        );
    }

    /**
     * Returns the distance and duration pair for given settings
     * @param float $fromLat
     * @param float $fromLon
     * @param float $toLat
     * @param float $toLon
     * @throws ExternalException
     * @return array
     */
    public function getMetrics($fromLat, $fromLon, $toLat, $toLon)
    {
        $parameters = [
            'units' => 'imperial',
            'origins' => "{$fromLat},{$fromLon}",
            'destinations' => "{$toLat},{$toLon}",
            'key' => env('GOOGLE_API_KEY')
        ];

        $response = Http::get(self::$maps."?".http_build_query($parameters));
        if($response->status() != 200)
            throw new ExternalException($response->json()['message'] /**Not sure */);
        
        $payload = $response->json()['rows'][0]['elements'][0];
            
        //In meters, In seconds
        return [$payload['distance']['value'], $payload['duration']['value']];
    }

    /**
     * Returns latitude and longitude for given postal code
     * @param string $postalCode
     * @throws ExternalException
     * @return array
     */
    public function getPoints(string $postalCode)
    {
        $response = Http::get(self::$postCodes."?q={$postalCode}");
        if($response['status'] != 200)
            throw new ExternalException($response['message']);

        $result = $response['result'][0];
        return [$result['latitude'], $result['longitude']];
    }

    /**
     * Returns a boolean indicating an appointment is possible with given config
     * @param string $postalCode
     * @param Carbon $date
     * @return bool
     */
    public function isBookable(string $postalCode, Carbon $date, $bookings = null)
    {
        [$distance, $duration] = $this->calculate($postalCode);

        $departure = $this->getDeparture($date, $duration);
        $checkDeparture = ($bookings ?? $this->bookings)->every(
            fn($dates, $appointmentId) => !($departure >= $dates[0] && $departure <= $dates[1])
        );

        $arrival = $this->getArrival($date, $duration);
        $checkArrival = ($bookings ?? $this->bookings)->every(
            fn($dates, $appointmentId) => !($arrival >= $dates[0] && $arrival <= $dates[1])
        );

        return $checkArrival & $checkDeparture;
    }

    /**
     * Returns the estimated departure date in seconds
     * @param Carbon $date
     * @param int $duration
     * @return int
     */
    protected function getDeparture(Carbon $date, int $duration)
    {
        return $date->timestamp - $duration;
    }

    /**
     * Returns the estimated arrival date in seconds
     * @param Carbon $date
     * @param int $duration
     * @return int
     */
    protected function getArrival(Carbon $date, int $duration)
    {
        return $date->timestamp + (60 * env('APPOINTMENT_DURATION')) + $duration;
    }

    /**
     * Books an appointment, adjust the cache accordingly
     * @param string $postalCode
     * @param Carbon $reservedAt
     * @param Contact $contact
     */
    public function book(string $postalCode, Carbon $reservedAt, Contact $contact)
    {
        [$distance, $duration] = $this->calculate($postalCode);

        return tap(Appointment::create([
            'contact_id' => $contact->id,
            'user_id' => $this->user->id,
            'postal_code' => $postalCode,
            'reserved_at' => $reservedAt,
            'distance' => $distance,
            'travel_time' => $duration,
            'departure' => $this->getDeparture($reservedAt, $duration),
            'arrival_back' => $this->getArrival($reservedAt, $duration)
        ]), fn($appointment) => $this->setAppointment($appointment));
    }

    /**
     * Completes an appointment
     * @param Appointment $appointment
     * @return void
     */
    public function complete(Appointment $appointment)
    {
        $appointment->completed = true;
        $appointment->save();

        Cache::put("bookings.{$this->user->id}", $this->bookings->forget($appointment->id));
    }

    public function setAppointment(Appointment $appointment)
    {
        $this->bookings[$appointment->id] = [$appointment->departure, $appointment->arrival_back];

        Cache::put("bookings.{$this->user->id}", $this->bookings);
    }

    public function isUpdatable(Appointment $appointment, Carbon $reservedAt)
    {
        $bookings = $this->bookings;

        return $this->isBookable(
            $appointment->postal_code, 
            $reservedAt, 
            $bookings->forget($appointment->id)
        );
    }

    public function update(Appointment $appointment, Carbon $reservedAt)
    {
        [$distance, $duration] = $this->calculate($appointment->postal_code);

        $departure = $this->getDeparture($reservedAt, $duration);
        $arrival = $this->getArrival($reservedAt, $duration);

        $appointment->departure = $departure;
        $appointment->arrival_back = $arrival;
        $appointment->reserved_at = $reservedAt;
        $appointment->save();

        $this->bookings[$appointment->id] = [
            $appointment->departure, $appointment->arrival_back
        ];

        Cache::put("bookings.{$this->user->id}", $this->bookings);

        return $appointment;
    }

    public function delete(Appointment $appointment)
    {
        Cache::put("bookings.{$this->user->id}", $this->bookings->forget($appointment->id));
        
        $appointment->delete();
    }
}