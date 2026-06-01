export default class IBlock {
    constructor(id, type, params) {
        this.id = id;
        this.type = type;
        this.params = params;
        this.order = 0;
    }

    render() {
        throw new Error('Render method must be implemented by subclasses');
    }

    renderWithEditor() {
        throw new Error('renderWithEditor method must be implemented by subclasses');
    }

    getData() {
        return {
            id: this.id,
            type: this.type,
            order: this.order,
            params: this.params
        };
    }

    toJson() {
        return JSON.stringify(this.getData());
    }

    fromJSON() {
        this.id = jsonData.id;
        this.type = jsonData.type;
        this.params = jsonData.params;
    }

    getType() {
        return this.type;
    }

    getOrder() {
        return this.order;
    }

    setOrder(order) {
        this.order = order;
    }
}
