<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\RokuyoCalculator;

class CalendarController extends Controller
{
    private $rokuyoCalculator;

    /**
     * コンストラクタでRokuyoCalculatorを注入
     */
    public function __construct(RokuyoCalculator $rokuyoCalculator)
    {
        $this->rokuyoCalculator = $rokuyoCalculator;
    }

    /**
     * カレンダービューを表示
     */
    public function index()
    {
        return view('calendar');
    }

    /**
     * カレンダーに表示するイベント（予約と六曜）を取得
     */
    public function getEvents(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $start_date = Carbon::parse($request->input('start_date'))->startOfDay();
        $end_date = Carbon::parse($request->input('end_date'))->endOfDay();

        // ユーザーの予約イベントを取得
        $reservations = Reservation::where('user_id', $request->user()->id)
            ->whereBetween('start_time', [$start_date, $end_date])
            ->get()
            ->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'title' => $reservation->event_name,
                    'start' => $reservation->start_time,
                    'end' => $reservation->end_time,
                    'color' => '#3498db',
                    'extendedProps' => [
                        'is_rokuyo' => false
                    ]
                ];
            });

        // 六曜イベントを生成
        $rokuyoEvents = [];
        $currentDate = $start_date->copy();
        while ($currentDate <= $end_date) {
            $rokuyoName = $this->rokuyoCalculator->getRokuyo($currentDate);
            $rokuyoEvents[] = [
                'id' => 'rokuyo-' . $currentDate->toDateString(),
                'title' => $rokuyoName,
                'start' => $currentDate->toDateString(),
                'allDay' => true,
                'extendedProps' => [
                    'is_rokuyo' => true
                ]
            ];
            $currentDate->addDay();
        }

        // 予約イベントと六曜イベントをマージ
        $events = $reservations->concat($rokuyoEvents);

        return response()->json($events);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'event_name' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after_or_equal:start_time'
        ]);

        $reservation = new Reservation();
        $reservation->user_id = $request->user()->id;
        $reservation->event_name = $request->input('event_name');
        $reservation->start_time = Carbon::parse($request->input('start_time'), 'Asia/Tokyo')->utc();
        $reservation->end_time = Carbon::parse($request->input('end_time'), 'Asia/Tokyo')->utc();
        $reservation->save();

        return response()->json([
            'id' => $reservation->id,
            'title' => $reservation->event_name,
            'start' => $reservation->start_time,
            'end' => $reservation->end_time,
            'color' => '#3498db',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $reservation = Reservation::where('user_id', $request->user()->id)->findOrFail($id);

        $request->validate([
            'event_name' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after_or_equal:start_time'
        ]);

        $reservation->event_name = $request->input('event_name');
        $reservation->start_time = Carbon::parse($request->input('start_time'), 'Asia/Tokyo')->utc();
        $reservation->end_time = Carbon::parse($request->input('end_time'), 'Asia/Tokyo')->utc();
        $reservation->save();

        return response()->json([
            'id' => $reservation->id,
            'title' => $reservation->event_name,
            'start' => $reservation->start_time,
            'end' => $reservation->end_time,
            'color' => '#3498db',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $reservation = Reservation::where('user_id', $request->user()->id)->findOrFail($id);
        $reservation->delete();

        return response()->json(['message' => 'Event deleted successfully.']);
    }
}

