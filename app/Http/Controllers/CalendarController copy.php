<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Services\RokuyoCalculator;
use Illuminate\Support\Facades\Log; // エラーログのために追加

class CalendarController extends Controller
{
    public function index()
    {
        return view('calendar');
    }

    public function getEvents(Request $request, RokuyoCalculator $rokuyoCalculator)
    {
        $start = Carbon::parse($request->input('start'))->startOfDay();
        $end = Carbon::parse($request->input('end'))->endOfDay();
        $user = $request->user();

        // ユーザーの予約を取得
        $userEvents = Reservation::where('user_id', $user->id)
            ->where(function($query) use ($start, $end) {
                $query->where('start', '<=', $end)
                      ->where('end', '>=', $start);
            })
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $event->start->toIso8601String(),
                    'end' => $event->end->toIso8601String(),
                ];
            });

        // ★★ 改善: 六曜イベントの生成をより堅牢に ★★
        $period = CarbonPeriod::create($start, $end);
        $rokuyoEvents = collect();
        foreach ($period as $date) {
            try {
                $rokuyoName = $rokuyoCalculator->getRokuyo($date);

                // 計算結果が空でなければイベントを追加
                if (!empty($rokuyoName)) {
                    $rokuyoEvents->push([
                        'title' => $rokuyoName,
                        'start' => $date->format('Y-m-d'),
                        'display' => 'none',
                        'extendedProps' => ['is_rokuyo' => true]
                    ]);
                }
            } catch (\Exception $e) {
                // 特定の日で六曜の計算に失敗しても、他の日の処理を続行します。
                // エラーをログに記録しておくと、将来のデバッグに役立ちます。
                Log::error("六曜の計算に失敗: " . $date->format('Y-m-d') . " - " . $e->getMessage());
                continue; // 次の日のループに進む
            }
        }
        
        // 全てのイベントを結合して返す
        $events = collect()->merge($userEvents)->merge($rokuyoEvents);
        return response()->json($events);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);
        
        $reservation = new Reservation();
        $reservation->user_id = $request->user()->id;
        $reservation->title = $request->input('title');
        // ブラウザから送られた時刻を日本時間として解釈し、DB保存用にUTCに変換
        $reservation->start = Carbon::parse($request->input('start'), 'Asia/Tokyo')->setTimezone('UTC');
        $reservation->end = Carbon::parse($request->input('end'), 'Asia/Tokyo')->setTimezone('UTC');
        $reservation->save();
        
        return response()->json($reservation);
    }

    public function update(Request $request, Reservation $reservation)
    {
        // 認可: ログインユーザーが予約の所有者であるか確認
        if ($request->user()->id !== $reservation->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);
        
        $reservation->title = $request->input('title');
        $reservation->start = Carbon::parse($request->input('start'), 'Asia/Tokyo')->setTimezone('UTC');
        $reservation->end = Carbon::parse($request->input('end'), 'Asia/Tokyo')->setTimezone('UTC');
        $reservation->save();

        return response()->json($reservation);
    }

    public function destroy(Request $request, Reservation $reservation)
    {
        // 認可
        if ($request->user()->id !== $reservation->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $reservation->delete();
        
        return response()->json(['message' => 'Deleted successfully']);
    }
}

