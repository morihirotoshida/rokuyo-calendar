<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Services\RokuyoCalculator; // 六曜計算クラスをインポート
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Overtrue\ChineseCalendar\Calendar;
use App\Services\CalendarServiceTrait;

class CalendarController extends Controller
{

    use CalendarServiceTrait; // ← 【2】この行を追加

    /**
     * ログイン後のダッシュボード用カレンダー
     */
    public function index(Request $request)
    {
        // --- ここから追加 ---
        try {
            $date = Carbon::parse($request->input('month', 'now'));
        } catch (\Exception $e) {
            $date = Carbon::now();
        }
        
        // Traitのメソッドを呼び出してカレンダーデータを生成
        $calendarData = $this->generateCalendarData($date);
        // --- ここまで追加 ---

        // 存在しない$eventsの行を削除し、必要な変数だけを渡す
        return view('dashboard', [
            'calendarData' => $calendarData,
            'currentDate' => $date,
        ]);
    }

    /**
     * カレンダービューを表示
     */
    // public function index()
    // {
    //     return view('calendar');
    // }

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

    /**
    * ChineseCalendar
    */
    
    public function show(Request $request)
    {
        // 1. 表示する年月を取得
        // クエリ文字列（例: /calendar?month=2025-10）があればその年月を、なければ今月を表示
        try {
            $date = Carbon::parse($request->input('month', 'now'));
        } catch (\Exception $e) {
            $date = Carbon::now();
        }
        $year = $date->year;
        $month = $date->month;

        // 2. 旧暦計算ライブラリのインスタンスを作成
        $lunarCalendar = new Calendar();
        
        // 3. 六曜のリストを定義
        $rokuyoList = ['大安', '赤口', '先勝', '友引', '先負', '仏滅'];

        // 4. カレンダーの日付データを生成
        $calendarData = [];
        $firstDayOfMonth = Carbon::create($year, $month, 1); // 今月の1日
        $lastDayOfMonth = $firstDayOfMonth->copy()->endOfMonth(); // 今月の末日

        // 空白マスを追加（1日が月曜日でない場合）
        for ($i = 0; $i < $firstDayOfMonth->dayOfWeekIso -1; $i++) {
            $calendarData[] = null;
        }

        // 1日から末日までループ
        for ($day = $firstDayOfMonth->copy(); $day->lte($lastDayOfMonth); $day->addDay()) {
            
            // 旧暦の情報を取得
            $lunarData = $lunarCalendar->solar($day->year, $day->month, $day->day);

            // 六曜を計算 (旧暦の月と日の合計を6で割った余りで決まる)
            $rokuyoIndex = ($lunarData['lunar_month'] + $lunarData['lunar_day']) % 6;
            $rokuyo = $rokuyoList[$rokuyoIndex];

            // ビューに渡すデータを配列に追加
            $calendarData[] = [
                'day' => $day->day,
                'rokuyo' => $rokuyo,
                'kyureki_date' => $lunarData['lunar_month_name'] . $lunarData['lunar_day_name']
            ];
        }
        
        // 5. ビューに変数を渡して表示
        return view('calendar', [
            'calendarData' => $calendarData,
            'currentDate' => $date, // "2025年 9月" のような表示や、前月/次月リンクに使う
        ]);
    }
}

