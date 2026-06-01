export default class NotificationFactory {
    static createNotification(type, message, notificationId) {
        switch (type) {
            case 'task_success':
                return NotificationFactory.renderSuccessNotification(notificationId, message);
            case 'task_error':
                return NotificationFactory.renderErrorNotification(notificationId, message);
            default:
                throw new Error(`Unknown notification type: ${type}`);
        }
    }

    static renderErrorNotification(notificationId, message) {
        const notification = document.createElement('div');
        notification.className = "notification-item unread task_error";
        notification.dataset.notificationId = notificationId;

        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div>
                    <div>${message}</div>
                    <div class="notification-time">
                        <i class="far fa-clock"></i>Только что
                    </div>
                </div>
            </div>
        `;

        return notification;
    }

    static renderSuccessNotification(notificationId, message) {
        const notification = document.createElement('div');
        notification.className = "notification-item unread task_success";
        notification.dataset.notificationId = notificationId;

        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                 <div>
                    <div>${message}</div>
                        <div class="notification-time">
                            <i class="far fa-clock"></i>Только что
                        </div>
                </div>
            </div>
        `;

        return notification;
    }
}