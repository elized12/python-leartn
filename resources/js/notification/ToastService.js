import "./../../css/toast.css";

export default class ToastService {
    constructor() {
        this.toastContainer = null;
        this.initContainer();
    }

    initContainer() {
        if (!document.getElementById('toast-container')) {
            this.toastContainer = document.createElement('div');
            this.toastContainer.id = 'toast-container';
            document.body.appendChild(this.toastContainer);
        } else {
            this.toastContainer = document.getElementById('toast-container');
        }
    }

    getToastIcon(type) {
        const icons = {
            'task_success': 'fas fa-check-circle',
            'task_error': 'fas fa-exclamation-circle',
            'warning': 'fas fa-exclamation-triangle',
            'info': 'fas fa-info-circle'
        };
        return `<i class="${icons[type] || 'fas fa-bell'}"></i>`;
    }

    showToast(options) {
        const {
            type = 'info',
            content = '',
            duration = 5000,
            closeable = true
        } = options;

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;


        const parsedContent = content;
        const toastIcon = this.getToastIcon(type);

        toast.innerHTML = `
            <div class="toast-icon">${toastIcon}</div>
            <div class="toast-content">
                <div class="toast-message">${parsedContent}</div>
            </div>
            ${closeable ? '<button class="toast-close"><i class="fas fa-times"></i></button>' : ''}
        `;


        this.toastContainer.appendChild(toast);


        setTimeout(() => {
            toast.classList.add('show');
        }, 10);


        if (closeable) {
            toast.querySelector('.toast-close').addEventListener('click', (e) => {
                e.stopPropagation();
                this.hideToast(toast);
            });
        }


        if (duration > 0) {
            const timeoutId = setTimeout(() => {
                this.hideToast(toast);
            }, duration);


            toast.dataset.timeoutId = timeoutId;
        }


        toast.addEventListener('click', (e) => {
            if (e.target.tagName === 'A' || e.target.closest('a')) {
                return;
            }


            if (closeable) {
                this.hideToast(toast);
            }
        });

        return toast;
    }

    hideToast(toast) {
        if (!toast) return;


        if (toast.dataset.timeoutId) {
            clearTimeout(parseInt(toast.dataset.timeoutId));
        }


        toast.classList.remove('show');


        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }


    success(content, title = '', duration = 5000) {
        return this.showToast({
            type: 'task_success',
            title,
            content,
            duration
        });
    }

    error(content, title = '', duration = 5000) {
        return this.showToast({
            type: 'task_error',
            title,
            content,
            duration
        });
    }

    warning(content, title = '', duration = 5000) {
        return this.showToast({
            type: 'warning',
            title,
            content,
            duration
        });
    }

    info(content, title = '', duration = 5000) {
        return this.showToast({
            type: 'info',
            title,
            content,
            duration
        });
    }
}

window.ToastService = new ToastService();