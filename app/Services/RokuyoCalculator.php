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

    public function getRokuyo(DateTime $date): string
    {
        // PHPの国際化機能(intl)を使って、日付を旧暦に変換
        $formatter = new \IntlDateFormatter(
            'ja_JP@calendar=japanese', // ロケールとカレンダーの種類
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            'Asia/Tokyo',
            \IntlDateFormatter::TRADITIONAL,
            'yyyy-MM-dd'
        );

        $lunarDateStr = $formatter->format($date);
        
        // 旧暦の日付を年月日に分割
        list(, $lunarMonth, $lunarDay) = explode('-', $lunarDateStr);

        // (旧暦の月 + 旧暦の日) を6で割った余りから、その日の六曜が決定される
        $rokuyoIndex = ((int)$lunarMonth + (int)$lunarDay) % 6;

        return self::ROKUYOU_NAMES[$rokuyoIndex];
    }
}

