<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

enum PlaceType: string {
    case from = 'from';
    case back = 'back';
}

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return $user->bookings->map(fn ($booking) => [
            'code' => $booking->code,
            'cost' => ($booking->from->cost + $booking->back->cost) * $booking->passengers->count(),
            'trips' => [
                [
                    'trip_id' => $booking->from->id,
                    'trip_code' => $booking->from->code,
                    'from' => [
                        'city' => $booking->from->from_station->city,
                        'station' => $booking->from->from_station->name,
                        'code' => $booking->from->from_station->code,
                        'date' => $booking->from->from_date,
                        'time' => $booking->from->from_time,
                    ],
                    'to' => [
                        'city' => $booking->from->to_station->city,
                        'station' => $booking->from->to_station->name,
                        'code' => $booking->from->to_station->code,
                        'date' => $booking->from->to_date,
                        'time' => $booking->from->to_time,
                    ],
                    'cost' => $booking->from->cost,
                    'availability' => $booking->from->availability,
                ],
                [
                    'trip_id' => $booking->back->id,
                    'trip_code' => $booking->back->code,
                    'from' => [
                        'city' => $booking->back->from_station->city,
                        'station' => $booking->back->from_station->name,
                        'code' => $booking->back->from_station->code,
                        'date' => $booking->back->from_date,
                        'time' => $booking->back->from_time,
                    ],
                    'to' => [
                        'city' => $booking->back->to_station->city,
                        'station' => $booking->back->to_station->name,
                        'code' => $booking->back->to_station->code,
                        'date' => $booking->back->to_date,
                        'time' => $booking->back->to_time,
                    ],
                    'cost' => $booking->back->cost,
                    'availability' => $booking->back->availability,
                ],
            ],
            'passengers' => $booking->passengers->map(fn ($passenger) => [
                'id' => $passenger->id,
                'first_name' => $passenger->first_name,
                'last_name' => $passenger->last_name,
                'birth_date' => $passenger->birth_date,
                'document_number' => $passenger->document_number,
                'place_from' => $passenger->pivot->place_from,
                'place_back' => $passenger->pivot->place_back,
            ])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookingRequest $request)
    {
        $data = $request->validated();

        $booking_code = Str::random(5);
        $new_booking = Booking::create([
            'code' => $booking_code,
            'trip_from' => $data['trip_from']['id'],
            'trip_back' => $data['trip_back']['id'],
        ]);

        $users_ids = [];
        foreach ($data['passengers'] as $passenger) {
            $user = User::firstOrCreate($passenger);
            $users_ids[] = $user->id;
        }
        $new_booking->passengers()->attach($users_ids);

        return response([
            'data' => [
                'code' => $booking_code
            ]
        ], 422);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $code)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        return [
            'data' => [
                'code' => $booking->code,
                'cost' => ($booking->from->cost + $booking->back->cost) * $booking->passengers->count(),
                'trips' => [
                    [
                        'trip_id' => $booking->from->id,
                        'trip_code' => $booking->from->code,
                        'from' => [
                            'city' => $booking->from->from_station->city,
                            'station' => $booking->from->from_station->name,
                            'code' => $booking->from->from_station->code,
                            'date' => $booking->from->from_date,
                            'time' => $booking->from->from_time,
                        ],
                        'to' => [
                            'city' => $booking->from->to_station->city,
                            'station' => $booking->from->to_station->name,
                            'code' => $booking->from->to_station->code,
                            'date' => $booking->from->to_date,
                            'time' => $booking->from->to_time,
                        ],
                        'cost' => $booking->from->cost,
                        'availability' => $booking->from->availability,
                    ],
                    [
                        'trip_id' => $booking->back->id,
                        'trip_code' => $booking->back->code,
                        'from' => [
                            'city' => $booking->back->from_station->city,
                            'station' => $booking->back->from_station->name,
                            'code' => $booking->back->from_station->code,
                            'date' => $booking->back->from_date,
                            'time' => $booking->back->from_time,
                        ],
                        'to' => [
                            'city' => $booking->back->to_station->city,
                            'station' => $booking->back->to_station->name,
                            'code' => $booking->back->to_station->code,
                            'date' => $booking->back->to_date,
                            'time' => $booking->back->to_time,
                        ],
                        'cost' => $booking->back->cost,
                        'availability' => $booking->back->availability,
                    ],
                ],
                'passengers' => $booking->passengers->map(fn ($passenger) => [
                    'id' => $passenger->id,
                    'first_name' => $passenger->first_name,
                    'last_name' => $passenger->last_name,
                    'birth_date' => $passenger->birth_date,
                    'document_number' => $passenger->document_number,
                    'place_from' => $passenger->pivot->place_from,
                    'place_back' => $passenger->pivot->place_back,
                ])
            ]
        ];
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $code)
    {
        $data = $request->validate([
            'passenger' => ['required', Rule::exists('users', 'id')],
            'seat' => ['required', 'string'],
            'type' => ['required', Rule::enum(PlaceType::class)],
        ]);

        $booking = Booking::where('code', $code)->firstOrFail();
        $place = 'place_' . $data['type'];

        $user = $booking->passengers()->find($data['passenger']);
        if (!isset($user)) {
            return response([
                'error' => [
                    'code' => 422,
                    'message' => 'Место занято',
                ]
            ], 422);
        }

        $seat = $booking->passengers()->wherePivot($place, $data['seat'])->first();

        if (isset($seat)) {
            return response([
                'error' => [
                    'code' => 422,
                    'message' => 'Место занято',
                ]
            ], 422);
        }


        $booking->passengers()->updateExistingPivot($data['passenger'], [
            $place => $data['seat']
        ]);

        $user = $booking->passengers()->find($data['passenger']);

        return [
            'data' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'birth_date' => $user->birth_date,
                'document_number' => $user->document_number,
                'place_from' => $user->pivot->place_from,
                'place_back' => $user->pivot->place_back,
            ]
        ];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking)
    {
        //
    }


    function get_occupied_seats(string $code) {
        $booking = Booking::where('code', $code)->firstOrFail();

        return [
            'data' => [
                'occupied_from' => $booking->passengers->filter(fn ($passenger) => isset($passenger->pivot->place_from))->map(fn ($passenger) => [
                    'passenger_id' => $passenger->id,
                    'place' => $passenger->pivot->place_from,
                ])->values(),
                'occupied_back' => $booking->passengers->filter(fn ($passenger) => isset($passenger->pivot->place_back))->map(fn ($passenger) => [
                    'passenger_id' => $passenger->id,
                    'place' => $passenger->pivot->place_back,
                ])->values(),
            ]
        ];
    }
}
