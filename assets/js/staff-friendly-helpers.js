/**
 * FRIENDLY HELP TOOLTIPS & VISUAL GUIDES
 * Makes the system self-explanatory for everyone
 */

// Add helpful tooltips to dashboard elements
document.addEventListener('DOMContentLoaded', function() {
    
    // Add visual hints to stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.style.cursor = 'pointer';
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-6px) scale(1.02)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'none';
        });
    });
    
    // Add "Tap to view" hints on mobile
    if (window.innerWidth <= 768) {
        statCards.forEach(card => {
            const label = card.querySelector('.stat-label');
            if (label && !label.textContent.includes('👆')) {
                label.innerHTML += ' <span style="opacity: 0.7;">👆 Tap to see</span>';
            }
        });
    }
    
    // Make shift toggle button more obvious with animation
    const shiftButton = document.querySelector('.btn-shift-toggle');
    if (shiftButton && !shiftButton.classList.contains('btn-shift-out')) {
        // Gently pulse the clock-in button
        shiftButton.style.animation = 'gentlePulse 3s infinite';
    }
    
    // Add visual indicators for urgent items
    const badges = document.querySelectorAll('.badge');
    badges.forEach(badge => {
        const count = parseInt(badge.textContent);
        if (count > 0) {
            badge.style.animation = 'pulse 2s infinite';
            badge.title = `You have ${count} item${count > 1 ? 's' : ''} waiting`;
        }
    });
    
    // Make empty states more encouraging
    const emptyStates = document.querySelectorAll('.empty-state');
    emptyStates.forEach(state => {
        state.style.minHeight = '200px';
        state.style.display = 'flex';
        state.style.flexDirection = 'column';
        state.style.alignItems = 'center';
        state.style.justifyContent = 'center';
    });
    
    // Add friendly tooltips to navigation items
    const navItems = document.querySelectorAll('.nav-item');
    const navHints = {
        'Home': '👉 See everything happening right now',
        'View Orders': '👉 Check what customers ordered',
        'Cook Orders': '👉 See what needs to be cooked',
        'Customer Calls': '👉 See which tables need help',
        'Manage Tables': '👉 See which tables are free or busy',
        'See Menu': '👉 Look at all the food we serve',
        'Menu Items': '👉 Add or change menu items',
        'Money & Payments': '👉 Handle cash and payments',
        'Unpaid Bills': '👉 See bills that haven\'t been paid yet',
        'Approvals Needed': '👉 Things waiting for your approval',
        'Sales Reports': '👉 See how much money we made',
        'Get Help': '👉 Contact support if you need help'
    };
    
    navItems.forEach(item => {
        const spanElement = item.querySelector('span');
        const text = spanElement ? spanElement.textContent.trim() : '';
        if (text && navHints[text]) {
            item.setAttribute('title', navHints[text]);
            item.setAttribute('data-hint', navHints[text]);
        }
    });
    
    // Add gentle guidance for first-time users
    const hasSeenGuide = localStorage.getItem('staffDashboardGuideShown');
    if (!hasSeenGuide) {
        showFirstTimeGuidance();
    }
    
    // Add keyboard shortcuts hint
    addKeyboardHint();
});

// Gentle pulse animation for important buttons
const helperAnimationStyle = document.createElement('style');
helperAnimationStyle.textContent = `
    @keyframes gentlePulse {
        0%, 100% {
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        50% {
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.5);
        }
    }
    
    @keyframes slideInFromBottom {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .first-time-guide {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        padding: 20px 24px;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(99, 102, 241, 0.4);
        max-width: 350px;
        z-index: 10000;
        animation: slideInFromBottom 0.5s ease-out;
    }
    
    .first-time-guide h3 {
        margin: 0 0 12px 0;
        font-size: 18px;
        font-weight: 700;
    }
    
    .first-time-guide p {
        margin: 0 0 16px 0;
        font-size: 14px;
        line-height: 1.6;
        opacity: 0.95;
    }
    
    .first-time-guide ul {
        margin: 0 0 16px 0;
        padding-left: 20px;
        font-size: 14px;
        line-height: 1.8;
    }
    
    .first-time-guide button {
        background: white;
        color: #6366f1;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
        width: 100%;
        font-size: 15px;
        transition: all 0.3s ease;
    }
    
    .first-time-guide button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    
    .keyboard-hint {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(31, 41, 55, 0.95);
        color: white;
        padding: 12px 20px;
        border-radius: 24px;
        font-size: 13px;
        font-weight: 600;
        z-index: 9999;
        backdrop-filter: blur(10px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
        display: none;
        animation: slideInFromBottom 0.3s ease-out;
    }
    
    .keyboard-hint.show {
        display: block;
    }
    
    .keyboard-hint kbd {
        background: white;
        color: #1f2937;
        padding: 4px 8px;
        border-radius: 4px;
        font-family: monospace;
        font-size: 12px;
        font-weight: 700;
        margin: 0 4px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    
    .visual-indicator {
        position: relative;
    }
    
    .visual-indicator::after {
        content: '👈';
        position: absolute;
        right: -30px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 24px;
        animation: pointLeft 2s infinite;
    }
    
    @keyframes pointLeft {
        0%, 100% {
            transform: translateY(-50%) translateX(0);
        }
        50% {
            transform: translateY(-50%) translateX(-5px);
        }
    }
`;
    document.head.appendChild(helperNotificationStyle);
// Show first-time guidance
function showFirstTimeGuidance() {
    const guide = document.createElement('div');
    guide.className = 'first-time-guide';
    guide.innerHTML = `
        <h3>👋 Welcome! Quick Guide</h3>
        <p>This is your work station. Here's how to use it:</p>
        <ul>
            <li>📊 <strong>Top boxes</strong> = Important numbers</li>
            <li>👈 <strong>Left sidebar</strong> = All your tools</li>
            <li>👆 <strong>Click/Tap anything</strong> = See more details</li>
            <li>🔄 <strong>Refresh button</strong> = Get latest updates</li>
        </ul>
        <p>Everything is designed to be simple - just click and follow!</p>
        <button onclick="this.parentElement.remove(); localStorage.setItem('staffDashboardGuideShown', 'true');">
            ✓ Got it, let's start!
        </button>
    `;
    document.body.appendChild(guide);
    
    // Auto-hide after 30 seconds if user doesn't click
    setTimeout(() => {
        if (document.body.contains(guide)) {
            guide.style.animation = 'slideInFromBottom 0.3s ease-out reverse';
            setTimeout(() => guide.remove(), 300);
            localStorage.setItem('staffDashboardGuideShown', 'true');
        }
    }, 30000);
}

// Show keyboard shortcuts hint
function addKeyboardHint() {
    const hint = document.createElement('div');
    hint.className = 'keyboard-hint';
    hint.innerHTML = `
        💡 Quick Tip: Press <kbd>R</kbd> to refresh · <kbd>H</kbd> for home · <kbd>?</kbd> for help
    `;
    document.body.appendChild(hint);
    
    // Show hint briefly after 5 seconds
    setTimeout(() => {
        hint.classList.add('show');
        setTimeout(() => {
            hint.classList.remove('show');
        }, 5000);
    }, 5000);
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        
        if (e.key === 'r' || e.key === 'R') {
            e.preventDefault();
            const refreshBtn = document.querySelector('.btn-refresh');
            if (refreshBtn) refreshBtn.click();
            showToast('🔄 Refreshing...', 'info');
        } else if (e.key === 'h' || e.key === 'H') {
            e.preventDefault();
            window.location.href = BASE_URL + '/staff/dashboard';
        } else if (e.key === '?') {
            e.preventDefault();
            showFirstTimeGuidance();
        }
    });
}

// Show quick toast notifications
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    const colors = {
        success: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
        error: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
        info: 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)',
        warning: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'
    };
    
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${colors[type] || colors.info};
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        z-index: 10001;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        animation: slideInFromBottom 0.3s ease-out;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideInFromBottom 0.3s ease-out reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Export for use in other scripts
window.staffHelpers = {
    showToast,
    showFirstTimeGuidance
};

// Add visual indicators for important actions
document.addEventListener('DOMContentLoaded', function() {
    // Highlight urgent items
    const urgentOrders = document.querySelectorAll('.order-item');
    urgentOrders.forEach(order => {
        if (order.innerHTML.includes('Delayed')) {
            order.style.borderLeftWidth = '6px';
            order.style.borderLeftColor = '#ef4444';
            const badge = document.createElement('span');
            badge.innerHTML = '⚠️';
            badge.style.cssText = 'position: absolute; top: 10px; right: 10px; font-size: 24px;';
            order.style.position = 'relative';
            order.appendChild(badge);
        }
    });
    
    // Make monetary values stand out
    const moneyValues = document.querySelectorAll('.stat-value');
    moneyValues.forEach(value => {
        if (value.textContent.includes('RWF')) {
            value.style.color = '#10b981';
            value.style.textShadow = '0 2px 4px rgba(16, 185, 129, 0.2)';
        }
    });
});

// Prevent accidental navigation away
window.addEventListener('beforeunload', function(e) {
    const activeOrders = document.querySelectorAll('.order-item').length;
    if (activeOrders > 0) {
        e.preventDefault();
        e.returnValue = 'You have active orders. Are you sure you want to leave?';
    }
});

console.log('✅ Friendly Staff Dashboard Helpers Loaded - Making work easier for everyone!');
