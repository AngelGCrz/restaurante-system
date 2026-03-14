// import Alpine from 'alpinejs'

// window.Alpine = Alpine

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
        persistKey: 'order_form_draft_v2',
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

        // Clave compuesta: mismo producto puede tener distinto precio por categoría
        _key(product) {
            return `${product.id}_${product.category_id ?? 0}`;
        },

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
            // Limpiar del draft y refrescar precios desde datos en vivo del servidor
            Object.keys(this.selectedMap || {}).forEach(key => {
                const item    = this.selectedMap[key];
                const product = this.products.find(p => this._key(p) === key);
                if (!product || product.sold_out ||
                    (!product.allow_negative && typeof product.stock === 'number' && item.quantity > product.stock)) {
                    delete this.selectedMap[key];
                } else {
                    // Refresh price/category_id from server to fix any stale draft data
                    this.selectedMap[key] = { ...item, price: product.price, category_id: product.category_id };
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

        addProductByKey(key) {
            const product = this.products.find(p => this._key(p) === key);
            if (product) this.addProduct(product);
        },

        addProduct(product) {
            if (product.sold_out) return;
            const key = this._key(product);
            // Always use live price from products array to guard against stale Alpine scope
            const liveProduct = this.products.find(p => p.id === product.id && String(p.category_id) === String(product.category_id)) || product;
            const existing  = this.selectedMap[key] ?? { ...liveProduct, quantity: 0, _key: key };
            const newQty    = existing.quantity + 1;
            if (!liveProduct.allow_negative && typeof liveProduct.stock === 'number' && newQty > liveProduct.stock) {
                window.showToast && showToast('Stock insuficiente para ' + liveProduct.name + '. Disponible: ' + liveProduct.stock);
                return;
            }
            this.selectedMap[key] = { ...existing, _key: key, price: liveProduct.price, category_id: liveProduct.category_id, quantity: newQty };
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
                const key = this.currentCommentItem._key;
                this.selectedMap[key] = { ...this.selectedMap[key], comment: this.currentCommentText };
                this.saveDraft();
            }
            this.closeCommentModal();
        },

        itemSubtotal(item) { return (Number(item.price) || 0) * (Number(item.quantity) || 0); },

        get previewTotal() { return this.selectedList.reduce((s, i) => s + this.itemSubtotal(i), 0); },
        get itemCount()    { return this.selectedList.reduce((s, i) => s + (Number(i.quantity) || 0), 0); },

        increment(key) {
            if (!this.selectedMap[key]) return;
            const item    = this.selectedMap[key];
            const product = this.products.find(p => this._key(p) === key) || item;
            const newQty  = item.quantity + 1;
            if (!product.allow_negative && typeof product.stock === 'number' && newQty > product.stock) {
                window.showToast && showToast('Stock insuficiente para ' + product.name + '. Disponible: ' + product.stock);
                return;
            }
            this.selectedMap[key] = { ...item, quantity: newQty };
            this.saveDraft();
        },
        decrement(key) {
            if (!this.selectedMap[key]) return;
            const newQty = this.selectedMap[key].quantity - 1;
            if (newQty <= 0) {
                const m = { ...this.selectedMap };
                delete m[key];
                this.selectedMap = m;
            } else {
                this.selectedMap[key] = { ...this.selectedMap[key], quantity: newQty };
            }
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

// Alpine.start()
