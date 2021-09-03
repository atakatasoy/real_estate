<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

use App\Core\Appointment\Exceptions\ExternalException;
use App\Core\Appointment\AppointmentManager;
use App\Core\Auth\TokenHandler;

use App\Models\Appointment;
use App\Models\Contact;
use App\Models\User;

use \Exception;

class AppointmentController extends Controller
{
    public function create(Request $request, AppointmentManager $manager)
    {           
        $input = $request->validate([
            'postal_code' => 'required|string',
            'reserved_at' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string',
            'phone' => 'required|string'
        ]);
        
        try{
            $reservedAt = Carbon::createFromFormat(env('APPOINTMENT_DATE_FORMAT'),  $input['reserved_at']);
            if(!$manager->isBookable($input['postal_code'], $reservedAt)){
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
    
            $appointment = $manager->book($input['postal_code'], $reservedAt, $contact);
    
            return response()->json([
                'status' => 'success',
                'message' => 'Appointment created',
                'appointment' => $appointment
            ]);
        }catch(ExternalException $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'We have encountered an unexpected error. Please try again later or ask for support'
            ]);
        }
    }

    public function update(Request $request, AppointmentManager $manager)
    {
        $input = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'reserved_at' => 'string'
        ]);

        try{            
            $appointment = Appointment::find($input['appointment_id']);
            $reservedAt = Carbon::createFromFormat(env('APPOINTMENT_DATE_FORMAT'), $input['reserved_at']);
            
            if(!$manager->isUpdatable($appointment, $reservedAt)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Specified date is already taken'
                ]);
            }
    
            $manager->update($appointment, $reservedAt);

            return response()->json([
                'status' => 'success'
            ]);
        }catch(ExternalException $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'We have encountered an unexpected error. Please try again later or ask for support'
            ]);
        }
    }

    public function delete(Request $request, AppointmentManager $manager)
    {
        $input = $request->validate([
            'appointment_id' => 'required|integer|exists:appointments,id'
        ]);

        try{
            $appointment = Appointment::find($input['appointment_id']);
    
            $manager->delete($appointment);
    
            return response()->json([
                'status' => 'success'
            ]);
        }catch(ExternalException $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'We have encountered an unexpected error. Please try again later or ask for support'
            ]);
        }
    }
}
