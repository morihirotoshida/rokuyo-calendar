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
        /* 日付番号と六曜を横並びにするための設定 */
        .fc .fc-daygrid-day-top {
            display: flex;
            flex-direction: row;
            align-items: center;
        }

        /* 六曜テキスト用のカスタムスタイル */
        .rokuyo-text {
            color: #888;
            font-size: 0.75em;
            margin-left: 5px;
            font-weight: bold;
        }

        /* 仏滅の日を少し強調する */
        .fc-day-butsumetsu .rokuyo-text {
            color: #c95252;
        }

        /* 大安の日を少し強調する */
        .fc-day-taian .rokuyo-text {
            color: #527ac9;
        }
        
        /* 1. 基本となる曜日の色 */
        .fc-day-sat {
            background-color: #f0f8ff; /* 土曜日: ごく薄い青色 */
        }
        .fc-day-sun {
            background-color: #fff0f0; /* 日曜日: ごく薄い赤色 */
        }

        /* 2. 「今日」の色（曜日よりも優先） */
        .fc-day-today {
            background-color: #fffde7 !important; /* ごく薄い黄色 */
        }

        /* 3. 「大安」の色（「今日」や曜日よりも最優先） */
        .fc-day-taian {
            background-color: #ffebee !important; /* 大安専用の、より分かりやすい薄赤色 */
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
<!-- --- ▼▼▼ ここに z-50 を追加しました ▼▼▼ --- -->
<div id="event-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
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
    // DOM要素の取得は一度だけ行う
    const calendarEl = document.getElementById('calendar');
    const eventModal = document.getElementById('event-modal');
    const saveEventBtn = document.getElementById('save-event-btn');
    const deleteEventBtn = document.getElementById('delete-event-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const eventIdInput = document.getElementById('event-id');
    const eventNameInput = document.getElementById('event-name');
    const startTimeInput = document.getElementById('start-time');
    const endTimeInput = document.getElementById('end-time');
    const modalTitle = document.getElementById('modal-title');
    const eventForm = document.getElementById('event-form');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const calendar = new FullCalendar.Calendar(calendarEl, {
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
            .then(response => successCallback(response.data))
            .catch(error => {
                console.error("イベントの読み込みに失敗しました", error);
                failureCallback(error);
            });
        },
        dayCellContent: function(arg) {
            let allEvents = calendar.getEvents();
            let dateStr = arg.date.toISOString().slice(0, 10);
            let rokuyoEvent = allEvents.find(event =>
                event.startStr === dateStr && event.extendedProps.is_rokuyo
            );

            if (rokuyoEvent) {
                return { html: `<a class="fc-daygrid-day-number">${arg.dayNumberText}</a><span class="rokuyo-text">${rokuyoEvent.title}</span>` };
            }
            return { html: `<a class="fc-daygrid-day-number">${arg.dayNumberText}</a>` };
        },
        datesSet: function(dateInfo) {
            setTimeout(() => {
                let allEvents = calendar.getEvents();
                document.querySelectorAll('.fc-daygrid-day').forEach(dayEl => {
                    const dateStr = dayEl.getAttribute('data-date');
                    if (!dateStr) return;

                    dayEl.classList.remove('fc-day-taian', 'fc-day-butsumetsu');

                    const rokuyoEvent = allEvents.find(event =>
                        event.startStr === dateStr && event.extendedProps.is_rokuyo
                    );

                    if (rokuyoEvent) {
                        if (rokuyoEvent.title === '大安') {
                            dayEl.classList.add('fc-day-taian');
                        } else if (rokuyoEvent.title === '仏滅') {
                            dayEl.classList.add('fc-day-butsumetsu');
                        }
                    }
                });
            }, 100);
        },
        eventContent: function(arg) {
            return !arg.event.extendedProps.is_rokuyo;
        },
        eventClick: function(info) {
            if (info.event.extendedProps.is_rokuyo) {
                info.jsEvent.preventDefault();
                return;
            }
            
            eventIdInput.value = info.event.id;
            eventNameInput.value = info.event.title;
            startTimeInput.value = info.event.start.toISOString().slice(0, 16);
            endTimeInput.value = info.event.end ? info.event.end.toISOString().slice(0, 16) : '';
            
            modalTitle.innerText = '予定の編集';
            deleteEventBtn.classList.remove('hidden');
            eventModal.classList.remove('hidden');
        },
        dateClick: function(info) {
            eventForm.reset();
            eventIdInput.value = '';
            startTimeInput.value = `${info.dateStr}T09:00`;
            endTimeInput.value = `${info.dateStr}T10:00`;
            
            modalTitle.innerText = '予定の追加';
            deleteEventBtn.classList.add('hidden');
            eventModal.classList.remove('hidden');
        },
    });

    calendar.render();

    function handleApiResponse(response) {
        calendar.refetchEvents();
        eventModal.classList.add('hidden');
    }

    function handleError(error) {
        console.error('API Error:', error);
        const errorMessage = error.response?.data?.message || 'エラーが発生しました。コンソールを確認してください。';
        alert(errorMessage);
    }

    saveEventBtn.addEventListener('click', function() {
        const eventId = eventIdInput.value;
        const url = eventId ? `/calendar/events/${eventId}` : '/calendar/events';
        const method = eventId ? 'put' : 'post';
        
        const eventData = {
            event_name: eventNameInput.value,
            start_time: startTimeInput.value,
            end_time: endTimeInput.value,
            _token: csrfToken
        };

        axios({ method, url, data: eventData })
            .then(handleApiResponse)
            .catch(handleError);
    });

    deleteEventBtn.addEventListener('click', function() {
        const eventId = eventIdInput.value;
        if (eventId && confirm('この予定を削除してもよろしいですか？')) {
            axios.delete(`/calendar/events/${eventId}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken }
            })
            .then(handleApiResponse)
            .catch(handleError);
        }
    });

    cancelBtn.addEventListener('click', function() {
        eventModal.classList.add('hidden');
    });
});
</script>

</body>
</html>

