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
        // 旧暦計算には、日本の旧暦のベースである 'chinese' カレンダーを使用するのが最も信頼性が高いです。
        $locale = 'ja_JP@calendar=chinese';

        // --- 月の取得 ---
        $formatterMonth = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            'Asia/Tokyo',
            IntlDateFormatter::TRADITIONAL,
            'M' // 月のみを取得
        );
        $lunarMonth = (int)$formatterMonth->format($date);

        // --- 日の取得 ---
        $formatterDay = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            'Asia/Tokyo',
            IntlDateFormatter::TRADITIONAL,
            'd' // 日のみを取得
        );
        $lunarDay = (int)$formatterDay->format($date);
        
        // 旧暦の月が存在しない、または範囲外の場合は計算不能
        if (!isset(self::LUNAR_MONTH_START_INDEX[$lunarMonth])) {
            // 稀なケース（閏月など）で予期せぬ値が返ってきた場合のフォールバック
            return ''; 
        }

        // 1. その月の1日の六曜のインデックスを取得
        $startIndex = self::LUNAR_MONTH_START_INDEX[$lunarMonth];

        // 2. 1日からの差分を計算
        $dayOffset = $lunarDay - 1;

        // 3. (1日のインデックス + 差分) を6で割った余りが、その日の六曜のインデックスとなる
        $rokuyoIndex = ($startIndex + $dayOffset) % 6;

        return self::ROKUYOU_NAMES[$rokuyoIndex];
    }
}

