<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <style>
        /* --- ▼▼▼ CSS 変更箇所 ▼▼▼ --- */
        /* 日付番号と六曜を横並びにするための設定 */
        .fc .fc-daygrid-day-top {
            display: flex;
            flex-direction: row;
            align-items: center;
        }

        /* 六曜テキスト用のカスタムスタイル */
        .rokuyo-text {
            color: #888; /* 少し薄いグレー */
            font-size: 0.75em; /* 日付より少し小さく */
            margin-left: 5px; /* 日付番号との間隔 */
            font-weight: bold;
            /* z-indexは不要なため削除 */
        }
        /* --- ▲▲▲ CSS 変更箇所 ▲▲▲ --- */

        /* 仏滅の日を少し強調する */
        .fc-day-butsumetsu .rokuyo-text {
            color: #c95252;
        }

        /* 大安の日を少し強調する */
        .fc-day-taian .rokuyo-text {
            color: #527ac9;
        }

        /* 大安の日の背景色をごく薄い赤色にする */
        .fc-day-taian {
            background-color: #fff5f5 !important; /* ごく薄い赤色 (importantを追加) */
        }
    </style>
</head>
<body class="font-sans antialiased">
<div class="min-h-screen bg-gray-100">
    @include('layouts.navigation')

    <!-- Page Heading -->
    @if (isset($header))
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endif

    <!-- Page Content -->
    <main>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div id='calendar'></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Event Modal -->
<div id="event-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">予定の追加</h3>
            <div class="mt-2 px-7 py-3">
                <form id="event-form">
                    <input type="hidden" id="event-id" name="id">
                    <div class="mb-4">
                        <label for="event-name" class="block text-sm font-medium text-gray-700 text-left">イベント名</label>
                        <input type="text" id="event-name" name="event_name" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div class="mb-4">
                        <label for="start-time" class="block text-sm font-medium text-gray-700 text-left">開始日時</label>
                        <input type="datetime-local" id="start-time" name="start_time" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div class="mb-4">
                        <label for="end-time" class="block text-sm font-medium text-gray-700 text-left">終了日時</label>
                        <input type="datetime-local" id="end-time" name="end_time" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                </form>
            </div>
            <div class="items-center px-4 py-3">
                <button id="save-event-btn" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md w-auto shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">保存</button>
                <button id="delete-event-btn" class="hidden px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-auto shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">削除</button>
                <button id="cancel-btn" class="px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md w-auto shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200">キャンセル</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'ja',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            axios.get('/calendar/events', {
                params: {
                    start_date: fetchInfo.startStr,
                    end_date: fetchInfo.endStr
                }
            })
            .then(function(response) {
                successCallback(response.data);
            })
            .catch(function(error) {
                console.error("イベントの読み込みに失敗しました", error);
                failureCallback(error);
            });
        },
        
        // --- ▼▼▼ JavaScript 全面改修箇所 ▼▼▼ ---
        // 表示期間が変更された後（データ読み込み完了後）に実行される
        datesSet: function(dateInfo) {
            // 確実な実行のため、わずかな遅延を入れる
            setTimeout(() => this.addRokuyoDisplay(), 50); 
        },

        // 六曜の表示をDOMに書き込む専用の関数
        addRokuyoDisplay: function() {
            const allEvents = calendar.getEvents();
            const rokuyoEvents = allEvents.filter(e => e.extendedProps.is_rokuyo);
            if (rokuyoEvents.length === 0) return; // 六曜データがなければ何もしない

            // 高速に検索できるよう、日付をキーにしたマップを作成
            const rokuyoMap = new Map(rokuyoEvents.map(e => [e.startStr, e.title]));

            // 表示されている全ての日付セルをループ
            document.querySelectorAll('.fc-daygrid-day').forEach(dayEl => {
                const dateStr = dayEl.getAttribute('data-date');
                const dayTopEl = dayEl.querySelector('.fc-daygrid-day-top');
                if (!dateStr || !dayTopEl) return;
                
                // 既存の六曜テキストやクラスを一度リセット
                const existingRokuyo = dayTopEl.querySelector('.rokuyo-text');
                if (existingRokuyo) existingRokuyo.remove();
                dayEl.classList.remove('fc-day-taian', 'fc-day-butsumetsu');

                // マップから六曜を探す
                if (rokuyoMap.has(dateStr)) {
                    const rokuyoTitle = rokuyoMap.get(dateStr);
                    
                    // 六曜テキストのspan要素を作成して追加
                    const textSpan = document.createElement('span');
                    textSpan.className = 'rokuyo-text';
                    textSpan.innerText = rokuyoTitle;
                    dayTopEl.appendChild(textSpan);
                    
                    // 対応するクラスを日付セルに付与
                    if (rokuyoTitle === '大安') {
                        dayEl.classList.add('fc-day-taian');
                    } else if (rokuyoTitle === '仏滅') {
                        dayEl.classList.add('fc-day-butsumetsu');
                    }
                }
            });
        },
        // --- ▲▲▲ JavaScript 全面改修箇所 ▲▲▲ ---

        // 六曜イベント自体は予定欄には表示しないようにする
        eventContent: function(arg) {
            return !arg.event.extendedProps.is_rokuyo;
        },

        eventClick: function(info) {
            if (info.event.extendedProps.is_rokuyo) {
                info.jsEvent.preventDefault();
                return;
            }
            document.getElementById('event-id').value = info.event.id;
            document.getElementById('event-name').value = info.event.title;
            document.getElementById('start-time').value = info.event.start.toISOString().slice(0, 16);
            document.getElementById('end-time').value = info.event.end ? info.event.end.toISOString().slice(0, 16) : '';
            document.getElementById('event-modal').classList.remove('hidden');
            document.getElementById('delete-event-btn').classList.remove('hidden');
            document.getElementById('modal-title').innerText = '予定の編集';
        },

        dateClick: function(info) {
            document.getElementById('event-form').reset();
            document.getElementById('event-id').value = '';
            document.getElementById('start-time').value = info.dateStr + 'T09:00';
            document.getElementById('end-time').value = info.dateStr + 'T10:00';
            document.getElementById('event-modal').classList.remove('hidden');
            document.getElementById('delete-event-btn').classList.add('hidden');
            document.getElementById('modal-title').innerText = '予定の追加';
        },
    });

    calendar.render();

    // モーダル関連の処理（変更なし）
    const eventModal = document.getElementById('event-modal');
    const saveEventBtn = document.getElementById('save-event-btn');
    const deleteEventBtn = document.getElementById('delete-event-btn');
    const cancelBtn = document.getElementById('cancel-btn');

    function handleApiResponse(response) {
        calendar.refetchEvents();
        eventModal.classList.add('hidden');
    }

    function handleError(error) {
        console.error('API Error:', error);
        alert('エラーが発生しました。コンソールを確認してください。');
    }

    saveEventBtn.addEventListener('click', function() {
        const eventId = document.getElementById('event-id').value;
        const url = eventId ? `/calendar/events/${eventId}` : '/calendar/events';
        const method = eventId ? 'put' : 'post';
        const eventData = {
            event_name: document.getElementById('event-name').value,
            start_time: document.getElementById('start-time').value,
            end_time: document.getElementById('end-time').value,
            _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        };
        axios[method](url, eventData).then(handleApiResponse).catch(handleError);
    });

    deleteEventBtn.addEventListener('click', function() {
        const eventId = document.getElementById('event-id').value;
        if (eventId && confirm('この予定を削除してもよろしいですか？')) {
            axios.delete(`/calendar/events/${eventId}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            }).then(handleApiResponse).catch(handleError);
        }
    });

    cancelBtn.addEventListener('click', function() {
        eventModal.classList.add('hidden');
    });
});
</script>

</body>
</html>