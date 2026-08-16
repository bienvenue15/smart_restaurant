/**
 * Real-Time Events Manager
 * Handles Server-Sent Events (SSE) for real-time updates
 */

class RealtimeEvents {
    constructor(baseUrl) {
        this.baseUrl = baseUrl || '';
        this.eventSource = null;
        this.lastEventId = 0;
        this.reconnectInterval = null;
        this.connected = false;
        this.listeners = {};
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
    }

    /**
     * Connect to SSE stream
     */
    connect() {
        if (this.eventSource) {
            this.eventSource.close();
        }

        const url = `${this.baseUrl}/api/events/stream?lastEventId=${this.lastEventId}`;
        this.eventSource = new EventSource(url);

        // Connection opened
        this.eventSource.addEventListener('connected', (e) => {
            console.log('[Realtime] Connected to event stream');
            this.connected = true;
            this.reconnectAttempts = 0;
            this.trigger('connected', JSON.parse(e.data));
        });

        // Heartbeat to keep connection alive
        this.eventSource.addEventListener('heartbeat', (e) => {
            // Silent heartbeat
        });

        // Connection timeout
        this.eventSource.addEventListener('timeout', (e) => {
            console.log('[Realtime] Connection timeout, reconnecting...');
            this.reconnect();
        });

        // Order created
        this.eventSource.addEventListener('order_created', (e) => {
            const data = JSON.parse(e.data);
            console.log('[Realtime] Order created:', data);
            this.trigger('order_created', data);
            this.playNotificationSound();
            this.showToast(`New Order #${data.order_number} - Table ${data.table_number || 'N/A'}`, 'info');
        });

        // Order status updated
        this.eventSource.addEventListener('order_status_updated', (e) => {
            const data = JSON.parse(e.data);
            console.log('[Realtime] Order status updated:', data);
            this.trigger('order_status_updated', data);
            
            if (data.status === 'ready') {
                this.playNotificationSound();
                this.showToast(`Order #${data.order_number} is ready!`, 'success');
            } else if (data.status === 'completed') {
                this.showToast(`Order #${data.order_number} completed`, 'success');
            }
        });

        // Waiter call received
        this.eventSource.addEventListener('waiter_call_received', (e) => {
            const data = JSON.parse(e.data);
            console.log('[Realtime] Waiter call received:', data);
            this.trigger('waiter_call_received', data);
            this.playUrgentSound();
            this.showToast(`🔔 Table ${data.table_number} needs assistance!`, 'warning');
        });

        // Waiter call completed
        this.eventSource.addEventListener('waiter_call_completed', (e) => {
            const data = JSON.parse(e.data);
            console.log('[Realtime] Waiter call completed:', data);
            this.trigger('waiter_call_completed', data);
        });

        // Payment received
        this.eventSource.addEventListener('payment_received', (e) => {
            const data = JSON.parse(e.data);
            console.log('[Realtime] Payment received:', data);
            this.trigger('payment_received', data);
            this.showToast(`💰 Payment received for Order #${data.order_number}`, 'success');
        });

        // Liability created
        this.eventSource.addEventListener('liability_created', (e) => {
            const data = JSON.parse(e.data);
            console.log('[Realtime] Liability created:', data);
            this.trigger('liability_created', data);
            this.showToast(`⚠️ New liability: Order #${data.order_number}`, 'warning');
        });

        // Error handling
        this.eventSource.onerror = (error) => {
            console.error('[Realtime] Connection error:', error);
            this.connected = false;
            this.trigger('disconnected', error);
            
            // Attempt reconnection
            if (this.reconnectAttempts < this.maxReconnectAttempts) {
                this.reconnect();
            } else {
                console.error('[Realtime] Max reconnection attempts reached');
                this.showToast('Real-time connection lost. Please refresh the page.', 'error');
            }
        };

        // Track last event ID
        this.eventSource.addEventListener('message', (e) => {
            if (e.lastEventId) {
                this.lastEventId = parseInt(e.lastEventId);
            }
        });
    }

    /**
     * Reconnect to SSE stream
     */
    reconnect() {
        this.reconnectAttempts++;
        const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 30000); // Max 30s
        
        console.log(`[Realtime] Reconnecting in ${delay/1000}s (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
        
        clearTimeout(this.reconnectInterval);
        this.reconnectInterval = setTimeout(() => {
            this.connect();
        }, delay);
    }

    /**
     * Disconnect from SSE stream
     */
    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
            this.connected = false;
        }
        clearTimeout(this.reconnectInterval);
    }

    /**
     * Register event listener
     */
    on(eventType, callback) {
        if (!this.listeners[eventType]) {
            this.listeners[eventType] = [];
        }
        this.listeners[eventType].push(callback);
    }

    /**
     * Remove event listener
     */
    off(eventType, callback) {
        if (this.listeners[eventType]) {
            this.listeners[eventType] = this.listeners[eventType].filter(cb => cb !== callback);
        }
    }

    /**
     * Trigger event listeners
     */
    trigger(eventType, data) {
        if (this.listeners[eventType]) {
            this.listeners[eventType].forEach(callback => callback(data));
        }
    }

    /**
     * Play notification sound
     */
    playNotificationSound() {
        try {
            const audio = new Audio(this.baseUrl + '/assets/sounds/notification.mp3');
            audio.volume = 0.5;
            audio.play().catch(e => console.log('Audio play prevented:', e));
        } catch (e) {
            // Silent fail
        }
    }

    /**
     * Play urgent sound for waiter calls
     */
    playUrgentSound() {
        try {
            const audio = new Audio(this.baseUrl + '/assets/sounds/urgent.mp3');
            audio.volume = 0.7;
            audio.play().catch(e => console.log('Audio play prevented:', e));
        } catch (e) {
            // Silent fail
        }
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        // Check if showNotification function exists (from dashboard)
        if (typeof showNotification === 'function') {
            showNotification(message, type);
            return;
        }

        // Fallback toast
        const toast = document.createElement('div');
        toast.className = `realtime-toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            background: ${type === 'error' ? '#e74c3c' : type === 'warning' ? '#f39c12' : type === 'success' ? '#27ae60' : '#3498db'};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
            max-width: 350px;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
}

// Add CSS animation styles
const realtimeNotificationStyle = document.createElement('style');
realtimeNotificationStyle.textContent = `
    @keyframes slideIn {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(400px); opacity: 0; }
    }
`;
document.head.appendChild(realtimeNotificationStyle);
