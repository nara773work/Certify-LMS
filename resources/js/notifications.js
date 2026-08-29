export async function initNotifications() {
    const root = document.querySelector(
        '[data-notification-popover-root]'
    );

    if (!root) {
        return;
    }

    let isAdmin = false;

    try {
        const userResponse = await fetch('/api/user', {
            method: 'GET',
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        if (userResponse.ok) {
            const user = await userResponse.json();
            isAdmin = user.role === 'admin';
        }
    } catch (error) {
        console.error(
            'ユーザー情報の取得に失敗しました',
            error
        );
    }

    const trigger = root.querySelector(
        '[data-notification-popover-trigger]'
    );

    const panel = root.querySelector(
        '[data-notification-popover-panel]'
    );

    if (!trigger || !panel) {
        return;
    }

    // 管理者はベルを残すが、ポップオーバーは開かない
    if (isAdmin) {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
        });

        return;
    }

    // ↓ここから今までの一般ユーザー用処理

    const list = root.querySelector(
        '[data-notification-popover-items]'
    );

    const empty = root.querySelector(
        '[data-notification-popover-empty]'
    );

    const loading = root.querySelector(
        '[data-notification-popover-loading]'
    );

    const unreadCount = root.querySelector(
        '[data-notification-popover-unread-count]'
    );

    const markAllButton = root.querySelector(
        '[data-notification-popover-mark-all]'
    );

    let notifications = [];
    let currentTab = 'all';

    trigger.addEventListener('click', async () => {
        const isOpen =
            trigger.getAttribute('aria-expanded') === 'true';

        if (isOpen) {
            closePopover();
            return;
        }

        openPopover();

        await loadNotifications();
    });


    function openPopover() {
        panel.classList.remove('hidden');
        panel.style.display = 'flex';

        requestAnimationFrame(() => {
            panel.classList.remove(
                'opacity-0',
                '-translate-y-1'
            );
        });

        trigger.setAttribute('aria-expanded', 'true');
    }

    function closePopover() {
        panel.classList.add(
            'opacity-0',
            '-translate-y-1'
        );

        setTimeout(() => {
            panel.classList.add('hidden');
            panel.style.display = 'none';
        }, 150);

        trigger.setAttribute('aria-expanded', 'false');
    }

    async function loadNotifications() {
        if (!list || !loading || !empty) {
            return;
        }

        loading.classList.remove('hidden');
        empty.classList.add('hidden');
        list.innerHTML = '';

        try {
            const response = await fetch(
                '/api/v1/notifications',
                {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                }
            );

            if (!response.ok) {
                throw new Error(
                    `通知取得に失敗しました: ${response.status}`
                );
            }

            const data = await response.json();

            console.log('通知API:', data);
            console.log(
                '通知API notifications:',
                data.notifications
            );


            if (Array.isArray(data.notifications)) {
                notifications = data.notifications;
            } else {
                notifications = [];
            }

            console.log(
                'JS notifications:',
                notifications
            );

            updateUnreadCount();
            renderNotifications();

        } catch (error) {
            console.error(error);

            empty.textContent =
                '通知の取得に失敗しました。';

            empty.classList.remove('hidden');

        } finally {
            loading.classList.add('hidden');
        }
    }

    function renderNotifications() {
        if (!list || !empty) {
            return;
        }

        list.innerHTML = '';

        let displayedNotifications = notifications;

        // 未読タブ
        if (currentTab === 'unread') {
            displayedNotifications =
                notifications.filter(
                    (notification) =>
                        notification.read_at === null
                );
        }

        // 表示対象が0件
        if (displayedNotifications.length === 0) {
            empty.textContent =
                currentTab === 'unread'
                    ? '未読の通知はありません。'
                    : '通知はありません。';

            empty.classList.remove('hidden');

            return;
        }

        empty.classList.add('hidden');

        const template = root.querySelector(
            '[data-notification-popover-row-template]'
        );

        if (!template) {
            return;
        }

        displayedNotifications.forEach(
            (notification) => {

                const row =
                    template.content.cloneNode(true);

                const link = row.querySelector(
                    '[data-notification-popover-row]'
                );

                const title = row.querySelector(
                    '[data-notification-popover-row-title]'
                );

                const message = row.querySelector(
                    '[data-notification-popover-row-message]'
                );

                const time = row.querySelector(
                    '[data-notification-popover-row-time]'
                );

                const dot = row.querySelector(
                    '[data-notification-popover-row-dot]'
                );

                const isUnread =
                    notification.read_at === null;

                if (title) {
                    title.textContent =
                        notification.data?.title ??
                        '通知';
                }

                if (message) {
                    message.textContent =
                        notification.data?.body ??
                        notification.data?.message ??
                        '';
                }

                if (time) {
                    time.textContent =
                        formatDate(
                            notification.created_at
                        );
                }

                if (link) {
                    link.href =
                        `/notifications/${notification.id}`;

                    link.dataset.unread =
                        String(isUnread);

                if (isUnread) {
                    link.classList.add('bg-primary-50/30');
                } else {
                    link.classList.remove('bg-primary-50/30');
                }
}

                if (dot) {
                    dot.classList.toggle(
                        'invisible',
                        !isUnread
                    );
                }

                list.appendChild(row);
            }
        );
    }


    const tabs = root.querySelectorAll(
        '[data-notification-popover-tab]'
    );

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {

            currentTab =
                tab.dataset.notificationPopoverTab;

            tabs.forEach((item) => {
                item.setAttribute(
                    'aria-selected',
                    String(item === tab)
                );
            });

            renderNotifications();
        });
    });


    function updateUnreadCount() {
        const count =
            Array.isArray(notifications)
                ? notifications.filter(
                    (notification) =>
                        notification.read_at === null
                ).length
                : 0;

        if (!unreadCount) {
            return;
        }

        unreadCount.textContent = count;

        if (count === 0) {
            unreadCount.classList.add('hidden');
        } else {
            unreadCount.classList.remove('hidden');
        }
    }


    async function markAsRead(id) {
        try {
            const response = await fetch(
                `/api/v1/notifications/${id}/read`,
                {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN':
                            document
                                .querySelector(
                                    'meta[name="csrf-token"]'
                                )
                                ?.getAttribute('content'),
                    },
                    credentials: 'same-origin',
                }
            );

            if (!response.ok) {
                throw new Error(
                    `既読処理に失敗しました: ${response.status}`
                );
            }

            notifications =
                notifications.map(
                    (notification) => {

                        if (
                            notification.id !== id
                        ) {
                            return notification;
                        }

                        return {
                            ...notification,
                            read_at:
                                new Date().toISOString(),
                            status: 'read',
                        };
                    }
                );

            updateUnreadCount();

        } catch (error) {
            console.error(
                '通知の既読処理に失敗しました',
                error
            );
        }
    }

    function formatDate(dateString) {
        if (!dateString) {
            return '';
        }

        const date = new Date(dateString);
        const now = new Date();

        const diffSeconds =
            Math.floor(
                (now.getTime() -
                    date.getTime()) /
                    1000
            );

        if (diffSeconds < 60) {
            return 'たった今';
        }

        const diffMinutes =
            Math.floor(
                diffSeconds / 60
            );

        if (diffMinutes < 60) {
            return `${diffMinutes}分前`;
        }

        const diffHours =
            Math.floor(
                diffMinutes / 60
            );

        if (diffHours < 24) {
            return `${diffHours}時間前`;
        }

        const diffDays =
            Math.floor(
                diffHours / 24
            );

        if (diffDays === 1) {
            return '昨日';
        }

        if (diffDays < 7) {
            return `${diffDays}日前`;
        }

        return date.toLocaleDateString(
            'ja-JP',
            {
                timeZone: 'Asia/Tokyo',
                year: 'numeric',
                month: 'numeric',
                day: 'numeric',
            }
        );
    }

    if (markAllButton) {
        markAllButton.addEventListener(
            'click',
            async () => {

                try {
                    const response =
                        await fetch(
                            '/api/v1/notifications/read-all',
                            {
                                method: 'POST',
                                headers: {
                                    Accept:
                                        'application/json',
                                    'X-CSRF-TOKEN':
                                        document
                                            .querySelector(
                                                'meta[name="csrf-token"]'
                                            )
                                            ?.getAttribute(
                                                'content'
                                            ),
                                },
                                credentials:
                                    'same-origin',
                            }
                        );

                    if (!response.ok) {
                        throw new Error(
                            `全件既読に失敗しました: ${response.status}`
                        );
                    }

                    notifications =
                        notifications.map(
                            (notification) => ({
                                ...notification,
                                read_at:
                                    new Date().toISOString(),
                                status: 'read',
                            })
                        );

                    updateUnreadCount();
                    renderNotifications();

                } catch (error) {
                    console.error(error);
                }
            }
        );
    }
}