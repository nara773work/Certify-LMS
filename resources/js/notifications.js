export function initNotifications() {
    const root = document.querySelector(
        '[data-notification-popover-root]'
    );

    if (!root) {
        return;
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

            notifications = data.notifications ?? [];

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

        if (notifications.length === 0) {
            empty.textContent = '通知はありません。';
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

        notifications.forEach((notification) => {
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
                    notification.data?.title ?? '通知';
            }

            if (message) {
                message.textContent =
                    notification.data?.body ?? '';
            }

            if (time) {
                time.textContent =
                    formatDate(notification.created_at);
            }

            if (link) {
                link.href =
                    `/notifications/${notification.id}`;

                link.dataset.unread =
                    String(isUnread);
            }

            if (dot && !isUnread) {
                dot.classList.add('invisible');
            }

            list.appendChild(row);
        });
    }

    function updateUnreadCount() {
        const count = notifications.filter(
            (notification) =>
                notification.read_at === null
        ).length;

        if (unreadCount) {
            unreadCount.textContent = count;
        }
    }

    function formatDate(dateString) {
        if (!dateString) {
            return '';
        }

        const date = new Date(dateString);

        return date.toLocaleString('ja-JP', {
            timeZone: 'Asia/Tokyo',
            month: 'numeric',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    if (markAllButton) {
        markAllButton.addEventListener(
            'click',
            async () => {
                try {
                    const response = await fetch(
                        '/api/v1/notifications/read-all',
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