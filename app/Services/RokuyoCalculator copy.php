<?php

namespace App\Services;

use DateTime;

/**
 * 六曜（ろくよう）を計算するクラス
 * 旧暦の日付を元に算出します。
 */
class RokuyoCalculator
{
    // 旧暦の月と日から六曜を求める
    // 0:先勝, 1:友引, 2:先負, 3:仏滅, 4:大安, 5:赤口
    private const ROKUYOU_NAMES = ['赤口', '先勝', '友引', '先負', '仏滅', '大安'];

public function getRokuyo(Carbon $date): string
{
    // 専門ライブラリを使って、新暦を旧暦に変換します
    $calendar = new Calendar();
    $lunarDate = $calendar->solar($date->year, $date->month, $date->day);

    // ★★ ご指摘の通りの計算式がここにあります ★★
    // (旧暦月 + 旧暦日) % 6 の公式で六曜を計算します
    $index = ($lunarDate['lunar_month'] + $lunarDate['lunar_day']) % 6;

    // 計算結果の数字（0〜5）を、対応する六曜の名前に変換して返します
    return $this->rokuyou[$index];
}
}

