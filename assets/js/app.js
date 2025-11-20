/**
 * Smart Restaurant - Main JavaScript
 * Vanilla JS with XSS Protection
 */

// Global Variables
let cart = [];
let tableData = typeof TABLE_DATA !== 'undefined' ? TABLE_DATA : null;
const baseUrl = typeof BASE_URL !== 'undefined' ? BASE_URL : '';

// ===================================
// XSS PROTECTION & SANITIZATION
// ===================================

/**
 * Sanitize string to prevent XSS attacks
 */
function sanitizeHTML(str) {
    const temp = document.createElement('div');
    temp.textContent = str;
    return temp.innerHTML;
}

/**
 * Sanitize object recursively
 */
function sanitizeObject(obj) {
    if (typeof obj === 'string') {
        return sanitizeHTML(obj);
    }
    if (Array.isArray(obj)) {
        return obj.map(sanitizeObject);
    }
    if (typeof obj === 'object' && obj !== null) {
        const sanitized = {};
        for (let key in obj) {
            sanitized[key] = sanitizeObject(obj[key]);
        }
        return sanitized;
    }
    return obj;
}

/**
 * Validate number input
 */
function validateNumber(value, min = 0, max = 999999) {
    const num = parseFloat(value);
    if (isNaN(num)) return min;
    return Math.max(min, Math.min(max, num));
}

// ===================================
// CART MANAGEMENT
// ===================================

/**
 * Add item to cart
 */
function addToCart(itemId) {
    const itemElement = document.querySelector(`[data-item-id="${itemId}"]`);
    if (!itemElement) {
        showNotification('Item not found', 'error');
        return;
    }
    
    const itemData = {
        id: parseInt(itemElement.dataset.itemId),
        name: sanitizeHTML(itemElement.dataset.itemName),
        price: parseFloat(itemElement.dataset.itemPrice),
        quantity: 1
    };
    
    // Check if item already in cart
    const existingItem = cart.find(item => item.id === itemData.id);
    
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push(itemData);
    }
    
    updateCartUI();
    showNotification(`${itemData.name} added to order`, 'success');
}

/**
 * Remove item from cart
 */
function removeFromCart(itemId) {
    cart = cart.filter(item => item.id !== itemId);
    updateCartUI();
    showNotification('Item removed from order', 'info');
}

/**
 * Update item quantity
 */
function updateQuantity(itemId, change) {
    const item = cart.find(i => i.id === itemId);
    if (!item) return;
    
    item.quantity = Math.max(1, item.quantity + change);
    updateCartUI();
}

/**
 * Clear entire cart
 */
function clearCart() {
    if (cart.length === 0) return;
    
    if (confirm('Are you sure you want to clear your order?')) {
        cart = [];
        updateCartUI();
        showNotification('Order cleared', 'info');
    }
}

/**
 * Update cart UI
 */
function updateCartUI() {
    const cartItemsList = document.getElementById('orderItemsList');
    const cartItemCount = document.getElementById('cartItemCount');
    const orderSummary = document.getElementById('orderSummary');
    const subtotalElement = document.getElementById('subtotalAmount');
    const totalElement = document.getElementById('totalAmount');
    const btnPlaceOrder = document.getElementById('btnPlaceOrder');
    const btnClearOrder = document.getElementById('btnClearOrder');
    
    // Update count
    cartItemCount.textContent = cart.length;
    
    // Show/hide empty state
    if (cart.length === 0) {
        cartItemsList.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Your order is empty</p>
                <p class="hint">Start adding items from the menu</p>
            </div>
        `;
        orderSummary.style.display = 'none';
        btnPlaceOrder.disabled = true;
        btnClearOrder.disabled = true;
        return;
    }
    
    // Build cart items HTML
    let cartHTML = '';
    let subtotal = 0;
    
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        
        cartHTML += `
            <div class="cart-item">
                <div class="cart-item-header">
                    <div class="cart-item-name">${sanitizeHTML(item.name)}</div>
                    <button class="cart-item-remove" onclick="removeFromCart(${item.id})" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="cart-item-controls">
                    <div class="quantity-controls">
                        <button class="qty-btn" onclick="updateQuantity(${item.id}, -1)">-</button>
                        <div class="qty-value">${item.quantity}</div>
                        <button class="qty-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
                    </div>
                    <div class="cart-item-price">RWF ${formatNumber(itemTotal)}</div>
                </div>
            </div>
        `;
    });
    
    cartItemsList.innerHTML = cartHTML;
    
    // Update summary
    subtotalElement.textContent = `RWF ${formatNumber(subtotal)}`;
    totalElement.textContent = `RWF ${formatNumber(subtotal)}`;
    orderSummary.style.display = 'block';
    
    // Enable buttons
    btnPlaceOrder.disabled = false;
    btnClearOrder.disabled = false;
}

/**
 * Format number with thousand separators
 */
function formatNumber(num) {
    return num.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// ===================================
// ORDER PLACEMENT
// ===================================

/**
 * Place order
 */
async function placeOrder() {
    if (!tableData) {
        showNotification('Table information not found', 'error');
        return;
    }
    
    if (cart.length === 0) {
        showNotification('Your cart is empty', 'error');
        return;
    }
    
    const specialInstructions = sanitizeHTML(document.getElementById('specialInstructions').value);
    
    const orderData = {
        table_id: tableData.id,
        items: cart,
        special_instructions: specialInstructions
    };
    
    showLoading(true);
    
    try {
        const response = await fetch(`${baseUrl}/?req=api&action=create_order`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(orderData)
        });
        
        const result = await response.json();
        
        showLoading(false);
        
        if (result.status === 'OK') {
            // Show confirmation modal
            document.getElementById('confirmOrderNumber').textContent = result.data.order_number;
            document.getElementById('confirmOrderTotal').textContent = `RWF ${formatNumber(result.data.total_amount)}`;
            openOrderConfirmModal();
            
            // Clear cart
            cart = [];
            document.getElementById('specialInstructions').value = '';
            updateCartUI();
        } else {
            showNotification(result.message || 'Failed to place order', 'error');
        }
        
    } catch (error) {
        showLoading(false);
        console.error('Order error:', error);
        showNotification('Network error. Please try again.', 'error');
    }
}

// ===================================
// WAITER CALL
// ===================================

/**
 * Open waiter call modal
 */
function openWaiterCallModal() {
    if (!tableData) {
        showNotification('Table information not found', 'error');
        return;
    }
    
    const modal = document.getElementById('waiterCallModal');
    modal.classList.add('active');
}

/**
 * Close waiter call modal
 */
function closeWaiterCallModal() {
    const modal = document.getElementById('waiterCallModal');
    modal.classList.remove('active');
    
    // Reset form
    document.getElementById('requestType').value = 'assistance';
    document.getElementById('waiterMessage').value = '';
    document.querySelector('input[name="priority"][value="normal"]').checked = true;
}

/**
 * Submit waiter call
 */
async function submitWaiterCall() {
    if (!tableData) {
        showNotification('Table information not found', 'error');
        return;
    }
    
    const requestType = sanitizeHTML(document.getElementById('requestType').value);
    const message = sanitizeHTML(document.getElementById('waiterMessage').value);
    const priority = document.querySelector('input[name="priority"]:checked').value;
    
    const callData = {
        table_id: tableData.id,
        request_type: requestType,
        message: message,
        priority: priority
    };
    
    showLoading(true);
    
    try {
        const response = await fetch(`${baseUrl}/?req=api&action=call_waiter`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(callData)
        });
        
        const result = await response.json();
        
        showLoading(false);
        
        if (result.status === 'OK') {
            closeWaiterCallModal();
            showNotification('Waiter has been notified!', 'success');
        } else {
            showNotification(result.message || 'Failed to call waiter', 'error');
        }
        
    } catch (error) {
        showLoading(false);
        console.error('Waiter call error:', error);
        showNotification('Network error. Please try again.', 'error');
    }
}

// ===================================
// ORDER CONFIRMATION MODAL
// ===================================

/**
 * Open order confirmation modal
 */
function openOrderConfirmModal() {
    const modal = document.getElementById('orderConfirmModal');
    modal.classList.add('active');
}

/**
 * Close order confirmation modal
 */
function closeOrderConfirmModal() {
    const modal = document.getElementById('orderConfirmModal');
    modal.classList.remove('active');
}

// ===================================
// SEARCH FUNCTIONALITY
// ===================================

/**
 * Search menu items
 */
function searchMenu() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;
    
    const query = searchInput.value.toLowerCase().trim();
    const menuItems = document.querySelectorAll('.menu-item');
    const categories = document.querySelectorAll('.menu-category');
    
    if (query === '') {
        // Show all items and categories
        menuItems.forEach(item => item.style.display = '');
        categories.forEach(cat => cat.style.display = '');
        return;
    }
    
    // Search through items
    menuItems.forEach(item => {
        const itemName = item.dataset.itemName.toLowerCase();
        const itemDescription = item.querySelector('.item-description')?.textContent.toLowerCase() || '';
        const dietaryInfo = item.querySelector('.dietary-tags')?.textContent.toLowerCase() || '';
        
        const matches = itemName.includes(query) || 
                       itemDescription.includes(query) || 
                       dietaryInfo.includes(query);
        
        item.style.display = matches ? '' : 'none';
    });
    
    // Hide empty categories
    categories.forEach(category => {
        const visibleItems = category.querySelectorAll('.menu-item[style=""], .menu-item:not([style*="display: none"])');
        category.style.display = visibleItems.length > 0 ? '' : 'none';
    });
}

// ===================================
// UI UTILITIES
// ===================================

/**
 * Show loading overlay
 */
function showLoading(show) {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.toggle('active', show);
    }
}

/**
 * Show notification (simple implementation)
 */
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existing = document.querySelector('.notification-toast');
    if (existing) {
        existing.remove();
    }
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `notification-toast notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${getNotificationIcon(type)}"></i>
        <span>${sanitizeHTML(message)}</span>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: white;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        z-index: 10001;
        animation: slideInRight 0.3s ease;
        min-width: 250px;
        border-left: 4px solid ${getNotificationColor(type)};
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function getNotificationIcon(type) {
    switch(type) {
        case 'success': return 'check-circle';
        case 'error': return 'exclamation-circle';
        case 'warning': return 'exclamation-triangle';
        default: return 'info-circle';
    }
}

function getNotificationColor(type) {
    switch(type) {
        case 'success': return '#10b981';
        case 'error': return '#ef4444';
        case 'warning': return '#f59e0b';
        default: return '#6366f1';
    }
}

// Add animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// ===================================
// EVENT LISTENERS
// ===================================

document.addEventListener('DOMContentLoaded', function() {
    // Search input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(searchMenu, 300));
    }
    
    // Close modals on overlay click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    });
    
    // Escape key to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });
    
    console.log('Smart Restaurant System Initialized');
    console.log('Table:', tableData);
});

// ===================================
// UTILITY FUNCTIONS
// ===================================

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Format currency
 */
function formatCurrency(amount, currency = 'RWF') {
    return `${currency} ${formatNumber(amount)}`;
}

/**
 * Get current timestamp
 */
function getCurrentTimestamp() {
    return new Date().toISOString();
}

// ===================================
// SMOOTH SCROLLING
// ===================================

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// ===================================
// ORDER HISTORY & CANCELLATION
// ===================================

/**
 * Load order history for current table
 */
async function loadOrderHistory() {
    if (!tableData) {
        return;
    }
    
    try {
        const response = await fetch(`${baseUrl}/?req=api&action=get_order_history&limit=10`);
        const result = await response.json();
        
        if (result.status === 'OK' && result.data.length > 0) {
            displayOrderHistory(result.data);
        }
        
    } catch (error) {
        console.error('Failed to load order history:', error);
    }
}

/**
 * Display order history in UI
 */
function displayOrderHistory(orders) {
    const historyContainer = document.getElementById('orderHistory');
    if (!historyContainer) return;
    
    let historyHTML = '<h3><i class="fas fa-history"></i> Recent Orders</h3>';
    
    orders.forEach(order => {
        const statusClass = order.status.toLowerCase();
        const statusIcon = getStatusIcon(order.status);
        const canCancel = order.can_cancel && order.cancel_seconds_remaining > 0;
        
        historyHTML += `
            <div class="order-history-item order-status-${statusClass}">
                <div class="order-history-header">
                    <div class="order-number">${sanitizeHTML(order.order_number)}</div>
                    <div class="order-status">
                        <i class="${statusIcon}"></i> ${sanitizeHTML(order.status)}
                    </div>
                </div>
                <div class="order-history-details">
                    <div class="order-info">
                        <span><i class="fas fa-shopping-bag"></i> ${order.item_count} item(s)</span>
                        <span><i class="fas fa-money-bill-wave"></i> RWF ${formatNumber(order.total_amount)}</span>
                    </div>
                    <div class="order-time">
                        ${getRelativeTime(order.seconds_since_order)} ago
                    </div>
                </div>
                ${canCancel ? `
                    <div class="order-cancel-section">
                        <button class="btn-cancel-order" onclick="cancelOrder(${order.id})" 
                                data-order-id="${order.id}"
                                data-remaining="${order.cancel_seconds_remaining}">
                            <i class="fas fa-times-circle"></i> Cancel Order 
                            (<span class="countdown-${order.id}">${order.cancel_seconds_remaining}</span>s)
                        </button>
                    </div>
                ` : ''}
            </div>
        `;
    });
    
    historyContainer.innerHTML = historyHTML;
    
    // Start countdown timers for cancellable orders
    orders.forEach(order => {
        if (order.can_cancel && order.cancel_seconds_remaining > 0) {
            startCancellationCountdown(order.id, order.cancel_seconds_remaining);
        }
    });
}

/**
 * Get status icon based on order status
 */
function getStatusIcon(status) {
    const icons = {
        'pending': 'fas fa-clock',
        'confirmed': 'fas fa-check',
        'preparing': 'fas fa-fire',
        'ready': 'fas fa-bell',
        'served': 'fas fa-utensils',
        'completed': 'fas fa-check-circle',
        'cancelled': 'fas fa-times-circle'
    };
    return icons[status.toLowerCase()] || 'fas fa-info-circle';
}

/**
 * Get relative time string
 */
function getRelativeTime(seconds) {
    if (seconds < 60) return `${seconds} second${seconds !== 1 ? 's' : ''}`;
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes} minute${minutes !== 1 ? 's' : ''}`;
    const hours = Math.floor(minutes / 60);
    return `${hours} hour${hours !== 1 ? 's' : ''}`;
}

/**
 * Start countdown timer for order cancellation
 */
function startCancellationCountdown(orderId, remainingSeconds) {
    let timeLeft = remainingSeconds;
    const countdownElement = document.querySelector(`.countdown-${orderId}`);
    const cancelButton = document.querySelector(`[data-order-id="${orderId}"]`);
    
    if (!countdownElement || !cancelButton) return;
    
    const countdown = setInterval(() => {
        timeLeft--;
        
        if (timeLeft <= 0) {
            clearInterval(countdown);
            cancelButton.disabled = true;
            cancelButton.innerHTML = '<i class="fas fa-lock"></i> Cannot Cancel';
            cancelButton.classList.add('disabled');
        } else {
            countdownElement.textContent = timeLeft;
        }
    }, 1000);
}

/**
 * Cancel an order
 */
async function cancelOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this order?')) {
        return;
    }
    
    showLoading(true);
    
    try {
        const response = await fetch(`${baseUrl}/?req=api&action=cancel_order`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ order_id: orderId })
        });
        
        const result = await response.json();
        
        showLoading(false);
        
        if (result.status === 'OK') {
            showNotification('Order cancelled successfully', 'success');
            // Reload order history
            setTimeout(() => loadOrderHistory(), 500);
        } else {
            showNotification(result.message || 'Failed to cancel order', 'error');
        }
        
    } catch (error) {
        showLoading(false);
        console.error('Cancel error:', error);
        showNotification('Network error. Please try again.', 'error');
    }
}

/**
 * Auto-refresh order history every 30 seconds
 */
function startOrderHistoryRefresh() {
    // Load initially
    loadOrderHistory();
    
    // Refresh every 30 seconds
    setInterval(() => {
        loadOrderHistory();
    }, 30000);
}

// Initialize order history on page load
if (tableData) {
    startOrderHistoryRefresh();
}

console.log('Smart Restaurant JS Loaded - XSS Protection Enabled');
