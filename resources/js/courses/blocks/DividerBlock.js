import IBlock from "./IBlock";

export default class DividerBlock extends IBlock {
    constructor(id, params = {}) {
        super(id, 'divider', {
            type: 'solid',
            thickness: 2,
            margin: 32,
            label: '',
            labelPosition: 'center',
            ...params
        });

        this.isEditing = false;
        this.baseColor = '#7c3aed';
    }

    render() {
        const block = document.createElement('div');
        block.className = `divider-block divider-type-${this.params.type}`;
        block.dataset.blockId = this.id;
        block.dataset.order = this.order;

        block.style.margin = `${this.params.margin}px 0`;

        const dividerHtml = this.getDividerHtml();
        block.innerHTML = `
            <div class="divider-content">
                ${dividerHtml}
            </div>
        `;

        return block;
    }

    renderWithEditor() {
        const block = document.createElement('div');
        block.className = `divider-block divider-type-${this.params.type} ${this.isEditing ? 'editing' : ''}`;
        block.dataset.blockId = this.id;
        block.dataset.order = this.order;

        block.style.margin = `${this.params.margin}px 0`;

        const dividerHtml = this.getDividerHtml();

        block.innerHTML = `
            <div class="divider-block-header">
                <button class="divider-drag-handle" title="Перетащить">
                    <i class="fas fa-grip-vertical"></i>
                </button>
                <button class="divider-delete-btn" title="Удалить блок">
                    <i class="fas fa-times"></i>
                </button>
                <button class="divider-edit-btn" title="Настроить">
                    <i class="fas fa-sliders-h"></i>
                </button>
            </div>
            
            <div class="divider-content">
                ${dividerHtml}
            </div>
            
            <div class="divider-edit-form">
                <div class="divider-edit-body">
                    <div class="divider-form-group">
                        <label>Тип линии</label>
                        <div class="divider-type-selector">
                            ${this.getDividerTypes().map(type => `
                                <button class="divider-type-btn ${this.params.type === type.value ? 'active' : ''}" data-type="${type.value}">
                                    <div class="divider-type-icon">${type.icon}</div>
                                    <span>${type.label}</span>
                                </button>
                            `).join('')}
                        </div>
                    </div>
                    
                    <div class="divider-form-group">
                        <label>Толщина: <span class="divider-value">${this.params.thickness}px</span></label>
                        <input type="range" class="divider-edit-thickness" 
                               min="1" max="6" 
                               value="${this.params.thickness}">
                    </div>
                    
                    <div class="divider-form-group">
                        <label>Отступ: <span class="divider-value">${this.params.margin}px</span></label>
                        <input type="range" class="divider-edit-margin" 
                               min="8" max="64" step="4"
                               value="${this.params.margin}">
                    </div>
                    
                    <div class="divider-form-group">
                        <label>Подпись (необязательно)</label>
                        <input type="text" class="divider-edit-label" 
                               value="${this.escapeHtml(this.params.label)}" 
                               placeholder="Введите подпись">
                    </div>
                    
                    ${this.params.label ? `
                        <div class="divider-form-group">
                            <label>Положение подписи</label>
                            <div class="divider-label-position">
                                <button class="divider-position-btn ${this.params.labelPosition === 'left' ? 'active' : ''}" data-position="left">
                                    <i class="fas fa-align-left"></i>
                                    Слева
                                </button>
                                <button class="divider-position-btn ${this.params.labelPosition === 'center' ? 'active' : ''}" data-position="center">
                                    <i class="fas fa-align-center"></i>
                                    Центр
                                </button>
                                <button class="divider-position-btn ${this.params.labelPosition === 'right' ? 'active' : ''}" data-position="right">
                                    <i class="fas fa-align-right"></i>
                                    Справа
                                </button>
                            </div>
                        </div>
                    ` : ''}
                    
                    <div class="divider-form-actions">
                        <button class="divider-btn divider-btn-save">Сохранить</button>
                    </div>
                </div>
            </div>
        `;

        this.initEventListeners(block);
        this.initEditListeners(block);
        return block;
    }

    getDividerTypes() {
        return [
            { value: 'solid', label: 'Сплошная', icon: '—' },
            { value: 'dashed', label: 'Пунктир', icon: '---' },
            { value: 'dotted', label: 'Точки', icon: '•••' },
            { value: 'double', label: 'Двойная', icon: '═' }
        ];
    }

    getDividerHtml() {
        const thickness = this.params.thickness;
        const color = this.baseColor;
        const label = this.params.label;
        const position = this.params.labelPosition;

        let dividerStyle = `height: ${thickness}px; background-color: ${color};`;

        if (this.params.type === 'dashed') {
            dividerStyle = `height: 0; border: none; border-top: ${thickness}px dashed ${color};`;
        } else if (this.params.type === 'dotted') {
            dividerStyle = `height: 0; border: none; border-top: ${thickness}px dotted ${color};`;
        } else if (this.params.type === 'double') {
            dividerStyle = `height: ${thickness * 2}px; border: none; border-top: ${thickness}px solid ${color}; border-bottom: ${thickness}px solid ${color}; background: transparent;`;
        }

        if (label) {
            return `
                <div class="divider-with-label divider-label-${position}">
                    <div class="divider-label-container">
                        <div class="divider-line divider-${this.params.type}" style="${dividerStyle}"></div>
                        <span class="divider-label-text">${label}</span>
                        <div class="divider-line divider-${this.params.type}" style="${dividerStyle}"></div>
                    </div>
                </div>
            `;
        } else {
            return `<div class="divider-line divider-${this.params.type}" style="${dividerStyle}"></div>`;
        }
    }

    initEventListeners(block) {
        const content = block.querySelector('.divider-content');
        const editBtn = block.querySelector('.divider-edit-btn');

        if (content && !this.isEditing) {
            content.addEventListener('click', () => {
                this.showEditForm(block);
            });
        }

        if (editBtn) {
            editBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.showEditForm(block);
            });
        }
    }

    initEditListeners(block) {
        const deleteBtn = block.querySelector('.divider-delete-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.onDelete(block);
            });
        }

        const saveBtn = block.querySelector('.divider-btn-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.saveChanges(block);
            });
        }

        block.querySelectorAll('.divider-type-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const type = btn.dataset.type;
                block.querySelectorAll('.divider-type-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                this.params.type = type;
                this.updatePreview(block);
            });
        });

        block.querySelectorAll('.divider-position-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const position = btn.dataset.position;
                block.querySelectorAll('.divider-position-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                this.params.labelPosition = position;
                this.updatePreview(block);
            });
        });

        const thicknessSlider = block.querySelector('.divider-edit-thickness');
        if (thicknessSlider) {
            thicknessSlider.addEventListener('input', (e) => {
                const value = e.target.value;
                block.querySelector('.divider-value').textContent = `${value}px`;
                this.params.thickness = parseInt(value);
                this.updatePreview(block);
            });
        }

        const marginSlider = block.querySelector('.divider-edit-margin');
        if (marginSlider) {
            marginSlider.addEventListener('input', (e) => {
                const value = e.target.value;
                block.querySelectorAll('.divider-value')[1].textContent = `${value}px`;
                this.params.margin = parseInt(value);
                block.style.margin = `${value}px 0`;
                this.updatePreview(block);
            });
        }

        const labelInput = block.querySelector('.divider-edit-label');
        if (labelInput) {
            labelInput.addEventListener('input', () => {
                this.params.label = labelInput.value.trim();
                this.updatePreview(block);
            });
        }

        block.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isEditing) {
                this.hideEditForm(block);
            }
        });
    }

    updatePreview(block) {
        const newDividerHtml = this.getDividerHtml();
        const content = block.querySelector('.divider-content');
        if (content) {
            content.innerHTML = newDividerHtml;
        }

        const types = ['solid', 'dashed', 'dotted', 'double'];
        types.forEach(type => block.classList.remove(`divider-type-${type}`));
        block.classList.add(`divider-type-${this.params.type}`);

        this.initEventListeners(block);
    }

    saveChanges(block) {
        const type = this.params.type;
        const thickness = parseInt(block.querySelector('.divider-edit-thickness')?.value) || 2;
        const margin = parseInt(block.querySelector('.divider-edit-margin')?.value) || 32;
        const label = block.querySelector('.divider-edit-label')?.value.trim() || '';
        const labelPosition = this.params.labelPosition;

        this.updateParams({
            type,
            thickness,
            margin,
            label,
            labelPosition
        });

        this.hideEditForm(block);
        this.showNotification('Разделитель обновлен');
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
        if (confirm('Удалить этот разделитель?')) {
            const event = new CustomEvent('blockDeleted', {
                detail: { blockId: this.id, type: 'divider' },
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

    showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.textContent = message;

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #7c3aed;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            z-index: 9999;
            font-size: 14px;
            animation: slideIn 0.3s ease;
        `;

        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 2000);
    }

    updateParams(newParams) {
        this.params = { ...this.params, ...newParams };
    }
}