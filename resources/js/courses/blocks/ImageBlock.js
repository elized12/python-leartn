import IBlock from "./IBlock";

async function uploadCourseAsset(file, type) {
    const formData = new FormData();
    formData.append('type', type);
    formData.append('file', file);

    const response = await fetch('/courses/assets', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: formData
    });

    const result = await response.json();
    if (!response.ok || !result.status) {
        const validationErrors = result.errors
            ? Object.values(result.errors).flat().join('\n')
            : '';
        throw new Error(validationErrors || result.message || 'Не удалось загрузить файл');
    }

    return result.url;
}

export default class ImageBlock extends IBlock {
    constructor(id, params = {}) {
        super(id, 'image', {
            src: '',
            alt: '',
            caption: '',
            width: '100%',
            align: 'center',
            rounded: true,
            shadow: true,
            ...params
        });

        this.isEditing = false;
    }

    render() {
        const block = document.createElement('div');
        block.className = `image-block image-align-${this.params.align}`;
        block.dataset.blockId = this.id;
        block.dataset.order = this.order;
        block.innerHTML = this.getImageHtml();
        return block;
    }

    renderWithEditor() {
        const block = document.createElement('div');
        block.className = `image-block image-align-${this.params.align} ${this.isEditing ? 'editing' : ''}`;
        block.dataset.blockId = this.id;
        block.dataset.order = this.order;
        block.innerHTML = `
            <div class="image-block-header">
                <button class="block-drag-handle" title="Перетащить"><i class="fas fa-grip-vertical"></i></button>
                <button class="image-edit-btn" title="Настроить"><i class="fas fa-sliders-h"></i></button>
                <button class="image-delete-btn" title="Удалить"><i class="fas fa-times"></i></button>
            </div>
            <div class="image-content">${this.getImageHtml()}</div>
            <div class="image-edit-form">
                <div class="image-edit-header">
                    <h4><i class="fas fa-image"></i> Изображение</h4>
                </div>
                <div class="image-edit-body">
                    <label class="image-upload-zone">
                        <input type="file" class="image-file-input" accept="image/*" hidden>
                        <i class="fas fa-cloud-upload-alt"></i>
                        <strong>${this.params.src ? 'Заменить изображение' : 'Загрузить изображение'}</strong>
                        <span>PNG, JPG, WEBP или GIF до 10 МБ</span>
                    </label>

                    <div class="image-form-grid">
                        <label>Alt-текст
                            <input type="text" class="image-alt-input" value="${this.escapeHtml(this.params.alt)}" placeholder="Кратко опишите изображение">
                        </label>
                        <label>Подпись
                            <input type="text" class="image-caption-input" value="${this.escapeHtml(this.params.caption)}" placeholder="Необязательная подпись">
                        </label>
                    </div>

                    <div class="image-control-row">
                        <div>
                            <span>Ширина</span>
                            <div class="image-segmented">
                                ${['100%', '80%', '60%', '40%'].map(width => `
                                    <button type="button" class="image-width-btn ${this.params.width === width ? 'active' : ''}" data-width="${width}">${width}</button>
                                `).join('')}
                            </div>
                        </div>
                        <div>
                            <span>Выравнивание</span>
                            <div class="image-segmented">
                                ${[
                                    ['left', 'fa-align-left'],
                                    ['center', 'fa-align-center'],
                                    ['right', 'fa-align-right'],
                                ].map(([align, icon]) => `
                                    <button type="button" class="image-align-btn ${this.params.align === align ? 'active' : ''}" data-align="${align}">
                                        <i class="fas ${icon}"></i>
                                    </button>
                                `).join('')}
                            </div>
                        </div>
                    </div>

                    <div class="image-toggle-row">
                        <label><input type="checkbox" class="image-rounded-input" ${this.params.rounded ? 'checked' : ''}> Скругление</label>
                        <label><input type="checkbox" class="image-shadow-input" ${this.params.shadow ? 'checked' : ''}> Тень</label>
                    </div>

                    <div class="image-form-actions">
                        <button type="button" class="image-btn image-btn-cancel">Отмена</button>
                        <button type="button" class="image-btn image-btn-save">Сохранить</button>
                    </div>
                </div>
            </div>
        `;

        this.initEventListeners(block);
        return block;
    }

    getImageHtml() {
        if (!this.params.src) {
            return `
                <div class="image-placeholder">
                    <i class="fas fa-image"></i>
                    <p>Загрузите изображение для урока</p>
                </div>
            `;
        }

        return `
            <figure class="course-image-figure ${this.params.shadow ? 'has-shadow' : ''}" style="width:${this.params.width}">
                <img class="${this.params.rounded ? 'is-rounded' : ''}" src="${this.escapeHtml(this.params.src)}" alt="${this.escapeHtml(this.params.alt)}">
                ${this.params.caption ? `<figcaption>${this.escapeHtml(this.params.caption)}</figcaption>` : ''}
            </figure>
        `;
    }

    initEventListeners(block) {
        block.querySelector('.image-edit-btn')?.addEventListener('click', (event) => {
            event.stopPropagation();
            this.showEditForm(block);
        });

        block.querySelector('.image-placeholder')?.addEventListener('click', () => this.showEditForm(block));

        block.querySelector('.image-delete-btn')?.addEventListener('click', (event) => {
            event.stopPropagation();
            this.onDelete(block);
        });

        block.querySelector('.image-btn-cancel')?.addEventListener('click', () => this.hideEditForm(block));
        block.querySelector('.image-btn-save')?.addEventListener('click', () => this.saveChanges(block));

        block.querySelector('.image-file-input')?.addEventListener('change', async (event) => {
            const file = event.target.files?.[0];
            if (!file) return;

            const uploadZone = block.querySelector('.image-upload-zone');
            uploadZone.classList.add('is-loading');

            try {
                this.params.src = await uploadCourseAsset(file, 'image');
                block.querySelector('.image-content').innerHTML = this.getImageHtml();
            } catch (error) {
                alert(error.message);
            } finally {
                uploadZone.classList.remove('is-loading');
            }
        });

        block.querySelectorAll('.image-width-btn').forEach(button => {
            button.addEventListener('click', () => {
                this.params.width = button.dataset.width;
                block.querySelectorAll('.image-width-btn').forEach(item => item.classList.remove('active'));
                button.classList.add('active');
                block.querySelector('.image-content').innerHTML = this.getImageHtml();
            });
        });

        block.querySelectorAll('.image-align-btn').forEach(button => {
            button.addEventListener('click', () => {
                this.params.align = button.dataset.align;
                block.querySelectorAll('.image-align-btn').forEach(item => item.classList.remove('active'));
                button.classList.add('active');
                block.className = `image-block image-align-${this.params.align} editing`;
            });
        });
    }

    saveChanges(block) {
        this.updateParams({
            alt: block.querySelector('.image-alt-input').value.trim(),
            caption: block.querySelector('.image-caption-input').value.trim(),
            rounded: block.querySelector('.image-rounded-input').checked,
            shadow: block.querySelector('.image-shadow-input').checked,
        });

        block.querySelector('.image-content').innerHTML = this.getImageHtml();
        this.hideEditForm(block);
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
        if (!confirm('Удалить изображение?')) {
            return;
        }

        block.dispatchEvent(new CustomEvent('blockDeleted', {
            detail: { blockId: this.id, type: 'image' },
            bubbles: true
        }));
        block.remove();
    }

    updateParams(newParams) {
        this.params = { ...this.params, ...newParams };
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
}
