import IBlock from "./IBlock";

export default class InfoBoxBlock extends IBlock {
    constructor(id, params = {}) {
        super(id, 'infoBox', {
            type: 'info',
            title: '',
            content: 'Здесь будет важная информация',
            collapsible: false,
            expanded: true,
            icon: true,
            ...params
        });

        this.isCollapsed = !this.params.expanded;
        this.isEditing = false;
    }

    render() {
        const block = document.createElement('div');
        block.className = `info-box-block type-${this.params.type} ${this.isCollapsed ? 'collapsed' : ''}`;
        block.dataset.blockId = this.id;
        block.dataset.order = this.order;

        const icon = this.getIcon();
        const typeLabel = this.getTypeLabel();

        block.innerHTML = `
            <div class="info-box-content">
                <div class="info-box-header">
                    <div class="info-box-icon-title">
                        ${icon}
                        ${this.params.title ? `<span class="info-box-title">${this.params.title}</span>` : ''}
                        ${!this.params.title ? `<span class="info-box-type">${typeLabel}</span>` : ''}
                    </div>
                    ${this.params.collapsible ? `
                        <button class="info-box-toggle">
                            <i class="fas fa-chevron-${this.isCollapsed ? 'down' : 'up'}"></i>
                        </button>
                    ` : ''}
                </div>
                
                <div class="info-box-body" style="${this.isCollapsed ? 'display: none;' : ''}">
                    <div class="info-box-text">${this.params.content}</div>
                </div>
            </div>
        `;

        this.initEventListeners(block);
        return block;
    }

    renderWithEditor() {
        const block = document.createElement('div');
        block.className = `info-box-block type-${this.params.type} ${this.isCollapsed ? 'collapsed' : ''} ${this.isEditing ? 'editing' : ''}`;
        block.dataset.blockId = this.id;
        block.dataset.order = this.order;

        const icon = this.getIcon();
        const typeLabel = this.getTypeLabel();

        block.innerHTML = `
            <div class="block-info-box-header">
                <button class="block-drag-handle" title="Перетащить">
                    <i class="fas fa-grip-vertical"></i>
                </button>
                <button class="block-delete-btn" title="Удалить блок">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="info-box-content">
                <div class="info-box-header">
                    <div class="info-box-icon-title">
                        ${icon}
                        ${this.params.title ? `<span class="info-box-title">${this.params.title}</span>` : ''}
                        ${!this.params.title ? `<span class="info-box-type">${typeLabel}</span>` : ''}
                    </div>
                    ${this.params.collapsible ? `
                        <button class="info-box-toggle">
                            <i class="fas fa-chevron-${this.isCollapsed ? 'down' : 'up'}"></i>
                        </button>
                    ` : ''}
                </div>
                
                <div class="info-box-body" style="${this.isCollapsed ? 'display: none;' : ''}">
                    <div class="info-box-text">${this.params.content}</div>
                </div>
            </div>
            
            <div class="edit-form">
                <div class="edit-header">
                    <h4><i class="fas fa-sticky-note"></i> Настройки информационного блока</h4>
                    <button class="close-edit-btn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="edit-body">
                    <div class="info-form-group">
                        <label>Тип блока</label>
                        <div class="type-selector">
                            ${['info', 'success', 'warning', 'error', 'tip', 'note', 'important'].map(type => `
                                <label class="type-option ${this.params.type === type ? 'selected' : ''}" data-type="${type}">
                                    <div class="type-preview type-${type}">
                                        <i class="${this.getIconClass(type)}"></i>
                                    </div>
                                    <span class="type-label">${this.getTypeLabel(type)}</span>
                                </label>
                            `).join('')}
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="info-form-group">
                            <label>Заголовок (необязательно)</label>
                            <input type="text" class="edit-title" 
                                   value="${this.escapeHtml(this.params.title)}" 
                                   placeholder="Введите заголовок">
                        </div>
                        
                        <div class="info-form-group">
                            <div class="properties-value">Иконка</div>
                            <label class="checkbox-label">
                                <input type="checkbox" class="edit-icon" ${this.params.icon ? 'checked' : ''}>
                                <div class="checkbox-custom"></div>
                                <span>Показывать иконку</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="info-form-group">
                            <div class="properties-value">Сворачиваемый</div>
                            <label class="checkbox-label">
                                <input type="checkbox" class="edit-collapsible" ${this.params.collapsible ? 'checked' : ''}>
                                <div class="checkbox-custom"></div>
                                <span>Можно сворачивать</span>
                            </label>
                        </div>
                        
                        <div class="info-form-group">
                            <div class="properties-value">По умолчанию</div>
                            <label class="checkbox-label">
                                <input type="checkbox" class="edit-expanded" ${this.params.expanded ? 'checked' : ''}>
                                <div class="checkbox-custom"></div>
                                <span>Развернут</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="info-form-group">
                        <label>Содержимое</label>
                        <textarea class="edit-content" rows="6">${this.escapeHtml(this.params.content)}</textarea>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i>
                            Поддерживается HTML и Markdown
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button class="btn btn-cancel">Отмена</button>
                        <button class="btn btn-primary btn-save">Сохранить</button>
                    </div>
                </div>
            </div>
        `;

        this.initEventListeners(block);
        this.initEditListeners(block);
        return block;
    }

    #getTypesList() {
        return ['info', 'success', 'warning', 'error', 'tip', 'note', 'important'];
    }

    changeTypeRender(element, type) {
        const types = this.#getTypesList();
        types.forEach(type => {
            element.classList.remove(`type-${type}`);
        });

        element.classList.add(`type-${type}`);
    }

    getIcon() {
        if (!this.params.icon) return '';

        const iconClass = this.getIconClass(this.params.type);
        return `<i class="info-box-icon ${iconClass}"></i>`;
    }

    getIconClass(type = null) {
        const currentType = type || this.params.type;
        const icons = {
            'info': 'fas fa-info-circle',
            'success': 'fas fa-check-circle',
            'warning': 'fas fa-exclamation-triangle',
            'error': 'fas fa-times-circle',
            'tip': 'fas fa-lightbulb',
            'note': 'fas fa-sticky-note',
            'important': 'fas fa-exclamation-circle'
        };
        return icons[currentType] || icons.info;
    }

    getTypeLabel(type = null) {
        const currentType = type || this.params.type;
        const labels = {
            'info': 'Информация',
            'success': 'Успех',
            'warning': 'Предупреждение',
            'error': 'Ошибка',
            'tip': 'Подсказка',
            'note': 'Заметка',
            'important': 'Важно'
        };
        return labels[currentType] || 'Информация';
    }

    initEventListeners(block) {
        const toggleBtn = block.querySelector('.info-box-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleCollapse(block);
            });
        }

        const header = block.querySelector('.info-box-header');
        if (header && !this.isEditing) {
            header.addEventListener('click', (e) => {
                if (!e.target.closest('.info-box-toggle') && !this.isEditing) {
                    this.showEditForm(block);
                }
            });
        }
    }

    initEditListeners(block) {
        const deleteBtn = block.querySelector('.block-delete-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => this.onDelete(block));
        }

        const content = block.querySelector('.info-box-content');
        if (content && !this.isEditing) {
            content.addEventListener('click', () => {
                this.showEditForm(block);
            });
        }

        const saveBtn = block.querySelector('.btn-save');
        if (saveBtn) saveBtn.addEventListener('click', () => this.saveChanges(block));

        const cancelBtn = block.querySelector('.btn-cancel');
        if (cancelBtn) cancelBtn.addEventListener('click', () => this.hideEditForm(block));

        const closeBtn = block.querySelector('.close-edit-btn');
        if (closeBtn) closeBtn.addEventListener('click', () => this.hideEditForm(block));


        block.querySelectorAll('.type-option').forEach(option => {
            option.addEventListener('click', () => {
                const type = option.dataset.type;
                block.querySelectorAll('.type-option').forEach(opt => opt.classList.remove('selected'));
                option.classList.add('selected');

                this.params.type = type;

                this.updatePreview(block);
            });
        });

        block.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.updatePreview(block);
            });
        });

        const titleInput = block.querySelector('.edit-title');
        const contentTextarea = block.querySelector('.edit-content');

        if (titleInput) {
            titleInput.addEventListener('input', () => {
                this.updatePreview(block);
            });
        }

        if (contentTextarea) {
            contentTextarea.addEventListener('input', () => {
                this.updatePreview(block);
            });
        }

        block.querySelector('.edit-form').addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && e.ctrlKey) {
                e.preventDefault();
                this.saveChanges(block);
            }
            if (e.key === 'Escape') {
                this.hideEditForm(block);
            }
        });
    }

    toggleCollapse(block) {
        this.isCollapsed = !this.isCollapsed;
        block.classList.toggle('collapsed');

        const body = block.querySelector('.info-box-body');
        const icon = block.querySelector('.info-box-toggle i');

        if (body) {
            if (this.isCollapsed) {
                body.style.display = 'none';
                icon.className = 'fas fa-chevron-down';
            } else {
                body.style.display = 'block';
                icon.className = 'fas fa-chevron-up';
            }
        }
    }

    updatePreview(block) {
        const type = this.params.type;
        const title = block.querySelector('.edit-title')?.value.trim() || '';
        const showIcon = block.querySelector('.edit-icon')?.checked;
        const isCollapsible = block.querySelector('.edit-collapsible')?.checked;
        const content = block.querySelector('.edit-content')?.value || '';

        const previewParams = {
            type,
            title,
            icon: showIcon,
            collapsible: isCollapsible,
            content
        };

        this.changeTypeRender(block, type);

        const infoBoxContent = block.querySelector('.info-box-content');
        const newPreview = this.renderPreview(previewParams);
        infoBoxContent.outerHTML = newPreview;

        this.initEventListeners(block);
    }

    renderPreview(params) {
        const icon = params.icon ? `<i class="info-box-icon ${this.getIconClass(params.type)}"></i>` : '';
        const typeLabel = this.getTypeLabel(params.type);

        return `
            <div class="info-box-content">
                <div class="info-box-header">
                    <div class="info-box-icon-title">
                        ${icon}
                        ${params.title ? `<span class="info-box-title">${params.title}</span>` : ''}
                        ${!params.title ? `<span class="info-box-type">${typeLabel}</span>` : ''}
                    </div>
                    ${params.collapsible ? `
                        <button class="info-box-toggle">
                            <i class="fas fa-chevron-${this.isCollapsed ? 'down' : 'up'}"></i>
                        </button>
                    ` : ''}
                </div>
                
                <div class="info-box-body" style="display: block;">
                    <div class="info-box-text">${params.content || 'Предпросмотр содержимого...'}</div>
                </div>
            </div>
        `;
    }

    saveChanges(block) {
        const type = this.params.type;
        const title = block.querySelector('.edit-title')?.value.trim() || '';
        const icon = block.querySelector('.edit-icon')?.checked;
        const collapsible = block.querySelector('.edit-collapsible')?.checked;
        const expanded = block.querySelector('.edit-expanded')?.checked;
        const content = block.querySelector('.edit-content')?.value || '';

        this.updateParams({
            type,
            title,
            icon,
            collapsible,
            expanded,
            content
        });

        this.isCollapsed = !expanded;
        this.hideEditForm(block);
        this.showNotification('Блок обновлен', 'success');

        const newContent = this.renderWithEditor();
        block.outerHTML = newContent.outerHTML;

        const newBlock = document.querySelector(`[data-block-id="${this.id}"]`);
        if (newBlock) {
            this.initEventListeners(newBlock);
            this.initEditListeners(newBlock);
        }
    }

    showEditForm(block) {
        this.isEditing = true;
        block.classList.add('editing');
    }

    hideEditForm(block) {
        this.isEditing = false;
        block.classList.remove('editing');
    }

    onDelete(block) {
        if (confirm('Удалить этот блок?')) {
            const event = new CustomEvent('blockDeleted', {
                detail: { blockId: this.id, type: 'infoBox' },
                bubbles: true
            });
            block.dispatchEvent(event);
            block.remove();
        }
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#10b981' : '#3b82f6'};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        `;

        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(20px)';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    updateParams(newParams) {
        this.params = { ...this.params, ...newParams };
    }
}