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
    {{-- axiosはLaravelの標準機能に含まれているため、個別の読み込みは不要 --}}

</head>
<body class="font-sans antialiased">
<div class="min-h-screen bg-gray-100">
    @include('layouts.navigation')

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
    // ... (他のDOM要素取得は省略) ...
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
        // dayCellContentは日付番号の表示のみに専念させ、処理の競合を防ぎます。
        dayCellContent: function(arg) {
            return { html: `<a class="fc-daygrid-day-number">${arg.dayNumberText}</a>` };
        },

        // eventsSetに、六曜に関する全ての表示処理を集約します。
        // これが最も安全で確実な方法です。
        eventsSet: function() {
            document.querySelectorAll('.fc-daygrid-day').forEach(dayEl => {
                const dateStr = dayEl.getAttribute('data-date');
                if (!dateStr) return;

                // 既存の表示を一度リセット
                dayEl.classList.remove('fc-day-taian', 'fc-day-butsumetsu');
                const existingTopRokuyo = dayEl.querySelector('.rokuyo-text');
                if (existingTopRokuyo) existingTopRokuyo.remove();
                const existingBottomEl = dayEl.querySelector('.day-grid-bottom');
                if (existingBottomEl) existingBottomEl.remove();

                // 対応する六曜イベントを探す
                const rokuyoEvent = calendar.getEvents().find(e => e.startStr === dateStr && e.extendedProps.is_rokuyo);

                if (rokuyoEvent) {
                    // 1. 左上の六曜を追加
                    const topEl = dayEl.querySelector('.fc-daygrid-day-top');
                    if(topEl) {
                        const rokuyoSpan = document.createElement('span');
                        rokuyoSpan.classList.add('rokuyo-text');
                        rokuyoSpan.innerText = rokuyoEvent.title;
                        topEl.appendChild(rokuyoSpan);
                    }

                    // 2. 背景色の設定
                    if (rokuyoEvent.title === '大安') dayEl.classList.add('fc-day-taian');
                    if (rokuyoEvent.title === '仏滅') dayEl.classList.add('fc-day-butsumetsu');

                    // 3. 左下の表示を追加
                    const frameEl = dayEl.querySelector('.fc-daygrid-day-frame');
                    if (frameEl) {
                        const bottomEl = document.createElement('div');
                        bottomEl.classList.add('day-grid-bottom');
                        
                        // --- ▼▼▼ この行を修正しました ▼▼▼ ---
                        const day = new Date(dateStr).getDate(); // YYYY-MM-DD から日を取得
                        bottomEl.innerHTML = `<span>${day}</span><span class="rokuyo-text">${rokuyoEvent.title}</span>`;
                        // --- ▲▲▲ この行を修正しました ▲▲▲ ---

                        frameEl.appendChild(bottomEl);
                    }
                }
            });
        },
        eventContent: function(arg) {
            // 六曜のイベント自体は予定バーとして表示しない
            return !arg.event.extendedProps.is_rokuyo;
        },
        eventClick: function(info) {
            // (変更なし)
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
            // (変更なし)
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

    // モーダルのボタン処理
    // ... (変更なし) ...
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

