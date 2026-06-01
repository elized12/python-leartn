import ToastService from "../notification/ToastService.js";

const toastService = new ToastService();

function modeFromType(type) {
    if (type === 'build_success') {
        return 'task_success';
    }

    if (type === 'build_error') {
        return 'task_error';
    }

    return 'info';
}

function ensureBuildStatus() {
    let status = document.getElementById('admin-build-status');
    if (status) {
        return status;
    }

    status = document.createElement('div');
    status.id = 'admin-build-status';
    status.className = 'admin-build-status';
    status.innerHTML = '<strong>Docker build</strong><span>Ожидание событий сборки</span>';

    const content = document.querySelector('.main-content');
    content?.insertBefore(status, content.querySelector('.header')?.nextSibling ?? content.firstChild);

    return status;
}

function updateBuildStatus(event) {
    if (!event.type?.startsWith('build')) {
        return;
    }

    const status = ensureBuildStatus();
    status.classList.remove('is-running', 'is-success', 'is-error');

    const className = event.type === 'build_success'
        ? 'is-success'
        : event.type === 'build_error'
            ? 'is-error'
            : 'is-running';

    status.classList.add(className);
    status.querySelector('strong').textContent = event.title || 'Docker build';
    status.querySelector('span').textContent = event.message || '';
}

document.addEventListener('DOMContentLoaded', () => {
    if (!window.Echo) {
        return;
    }

    window.Echo.private('admin.dashboard')
        .listen('.admin.dashboard.updated', (event) => {
            if (!event.type?.startsWith('build')) {
                return;
            }

            updateBuildStatus(event);
            toastService.showToast({
                type: modeFromType(event.type),
                content: event.message,
                duration: event.type === 'build' ? 8000 : 12000,
                closeable: true,
            });
        });
});
