import IBlock from "./IBlock";

const VIDEO_MAX_SIZE = 200 * 1024 * 1024;
const VIDEO_ALLOWED_EXTENSIONS = ['mp4', 'm4v', 'mov', 'webm', 'ogg', 'ogv', 'avi', 'mpeg', 'mpg', 'mkv'];

function validateVideoFile(file) {
    const extension = file.name.split('.').pop()?.toLowerCase() || '';

    if (!VIDEO_ALLOWED_EXTENSIONS.includes(extension)) {
        throw new Error('Загрузите видео в формате MP4, M4V, MOV, WEBM, OGG, AVI, MPEG или MKV.');
    }

    if (file.size > VIDEO_MAX_SIZE) {
        throw new Error('Видео должно быть не больше 200 МБ.');
    }
}

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
        throw new Error(validationErrors || result.message || 'Не удалось загрузить видео');
    }

    return result.url;
}

export default class VideoBlock extends IBlock {
    constructor(id, params = {}) {
        super(id, 'video', {
            url: '',
            platform: 'youtube',
            sourceType: 'link',
            title: '',
            width: '100%',
            aspectRatio: '16:9',
            autoplay: false,
            controls: true,
            loop: false,
            ...params
        });

        this.isEditing = false;
    }

    render() {
        const block = document.createElement('div');
        block.className = 'video-block';
        block.dataset.blockId = this.id;
        block.dataset.order = this.order;

        const videoHtml = this.getVideoHtml();
        block.innerHTML = `
            <div class="video-content">
                ${videoHtml}
            </div>
        `;

        return block;
    }

    renderWithEditor() {
        const block = document.createElement('div');
        block.className = `video-block ${this.isEditing ? 'editing' : ''}`;
        block.dataset.blockId = this.id;
        block.dataset.order = this.order;

        const videoHtml = this.getVideoHtml();

        block.innerHTML = `
            <div class="video-block-header">
                <button class="block-drag-handle" title="Перетащить">
                    <i class="fas fa-grip-vertical"></i>
                </button>
                <button class="video-delete-btn" title="Удалить блок">
                    <i class="fas fa-times"></i>
                </button>
                <button class="video-edit-btn" title="Настроить видео">
                    <i class="fas fa-cog"></i>
                </button>
            </div>
            
            <div class="video-content">
                ${videoHtml}
            </div>
            
            ${this.params.title ? `
                <div class="video-title">
                    <h3>${this.escapeHtml(this.params.title)}</h3>
                </div>
            ` : ''}
            
            <div class="video-edit-form">
                <div class="video-edit-header">
                    <h4><i class="fas fa-video"></i> Настройки видео</h4>
                </div>
                
                <div class="video-edit-body">
                    <div class="video-form-group">
                        <label>Платформа</label>
                        <div class="video-platform-selector">
                            <button class="video-platform-btn ${this.params.platform === 'youtube' ? 'active' : ''}" data-platform="youtube">
                                <i class="fab fa-youtube"></i>
                                YouTube
                            </button>
                            <button class="video-platform-btn ${this.params.platform === 'vimeo' ? 'active' : ''}" data-platform="vimeo">
                                <i class="fab fa-vimeo"></i>
                                Vimeo
                            </button>
                            <button class="video-platform-btn ${this.params.platform === 'upload' ? 'active' : ''}" data-platform="upload">
                                <i class="fas fa-upload"></i>
                                Файл
                            </button>
                        </div>
                    </div>
                    
                    <div class="video-form-group video-link-group" style="${this.params.platform === 'upload' ? 'display:none;' : ''}">
                        <label>Ссылка на видео</label>
                        <input type="text" class="video-edit-url" 
                               value="${this.params.url}" 
                               placeholder="https://www.youtube.com/watch?v=... или https://vimeo.com/...">
                        <div class="video-url-example">
                            Пример: https://www.youtube.com/watch?v=dQw4w9WgXcQ
                        </div>
                    </div>

                    <div class="video-form-group video-file-group" style="${this.params.platform === 'upload' ? '' : 'display:none;'}">
                        <label class="video-upload-zone">
                            <input type="file" class="video-file-input" accept=".mp4,.m4v,.mov,.webm,.ogg,.ogv,.avi,.mpeg,.mpg,.mkv,video/mp4,video/webm,video/ogg,video/quicktime,video/x-msvideo,video/mpeg,video/x-matroska" hidden>
                            <i class="fas fa-cloud-upload-alt"></i>
                            <strong>${this.params.url && this.params.platform === 'upload' ? 'Заменить видеофайл' : 'Загрузить видеофайл'}</strong>
                            <span>MP4, WEBM, OGG, MOV, AVI, MPEG или MKV до 200 МБ</span>
                        </label>
                    </div>
                    
                    <div class="video-form-group">
                        <label>Заголовок (необязательно)</label>
                        <input type="text" class="video-edit-title" 
                               value="${this.escapeHtml(this.params.title)}" 
                               placeholder="Название видео">
                    </div>
                    
                    <div class="video-form-row">
                        <div class="video-form-group">
                            <label>Соотношение сторон</label>
                            <div class="video-aspect-ratio">
                                <button class="video-ratio-btn ${this.params.aspectRatio === '16:9' ? 'active' : ''}" data-ratio="16:9">
                                    16:9
                                </button>
                                <button class="video-ratio-btn ${this.params.aspectRatio === '4:3' ? 'active' : ''}" data-ratio="4:3">
                                    4:3
                                </button>
                                <button class="video-ratio-btn ${this.params.aspectRatio === '1:1' ? 'active' : ''}" data-ratio="1:1">
                                    1:1
                                </button>
                                <button class="video-ratio-btn ${this.params.aspectRatio === '21:9' ? 'active' : ''}" data-ratio="21:9">
                                    21:9
                                </button>
                            </div>
                        </div>
                        
                        <div class="video-form-group">
                            <label>Ширина</label>
                            <div class="video-width-selector">
                                <button class="video-width-btn ${this.params.width === '100%' ? 'active' : ''}" data-width="100%">
                                    100%
                                </button>
                                <button class="video-width-btn ${this.params.width === '80%' ? 'active' : ''}" data-width="80%">
                                    80%
                                </button>
                                <button class="video-width-btn ${this.params.width === '60%' ? 'active' : ''}" data-width="60%">
                                    60%
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="video-form-row">
                        <div class="video-form-group">
                            <label class="video-checkbox-label">
                                <input type="checkbox" class="video-edit-autoplay" ${this.params.autoplay ? 'checked' : ''}>
                                <div class="video-checkbox-custom"></div>
                                Автовоспроизведение
                            </label>
                        </div>
                        
                        <div class="video-form-group">
                            <label class="video-checkbox-label">
                                <input type="checkbox" class="video-edit-controls" ${this.params.controls ? 'checked' : ''}>
                                <div class="video-checkbox-custom"></div>
                                Элементы управления
                            </label>
                        </div>
                        
                        <div class="video-form-group">
                            <label class="video-checkbox-label">
                                <input type="checkbox" class="video-edit-loop" ${this.params.loop ? 'checked' : ''}>
                                <div class="video-checkbox-custom"></div>
                                Зациклить видео
                            </label>
                        </div>
                    </div>
                    
                    <div class="video-form-actions">
                        <button class="video-btn video-btn-cancel">Отмена</button>
                        <button class="video-btn video-btn-primary video-btn-save">Сохранить</button>
                    </div>
                </div>
            </div>
        `;

        this.initEventListeners(block);
        this.initEditListeners(block);
        return block;
    }

    getVideoHtml() {
        if (!this.params.url) {
            return this.getPlaceholderHtml();
        }

        if (this.params.platform === 'upload') {
            return `
                <div class="video-embed-container" style="width: ${this.params.width}; aspect-ratio: ${this.params.aspectRatio.replace(':', '/')};">
                    <video
                        src="${this.escapeHtml(this.params.url)}"
                        ${this.params.controls ? 'controls' : ''}
                        ${this.params.autoplay ? 'autoplay muted' : ''}
                        ${this.params.loop ? 'loop' : ''}
                        preload="metadata"
                    ></video>
                </div>
            `;
        }

        const videoId = this.extractVideoId();
        if (!videoId) {
            return `
                <div class="video-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Неверная ссылка на видео. Пожалуйста, проверьте URL.</p>
                </div>
            `;
        }

        const embedUrl = this.getEmbedUrl(videoId);

        return `
            <div class="video-embed-container" style="width: ${this.params.width}; aspect-ratio: ${this.params.aspectRatio.replace(':', '/')};">
                <iframe 
                    src="${embedUrl}"
                    title="${this.escapeHtml(this.params.title || 'Видео')}"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    loading="lazy"
                ></iframe>
            </div>
        `;
    }

    getPlaceholderHtml() {
        return `
            <div class="video-placeholder">
                <div class="video-placeholder-icon">
                    <i class="fas fa-video"></i>
                </div>
                <p>Вставьте ссылку или загрузите видеофайл</p>
                <button class="video-placeholder-btn">
                    <i class="fas fa-plus"></i> Добавить видео
                </button>
            </div>
        `;
    }

    extractVideoId() {
        const url = this.params.url.trim();

        if (this.params.platform === 'youtube') {
            const patterns = [
                /(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/,
                /(?:youtube\.com\/embed\/)([^&\n?#]+)/,
                /(?:youtube\.com\/v\/)([^&\n?#]+)/
            ];

            for (const pattern of patterns) {
                const match = url.match(pattern);
                if (match && match[1]) {
                    return match[1];
                }
            }
        } else if (this.params.platform === 'vimeo') {
            const patterns = [
                /vimeo\.com\/(\d+)/,
                /player\.vimeo\.com\/video\/(\d+)/
            ];

            for (const pattern of patterns) {
                const match = url.match(pattern);
                if (match && match[1]) {
                    return match[1];
                }
            }
        }

        return null;
    }

    getEmbedUrl(videoId) {
        let embedUrl = '';
        const params = new URLSearchParams();

        if (this.params.platform === 'youtube') {
            embedUrl = `https://www.youtube.com/embed/${videoId}`;

            if (this.params.autoplay) params.append('autoplay', '1');
            if (!this.params.controls) params.append('controls', '0');
            if (this.params.loop) params.append('loop', '1');

        } else if (this.params.platform === 'vimeo') {
            embedUrl = `https://player.vimeo.com/video/${videoId}`;

            if (this.params.autoplay) params.append('autoplay', '1');
            if (!this.params.controls) params.append('controls', '0');
            if (this.params.loop) params.append('loop', '1');
            params.append('title', '0');
            params.append('byline', '0');
            params.append('portrait', '0');
        }

        const queryString = params.toString();
        return queryString ? `${embedUrl}?${queryString}` : embedUrl;
    }

    initEventListeners(block) {
        const editBtn = block.querySelector('.video-edit-btn');
        const placeholderBtn = block.querySelector('.video-placeholder-btn');
        const content = block.querySelector('.video-content');

        if (editBtn) {
            editBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.showEditForm(block);
            });
        }

        if (placeholderBtn) {
            placeholderBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.showEditForm(block);
            });
        }

        if (content && !this.params.url) {
            content.addEventListener('click', () => {
                this.showEditForm(block);
            });
        }
    }

    initEditListeners(block) {
        const deleteBtn = block.querySelector('.video-delete-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.onDelete(block);
            });
        }

        const saveBtn = block.querySelector('.video-btn-save');
        const cancelBtn = block.querySelector('.video-btn-cancel');

        if (saveBtn) saveBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.saveChanges(block);
        });
        if (cancelBtn) cancelBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.hideEditForm(block);
        });

        block.querySelectorAll('.video-platform-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const platform = btn.dataset.platform;
                block.querySelectorAll('.video-platform-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                this.params.platform = platform;
                this.updateExampleUrl(block);
                this.toggleSourceInputs(block);
                this.updatePreview(block);
            });
        });

        block.querySelectorAll('.video-ratio-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const ratio = btn.dataset.ratio;
                block.querySelectorAll('.video-ratio-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                this.params.aspectRatio = ratio;
                this.updatePreview(block);
            });
        });

        block.querySelectorAll('.video-width-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const width = btn.dataset.width;
                block.querySelectorAll('.video-width-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                this.params.width = width;
                this.updatePreview(block);
            });
        });

        const urlInput = block.querySelector('.video-edit-url');
        if (urlInput) {
            urlInput.addEventListener('input', () => {
                this.params.url = urlInput.value.trim();
                this.autoDetectPlatform(block);
                this.updatePreview(block);
            });
        }

        const fileInput = block.querySelector('.video-file-input');
        if (fileInput) {
            fileInput.addEventListener('change', async (event) => {
                const file = event.target.files?.[0];
                if (!file) return;

                const uploadZone = block.querySelector('.video-upload-zone');
                uploadZone.classList.add('is-loading');

                try {
                    validateVideoFile(file);
                    this.params.platform = 'upload';
                    this.params.sourceType = 'upload';
                    this.params.url = await uploadCourseAsset(file, 'video');
                    this.updatePreview(block);
                } catch (error) {
                    alert(error.message);
                } finally {
                    uploadZone.classList.remove('is-loading');
                }
            });
        }

        const titleInput = block.querySelector('.video-edit-title');
        if (titleInput) {
            titleInput.addEventListener('input', () => {
                this.params.title = titleInput.value.trim();
            });
        }

        const autoplayCheckbox = block.querySelector('.video-edit-autoplay');
        const controlsCheckbox = block.querySelector('.video-edit-controls');
        const loopCheckbox = block.querySelector('.video-edit-loop');

        if (autoplayCheckbox) {
            autoplayCheckbox.addEventListener('change', () => {
                this.params.autoplay = autoplayCheckbox.checked;
            });
        }

        if (controlsCheckbox) {
            controlsCheckbox.addEventListener('change', () => {
                this.params.controls = controlsCheckbox.checked;
            });
        }

        if (loopCheckbox) {
            loopCheckbox.addEventListener('change', () => {
                this.params.loop = loopCheckbox.checked;
            });
        }

        block.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isEditing) {
                this.hideEditForm(block);
            }
        });
    }

    autoDetectPlatform(block) {
        const url = this.params.url.toLowerCase();

        if (url.includes('youtube.com') || url.includes('youtu.be')) {
            this.params.platform = 'youtube';
            block.querySelectorAll('.video-platform-btn').forEach(b => b.classList.remove('active'));
            block.querySelector('.video-platform-btn[data-platform="youtube"]')?.classList.add('active');
            this.updateExampleUrl(block);
        } else if (url.includes('vimeo.com')) {
            this.params.platform = 'vimeo';
            block.querySelectorAll('.video-platform-btn').forEach(b => b.classList.remove('active'));
            block.querySelector('.video-platform-btn[data-platform="vimeo"]')?.classList.add('active');
            this.updateExampleUrl(block);
        }
    }

    updateExampleUrl(block) {
        const example = block.querySelector('.video-url-example');
        if (!example) return;

        if (this.params.platform === 'youtube') {
            example.textContent = 'Пример: https://www.youtube.com/watch?v=dQw4w9WgXcQ';
        } else if (this.params.platform === 'vimeo') {
            example.textContent = 'Пример: https://vimeo.com/148751763';
        } else {
            example.textContent = 'Загрузите видеофайл с компьютера';
        }
    }

    toggleSourceInputs(block) {
        const isUpload = this.params.platform === 'upload';
        const linkGroup = block.querySelector('.video-link-group');
        const fileGroup = block.querySelector('.video-file-group');

        if (linkGroup) linkGroup.style.display = isUpload ? 'none' : '';
        if (fileGroup) fileGroup.style.display = isUpload ? '' : 'none';
    }

    updatePreview(block) {
        const newVideoHtml = this.getVideoHtml();
        const content = block.querySelector('.video-content');
        if (content) {
            content.innerHTML = newVideoHtml;
            this.initEventListeners(block);
        }

        const titleContainer = block.querySelector('.video-title');
        const titleInput = block.querySelector('.video-edit-title');

        if (titleInput && this.params.title) {
            if (titleContainer) {
                titleContainer.querySelector('h3').textContent = this.params.title;
            } else {
                const newTitle = document.createElement('div');
                newTitle.className = 'video-title';
                newTitle.innerHTML = `<h3>${this.escapeHtml(this.params.title)}</h3>`;
                block.querySelector('.video-content').insertAdjacentElement('afterend', newTitle);
            }
        } else if (titleContainer && !this.params.title) {
            titleContainer.remove();
        }
    }

    saveChanges(block) {
        const platform = this.params.platform;
        const url = platform === 'upload' ? this.params.url : block.querySelector('.video-edit-url').value.trim();
        const title = block.querySelector('.video-edit-title').value.trim();
        const aspectRatio = this.params.aspectRatio;
        const width = this.params.width;
        const autoplay = block.querySelector('.video-edit-autoplay').checked;
        const controls = block.querySelector('.video-edit-controls').checked;
        const loop = block.querySelector('.video-edit-loop').checked;

        this.updateParams({
            platform,
            sourceType: platform === 'upload' ? 'upload' : 'link',
            url,
            title,
            aspectRatio,
            width,
            autoplay,
            controls,
            loop
        });

        this.hideEditForm(block);
        this.showNotification('Видео обновлено');

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
        if (confirm('Удалить это видео?')) {
            const event = new CustomEvent('blockDeleted', {
                detail: { blockId: this.id, type: 'video' },
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
