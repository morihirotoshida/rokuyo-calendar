<?php

// Laravelアプリケーションのブートストラップ
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 正しいRokuyoCalculatorクラスを使用
use App\Services\RokuyoCalculator;

// =======================================================
// 六曜計算ロジック テストスクリプト
// =======================================================
echo "【最終版】六曜計算ロジックのテストを開始します...\n\n";

// テスト対象のRokuyoCalculatorクラスのインスタンスを作成
$calculator = new RokuyoCalculator();

// テストしたい年
$year = 2025;
echo "{$year}年の六曜計算結果:\n";
echo "=====================================\n";

// 1月1日から12月31日までループ
$startDate = new DateTime("{$year}-01-01");
$endDate = new DateTime("{$year}-12-31");

$currentDate = clone $startDate;

while ($currentDate <= $endDate) {
    // 六曜を取得
    $rokuyo = $calculator->getRokuyo($currentDate);

    // 結果を出力
    // 例: 2025年01月01日 (Wed) : 先勝
    echo $currentDate->format('Y年m月d日 (D)') . " : " . $rokuyo . "\n";

    // 1日進める
    $currentDate->modify('+1 day');
}

echo "=====================================\n";
echo "テストが完了しました。\n";
