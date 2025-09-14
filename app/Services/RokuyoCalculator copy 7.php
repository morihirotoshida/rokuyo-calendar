<?php

namespace App\Services;

use Carbon\Carbon;
use Overtrue\ChineseCalendar\Calendar;

class RokuyoCalculator
{
    private $calendar;
    private const ROKUYOU_NAMES = ['大安', '赤口', '先勝', '友引', '先負', '仏滅'];

    public function __construct()
    {
        $this->calendar = new Calendar();
    }

    public function getRokuyo(Carbon $date): string
    {
        $lunarDate = $this->calendar->solar($date->year, $date->month, $date->day);
        $index = ($lunarDate['lunar_month'] + $lunarDate['lunar_day']) % 6;

        return self::ROKUYOU_NAMES[$index];
    }
}