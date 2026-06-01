import Sortable from "sortablejs";

export default class OrderManager {
    constructor(container, options = {}) {
        if (!container) {
            throw new Error("OrderManager: container is required");
        }

        this.container = container;
        this.options = {
            animation: 150,
            handle: null,
            draggable: null,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            filter: '.non-draggable',
            preventOnFilter: false,

            onStart: null,
            onEnd: null,
            onUpdate: null,
            onChange: null,

            dataIdAttribute: 'data-id',
            dataOrderAttribute: 'data-order',
            orderSelector: '.order-number',

            ...options
        };

        this.sortable = null;
        this.items = new Map();

        this.init();
    }

    init() {
        const sortableOptions = {
            animation: this.options.animation,
            ghostClass: this.options.ghostClass,
            chosenClass: this.options.chosenClass,
            dragClass: this.options.dragClass,
            filter: this.options.filter,
            preventOnFilter: this.options.preventOnFilter,

            onStart: (event) => {
                event.item.classList.add('dragging');
                this.options.onStart?.(event, this.getOrder());
            },

            onEnd: (event) => {
                event.item.classList.remove('dragging');
                this.updateOrderNumbers();
                
                const order = this.getOrder();
                this.options.onEnd?.(event, order);
                this.options.onChange?.(order, event);
            },

            onUpdate: (event) => {
                const order = this.getOrder();
                this.options.onUpdate?.(event, order);
            }
        };

        if (this.options.handle) {
            sortableOptions.handle = this.options.handle;
        }

        if (this.options.draggable) {
            sortableOptions.draggable = this.options.draggable;
        }

        this.sortable = Sortable.create(this.container, sortableOptions);

        this.updateOrderNumbers();
    }

    registerItems(itemsArray) {
        this.items.clear();
        
        itemsArray.forEach(item => {
            const id = this.getItemId(item);
            if (id) {
                this.items.set(id, item);
            }
        });
        
        this.updateOrderNumbers();
    }

    registerItem(item) {
        const id = this.getItemId(item);
        if (id) {
            this.items.set(id, item);
            this.updateOrderNumbers();
        }
    }

    unregisterItem(itemId) {
        const idStr = itemId.toString();
        this.items.delete(idStr);
        this.updateOrderNumbers();
    }

    getItemId(item) {
        if (!item) return null;
        
        if (item.id !== undefined) {
            return item.id.toString();
        } else if (item.getId && typeof item.getId === 'function') {
            return item.getId().toString();
        } else if (item.data && item.data.id) {
            return item.data.id.toString();
        }
        
        return null;
    }

    getOrder() {
        const elements = this.getDraggableElements();
        const order = [];

        elements.forEach((element, index) => {
            const itemId = this.getItemIdFromElement(element);
            const item = this.findItemById(itemId);

            order.push({
                element: element,
                item: item,
                id: itemId,
                order: index + 1,
                index: index
            });
        });

        return order;
    }

    updateOrderNumbers() {
        const elements = this.getDraggableElements();

        elements.forEach((element, index) => {
            const order = index + 1;
            this.updateElementOrder(element, order);
            this.updateItemOrder(element, order);
        });
    }

    updateElementOrder(element, order) {
        if (this.options.dataOrderAttribute) {
            element.setAttribute(this.options.dataOrderAttribute, order);
        }

        if (this.options.orderSelector) {
            const orderElement = element.querySelector(this.options.orderSelector);
            if (orderElement) {
                orderElement.textContent = order;
            }
        }
    }

    updateItemOrder(element, order) {
        const itemId = this.getItemIdFromElement(element);
        const item = this.findItemById(itemId);

        if (item) {
            if (item.setOrder) {
                item.setOrder(order);
            } else if (typeof item.order !== 'undefined') {
                item.order = order;
            }
        }
    }

    getDraggableElements() {
        if (this.options.draggable) {
            return Array.from(this.container.querySelectorAll(this.options.draggable));
        }
        return Array.from(this.container.children);
    }

    getItemIdFromElement(element) {
        if (this.options.dataIdAttribute) {
            return element.getAttribute(this.options.dataIdAttribute);
        }
        return element.id || element.dataset.id;
    }

    findItemById(itemId) {
        if (!itemId) return null;
        return this.items.get(itemId.toString());
    }

    getItemsArray() {
        return Array.from(this.items.values());
    }

    moveItem(itemId, newPosition) {
        const element = this.container.querySelector(`[${this.options.dataIdAttribute}="${itemId}"]`);
        if (!element) return false;

        const elements = this.getDraggableElements();
        const currentIndex = elements.indexOf(element);

        if (currentIndex === -1 || newPosition < 0 || newPosition >= elements.length) {
            return false;
        }

        if (newPosition > currentIndex) {
            if (elements[newPosition + 1]) {
                elements[newPosition + 1].before(element);
            } else {
                this.container.appendChild(element);
            }
        } else {
            elements[newPosition].before(element);
        }

        this.updateOrderNumbers();
        return true;
    }

    enable() {
        this.sortable.option('disabled', false);
    }

    disable() {
        this.sortable.option('disabled', true);
    }

    destroy() {
        if (this.sortable) {
            this.sortable.destroy();
        }
    }
}