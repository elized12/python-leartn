export default class ToolbarBlock extends EventTarget {
    constructor(elements = []) {
        super();

        this.toolbar = document.getElementById('blocksToolbar');
        this.addBlockFAB = document.getElementById('addBlockFAB');
        this.closeToolbarBtn = document.getElementById('closeToolbarBtn');
        this.blocksGrid = document.getElementById('blocksToolbarGrid');
        this.elements = elements;

        this.#renderElements();

        this.#hideButton();
        this.#enableEvents();
    }

    show() {
        this.#showButton();
    }

    hide() {
        this.#hideButton();
        this.hideToolbar();
    }

    showToolbar() {
        this.toolbar.classList.add('active');

        setTimeout(() => {
            this.#hideButton();
        }, 200);
    }

    hideToolbar() {
        this.toolbar.classList.remove('active');

        setTimeout(() => {
            this.#showButton();
        }, 200);
    }

    #enableEvents() {
        this.addBlockFAB.addEventListener('click', (event) => {
            this.showToolbar();
        });

        this.closeToolbarBtn.addEventListener('click', (event) => {
            this.hideToolbar();
        });

        this.blocksGrid.addEventListener('click', (e) => {
            const blockCard = e.target.closest('.block-card');
            if (!blockCard) return;

            if (blockCard.classList.contains('toolbar-element-disable')) {
                return;
            }

            e.stopPropagation();

            const type = blockCard.dataset.type;

            this.#dispatchBlockSelectEvent(type);

            this.hideToolbar();
        });
    }

    #dispatchBlockSelectEvent(type) {
        const event = new CustomEvent('block-select', {
            detail: {
                type: type,
                timestamp: Date.now(),
                toolbar: this
            }
        });

        this.dispatchEvent(event);
    }

    #showButton() {
        this.addBlockFAB.style.display = 'block';
    }

    #renderElements() {
        this.elements.forEach(element => {
            this.blocksGrid.appendChild(this.#renderElement(
                element.type,
                element.icon,
                element.title,
                element.description,
                element.disable ?? false
            ));
        });
    }

    #renderElement(type, icon, title, description, disable = false) {
        const element = document.createElement('div');
        element.classList.add('block-card');
        if (disable) {
            element.classList.add('toolbar-element-disable')
        }

        element.dataset.type = type;

        element.innerHTML = `
            <div class="block-icon">
                <i class="fas ${icon}"></i>
            </div>
            <div class="block-info">
                <h5 class="block-name">${title}</h5>
                <p class="block-desc">${description}</p>
            </div>
            <button class="block-add-btn" data-type="${type}">
                <i class="fas fa-plus"></i>
            </button>`;

        return element;
    }

    #hideButton() {
        this.addBlockFAB.style.display = 'none';
    }
};