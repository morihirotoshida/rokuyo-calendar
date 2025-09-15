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
     * (キー: 旧暦月, 値: ROKUYOU_NAMESのインデックス)
     */
    private const LUNAR_MONTH_START_INDEX = [
        1 => 0, 7 => 0, // 1月, 7月は「先勝」から
        2 => 1, 8 => 1, // 2月, 8月は「友引」から
        3 => 2, 9 => 2, // 3月, 9月は「先負」から
        4 => 3, 10 => 3,// 4月, 10月は「仏滅」から
        5 => 4, 11 => 4,// 5月, 11月は「大安」から
        6 => 5, 12 => 5 // 6月, 12月は「赤口」から
    ];

    /**
     * 指定された日付の六曜を取得します。
     *
     * @param DateTime $date
     * @return string
     */
    public function getRokuyo(DateTime $date): string
    {
        $locale = 'ja_JP@calendar=chinese';

        $formatterMonth = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'Asia/Tokyo', IntlDateFormatter::TRADITIONAL, 'M');
        $lunarMonth = (int)$formatterMonth->format($date);

        $formatterDay = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'Asia/Tokyo', IntlDateFormatter::TRADITIONAL, 'd');
        $lunarDay = (int)$formatterDay->format($date);
        
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
     * 指定された日付の旧暦の「日」を取得します。(例: 15)
     *
     * @param DateTime $date
     * @return integer
     */
    public function getLunarDay(DateTime $date): int
    {
        $locale = 'ja_JP@calendar=chinese';
        $formatterDay = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            'Asia/Tokyo',
            IntlDateFormatter::TRADITIONAL,
            'd' // パターンを 'd' にして日のみを取得
        );
        return (int)$formatterDay->format($date);
    }
    // --- ▲▲▲ 新しい機能を追加 ▲▲▲ ---
}

