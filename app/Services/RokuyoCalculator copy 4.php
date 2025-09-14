<?php

// Carbonライブラリなど、必要な部品を読み込みます
require __DIR__.'/vendor/autoload.php';

use Carbon\Carbon;
use Carbon\CarbonPeriod; // 期間を扱うために追加

/**
 * 外部ライブラリを使用しない、簡易的な六曜計算クラス
 *
 * @warning このクラスの旧暦計算は、ユリウス日を基準とした簡易的な近似計算です。
 * 天文学的な精度はなく、特に閏月（うるうづき）の扱いや、旧暦の正月周辺で
 * 公式な暦とは異なる結果を示す可能性があります。
 * 正確性が最優先される場合は、専門ライブラリの使用を強く推奨します。
 */
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

    /**
     * 指定された日付の六曜を計算して返す
     * @param Carbon $date
     * @return string
     */
    public function getRokuyo(Carbon $date): string
    {
        // 簡易的な計算で旧暦の日付を推定します
        list($lunarMonth, $lunarDay) = $this->getApproximateLunarDate($date);

        // (旧暦月 + 旧暦日) % 6 の公式で六曜を計算します
        $index = ($lunarMonth + $lunarDay) % 6;
        
        // ★★ ここから、ご提案いただいたカスタムロジックを修正 ★★

        // もし計算結果が「先勝」(index=2) だった場合の特別ルール
        if ($index === 2) {
            // そして、その日が旧暦の1月1日ではなかった場合
            if (!($lunarMonth === 1 && $lunarDay === 1)) {
                // 連続する友引を避けるため、結果を「先負」(index=4) に変更する
                $index = 4;
            }
        }

        return $this->rokuyou[$index];
    }

    /**
     * ユリウス日を用いて、新暦から簡易的な旧暦の日付を推定する
     * @param Carbon $date
     * @return array{int, int}
     */
    private function getApproximateLunarDate(Carbon $date): array
    {
        // ユリウス日を計算
        $y = $date->year;
        $m = $date->month;
        $d = $date->day;
        if ($m < 3) {
            $y--;
            $m += 12;
        }
        $jdn = floor(365.25 * ($y + 4716)) + floor(30.6001 * ($m + 1)) + $d - 1524.5;

        // 基準日からの経過日数 (基準: 1970年旧暦1月1日 = 新暦1970年2月6日)
        $referenceJdn = 2440623.5; 
        $days = $jdn - $referenceJdn;
        
        // 1朔望月(約29.53059日)で割る
        $monthsPassed = $days / 29.53059;
        
        // 旧暦の月を推定 (基準の月を1として加算)
        $lunarMonth = floor($monthsPassed) % 12 + 1;

        // 旧暦の日を推定
        $lunarDay = floor(fmod($days, 29.53059)) + 1;

        return [(int)$lunarMonth, (int)$lunarDay];
    }
}


// --- ここからテスト処理 ---

echo "六曜計算ロジックのテストを開始します...\n\n";

// 計算クラスのインスタンスを作成
$calculator = new RokuyoCalculator();

// ★★ 2025年1月1日から1ヶ月間の期間を設定 ★★
$startDate = Carbon::create(2025, 1, 1);
$endDate = $startDate->copy()->endOfMonth(); // 1月の末日まで
$period = CarbonPeriod::create($startDate, $endDate);

echo "{$startDate->format('Y年m月')}の六曜計算結果:\n";
echo "-------------------------------------\n";

// 期間内の各日付でループ処理
foreach ($period as $date) {
    // 六曜を計算
    $rokuyo = $calculator->getRokuyo($date);
    // 結果を表示
    echo "{$date->format('Y年m月d日 (D)')} : {$rokuyo}\n";
}

echo "-------------------------------------\n";
echo "\nテストを終了します。\n";

