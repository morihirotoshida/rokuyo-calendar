<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Services\RokuyoCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    protected $rokuyoCalculator;

    public function __construct(RokuyoCalculator $rokuyoCalculator)
    {
        $this->rokuyoCalculator = $rokuyoCalculator;
    }

    public function index()
    {
        return view('calendar');
    }

    public function getEvents(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $start_date = Carbon::parse($request->input('start_date'), config('app.timezone'))->startOfDay();
        $end_date = Carbon::parse($request->input('end_date'), config('app.timezone'))->endOfDay();

        // 予定の取得はコメントアウトされたまま
        $events = [];
        
        $rokuyo_events = [];
        for ($date = $start_date->copy(); $date->lte($end_date); $date->addDay()) {
            $rokuyo = $this->rokuyoCalculator->getRokuyo($date);
            $formattedLunarDate = $this->rokuyoCalculator->getFormattedLunarDate($date);

            $rokuyo_events[] = [
                'id' => 'rokuyo_' . $date->format('Y-m-d'),
                'title' => $rokuyo,
                'start' => $date->format('Y-m-d'),
                'is_rokuyo' => true,
                'formatted_lunar_date' => $formattedLunarDate,
                // --- ▼▼▼ この行を追加しました ▼▼▼ ---
                'className' => 'rokuyo-event-hidden'
            ];
        }

        return response()->json(array_merge($events, $rokuyo_events));
    }

    // (store, update, destroy メソッドは変更ありません)
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'event_name' => 'required|string|max:255',
            'start_time' => 'required|date_format:Y-m-d\TH:i',
            'end_time' => 'nullable|date_format:Y-m-d\TH:i|after_or_equal:start_time',
        ]);
        
        $validatedData['user_id'] = Auth::id();

        try {
            $reservation = Reservation::create($validatedData);
            return response()->json($reservation, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error occurred.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $reservation = Reservation::where('id', $id)->where('user_id', Auth::id())->first();
        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found.'], 404);
        }
        $validatedData = $request->validate([
            'event_name' => 'required|string|max:255',
            'start_time' => 'required|date_format:Y-m-d\TH:i',
            'end_time' => 'nullable|date_format:Y-m-d\TH:i|after_or_equal:start_time',
        ]);
        try {
            $reservation->update($validatedData);
            return response()->json($reservation);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error occurred.'], 500);
        }
    }

    public function destroy($id)
    {
        $reservation = Reservation::where('id', $id)->where('user_id', Auth::id())->first();
        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found.'], 404);
        }
        $reservation->delete();
        return response()->json(['message' => 'Reservation deleted.']);
    }
}

