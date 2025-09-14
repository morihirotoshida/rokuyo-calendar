<!-- Blade Test: {{ 1 + 1 }} -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm-px-6 lg:px-8"><h2 class="font-semibold text-xl text-gray-800 leading-tight">予約カレンダー</h2></div>
        </header>
        <main>
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div class="bg-white overflow-hidden shadow-sm sm-rounded-lg"><div class="p-6 text-gray-900" id='calendar'></div></div>
                </div>
            </div>
        </main>
    </div>
    <!-- 予約編集モーダル -->
    <div class="modal fade" id="eventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalLabel">予約</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="eventId">
                    <div class="mb-3">
                        <label for="eventTitle" class="form-label">コメント</label>
                        <input type="text" class="form-control" id="eventTitle" placeholder="会議の予定など">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="eventStartDate" class="form-label">開始</label>
                            <input type="datetime-local" class="form-control" id="eventStart">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="eventEndDate" class="form-label">終了</label>
                            <input type="datetime-local" class="form-control" id="eventEnd">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="deleteEventBtn" style="display:none;">削除</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <button type="button" class="btn btn-primary" id="saveEventBtn">保存</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ライブラリ読み込みチェック
            if (typeof FullCalendar === 'undefined' || typeof bootstrap === 'undefined') {
                document.getElementById('calendar').innerHTML = `<div class="alert alert-danger"><strong>エラー:</strong> カレンダーの表示に必要な外部ライブラリを読み込めませんでした。</div>`;
                return;
            }

            // 要素の取得
            const calendarEl = document.getElementById('calendar');
            const eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
            const eventIdInput = document.getElementById('eventId');
            const eventTitleInput = document.getElementById('eventTitle');
            const eventStartInput = document.getElementById('eventStart');
            const eventEndInput = document.getElementById('eventEnd');
            const saveEventBtn = document.getElementById('saveEventBtn');
            const deleteEventBtn = document.getElementById('deleteEventBtn');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const baseUrl = '/calendar/events';

            // FullCalendarの初期化
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'ja',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: baseUrl,
                editable: true,
                dayMaxEvents: false,
                
                // ★★【エラー修正】イベントデータの受信が完了してから六曜を描画する ★★
                eventsSet: function(events) { // パラメータはイベントの「配列」そのもの
                    // 以前描画した六曜の情報を一旦すべてクリアする
                    document.querySelectorAll('.rokuyo-text').forEach(el => el.remove());
                    document.querySelectorAll('.fc-day-taian').forEach(el => el.classList.remove('fc-day-taian'));

                    // 月表示の時だけ、六曜の描画処理を実行する
                    if (calendar.view.type === 'dayGridMonth') {
                        // パラメータ自体がイベントの配列なので、.eventsは不要
                        events.forEach(event => {
                            if (event.extendedProps.is_rokuyo) {
                                const dateStr = event.startStr.slice(0, 10);
                                const dayEl = document.querySelector(`.fc-day[data-date="${dateStr}"]`);
                                if (dayEl && dayEl.querySelector('.fc-daygrid-day-top')) {
                                    const rokuyoEl = document.createElement('div');
                                    rokuyoEl.className = 'rokuyo-text';
                                    rokuyoEl.innerText = event.title;
                                    dayEl.querySelector('.fc-daygrid-day-top').appendChild(rokuyoEl);
                                    
                                    if (event.title === '大安') {
                                        dayEl.classList.add('fc-day-taian');
                                    }
                                }
                            }
                        });
                    }
                },
                
                // --- 以下、既存のイベント処理 ---
                dateClick: function(info) {
                    resetModal();
                    const startDate = new Date(info.date);
                    startDate.setHours(9, 0, 0, 0);
                    const endDate = new Date(info.date);
                    endDate.setHours(10, 0, 0, 0);
                    eventStartInput.value = formatDateTimeLocal(startDate);
                    eventEndInput.value = formatDateTimeLocal(endDate);
                    document.getElementById('eventModalLabel').innerText = '新規予約';
                    eventModal.show();
                },
                eventClick: function(info) {
                    if (info.event.extendedProps.is_rokuyo || info.event.extendedProps.is_work) return;
                    resetModal(info.event.id, info.event.title);
                    eventStartInput.value = formatDateTimeLocal(info.event.start);
                    const endDate = info.event.end ? info.event.end : info.event.start;
                    eventEndInput.value = formatDateTimeLocal(endDate);
                    deleteEventBtn.style.display = 'block';
                    document.getElementById('eventModalLabel').innerText = '予約編集';
                    eventModal.show();
                },
                eventDrop: function(info) { if(!info.event.extendedProps.is_work) handleEventUpdate(info.event); },
                eventResize: function(info) { if(!info.event.extendedProps.is_work) handleEventUpdate(info.event); },
            });
            calendar.render();
            
            eventStartInput.addEventListener('input', function() {
                if (this.value) {
                    const startDate = new Date(this.value);
                    const endDate = new Date(startDate.getTime() + 60 * 60 * 1000);
                    eventEndInput.value = formatDateTimeLocal(endDate);
                }
            });

            function resetModal(id = '', title = '') {
                eventIdInput.value = id;
                eventTitleInput.value = title;
                eventStartInput.value = '';
                eventEndInput.value = '';
                deleteEventBtn.style.display = 'none';
            }

            function formatDateTimeLocal(date) {
                if (!date) return '';
                const d = new Date(date);
                const pad = (num) => num.toString().padStart(2, '0');
                return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
            }

            saveEventBtn.addEventListener('click', function() {
                const id = eventIdInput.value;
                const eventData = { title: eventTitleInput.value, start: eventStartInput.value, end: eventEndInput.value };
                if (!eventData.title || !eventData.start || !eventData.end) { alert('すべての項目を入力してください。'); return; }
                if (id) { updateEvent(id, eventData); } else { createEvent(eventData); }
            });

            deleteEventBtn.addEventListener('click', function() {
                const id = eventIdInput.value;
                if (id && confirm('この予約を削除しますか？')) { deleteEvent(id); }
            });
            
            function handleEventUpdate(event) {
                const eventData = { title: event.title, start: formatDateTimeLocal(event.start), end: event.end ? formatDateTimeLocal(event.end) : formatDateTimeLocal(event.start) };
                updateEvent(event.id, eventData);
            }

            const handleApiResponse = async (response, failureMessage) => {
                if (response.ok) { calendar.refetchEvents(); eventModal.hide(); } else {
                    const errorData = await response.json().catch(() => null);
                    let errorMessage = failureMessage;
                    if (errorData?.errors) errorMessage += '\n' + Object.values(errorData.errors).flat().join('\n');
                    else if (errorData?.message) errorMessage += '\n' + errorData.message;
                    alert(errorMessage);
                }
            };
            const handleApiError = (error, failureMessage) => alert(failureMessage + '\n通信エラーが発生しました。');

            function createEvent(data) { fetch(baseUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: JSON.stringify(data) }).then(res => handleApiResponse(res, '予約の保存に失敗しました。')).catch(err => handleApiError(err, '予約の保存に失敗しました。')); }
            function updateEvent(id, data) { fetch(`${baseUrl}/${id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: JSON.stringify(data) }).then(res => handleApiResponse(res, '予約の更新に失敗しました。')).catch(err => handleApiError(err, '予約の更新に失敗しました。')); }
            function deleteEvent(id) { fetch(`${baseUrl}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } }).then(res => handleApiResponse(res, '予約の削除に失敗しました。')).catch(err => handleApiError(err, '予約の削除に失敗しました。')); }
        });
    </script>
</body>
</html>

