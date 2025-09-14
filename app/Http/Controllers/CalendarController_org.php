<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Services\RokuyoCalculator; // 六曜計算クラスをインポート
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class CalendarController extends Controller
{
    /**
     * カレンダービューを表示
     */
    public function index()
    {
        return view('calendar');
    }

    /**
     * FullCalendarに表示するイベントデータを取得
     */
    public function getEvents(Request $request)
    {
        // バリデーション
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
        ]);

        $start = Carbon::parse($request->input('start'))->startOfDay();
        $end = Carbon::parse($request->input('end'))->endOfDay();

        // 1. ユーザー自身の予約を取得
        $userReservations = Reservation::where('user_id', auth()->id())
            ->where(function ($query) use ($start, $end) {
                $query->where('start', '<', $end)
                      ->where('end', '>', $start);
            })
            ->get()
            ->map(function (Reservation $reservation) {
                return [
                    'id' => $reservation->id,
                    'title' => $reservation->title,
                    'start' => $reservation->start->toIso8601String(),
                    'end' => $reservation->end->toIso8601String(),
                ];
            });

        // 2. 六曜イベントを生成
        $rokuyoEvents = collect();
        $period = CarbonPeriod::create($start, $end);
        $rokuyoCalculator = new RokuyoCalculator();
        foreach ($period as $date) {
            $rokuyoName = $rokuyoCalculator->getRokuyo($date);
            $rokuyoEvents->push([
                'id' => 'rokuyo_' . $date->format('Y-m-d'),
                'title' => $rokuyoName,
                'start' => $date->format('Y-m-d'),
                'display' => 'none',
                'extendedProps' => [
                    'is_rokuyo' => true,
                    'rokuyo_name' => $rokuyoName
                ]
            ]);
        }

        // ★★ 「仕事の予定」を生成していたモジュールを削除しました ★★

        // ユーザーの予約と六曜イベントを結合して返す
        return response()->json($userReservations->merge($rokuyoEvents));
    }

    /**
     * 新規予約を保存
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $reservation = new Reservation();
        $reservation->user_id = auth()->id();
        $reservation->title = $request->input('title');
        // ブラウザから送られた時刻を日本時間として解釈し、DB保存のためにUTCに変換
        $reservation->start = Carbon::parse($request->input('start'), 'Asia/Tokyo')->utc();
        $reservation->end = Carbon::parse($request->input('end'), 'Asia/Tokyo')->utc();
        $reservation->save();

        return response()->json($reservation);
    }

    /**
     * 予約を更新
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $reservation = Reservation::where('id', $id)->where('user_id', auth()->id())->firstOrFail();
        $reservation->title = $request->input('title');
        // ブラウザから送られた時刻を日本時間として解釈し、DB保存のためにUTCに変換
        $reservation->start = Carbon::parse($request->input('start'), 'Asia/Tokyo')->utc();
        $reservation->end = Carbon::parse($request->input('end'), 'Asia/Tokyo')->utc();
        $reservation->save();

        return response()->json($reservation);
    }

    /**
     * 予約を削除
     */
    public function destroy($id)
    {
        $reservation = Reservation::where('id', $id)->where('user_id', auth()->id())->firstOrFail();
        $reservation->delete();

        return response()->json(['status' => 'success']);
    }
}

