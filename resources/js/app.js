import Alpine from 'alpinejs'

window.Alpine = Alpine

document.addEventListener('livewire:navigated', () => {
    Alpine.initTree(document.body)
})

Alpine.start()


window.orderManager = function () {
    return {
        activeCategory: null,
        items: [],

        increase(id, name, price) {
            let existing = this.items.find(p => p.id === id);
            if (existing) {
                existing.quantity++;
            } else {
                this.items.push({ id, name, price, quantity: 1 });
            }
        },

        decrease(id) {
            let existing = this.items.find(p => p.id === id);
            if (!existing) return;

            existing.quantity--;
            if (existing.quantity <= 0) {
                this.items = this.items.filter(p => p.id !== id);
            }
        },

        getQty(id) {
            let item = this.items.find(p => p.id === id);
            return item ? item.quantity : 0;
        }
    }
}
