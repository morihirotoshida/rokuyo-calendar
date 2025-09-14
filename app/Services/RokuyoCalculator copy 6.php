<?php

namespace App\Services;

use Carbon\Carbon;
use Overtrue\ChineseCalendar\Calendar; // 専門ライブラリを使用

class RokuyoCalculator
{
    /**
     * @var array<string>
     */
    private $rokuyou = [
        '大安', // 0: Taian
        '赤口', // 1: Shakku
        '先勝', // 2: Sensho
        '友引', // 3: Tomobiki
        '先負', // 4: Senbu
        '仏滅'  // 5: Butsumetsu
    ];

    private $calendar;

    public function __construct()
    {
        // ★★【バグ修正】タイムゾーンを'Asia/Tokyo'に明示的に設定する ★★
        $this->calendar = new Calendar('Asia/Tokyo');
    }

    /**
     * 指定された日付の六曜を計算して返す
     * @param Carbon $date
     * @return string
     */
    public function getRokuyo(Carbon $date): string
    {
        // 専門ライブラリを使って、正確な旧暦を取得します
        $lunarDate = $this->calendar->solar($date->year, $date->month, $date->day);

        // (旧暦月 + 旧暦日) % 6 の公式で六曜を計算します
        $index = ($lunarDate['lunar_month'] + $lunarDate['lunar_day']) % 6;
        
        return $this->rokuyou[$index];
    }
}

