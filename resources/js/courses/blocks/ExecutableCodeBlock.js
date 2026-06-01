import IBlock from "./IBlock";
import PythonService from "../../service/language/PythonService";

export default class ExecutableCodeBlock extends IBlock {
    constructor(id, params = {}) {
        super(id, 'executableCode', {
            code: '# Напишите код на Python\nprint("Hello, World!")',
            language: 'python',
            title: 'Задание по программированию',
            description: 'Напишите код для решения задачи',
            timeout: 10000,
            ...params
        });

        this.isEditing = false;
        this.isRunning = false;
    }

    render() {
        const block = document.createElement('div');
        block.className = `executable-code-block ${this.isEditing ? 'editing' : ''}`;
        block.dataset.blockId = this.id;
        block.dataset.order = this.order;

        const highlightedCode = this.highlightCode(this.params.code, this.params.language);

        block.innerHTML = `            
            <div class="executable-code-content">
                <div class="code-block-title">${this.params.title}</div>
                <div class="code-block-description">${this.params.description}</div>
                
                <div class="code-editor-container">
                    <div class="code-header">
                        <div class="language-badge">
                            <i class="fas fa-code"></i>
                            <span>${this.getLanguageName(this.params.language)}</span>
                        </div>
                        <div class="code-actions">
                            <button class="code-action-btn copy-code-btn" title="Копировать код">
                                <i class="far fa-copy"></i>
                            </button>
                            <button class="code-action-btn run-code-btn" title="Запустить код">
                                <i class="fas fa-play"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="runtime-code-workspace">
                        <div class="runtime-editor-shell">
                            <div class="runtime-pane-title">Редактор</div>
                            <textarea class="runtime-code-editor" spellcheck="false" wrap="off">${this.escapeHtml(this.params.code)}</textarea>
                        </div>
                        <div class="runtime-preview-shell">
                            <div class="runtime-pane-title">Подсветка</div>
                            <pre class="runtime-code-highlight"><code class="hljs language-${this.params.language}">${highlightedCode}</code></pre>
                        </div>
                    </div>
                </div>
                
                <div class="code-output">
                    <div class="output-header">
                        <span class="output-title">
                            <i class="fas fa-terminal"></i>
                            Вывод:
                        </span>
                        <button class="clear-output-btn" title="Очистить вывод">
                            <i class="fas fa-trash-alt"></i> Очистить
                        </button>
                    </div>
                    <div class="output-content" id="output-${this.id}">
                        <div class="output-placeholder">
                            <i class="fas fa-code"></i>
                            <span>Нажмите "Запустить" чтобы увидеть результат</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.initEventListeners(block);

        return block;
    }

    renderWithEditor() {
        const block = document.createElement('div');
        block.className = `executable-code-block ${this.isEditing ? 'editing' : ''}`;
        block.dataset.blockId = this.id;
        block.dataset.order = this.order;

        const highlightedCode = this.highlightCode(this.params.code, this.params.language);

        block.innerHTML = `
            <div class="executable-code-header">
                <button class="block-drag-handle" title="Перетащить">
                    <i class="fas fa-grip-vertical"></i>
                </button>
                <button class="block-delete-btn" title="Удалить блок">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="executable-code-content">
                <div class="code-block-title">${this.params.title}</div>
                <div class="code-block-description">${this.params.description}</div>
                
                <div class="code-editor-container">
                    <div class="code-header">
                        <div class="language-badge">
                            <i class="fas fa-code"></i>
                            <span>${this.getLanguageName(this.params.language)}</span>
                        </div>
                        <div class="code-actions">
                            <button class="code-action-btn copy-code-btn" title="Копировать код">
                                <i class="far fa-copy"></i>
                            </button>
                            <button class="code-action-btn run-code-btn" title="Запустить код">
                                <i class="fas fa-play"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="code-container">
                        <pre><code class="hljs language-${this.params.language}">${highlightedCode}</code></pre>
                    </div>
                </div>
                
                <div class="code-output">
                    <div class="output-header">
                        <span class="output-title">
                            <i class="fas fa-terminal"></i>
                            Вывод:
                        </span>
                        <button class="clear-output-btn" title="Очистить вывод">
                            <i class="fas fa-trash-alt"></i> Очистить
                        </button>
                    </div>
                    <div class="output-content" id="output-${this.id}">
                        <div class="output-placeholder">
                            <i class="fas fa-code"></i>
                            <span>Нажмите "Запустить" чтобы увидеть результат</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="executable-code-edit">
                <div class="edit-form">
                    <div class="edit-header">
                        <h4>Редактирование задания</h4>
                        <button class="close-edit-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label>Заголовок задания</label>
                        <input type="text" class="edit-title" 
                               placeholder="Введите название задания" 
                               value="${this.escapeHtml(this.params.title)}">
                    </div>
                    
                    <div class="form-group">
                        <label>Описание задания</label>
                        <textarea class="edit-description" 
                                  placeholder="Опишите задание..." 
                                  rows="3">${this.escapeHtml(this.params.description)}</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Язык программирования</label>
                        <select class="edit-language">
                            <option value="python" ${this.params.language === 'python' ? 'selected' : ''}>Python</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Код</label>
                        <textarea class="edit-code" 
                                  placeholder="Введите код на Python..." 
                                  rows="10">${this.escapeHtml(this.params.code)}</textarea>
                        <div class="code-hint">
                            <i class="fas fa-info-circle"></i>
                            Код выполняется в отдельном потоке. Максимальное время выполнения — 10 секунд.
                        </div>
                    </div>
                    
                    <div class="edit-footer">
                        <button class="btn btn-cancel">Отмена</button>
                        <button class="btn btn-primary btn-save">Сохранить изменения</button>
                    </div>
                </div>
            </div>
        `;

        this.initEventListeners(block);
        this.initEditFormListeners(block);

        return block;
    }

    initEventListeners(blockElement) {
        const copyBtn = blockElement.querySelector('.copy-code-btn');
        copyBtn.addEventListener('click', () => {
            this.copyCodeToClipboard(blockElement);
        });

        const runBtn = blockElement.querySelector('.run-code-btn');
        runBtn.addEventListener('click', (event) => {
            event.stopPropagation();
            this.runCode(blockElement);
        });

        const clearBtn = blockElement.querySelector('.clear-output-btn');
        clearBtn.addEventListener('click', () => {
            this.clearOutput(blockElement);
        });

        this.initRuntimeCodeEditor(blockElement);
    }

    initRuntimeCodeEditor(blockElement) {
        const editor = blockElement.querySelector('.runtime-code-editor');
        const highlight = blockElement.querySelector('.runtime-code-highlight code');

        if (!editor || !highlight) {
            return;
        }

        const renderHighlight = () => {
            const code = editor.value || ' ';
            highlight.className = `hljs language-${this.params.language}`;
            highlight.innerHTML = this.highlightCode(code, this.params.language);
        };

        editor.addEventListener('input', renderHighlight);
        editor.addEventListener('keydown', (event) => {
            if (event.key !== 'Tab') {
                return;
            }

            event.preventDefault();
            const start = editor.selectionStart;
            const end = editor.selectionEnd;
            const value = editor.value;

            editor.value = `${value.substring(0, start)}    ${value.substring(end)}`;
            editor.selectionStart = editor.selectionEnd = start + 4;
            renderHighlight();
        });

        renderHighlight();
    }

    initEditFormListeners(blockElement) {
        const titleElement = blockElement.querySelector('.code-block-title');
        titleElement.addEventListener('click', (e) => {
            e.stopPropagation();
            this.showEditForm(blockElement);
        });

        const descElement = blockElement.querySelector('.code-block-description');
        descElement.addEventListener('click', (e) => {
            e.stopPropagation();
            this.showEditForm(blockElement);
        });

        const codeContainer = blockElement.querySelector('.code-editor-container');
        codeContainer.addEventListener('click', (e) => {
            if (!e.target.closest('.code-actions')) {
                this.showEditForm(blockElement);
            }
        });

        const deleteBtn = blockElement.querySelector('.block-delete-btn');
        deleteBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.onDelete(blockElement);
        });

        const editTitle = blockElement.querySelector('.edit-title');
        const editDescription = blockElement.querySelector('.edit-description');
        const editLanguage = blockElement.querySelector('.edit-language');

        const cancelEdit = () => {
            this.hideEditForm(blockElement);
        };
        const cancelBtn = blockElement.querySelector('.btn-cancel');
        cancelBtn.addEventListener('click', cancelEdit);


        const saveEdit = () => {
            this.saveChanges(
                editTitle.value.trim(),
                editDescription.value.trim(),
                editLanguage.value,
                editCode.value,
                blockElement
            );
            this.hideEditForm(blockElement);
        };
        const saveBtn = blockElement.querySelector('.btn-save');
        saveBtn.addEventListener('click', saveEdit);

        const closeBtn = blockElement.querySelector('.close-edit-btn');
        closeBtn.addEventListener('click', cancelEdit);

        const editCode = blockElement.querySelector('.edit-code');
        editCode.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                saveEdit();
            }
        });

        blockElement.querySelector('.edit-form').addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                cancelEdit();
            }
        });
    }

    showEditForm(blockElement) {
        if (this.isEditing) return;

        this.isEditing = true;
        blockElement.classList.add('editing');

        const editContainer = blockElement.querySelector('.executable-code-edit');
        editContainer.style.display = 'block';

        editContainer.style.opacity = '0';
        editContainer.style.transform = 'translateY(-10px)';

        setTimeout(() => {
            editContainer.style.opacity = '1';
            editContainer.style.transform = 'translateY(0)';
        }, 10);

        const editCode = blockElement.querySelector('.edit-code');
        setTimeout(() => {
            editCode.focus();
            editCode.select();
        }, 50);
    }

    hideEditForm(blockElement) {
        this.isEditing = false;
        blockElement.classList.remove('editing');

        const editContainer = blockElement.querySelector('.executable-code-edit');
        editContainer.style.opacity = '0';
        editContainer.style.transform = 'translateY(-10px)';

        setTimeout(() => {
            editContainer.style.display = 'none';
        }, 200);
    }

    saveChanges(title, description, language, code, blockElement) {
        this.updateParams({
            title: title || 'Задание по программированию',
            description: description || 'Напишите код для решения задачи',
            language: language,
            code: code
        });

        const titleElement = blockElement.querySelector('.code-block-title');
        titleElement.textContent = this.params.title;

        const descElement = blockElement.querySelector('.code-block-description');
        descElement.textContent = this.params.description;

        const languageBadge = blockElement.querySelector('.language-badge span');
        languageBadge.textContent = this.getLanguageName(this.params.language);

        const codeElement = blockElement.querySelector('.code-container pre code');
        if (codeElement) {
            codeElement.textContent = this.params.code;
            codeElement.className = `hljs language-${this.params.language}`;

            if (window.hljs) {
                hljs.highlightElement(codeElement);
            }
        }

        const runtimeEditor = blockElement.querySelector('.runtime-code-editor');
        if (runtimeEditor) {
            runtimeEditor.value = this.params.code;
            runtimeEditor.dispatchEvent(new Event('input'));
        }

        this.showNotification('Изменения сохранены', 'success');
    }

    async runCode(blockElement) {
        if (this.isRunning) return;

        this.isRunning = true;
        const runBtn = blockElement.querySelector('.run-code-btn');
        const outputDiv = blockElement.querySelector(`#output-${this.id}`);

        const originalText = runBtn.innerHTML;
        runBtn.disabled = true;

        this.clearOutput(blockElement);

        outputDiv.innerHTML = `
            <div class="output-loading">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Загрузка интерпретатора Python...</span>
            </div>
        `;

        try {

            const startTime = performance.now();

            const runtimeEditor = blockElement.querySelector('.runtime-code-editor');
            const code = runtimeEditor ? runtimeEditor.value : this.params.code;
            const result = await PythonService.executeCodeWithTimeout(code, this.params.timeout || 10000);
            const endTime = performance.now();
            const executionTime = ((endTime - startTime) / 1000).toFixed(2);

            this.showResult(result, executionTime, outputDiv);

        } catch (error) {

            this.showError(error.message, outputDiv);
        } finally {
            runBtn.disabled = false;
            runBtn.innerHTML = originalText;
            this.isRunning = false;
        }
    }

    showResult(result, executionTime, outputDiv) {
        const { output, error } = result;
        if (error) {

            this.showError(error, outputDiv);
            return;
        }

        let formattedOutput = output || '';

        if (!formattedOutput.trim()) {
            formattedOutput = 'Код выполнен успешно (нет вывода)';
        }

        outputDiv.innerHTML = `
            <div class="output-success">
                <pre class="output-text">${this.escapeHtml(formattedOutput)}</pre>
                <div class="execution-stats">
                    <span class="stat-item">
                        <i class="fas fa-clock"></i>
                        Время выполнения: ${executionTime} сек
                    </span>
                    <span class="stat-item status-success">
                        <i class="fas fa-check-circle"></i>
                        Успешно
                    </span>
                </div>
            </div>
        `;
    }

    showError(errorMessage, outputDiv) {
        let errorText = errorMessage;

        if (errorMessage.includes('Traceback')) {
            errorText = errorMessage;
        } else if (errorMessage.includes('PythonError')) {

            const match = errorMessage.match(/PythonError: (.+)/);
            errorText = match ? match[1] : errorMessage;
        }

        outputDiv.innerHTML = `
            <div class="output-error">
                <div class="error-header">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Ошибка выполнения</span>
                </div>
                <pre class="error-text">${this.escapeHtml(errorText)}</pre>
                <div class="error-help">
                    <i class="fas fa-lightbulb"></i>
                    <span>Проверьте синтаксис и переменные в вашем коде</span>
                </div>
            </div>
        `;
    }

    clearOutput(blockElement) {
        const outputDiv = blockElement.querySelector(`#output-${this.id}`);
        outputDiv.innerHTML = `
            <div class="output-placeholder">
                <i class="fas fa-code"></i>
                <span>Нажмите "Запустить" чтобы увидеть результат</span>
            </div>
        `;
    }

    onDelete(blockElement) {
        if (confirm('Удалить этот блок с заданием?')) {

            blockElement.style.opacity = '0';
            blockElement.style.transform = 'scale(0.9)';

            setTimeout(() => {
                if (blockElement.parentNode) {
                    blockElement.remove();

                    const event = new CustomEvent('blockDeleted', {
                        detail: { blockId: this.id, type: 'executableCode' },
                        bubbles: true
                    });
                    document.dispatchEvent(event);
                }
            }, 300);
        }
    }

    highlightCode(code, language) {
        if (!code || !window.hljs) return this.escapeHtml(code);

        try {
            if (hljs.getLanguage(language)) {
                return hljs.highlight(code, { language: language }).value;
            } else {
                return hljs.highlightAuto(code).value;
            }
        } catch (error) {
            console.warn('Error highlighting code:', error);
            return this.escapeHtml(code);
        }
    }

    getLanguageName(langCode) {
        const languages = {
            'python': 'Python'
        };
        return languages[langCode] || langCode;
    }

    copyCodeToClipboard(blockElement = null) {
        const runtimeEditor = blockElement?.querySelector?.('.runtime-code-editor');
        const code = runtimeEditor ? runtimeEditor.value : (this.params.code || '');

        navigator.clipboard.writeText(code).then(() => {
            this.showNotification('Код скопирован в буфер обмена!', 'success');
        }).catch(err => {
            this.showNotification('Не удалось скопировать код', 'error');
            console.error('Copy failed:', err);
        });
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
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

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    updateParams(newParams) {
        this.params = { ...this.params, ...newParams };
    }
}
