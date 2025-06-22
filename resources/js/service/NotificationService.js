export class NotificationService {
    constructor() {
        this.notificationContainer = null;
        this.initContainer();
        this.initStyles();
    }

    initContainer() {
        if (!document.getElementById('notification-container')) {
            this.notificationContainer = document.createElement('div');
            this.notificationContainer.id = 'notification-container';
            document.body.appendChild(this.notificationContainer);
        } else {
            this.notificationContainer = document.getElementById('notification-container');
        }
    }

    initStyles() {
        if (!document.getElementById('notification-styles')) {
            const styleElement = document.createElement('style');
            styleElement.id = 'notification-styles';
            styleElement.innerHTML = `
                #notification-container {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    width: 350px;
                    max-width: 90%;
                    z-index: 9999;
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }

                .notification {
                    position: relative;
                    padding: 15px;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    display: flex;
                    align-items: flex-start;
                    gap: 12px;
                    opacity: 0;
                    transform: translateX(100%);
                    transition: all 0.3s ease-out;
                    color: white;
                }

                .notification.show {
                    opacity: 1;
                    transform: translateX(0);
                }

                .notification-icon {
                    font-size: 20px;
                    margin-top: 2px;
                }

                .notification-content {
                    flex: 1;
                }

                .notification-title {
                    font-weight: 600;
                    margin-bottom: 5px;
                    font-size: 16px;
                }

                .notification-message {
                    font-size: 14px;
                    line-height: 1.4;
                }

                .notification-close {
                    background: none;
                    border: none;
                    color: inherit;
                    opacity: 0.7;
                    cursor: pointer;
                    transition: opacity 0.2s;
                    padding: 0;
                    margin-left: 5px;
                }

                .notification-close:hover {
                    opacity: 1;
                }

                /* Типы уведомлений */
                .notification-success {
                    background-color: #10B981;
                    border-left: 4px solid #059669;
                }

                .notification-error {
                    background-color: #EF4444;
                    border-left: 4px solid #DC2626;
                }

                .notification-warning {
                    background-color: #F59E0B;
                    border-left: 4px solid #D97706;
                    color: #1F2937;
                }

                .notification-info {
                    background-color: #3B82F6;
                    border-left: 4px solid #2563EB;
                }
            `;
            document.head.appendChild(styleElement);
        }
    }

    showNotification(options) {
        const {
            type = 'info',
            title = '',
            message = '',
            duration = 5000,
            closeable = true
        } = options;

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;

        let icon;
        switch (type) {
            case 'success':
                icon = '<i class="fas fa-check-circle"></i>';
                break;
            case 'error':
                icon = '<i class="fas fa-exclamation-circle"></i>';
                break;
            case 'warning':
                icon = '<i class="fas fa-exclamation-triangle"></i>';
                break;
            default:
                icon = '<i class="fas fa-info-circle"></i>';
        }

        notification.innerHTML = `
            <div class="notification-icon">${icon}</div>
            <div class="notification-content">
                ${title ? `<div class="notification-title">${title}</div>` : ''}
                <div class="notification-message">${message}</div>
            </div>
            ${closeable ? '<button class="notification-close"><i class="fas fa-times"></i></button>' : ''}
        `;

        this.notificationContainer.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        if (closeable) {
            notification.querySelector('.notification-close').addEventListener('click', () => {
                this.hideNotification(notification);
            });
        }

        if (duration > 0) {
            setTimeout(() => {
                this.hideNotification(notification);
            }, duration);
        }

        return notification;
    }

    hideNotification(notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }
}

