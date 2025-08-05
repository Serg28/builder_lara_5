@if(method_exists($admin, 'showNotifications') && $admin->showNotifications())
    <div class="btn-header transparent pull-right dropdown" id="notification-wrapper">
        <span data-toggle="dropdown" class="dropdown-toggle" title="{{ __cms('Уведомления') }}">
            <div class="notification-icon">
                <i class="fal fa-bell fa-2x"></i>
                    <span class="notification-badge" id="badge">0</span>
            </div>
        </span>
        <ul class="dropdown-menu pull-right" style="width: 250px;">
            <li class="dropdown-header d-flex justify-content-between align-items-center position-relative">
                <span>{{ __cms('Уведомления') }}</span>
                <button id="mark-all-read-btn"
                        class="btn btn-link"
                        onclick="markAllRead()"
                >
                    {{ __cms('Прочитать все') }}
                </button>
            </li>
            <div id="notification-list">
                <!-- JS вставить сюди -->
            </div>
        </ul>
    </div>

    <script>
        let nextPage = null;

        function loadNotifications(pageUrl = null, showMore = false) {
            const url = pageUrl ?? '{{ route('admin.notifications') }}';
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    const list = document.getElementById('notification-list');
                    const badge = document.getElementById('badge');
                    const markAllBtn = document.getElementById('mark-all-read-btn');
                    badge.innerText = data.unread;
                    const notifications = data.notifications;

                    stopFlashingTitle();
                    if(data.unread > 0) {
                        flashTitle(data.unread);
                    }

                    if (notifications.length === 0 && !showMore) {
                        // Немає жодного сповіщення
                        if (markAllBtn) markAllBtn.style.display = 'none';

                        list.innerHTML = `
                    <li class="text-center text-muted p-2">
                        {{ __cms('Пока нет уведомлений') }}
                        </li>
`;
                        return;
                    } else {
                        if (markAllBtn) markAllBtn.style.display = 'inline-block';
                    }

                    const items = notifications.map(n => {
                        const checked = n.read_at ? 'checked' : '';
                        const readClass = n.read_at ? 'notification-read' : '';

                        return `
                    <li class="notification-item">
                        <div style="display: flex; justify-content: space-between;">
                            <a href="${n.data.url ?? '#'}" class="${readClass}">
                                ${n.data.message ?? 'Новое уведомление'}
                            </a>
                            <input type="checkbox" class="notification-read-toggle" data-id="${n.id}" ${checked} title="{{__cms('Отметить как прочитанное')}}">
                        </div>
                        <div class="notification-created">
                            <i class="far fa-clock mr-1"></i> ${n.created_human}
                        </div>
                    </li>
                `;
                    }).join('');

                    if (showMore) {
                        list.innerHTML += items;
                    } else {
                        list.innerHTML = items;
                    }

                    // Видаляємо стару кнопку
                    const oldButton = document.getElementById('load-more-btn');
                    if (oldButton) oldButton.remove();

                    if (data.next_page_url) {
                        nextPage = data.next_page_url;
                        const loadMore = document.createElement('li');
                        loadMore.innerHTML = `<a href="#" id="load-more-btn" class="text-center">{{__cms('Показать еще')}}</a>`;
                        list.appendChild(loadMore);

                        document.getElementById('load-more-btn').addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            loadNotifications(nextPage, true);
                        });
                    }

                    document.querySelectorAll('.notification-read-toggle').forEach(cb => {
                        cb.addEventListener('click', function (e) {
                            e.stopPropagation();
                        });
                        cb.addEventListener('change', function () {
                            const notificationId = this.dataset.id;
                            const read = this.checked ? 1 : 0;

                            fetch('{{ route('admin.notifications.mark-read') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ read: read, id: notificationId})
                            })
                                .then(resp => resp.json())
                                .then(() => loadNotifications()) // оновимо список
                                .catch(() => alert('{{__cms('Ошибка обновления статуса')}}'));
                        });
                    });
                });
        }

        //-----------Логіка блимання новий сповіщень в тайтлі - start ------------
        let originalTitle = document.title;
        let flashInterval = null;

        function flashTitle(newCount) {
            if (flashInterval) return; // уже блимає

            flashInterval = setInterval(() => {
                document.title = document.title === originalTitle
                    ? `🔔 Нових: ${newCount}`
                    : originalTitle;
            }, 1000);
        }

        function stopFlashingTitle() {
            if (flashInterval) {
                clearInterval(flashInterval);
                flashInterval = null;
                document.title = originalTitle;
            }
        }
        //-----------Логіка блимання новий сповіщень в тайтлі - end ------------

        // Перший виклик
        loadNotifications();

        setInterval(() => {
            loadNotifications();
        }, {{ $admin->refreshNotificationsTime() * 1000 }});

        function markAllRead() {
            fetch('{{ route('admin.notifications.mark-all-read') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
            })
                .then(response => response.json())
                .then(() => {
                    loadNotifications(); // перезавантажити список
                })
                .catch(error => console.error('{{__cms('Ошибка при обозначении прочитанных')}}:', error));
        }
    </script>

    <style>
        #notification-wrapper {
            cursor: pointer;
        }
        .notification-icon {
            position: relative;
            display: inline-block;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: red;
            color: white;
            font-size: 12px;
            padding: 2px 5px;
            border-radius: 50%;
            min-width: 18px;
            text-align: center;
            line-height: 1;
        }
        #notification-list li a {
            white-space: normal; /* переноси рядків */
            overflow-wrap: break-word; /* розрив довгих слів */
            max-width: 100%;
            display: block;
        }
        .notification-item {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
        }
        .notification-item a {
            white-space: normal;
            overflow-wrap: break-word;
            max-width: 70%;
            display: block;
        }
        .notification-read-toggle {
            margin-left: 10px;
        }
        .notification-read {
            text-decoration: line-through;
            opacity: 0.7;
        }
        .notification-created {
            font-size: 11px;
            color: #888;
            margin-top: 5px;
        }
        #mark-all-read-btn {
            top: 0;
            right: 0;
            font-size: 13px;
            position: absolute;
            color: #739e73;
        }
        #load-more-btn {
            margin-top: 5px;
        }
    </style>
@endif
