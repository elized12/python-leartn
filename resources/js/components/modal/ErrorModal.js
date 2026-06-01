import '../../../css/components/modal/ErrorModal.css';

export default class ErrorModal {
    constructor() {
        this.modal = null;
        this.initialize();
    }

    initialize() {
        this.modal = document.createElement('div');
        this.modal.className = 'error-modal';
        this.modal.innerHTML = `
            <div class="error-modal-overlay"></div>
            <div class="error-modal-content">
                <div class="error-modal-header">
                    <h3 class="error-modal-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        Ошибки валидации
                    </h3>
                    <button class="error-modal-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="error-modal-body">
                    <div class="error-list"></div>
                </div>
                <div class="error-modal-footer">
                    <button class="btn btn-primary error-modal-ok">Понятно</button>
                </div>
            </div>
        `;

        document.body.appendChild(this.modal);

        this.modal.querySelector('.error-modal-close').addEventListener('click', () => this.hide());
        this.modal.querySelector('.error-modal-ok').addEventListener('click', () => this.hide());
        this.modal.querySelector('.error-modal-overlay').addEventListener('click', () => this.hide());

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isVisible()) {
                this.hide();
            }
        });
    }

    show(errors) {
        const errorList = this.modal.querySelector('.error-list');
        errorList.innerHTML = '';

        if (Array.isArray(errors)) {
            errors.forEach(error => {
                const errorItem = document.createElement('div');
                errorItem.className = 'error-item';
                errorItem.innerHTML = `<i class="fas fa-times-circle"></i> <span>${this.escapeHtml(error)}</span>`;
                errorList.appendChild(errorItem);
            });
        } else if (typeof errors === 'string') {
            const errorItem = document.createElement('div');
            errorItem.className = 'error-item';
            errorItem.innerHTML = `<i class="fas fa-times-circle"></i> <span>${this.escapeHtml(errors)}</span>`;
            errorList.appendChild(errorItem);
        } else if (errors && typeof errors === 'object') {
            for (const [field, fieldErrors] of Object.entries(errors)) {
                fieldErrors.forEach(error => {
                    const errorItem = document.createElement('div');
                    errorItem.className = 'error-item';
                    errorItem.innerHTML = `<i class="fas fa-times-circle"></i> <span>${field}: ${this.escapeHtml(error)}</span>`;
                    errorList.appendChild(errorItem);
                });
            }
        }

        this.modal.classList.add('visible');
        document.body.style.overflow = 'hidden';
    }

    hide() {
        this.modal.classList.remove('visible');
        document.body.style.overflow = '';
    }

    isVisible() {
        return this.modal.classList.contains('visible');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}