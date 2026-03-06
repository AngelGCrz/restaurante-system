import Alpine from 'alpinejs'

window.Alpine = Alpine

document.addEventListener('livewire:navigated', () => {
    Alpine.initTree(document.body)
})

// ── orderManager: usado en add-items.blade.php ────────────────────────────────
window.orderManager = function () {
    return {
        activeCategory: null,
        items: [],
        increase(id, name, price) {
            let existing = this.items.find(p => p.id === id);
            if (existing) { existing.quantity++; }
            else { this.items.push({ id, name, price, quantity: 1 }); }
        },
        decrease(id) {
            let existing = this.items.find(p => p.id === id);
            if (!existing) return;
            existing.quantity--;
            if (existing.quantity <= 0) { this.items = this.items.filter(p => p.id !== id); }
        },
        getQty(id) {
            let item = this.items.find(p => p.id === id);
            return item ? item.quantity : 0;
        }
    }
}

// ── orderFormComponent: usado en orders/create.blade.php ──────────────────────
window.orderFormComponent = function ({ totalTables = 0, presetTables = [], presetSelection = [], tableSelectUrl = '', products = [], categories = [], initialServiceType = 'mesa', initialCustomerName = '', initialComment = '' }) {
    return {
        persistKey: 'order_form_draft',
        serviceType: initialServiceType || 'mesa',
        customerName: initialCustomerName || '',
        comment: initialComment || '',
        showCommentModal: false,
        currentCommentItem: null,
        currentCommentText: '',
        totalTables,
        tableNumbers: presetTables.length ? presetTables : Array.from({ length: totalTables }, (_, idx) => idx + 1),
        selectedTables: presetSelection,
        tableSelectUrl,
        products,
        categories,
        currentCategory: categories.length ? categories[0].id : null,
        selectedMap: {},

        init() {
            if (sessionStorage.getItem('order_just_submitted')) {
                sessionStorage.removeItem('order_just_submitted');
                this.saveDraft();
                return;
            }
            const saved = this.loadDraft();
            if (saved) {
                this.serviceType = saved.serviceType || this.serviceType;
                this.selectedMap  = saved.selectedMap  || {};
                this.customerName = saved.customerName || '';
                this.comment      = saved.comment      || '';
                if (!this.selectedTables.length && Array.isArray(saved.selectedTables)) {
                    this.selectedTables = saved.selectedTables;
                }
            }
            if (this.serviceType !== 'mesa') { this.selectedTables = []; }
            // Limpiar del draft productos que ya no existen o no tienen stock
            Object.keys(this.selectedMap || {}).forEach(id => {
                const item    = this.selectedMap[id];
                const product = this.products.find(p => p.id == id);
                if (!product || product.sold_out ||
                    (!product.allow_negative && typeof product.stock === 'number' && item.quantity > product.stock)) {
                    delete this.selectedMap[id];
                }
            });
            this.saveDraft();
        },

        loadDraft() {
            try {
                const raw = localStorage.getItem(this.persistKey);
                return raw ? JSON.parse(raw) : null;
            } catch (e) { return null; }
        },
        saveDraft() {
            localStorage.setItem(this.persistKey, JSON.stringify({
                serviceType:    this.serviceType,
                selectedTables: this.selectedTables,
                selectedMap:    this.selectedMap,
                customerName:   this.customerName,
                comment:        this.comment,
            }));
        },
        clearDraft()  { localStorage.removeItem(this.persistKey); },
        isSelected(t) { return this.selectedTables.includes(t); },
        clearSelection() { this.selectedTables = []; this.saveDraft(); },
        clearProducts()  { this.selectedMap = {}; this.saveDraft(); },

        selectionLabel() {
            if (this.serviceType !== 'mesa') return 'Pedido para llevar';
            if (!this.selectedTables.length)
                return '<span style="color:red">SELECCIONA MESAS</span>';
            const prefix = this.selectedTables.length === 1 ? 'Mesa' : 'Mesas';
            return `${prefix} ${this.selectedTables.join(' + ')}`;
        },
        handleTypeChange() {
            if (this.serviceType !== 'mesa') { this.selectedTables = []; }
            this.saveDraft();
        },
        goToTableSelector() {
            this.saveDraft();
            const params = new URLSearchParams();
            this.selectedTables.forEach(t => params.append('tables[]', t));
            window.location.href = params.toString()
                ? `${this.tableSelectUrl}?${params.toString()}`
                : this.tableSelectUrl;
        },

        addProduct(product) {
            if (product.sold_out) return;
            const existing  = this.selectedMap[product.id] ?? { ...product, quantity: 0 };
            const newQty    = existing.quantity + 1;
            if (!product.allow_negative && typeof product.stock === 'number' && newQty > product.stock) {
                window.showToast && showToast('Stock insuficiente para ' + product.name + '. Disponible: ' + product.stock);
                return;
            }
            existing.quantity = newQty;
            this.selectedMap[product.id] = existing;
            this.saveDraft();
        },

        // Modal de comentario por item
        openCommentModal(item) {
            this.currentCommentItem = item;
            this.currentCommentText = item.comment || '';
            this.showCommentModal = true;
        },
        closeCommentModal() {
            this.showCommentModal = false;
            this.currentCommentItem = null;
            this.currentCommentText = '';
        },
        saveItemComment() {
            if (this.currentCommentItem) {
                const id = this.currentCommentItem.id;
                this.selectedMap[id] = { ...this.selectedMap[id], comment: this.currentCommentText };
                this.saveDraft();
            }
            this.closeCommentModal();
        },

        itemSubtotal(item) { return (Number(item.price) || 0) * (Number(item.quantity) || 0); },

        get previewTotal() { return this.selectedList.reduce((s, i) => s + this.itemSubtotal(i), 0); },
        get itemCount()    { return this.selectedList.reduce((s, i) => s + (Number(i.quantity) || 0), 0); },

        increment(productId) {
            if (!this.selectedMap[productId]) return;
            const item    = this.selectedMap[productId];
            const product = this.products.find(p => p.id == productId) || item;
            const newQty  = item.quantity + 1;
            if (!product.allow_negative && typeof product.stock === 'number' && newQty > product.stock) {
                window.showToast && showToast('Stock insuficiente para ' + product.name + '. Disponible: ' + product.stock);
                return;
            }
            this.selectedMap[productId].quantity = newQty;
            this.saveDraft();
        },
        decrement(productId) {
            if (!this.selectedMap[productId]) return;
            this.selectedMap[productId].quantity -= 1;
            if (this.selectedMap[productId].quantity <= 0) { delete this.selectedMap[productId]; }
            this.saveDraft();
        },

        get selectedList() { return Object.values(this.selectedMap); },
        get filteredProducts() {
            if (!this.currentCategory) return this.products;
            return this.products.filter(p => String(p.category_id) === String(this.currentCategory));
        },
        currency(value) {
            return 'S/ ' + Number(value).toFixed(2);
        },
    };
}

Alpine.start()
