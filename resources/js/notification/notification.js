import ToastService from "./ToastService.js";
import NotificationFactory from "./NotificationFactory.js";
import "./../../css/notification.css";

const toastService = new ToastService();

function updateNotificationCount() {
    const unreadCount = document.querySelectorAll('.notification-item.unread').length;
    if (unreadCount != 0) {
        document.querySelector('.notification-count').textContent = unreadCount;
        if (unreadCount == 0) {
            document.querySelector('.notification-count').style.display = 'none';
        } else {
            document.querySelector('.notification-count').style.display = 'flex';
        }
    }
    else {
         document.querySelector('.notification-count').textContent = 0;
    }
}

function handleClickNotification(event) {
    const notificationId = this.getAttribute('data-notification-id');

    hideNotification(notificationId, () => {
        this.remove();
        updateNotificationCount();
    });
}

function hideNotification(notificationId, onHidden = null) {
    if (!notificationId) {
        return;
    }

    fetch(`/notification/${notificationId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ _method: 'PUT' })
    })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                onHidden?.();
            } else {
                console.error('Ошибка при скрытии уведомления:', data.message);
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
        });
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', handleClickNotification);
    });

    if (window.userId) {
        document.getElementById('markAllAsRead').addEventListener('click', function (e) {
            e.preventDefault();

            const unreadNotifications = document.querySelectorAll('.notification-item.unread');

            const promises = Array.from(unreadNotifications).map(notification => {
                const notificationId = notification.getAttribute('data-notification-id');

                return fetch(`/notification/${notificationId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status) {
                            notification.remove();
                        }
                        return data;
                    });
            });

            Promise.all(promises)
                .then(() => updateNotificationCount())
                .catch(error => console.error('Ошибка:', error));
        });

        const notificationBell = document.getElementById('notificationBell');
        const notificationDropdown = document.getElementById('notificationDropdown');

        notificationBell.addEventListener('click', function (e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('show');
        });

        document.addEventListener('click', function () {
            notificationDropdown.classList.remove('show');
        });

        notificationDropdown.addEventListener('click', function (e) {
            e.stopPropagation();
        });

        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function () {
                this.classList.remove('unread');
                updateNotificationCount();
            });
        });

        const channel = `user.notification.${window.userId}`;
        window.Echo.private(channel).listen('.user.notification', (event) => {
            const typeMessage = (event.type).toLowerCase();

            console.log('Новое уведомление:', event);

            if (window.taskId && event.task_id && Number(window.taskId) === Number(event.task_id)) {
                hideNotification(event.id);
                return;
            }

            toastService.showToast({
                type: typeMessage,
                content: event.content,
                duration: 5000,
                closeable: true
            });

            let count = 0;
            const notificationBlockCount = document.getElementById('notification-count-element');
            if (notificationBlockCount) {
                count = Number.parseInt(notificationBlockCount.innerText) + 1;
            } else {
                count = 1;
            }

            notificationBlockCount.innerText = count;

            const emptyBlockNotification = document.querySelector('.no-notifications');
            if (emptyBlockNotification) {
                emptyBlockNotification.remove();
            }

            const notification = NotificationFactory.createNotification(typeMessage, event.content, event.id);
            notification.addEventListener('click', handleClickNotification);

            const notificationHeader = document.querySelector('.notification-header');
            notificationHeader.insertAdjacentElement('afterend', notification);
        });
    }
});


