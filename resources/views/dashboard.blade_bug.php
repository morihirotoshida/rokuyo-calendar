<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>カレンダー</title>
    {{-- Tailwind CSSを直接読み込みます --}}
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- CSSを直接書きます --}}
    <style>
        /* 日付マスの親要素（td）を基準点にする */
        .calendar-cell {
            position: relative;
            vertical-align: top; /* 数字などを上揃えに */
        }
        /* 旧暦の表示位置とスタイルを指定 */
        .kyureki {
            position: absolute; /* 親要素を基準に絶対位置を指定 */
            bottom: 5px;        /* 下から5px */
            left: 5px;          /* 左から5px */
            font-size: 12px;    /* 文字を少し小さく */
            color: #555;        /* 文字色を少し薄く */
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto p-4">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">

                <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">
                    {{ $currentDate->format('Y年 n月') }}
                </h2>

                {{-- 前月・次月へのナビゲーション --}}
                <div class="flex justify-between mb-4">
                    <a href="{{ url('/dashboard?month=' . $currentDate->copy()->subMonth()->format('Y-m')) }}" class="px-4 py-2 bg-gray-200 rounded">&lt; 前月</a>
                    <a href="{{ url('/dashboard?month=' . $currentDate->copy()->addMonth()->format('Y-m')) }}" class="px-4 py-2 bg-gray-200 rounded">次月 &gt;</a>
                </div>

                {{-- カレンダー本体 --}}
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="py-2 border">月</th>
                            <th class="py-2 border">火</th>
                            <th class="py-2 border">水</th>
                            <th class="py-2 border">木</th>
                            <th class="py-2 border">金</th>
                            <th class="py-2 border text-blue-600">土</th>
                            <th class="py-2 border text-red-600">日</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (array_chunk($calendarData, 7) as $week)
                            <tr class="border">
                                @foreach ($week as $day)
                                    <td class="px-2 py-2 h-24 border calendar-cell">
                                        @if ($day)
                                            <div class="text-lg font-bold">{{ $day['day'] }}</div>
                                            <div class="text-sm text-center">{{ $day['rokuyo'] }}</div>
                                            <div class="kyureki">{{ $day['kyureki_date'] }}</div>
                                        @endif
                                    </td>
                                @endforeach
                                @if (count($week) < 7)
                                    @for ($i = 0; $i < 7 - count($week); $i++)
                                        <td class="px-2 py-2 h-24 border calendar-cell"></td>
                                    @endfor
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        </div>
    </div>

</body>
</html>