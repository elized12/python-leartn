import IBlock from "./IBlock";

export default class TaskListBlock extends IBlock {
    constructor(id, params = {}) {
        super(id, 'taskList', {
            title: 'Задачи для практики',
            description: '',
            tasks: [],
            ...params
        });

        this.isEditing = false;
    }

    render() {
        const block = document.createElement('div');
        block.className = 'task-list-block';
        block.dataset.blockId = this.id;
        block.dataset.order = this.order;
        block.innerHTML = this.getContentHtml(false);

        return block;
    }

    renderWithEditor() {
        const block = document.createElement('div');
        block.className = `task-list-block ${this.isEditing ? 'editing' : ''}`;
        block.dataset.blockId = this.id;
        block.dataset.order = this.order;

        block.innerHTML = `
            <div class="task-list-block-header">
                <button class="block-drag-handle" title="Перетащить">
                    <i class="fas fa-grip-vertical"></i>
                </button>
                <button class="task-list-edit-btn" title="Настроить">
                    <i class="fas fa-sliders-h"></i>
                </button>
                <button class="block-delete-btn" title="Удалить блок">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="task-list-preview">
                ${this.getContentHtml(true)}
            </div>
            <div class="task-list-edit-form">
                <div class="task-list-edit-head">
                    <h4><i class="fas fa-list-check"></i> Блок с задачами</h4>
                    <button type="button" class="task-list-close-btn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="task-list-edit-grid">
                    <label>
                        Заголовок
                        <input type="text" class="task-list-title-input" value="${this.escapeHtml(this.params.title)}" placeholder="Например: Закрепление темы">
                    </label>
                    <label>
                        Описание
                        <textarea class="task-list-description-input" rows="3" placeholder="Коротко объясните, что нужно решить">${this.escapeHtml(this.params.description)}</textarea>
                    </label>
                </div>
                <div class="task-list-picker">
                    <div class="task-list-picker-top">
                        <strong>Выберите задачи</strong>
                        <input type="search" class="task-list-search" placeholder="Поиск по названию или теме">
                    </div>
                    <div class="task-list-options">
                        ${this.getTaskOptionsHtml()}
                    </div>
                </div>
                <div class="task-list-edit-actions">
                    <button type="button" class="task-list-btn task-list-cancel-btn">Отмена</button>
                    <button type="button" class="task-list-btn task-list-save-btn">Сохранить</button>
                </div>
            </div>
        `;

        this.initEditorListeners(block);
        return block;
    }

    getContentHtml(isEditor = false) {
        const tasks = this.normalizedTasks();

        return `
            <section class="task-list-content">
                <div class="task-list-title-row">
                    <div>
                        <span class="task-list-kicker">Практика</span>
                        <h3>${this.escapeHtml(this.params.title || 'Задачи для практики')}</h3>
                    </div>
                    <span class="task-list-count">${tasks.length}</span>
                </div>
                ${this.params.description ? `<p class="task-list-description">${this.escapeHtml(this.params.description)}</p>` : ''}
                ${tasks.length ? `
                    <div class="task-list-items">
                        ${tasks.map((task, index) => this.getTaskItemHtml(task, index, isEditor)).join('')}
                    </div>
                ` : `
                    <div class="task-list-empty">
                        <i class="fas fa-list-check"></i>
                        <span>Задачи пока не выбраны</span>
                    </div>
                `}
            </section>
        `;
    }

    getTaskItemHtml(task, index, isEditor = false) {
        const solved = Boolean(task.solved);
        const url = task.url || `/task/solution/${task.id}`;
        const rating = task.rating ? `<span class="task-list-rating">${this.escapeHtml(task.rating)}</span>` : '';
        const tag = isEditor ? 'div' : 'a';
        const href = isEditor ? '' : `href="${this.escapeAttribute(url)}"`;

        return `
            <${tag} class="task-list-item ${solved ? 'is-solved' : ''}" ${href}>
                <span class="task-list-number">${solved ? '<i class="fas fa-check"></i>' : index + 1}</span>
                <span class="task-list-main">
                    <strong>${this.escapeHtml(task.title || `Задача ${task.id}`)}</strong>
                    <small>${solved ? 'Решена' : 'Перейти к решению'}</small>
                </span>
                ${rating}
                <i class="fas fa-arrow-right task-list-arrow"></i>
            </${tag}>
        `;
    }

    getTaskOptionsHtml() {
        const options = this.taskOptions();

        if (!options.length) {
            return '<div class="task-list-no-options">Задач пока нет. Создайте задачи в админ-панели.</div>';
        }

        const selectedIds = new Set(this.normalizedTasks().map(task => Number(task.id)));

        return options.map(task => {
            const categories = Array.isArray(task.categories) ? task.categories.join(', ') : '';
            return `
                <label class="task-list-option" data-search="${this.escapeAttribute(`${task.title} ${categories}`.toLowerCase())}">
                    <input type="checkbox" value="${task.id}" ${selectedIds.has(Number(task.id)) ? 'checked' : ''}>
                    <span>
                        <strong>${this.escapeHtml(task.title)}</strong>
                        <small>${this.escapeHtml(categories || 'Без категории')}</small>
                    </span>
                </label>
            `;
        }).join('');
    }

    initEditorListeners(block) {
        block.querySelector('.task-list-edit-btn')?.addEventListener('click', () => this.showEditForm(block));
        block.querySelector('.task-list-content')?.addEventListener('click', () => this.showEditForm(block));
        block.querySelector('.task-list-close-btn')?.addEventListener('click', () => this.hideEditForm(block));
        block.querySelector('.task-list-cancel-btn')?.addEventListener('click', () => this.hideEditForm(block));
        block.querySelector('.task-list-save-btn')?.addEventListener('click', () => this.saveChanges(block));
        block.querySelector('.block-delete-btn')?.addEventListener('click', () => this.onDelete(block));

        block.querySelector('.task-list-search')?.addEventListener('input', (event) => {
            const query = event.target.value.trim().toLowerCase();
            block.querySelectorAll('.task-list-option').forEach(option => {
                option.hidden = query && !option.dataset.search.includes(query);
            });
        });
    }

    showEditForm(block) {
        this.isEditing = true;
        block.classList.add('editing');
        block.querySelector('.task-list-title-input')?.focus();
    }

    hideEditForm(block) {
        this.isEditing = false;
        block.classList.remove('editing');
    }

    saveChanges(block) {
        const selectedTasks = Array.from(block.querySelectorAll('.task-list-option input:checked'))
            .map(input => this.taskOptions().find(task => Number(task.id) === Number(input.value)))
            .filter(Boolean)
            .map(task => ({
                id: Number(task.id),
                title: task.title,
                rating: task.rating,
                url: task.url,
            }));

        this.params = {
            ...this.params,
            title: block.querySelector('.task-list-title-input')?.value.trim() || 'Задачи для практики',
            description: block.querySelector('.task-list-description-input')?.value.trim() || '',
            tasks: selectedTasks,
        };

        block.querySelector('.task-list-preview').innerHTML = this.getContentHtml(true);
        this.hideEditForm(block);
    }

    onDelete(block) {
        if (!confirm('Удалить блок с задачами?')) {
            return;
        }

        block.remove();
        document.dispatchEvent(new CustomEvent('blockDeleted', {
            detail: { blockId: this.id, type: 'taskList' },
            bubbles: true,
        }));
    }

    normalizedTasks() {
        return Array.isArray(this.params.tasks) ? this.params.tasks : [];
    }

    taskOptions() {
        return Array.isArray(window.courseTaskOptions) ? window.courseTaskOptions : [];
    }

    escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    escapeAttribute(value) {
        return this.escapeHtml(value).replace(/"/g, '&quot;');
    }
}
