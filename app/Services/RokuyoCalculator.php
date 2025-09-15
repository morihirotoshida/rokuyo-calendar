<?php

namespace App\Services;

use DateTime;
use IntlDateFormatter;

/**
 * 六曜（ろくよう）を計算するクラス
 * 旧暦の日付を元に、伝統的なルールに従って算出します。
 */
class RokuyoCalculator
{
    /**
     * 六曜の正しい順序を定義
     * 0:先勝, 1:友引, 2:先負, 3:仏滅, 4:大安, 5:赤口
     */
    private const ROKUYOU_NAMES = ['先勝', '友引', '先負', '仏滅', '大安', '赤口'];

    /**
     * 旧暦の各月の1日がどの六曜から始まるかを定義
     */
    private const LUNAR_MONTH_START_INDEX = [
        1 => 0, 7 => 0, 2 => 1, 8 => 1, 3 => 2, 9 => 2,
        4 => 3, 10 => 3, 5 => 4, 11 => 4, 6 => 5, 12 => 5
    ];

    /**
     * 指定された日付の六曜を取得します。
     *
     * @param DateTime $date
     * @return string
     */
    public function getRokuyo(DateTime $date): string
    {
        list($lunarMonth, $lunarDay) = $this->getLunarMonthAndDay($date);
        
        if (!isset(self::LUNAR_MONTH_START_INDEX[$lunarMonth])) {
            return ''; 
        }

        $startIndex = self::LUNAR_MONTH_START_INDEX[$lunarMonth];
        $dayOffset = $lunarDay - 1;
        $rokuyoIndex = ($startIndex + $dayOffset) % 6;

        return self::ROKUYOU_NAMES[$rokuyoIndex];
    }

    // --- ▼▼▼ 新しい機能を追加 ▼▼▼ ---
    /**
     * 指定された日付の旧暦の月と日を配列で取得します。
     *
     * @param DateTime $date
     * @return array
     */
    private function getLunarMonthAndDay(DateTime $date): array
    {
        $locale = 'ja_JP@calendar=chinese';
        $formatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'Asia/Tokyo', IntlDateFormatter::TRADITIONAL, 'M-d');
        list($month, $day) = explode('-', $formatter->format($date));
        return [(int)$month, (int)$day];
    }

    /**
     * 指定された日付の旧暦を "月/日" 形式の文字列で取得します。(例: "8/1")
     *
     * @param DateTime $date
     * @return string
     */
    public function getFormattedLunarDate(DateTime $date): string
    {
        list($month, $day) = $this->getLunarMonthAndDay($date);
        return "{$month}/{$day}";
    }
    // --- ▲▲▲ 新しい機能を追加 ▲▲▲ ---
}

