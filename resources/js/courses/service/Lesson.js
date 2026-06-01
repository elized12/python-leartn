export default class Lesson {
    constructor(id, title = '') {
        this.id = id;
        this.title = title;
        this.order = 0;
        this.nextBlockOrder = 10;

        this.blocks = [];
    }

    getId() {
        return this.id;
    }

    empty() {
        return this.blocks.length === 0;
    }

    addBlock(block, order = null) {
        const blockOrder = order ?? this.nextBlockOrder;
        block.setOrder(blockOrder);
        this.blocks.push(block);

        this.nextBlockOrder = Math.max(this.nextBlockOrder, blockOrder + 10);
    }

    removeBlock(blockId) {
        this.blocks = this.blocks.filter(block => block.id != blockId);
    }

    setOrder(order) {
        this.order = order;
    }

    getOrder() {
        return this.order;
    }

    toJson() {
        return {
            id: this.id,
            title: this.title,
            blocks: this.#toJsonBlocks(),
            order: this.order
        }
    }

    getBlocks() {
        return this.blocks;
    }

    loadFromJson(jsonData) {
        this.id = jsonData.id;
        this.title = jsonData.title;
        this.order = jsonData.order || 0;
        this.blocks = jsonData.blocks;
    }

    #toJsonBlocks() {
        return this.blocks.map(block => block.toJson());
    }
}
