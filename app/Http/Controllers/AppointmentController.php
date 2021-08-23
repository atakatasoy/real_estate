<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

use App\Core\Appointment\AppointmentManager;
use App\Core\Auth\TokenHandler;

use Illuminate\Support\Facades\Hash;

use App\Models\Appointment;
use App\Models\Contact;
use App\Models\User;

class AppointmentController extends Controller
{
    public function create(Request $request, AppointmentManager $manager)
    {           
        // $from = $am->getPoints("TW135JT")
        $input = $request->validate([
            'postal_code' => 'required|string',
            'reserved_at' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string',
            'phone' => 'required|string'
        ]);

        if(!$manager->isBookable($input['postal_code'], $input['reserved_at'])){
            return response()->json([
                'status' => 'error',
                'message' => 'This time frame is already taken' // ??
            ]);
        }

        $contact = Contact::firstOrCreate(
            [
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'email' => $input['email']
            ],
            [
                'phone' => $input['phone']
            ]
        );

        $appointment = $manager->book($input['postal_code'], $input['reserved_at'], $contact);

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment created',
            'appointment' => $appointment
        ]);
    }

    public function update(Request $request, AppointmentManager $manager)
    {
        $input = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'reserved_at' => 'string'
        ]);

        $appointment = Appointment::find($input['appointment_id']);

        if(!$manager->isUpdatable($appointment, $input['reserved_at'])){
            return response()->json([
                'status' => 'error',
                'message' => 'Specified date is already taken'
            ]);
        }

        $manager->update($appointment, $input['reserved_at']);

        return response()->json([
            'status' => 'success'
        ]);
    }

    public function delete(Request $request, AppointmentManager $manager)
    {
        $input = $request->validate([
            'appointment_id' => 'required|integer|exists:appointments,id'
        ]);

        $appointment = Appointment::find($input['appointment_id']);

        $manager->delete($appointment);

        return response()->json([
            'status' => 'success'
        ]);
    }
}
