<?php

namespace App\Services;

use Carbon\Carbon;
use Overtrue\ChineseCalendar\Calendar;

trait CalendarServiceTrait
{
    /**
     * 指定された年月のカレンダーデータを生成する
     *
     * @param Carbon $date
     * @return array
     */
    protected function generateCalendarData(Carbon $date): array
    {
        date_default_timezone_set('Asia/Tokyo');
        // 旧暦計算ライブラリのインスタンスを作成
        $lunarCalendar = new Calendar();
        
        // 六曜のリストを定義
        $rokuyoList = ['大安', '赤口', '先勝', '友引', '先負', '仏滅'];

        $year = $date->year;
        $month = $date->month;
        
        $calendarData = [];
        $firstDayOfMonth = Carbon::create($year, $month, 1);
        $lastDayOfMonth = $firstDayOfMonth->copy()->endOfMonth();

        // 月曜始まりの場合の空白マスを追加
        for ($i = 0; $i < $firstDayOfMonth->dayOfWeekIso -1; $i++) {
            $calendarData[] = null;
        }

        // 1日から末日までループ
        for ($day = $firstDayOfMonth->copy(); $day->lte($lastDayOfMonth); $day->addDay()) {
            
            // 旧暦の情報を取得
            $lunarData = $lunarCalendar->solar($day->year, $day->month, $day->day);

            // 六曜を計算
            $rokuyoIndex = ($lunarData['lunar_month'] + $lunarData['lunar_day']) % 6;
            
            // ビューに渡すデータを配列に追加
            $calendarData[] = [
                'day' => $day->day,
                'rokuyo' => $rokuyoList[$rokuyoIndex],
                'kyureki_date' => $lunarData['lunar_month_name'] . $lunarData['lunar_day_name']
            ];
        }

        return $calendarData;
    }
}