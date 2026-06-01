import IBlock from "./IBlock";
import { marked } from 'marked';

export default class TextBlock extends IBlock {
    constructor(id, params = {}) {
        super(id, 'text', {
            content: '',
            markdown: '',
            ...params
        });

        marked.setOptions({
            breaks: true,
            gfm: true,
            headerIds: true,
            smartypants: true,
            sanitize: false
        });

        this.editTimeout = null;
    }

    render() {
        const block = document.createElement('div');
        block.className = `text-block ${this.isEditing ? 'editing' : ''}`;
        block.dataset.blockId = this.id;
        block.dataset.order = this.order;

        const contentHtml = this.params.markdown
            ? this.parseMarkdown(this.params.markdown)
            : (this.params.content || '');

        block.innerHTML = `
            <div class="text-block-content">${contentHtml}</div>
        `;

        return block;
    }

    renderWithEditor() {
        const block = document.createElement('div');
        block.className = `text-block ${this.isEditing ? 'editing' : ''}`;
        block.dataset.blockId = this.id;
        block.dataset.order = this.order;

        const contentHtml = this.params.markdown
            ? this.parseMarkdown(this.params.markdown)
            : (this.params.content || '');

        block.innerHTML = `
            <div class="text-block-header">
                <button class="block-drag-handle" title="Перетащить">
                    <i class="fas fa-grip-vertical"></i>
                </button>
                <button class="block-delete-btn" title="Удалить блок">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="text-block-content">${contentHtml}</div>
            <div class="text-block-edit">
                <textarea class="text-block-edit-input" 
                         placeholder="Введите текст с поддержкой Markdown..."></textarea>
                <div class="text-block-edit-footer">
                    <div class="markdown-hint">
                        <i class="fas fa-markdown"></i>
                        Поддерживается Markdown
                    </div>
                    <div class="edit-actions">
                        <button class="btn btn-cancel">Отмена</button>
                        <button class="btn btn-save">Сохранить</button>
                    </div>
                </div>
            </div>
        `;

        this.initEventListeners(block);

        return block;
    }

    initEventListeners(blockElement) {
        const deleteBtn = blockElement.querySelector('.block-delete-btn');
        deleteBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.onDelete();
        });

        const contentDiv = blockElement.querySelector('.text-block-content');
        contentDiv.addEventListener('click', () => {
            this.showEditInput(blockElement);
        });

        const editInput = blockElement.querySelector('.text-block-edit-input');
        const cancelBtn = blockElement.querySelector('.btn-cancel');
        const saveBtn = blockElement.querySelector('.btn-save');

        if (editInput) {
            editInput.value = this.params.markdown || this.params.content || '';

            editInput.addEventListener('input', () => {
                if (this.editTimeout) {
                    clearTimeout(this.editTimeout);
                }

                this.editTimeout = setTimeout(() => {
                    this.saveChanges(editInput.value, blockElement);
                }, 500);
            });

            editInput.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    this.saveAndHideEdit(editInput.value, blockElement);
                }
                if (e.key === 'Escape') {
                    this.hideEditInput(blockElement);
                }
            });

            editInput.addEventListener('blur', (e) => {
                if (!e.relatedTarget ||
                    !e.relatedTarget.closest('.text-block-edit-footer')) {
                    this.saveAndHideEdit(editInput.value, blockElement);
                }
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                this.hideEditInput(blockElement);
            });
        }

        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                this.saveAndHideEdit(editInput.value, blockElement);
            });
        }
    }

    showEditInput(blockElement) {
        if (this.isEditing) return;

        this.isEditing = true;
        blockElement.classList.add('editing');

        const editContainer = blockElement.querySelector('.text-block-edit');
        editContainer.style.display = 'block';

        const editInput = blockElement.querySelector('.text-block-edit-input');
        setTimeout(() => {
            editInput.focus();
            editInput.select();
        }, 10);
    }

    saveAndHideEdit(text, blockElement) {
        this.saveChanges(text, blockElement);
        this.hideEditInput(blockElement);
    }

    saveChanges(text, blockElement) {
        this.updateParams({
            markdown: text,
            content: this.parseMarkdown(text)
        });

        const contentDiv = blockElement.querySelector('.text-block-content');
        if (contentDiv) {
            contentDiv.innerHTML = this.parseMarkdown(text) || '';
        }
    }

    hideEditInput(blockElement) {
        this.isEditing = false;
        blockElement.classList.remove('editing');

        const editContainer = blockElement.querySelector('.text-block-edit');
        editContainer.style.display = 'none';

        if (this.editTimeout) {
            clearTimeout(this.editTimeout);
            this.editTimeout = null;
        }
    }

    onDelete() {
        if (confirm('Удалить этот блок?')) {
            const blockElement = document.querySelector(`[data-block-id="${this.id}"]`);
            if (blockElement) {
                blockElement.style.opacity = '0';
                blockElement.style.transform = 'scale(0.8)';

                setTimeout(() => {
                    blockElement.remove();
                    const event = new CustomEvent('blockDeleted', {
                        detail: { blockId: this.id },
                        bubbles: true
                    });
                    document.dispatchEvent(event);
                }, 300);
            }
        }
    }

    parseMarkdown(markdown) {
        if (!markdown || !markdown.trim()) return '';

        try {
            return marked.parse(markdown);
        } catch (error) {
            console.error('Error parsing markdown:', error);
            return markdown;
        }
    }

    updateParams(newParams) {
        this.params = { ...this.params, ...newParams };
    }
}