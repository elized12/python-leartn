import { NotificationService } from "./service/NotificationService";

const notificationService = new NotificationService();

function updateNotificationCount() {
    const unreadCount = document.querySelectorAll('.notification-item.unread').length;
    if (unreadCount != 0) {
        document.querySelector('.notification-count').textContent = unreadCount;
        if (unreadCount === 0) {
            document.querySelector('.notification-count').style.display = 'none';
        } else {
            document.querySelector('.notification-count').style.display = 'flex';
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function () {
            const notificationId = this.getAttribute('data-notification-id');

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
                        this.remove();
                        updateNotificationCount();
                    } else {
                        console.error('Ошибка при скрытии уведомления:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                });
        });
    });

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


    const channel = `user.task.${window.userId}`;
    window.Echo.private(channel).listen('.attempt.notification', (event) => {
        const typeMessage = event.type;

        notificationService.showNotification({
            type: typeMessage,
            title: 'Попытка задачи',
            message: event.messafge,
            duration: 5000,
            closeable: true
        });
    });
});



