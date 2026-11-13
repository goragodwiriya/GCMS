/**
 * Shopping Cart Component
 * Framework: Now.js
 *
 * Features:
 * - Add/Remove/Update items
 * - LocalStorage persistence
 * - Cart sidebar/modal
 * - Quantity controls
 * - Total calculation
 * - Event system for cart updates
 */

const Cart = {
  /**
   * Storage key
   */
  STORAGE_KEY: 'nowjs_bookstore_cart',

  /**
   * Cart state
   */
  state: {
    items: [],
    total: 0,
    count: 0,
    isOpen: false
  },

  /**
   * Initialize cart
   */
  init() {
    // Load from localStorage
    this.loadFromStorage();

    // Create cart UI
    this.createCartUI();

    // Bind events
    this.bindEvents();

    // Update display
    this.updateDisplay();
  },

  /**
   * Load cart from localStorage
   */
  loadFromStorage() {
    try {
      const stored = localStorage.getItem(this.STORAGE_KEY);
      if (stored) {
        const data = JSON.parse(stored);
        this.state.items = data.items || [];
        this.calculate();
      }
    } catch (error) {
      console.error('[Cart] Load error:', error);
      this.state.items = [];
    }
  },

  /**
   * Save cart to localStorage
   */
  saveToStorage() {
    try {
      localStorage.setItem(this.STORAGE_KEY, JSON.stringify({
        items: this.state.items,
        updated: Date.now()
      }));
    } catch (error) {
      console.error('[Cart] Save error:', error);
    }
  },

  /**
   * Calculate totals
   */
  calculate() {
    this.state.count = this.state.items.reduce((sum, item) => sum + item.quantity, 0);
    this.state.total = this.state.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  },

  /**
   * Add item to cart
   * @param {Object} data - Item data {id, topic, price, thumb, stock}
   */
  addItem(data) {
    const id = parseInt(data.id);
    const price = parseFloat(data.price);
    const stock = parseInt(data.stock) || 99;

    // Check if item exists
    const existingItem = this.state.items.find(item => item.id === id);

    if (existingItem) {
      // Check stock
      if (existingItem.quantity >= stock) {
        this.showNotification('Out of stock', 'Cannot add more items, stock is insufficient', 'warning');
        return;
      }
      existingItem.quantity += 1;
    } else {
      // Add new item
      this.state.items.push({
        id,
        topic: data.topic,
        price,
        thumb: data.thumb,
        stock,
        quantity: 1
      });
    }

    this.calculate();
    this.saveToStorage();
    this.updateDisplay();
    this.showNotification('Added to cart', data.topic, 'success');

    // Trigger animation on button
    const button = event?.target?.closest('.btn-add-cart');
    if (button) {
      button.classList.add('added');
      setTimeout(() => button.classList.remove('added'), 600);
    }

    // Dispatch event
    this.dispatchEvent('cart:add', {id, quantity: existingItem ? existingItem.quantity : 1});
  },

  /**
   * Add item to cart with quantity in one operation (single notification)
   * @param {Object} data - Item data {id, topic, price, thumb, stock}
   * @param {number} qty - Quantity to add
   */
  addItemWithQuantity(data, qty) {
    const id = parseInt(data.id);
    const price = parseFloat(data.price);
    const stock = parseInt(data.stock) || 99;
    qty = Math.max(1, parseInt(qty, 10) || 1);

    const existingItem = this.state.items.find(item => item.id === id);

    if (existingItem) {
      // cannot exceed stock
      if (existingItem.quantity >= stock) {
        this.showNotification('Out of stock', 'Cannot add more items, stock is insufficient', 'warning');
        return;
      }
      existingItem.quantity = Math.min(stock, existingItem.quantity + qty);
    } else {
      const addQty = Math.min(stock, qty);
      this.state.items.push({
        id,
        topic: data.topic,
        price,
        thumb: data.thumb,
        stock,
        quantity: addQty
      });
    }

    this.calculate();
    this.saveToStorage();
    this.updateDisplay();

    // Single notification for bulk add
    this.showNotification('Added to cart', `${data.topic} x${qty}`, 'success');

    // Try to animate a nearby add button for feedback
    try {
      const sel = `.btn-add-to-cart[data-id="${id}"], .btn-add-cart[data-id="${id}"]`;
      const button = document.querySelector(sel);
      if (button) {
        button.classList.add('added');
        setTimeout(() => button.classList.remove('added'), 600);
      }
    } catch (e) {
      // ignore animation errors
    }

    const newQuantity = this.getItem(id)?.quantity || qty;
    this.dispatchEvent('cart:add', {id, quantity: newQuantity});
  },

  /**
   * Remove item from cart
   * @param {number} id - Item ID
   */
  removeItem(id) {
    const index = this.state.items.findIndex(item => item.id === id);
    if (index !== -1) {
      const item = this.state.items[index];
      this.state.items.splice(index, 1);
      this.calculate();
      this.saveToStorage();
      this.updateDisplay();
      this.showNotification('Deleted', item.topic, 'info');
      this.dispatchEvent('cart:remove', {id});
    }
  },

  /**
   * Update item quantity
   * @param {number} id - Item ID
   * @param {number} quantity - New quantity
   */
  updateQuantity(id, quantity) {
    const item = this.state.items.find(item => item.id === id);
    if (!item) return;

    quantity = Math.max(1, Math.min(quantity, item.stock));
    item.quantity = quantity;

    this.calculate();
    this.saveToStorage();
    this.updateDisplay();
    this.dispatchEvent('cart:update', {id, quantity});
  },

  /**
   * Clear cart
   */
  clear(skipConfirm = false) {

    if (skipConfirm || confirm('Do you want to clear all items in the cart?')) {
      this.state.items = [];
      this.calculate();
      this.saveToStorage();
      this.updateDisplay();
      this.dispatchEvent('cart:clear');
    }
  },

  /**
   * Get cart items
   * @returns {Array}
   */
  getItems() {
    return this.state.items;
  },

  /**
   * Get item by ID
   * @param {number} id
   * @returns {Object|null}
   */
  getItem(id) {
    return this.state.items.find(item => item.id === id) || null;
  },

  /**
   * Create cart UI
   */
  createCartUI() {
    // Cart sidebar HTML
    const cartHTML = `
      <div id="cartSidebar" class="cart-sidebar">
        <div class="cart-overlay"></div>
        <div class="cart-panel">
          <div class="cart-header">
            <h3 class="cart-title" data-i18n>Shopping Cart</h3>
            <button class="cart-close btn-close" aria-label="Close"></button>
          </div>

          <div class="cart-body">
            <div class="cart-empty">
              <span class="icon-cart empty-icon"></span>
              <p data-i18n>No items in the cart</p>
            </div>
            <div class="cart-items">0 THB</div>
          </div>

          <div class="cart-footer">
            <div class="cart-total">
              <span data-i18n>Total</span>
              <span class="cart-total-amount">0 THB</span>
            </div>
            <button class="btn btn-primary btn-checkout width100 icon-cart" onclick="Cart.checkout()" data-i18n>Proceed to checkout</button>
            <button class="btn btn-clear outline width100" onclick="Cart.clear()" data-i18n>Clear cart</button>
          </div>
        </div>
      </div>
    `;

    // Append to body
    document.body.insertAdjacentHTML('beforeend', cartHTML);

    // Store references
    this.elements = {
      sidebar: document.getElementById('cartSidebar'),
      overlay: document.querySelector('.cart-overlay'),
      panel: document.querySelector('.cart-panel'),
      close: document.querySelector('.cart-close'),
      empty: document.querySelector('.cart-empty'),
      items: document.querySelector('.cart-items'),
      totalAmount: document.querySelector('.cart-total-amount')
    };
  },

  /**
   * Bind events
   */
  bindEvents() {
    // Close button
    this.elements.close.addEventListener('click', () => this.close());

    // Overlay click
    this.elements.overlay.addEventListener('click', () => this.close());

    // ESC key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.state.isOpen) this.close();
    });
  },

  /**
   * Update display
   */
  updateDisplay() {
    // Update badge in header
    this.updateBadge();

    // Update cart sidebar
    this.renderItems();
  },

  /**
   * Update cart badge
   */
  updateBadge() {
    const badge = document.querySelector('.cart-badge');
    if (badge) {
      badge.textContent = this.state.count;
      badge.style.display = this.state.count > 0 ? '' : 'none';
    }
  },

  /**
   * Render cart items
   */
  renderItems() {
    const {empty, items, totalAmount} = this.elements;

    if (this.state.items.length === 0) {
      empty.style.display = 'flex';
      items.innerHTML = '';
      totalAmount.textContent = Utils.number.currency(0, 'THB');
      return;
    }

    empty.style.display = 'none';

    const itemsHTML = this.state.items.map(item => `
      <div class="cart-item" data-id="${item.id}">
        <img src="${item.thumb}" alt="${item.topic}" class="cart-item-image">
        <div class="cart-item-details">
          <h4 class="cart-item-title">${item.topic}</h4>
          <p class="cart-item-price">${Utils.number.currency(item.price, 'THB')}</p>

          <div class="cart-item-controls">
            <button class="btn-quantity icon-minus" onclick="Cart.updateQuantity(${item.id}, ${item.quantity - 1})" ${item.quantity <= 1 ? 'disabled' : ''}></button>
            <input type="number" class="quantity-input" value="${item.quantity}" min="1" max="${item.stock}" onchange="Cart.updateQuantity(${item.id}, parseInt(this.value))">
            <button class="btn-quantity icon-plus" onclick="Cart.updateQuantity(${item.id}, ${item.quantity + 1})" ${item.quantity >= item.stock ? 'disabled' : ''}></button>
          </div>

          <p class="cart-item-subtotal"><span data-i18n>Total</span>: ${Utils.number.currency(item.price * item.quantity, 'THB')}</p>
        </div>
        <button class="btn-remove icon-delete" onclick="Cart.removeItem(${item.id})" aria-label="Delete"></button>
      </div>
    `).join('');

    items.innerHTML = itemsHTML;
    totalAmount.textContent = Utils.number.currency(this.state.total, 'THB');
  },

  /**
   * Open cart
   */
  open() {
    this.state.isOpen = true;
    this.elements.sidebar.classList.add('active');
    document.body.style.overflow = 'hidden';
    this.dispatchEvent('cart:open');
  },

  /**
   * Close cart
   */
  close() {
    this.state.isOpen = false;
    this.elements.sidebar.classList.remove('active');
    document.body.style.overflow = '';
    this.dispatchEvent('cart:close');
  },

  /**
   * Toggle cart
   */
  toggle() {
    this.state.isOpen ? this.close() : this.open();
  },

  /**
   * Go to checkout
   */
  checkout() {
    if (this.state.items.length === 0) {
      this.showNotification('Empty cart', 'Please add items before proceeding', 'warning');
      return;
    }

    // Check if user is logged in
    const authManager = Now.getManager ? Now.getManager('auth') : window.AuthManager;
    if (authManager && typeof authManager.isAuthenticated === 'function') {
      if (!authManager.isAuthenticated()) {
        this.showNotification('Login required', 'Please login to continue checkout', 'warning');
        // Redirect to login with return URL
        window.location.href = WEB_URL + 'login?return_to=' + encodeURIComponent(WEB_URL + 'product/checkout');
        return;
      }
    }

    window.location.href = WEB_URL + 'product/checkout';
  },

  /**
   * Show notification
   * @param {string} title
   * @param {string} message
   * @param {string} type - success, error, warning, info
   */
  showNotification(title, message, type = 'info') {
    NotificationManager.show({
      type: type,
      icon: 'cart',
      title: Now.translate(title),
      message: Now.translate(message)
    });
  },

  /**
   * Dispatch custom event
   * @param {string} eventName
   * @param {Object} detail
   */
  dispatchEvent(eventName, detail = {}) {
    window.dispatchEvent(new CustomEvent(eventName, {
      detail: {...detail, cart: this.state}
    }));
  }
};

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => Cart.init());
} else {
  Cart.init();
}

// Export globally
window.Cart = Cart;
