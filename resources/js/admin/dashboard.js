function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function updateStat(name, value) {
    const element = document.querySelector(`[data-stat="${name}"]`);
    if (!element || value === undefined || value === null) {
        return;
    }

    element.textContent = value;
    element.closest('.stat-card')?.classList.add('is-updated');
    setTimeout(() => element.closest('.stat-card')?.classList.remove('is-updated'), 900);
}

function setLiveStatus(text, mode) {
    const status = document.getElementById('admin-live-status');
    if (!status) {
        return;
    }

    status.classList.remove('is-online', 'is-offline');
    status.classList.add(mode);
    status.lastChild.textContent = ` ${text}`;
}

function addActivity(event) {
    const feed = document.getElementById('admin-activity-feed');
    if (!feed) {
        return;
    }

    feed.querySelector('.empty-state')?.remove();

    const item = document.createElement('article');
    item.className = `activity-item activity-${event.type || 'system'} is-new`;
    item.innerHTML = `
        <span class="activity-dot"></span>
        <div>
            <strong>${escapeHtml(event.title || 'Событие')}</strong>
            <p>${escapeHtml(event.message || '')}</p>
            <time>${escapeHtml(event.created_at || 'только что')}</time>
        </div>
    `;

    feed.prepend(item);

    while (feed.querySelectorAll('.activity-item').length > 12) {
        feed.querySelector('.activity-item:last-child')?.remove();
    }

    const counter = document.getElementById('feed-counter');
    if (counter) {
        counter.textContent = String(feed.querySelectorAll('.activity-item').length);
    }

    setTimeout(() => item.classList.remove('is-new'), 1200);
}

function handleDashboardEvent(event) {
    Object.entries(event.stats || {}).forEach(([name, value]) => updateStat(name, value));
    addActivity(event);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.close-alert').forEach((button) => {
        button.addEventListener('click', () => button.closest('.alert')?.remove());
    });

    if (!window.Echo) {
        setLiveStatus('Echo не загружен', 'is-offline');
        return;
    }

    setLiveStatus('Онлайн', 'is-online');

    window.Echo.private('admin.dashboard')
        .listen('.admin.dashboard.updated', handleDashboardEvent)
        .error(() => setLiveStatus('Нет доступа к каналу', 'is-offline'));
});
