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
    {{-- Axios is loaded via Vite in app.js, so the CDN script is removed to prevent conflicts --}}

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
    // DOM要素の取得
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
        // --- ▼▼▼ ロジックを簡素化・集約 ▼▼▼ ---
        dayCellContent: function(arg) {
            // 左上の日付と六曜の表示
            const rokuyoEvent = calendar.getEvents().find(e => e.startStr === arg.date.toISOString().slice(0,10) && e.extendedProps.is_rokuyo);
            if (rokuyoEvent) {
                return { html: `<a class="fc-daygrid-day-number">${arg.dayNumberText}</a><span class="rokuyo-text">${rokuyoEvent.title}</span>` };
            }
            return { html: `<a class="fc-daygrid-day-number">${arg.dayNumberText}</a>` };
        },
        eventsSet: function() {
            // この関数はイベントが全て読み込まれた後に実行されるので、DOM操作に最も安全です
            document.querySelectorAll('.fc-daygrid-day').forEach(dayEl => {
                const dateStr = dayEl.getAttribute('data-date');
                if (!dateStr) return;

                // 描画の前に、前回の状態をクリーンアップ
                dayEl.classList.remove('fc-day-taian', 'fc-day-butsumetsu');
                const existingBottomEl = dayEl.querySelector('.day-grid-bottom');
                if (existingBottomEl) {
                    existingBottomEl.remove();
                }

                // 該当日の六曜イベントを探す
                const rokuyoEvent = calendar.getEvents().find(e => e.startStr === dateStr && e.extendedProps.is_rokuyo);

                if (rokuyoEvent) {
                    // 背景色を適用
                    if (rokuyoEvent.title === '大安') dayEl.classList.add('fc-day-taian');
                    if (rokuyoEvent.title === '仏滅') dayEl.classList.add('fc-day-butsumetsu');

                    // 左下の要素を作成して追加
                    const frameEl = dayEl.querySelector('.fc-daygrid-day-frame');
                    if (frameEl) {
                        const bottomEl = document.createElement('div');
                        bottomEl.classList.add('day-grid-bottom');
                        const dayNumber = dayEl.querySelector('.fc-daygrid-day-number').innerText;
                        
                        bottomEl.innerHTML = `<span>${dayNumber}</span><span class="rokuyo-text">${rokuyoEvent.title}</span>`;
                        frameEl.appendChild(bottomEl);
                    }
                }
            });
        },
        // --- ▲▲▲ ロジックを簡素化・集約 ▲▲▲ ---
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

