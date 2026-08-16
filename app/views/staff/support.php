<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - Staff Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/staff.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; }
        
        .support-container {
            margin-left: 260px;
            padding: 30px;
            min-height: 100vh;
        }
        
        .support-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .support-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
            font-weight: 700;
        }
        
        .actions-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .tickets-grid {
            display: grid;
            gap: 20px;
        }
        
        .ticket-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border-left: 4px solid #667eea;
        }
        
        .ticket-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }
        
        .ticket-card.status-open { border-left-color: #f59e0b; }
        .ticket-card.status-in_progress { border-left-color: #3b82f6; }
        .ticket-card.status-resolved { border-left-color: #10b981; }
        .ticket-card.status-closed { border-left-color: #6b7280; }
        
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .ticket-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .ticket-meta {
            display: flex;
            gap: 20px;
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .ticket-body {
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.open { background: #fef3c7; color: #92400e; }
        .status-badge.in_progress { background: #dbeafe; color: #1e40af; }
        .status-badge.resolved { background: #d1fae5; color: #065f46; }
        .status-badge.closed { background: #f3f4f6; color: #4b5563; }
        
        .priority-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .priority-high { background: #fee2e2; color: #991b1b; }
        .priority-medium { background: #fef3c7; color: #92400e; }
        .priority-low { background: #e5e7eb; color: #4b5563; }
        
        .ticket-replies {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .reply-item {
            padding: 12px;
            background: white;
            border-radius: 6px;
            margin-bottom: 10px;
            border-left: 3px solid #667eea;
        }
        
        .reply-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        .reply-author {
            font-weight: 600;
            color: #667eea;
        }
        
        .reply-admin {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
        }
        
        .reply-body {
            color: #4b5563;
            line-height: 1.5;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px 15px 0 0;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 24px;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding: 20px 25px;
            border-top: 1px solid #e5e7eb;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        /* Mobile Responsive Design */
        @media (max-width: 768px) {
            .support-container {
                padding: 16px;
            }
            
            .page-header {
                padding: 18px;
            }
            
            .page-title {
                font-size: 1.375rem;
            }
            
            .tickets-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .ticket-card {
                padding: 16px;
            }
            
            .form-group {
                margin-bottom: 16px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .support-container {
                padding: 12px;
            }
            
            .page-header {
                padding: 14px;
            }
            
            .page-title {
                font-size: 1.125rem;
            }
            
            .ticket-card {
                padding: 12px;
            }
            
            .empty-state i {
                font-size: 48px;
            }
        }
    </style>
</head>
<body>

<?php include 'app/views/staff/_sidebar.php'; ?>

<div class="support-container">
    <div class="support-header">
        <h1><i class="fas fa-life-ring"></i> Support Tickets</h1>
        <p style="margin: 0; opacity: 0.9;">Need help? Create a ticket and our support team will assist you.</p>
    </div>
    
    <div class="actions-bar">
        <button class="btn btn-primary" onclick="showNewTicketModal()">
            <i class="fas fa-plus"></i> New Ticket
        </button>
    </div>
    
    <div class="tickets-grid" id="ticketsGrid">
        <div class="empty-state">
            <i class="fas fa-spinner fa-spin"></i>
            <h3>Loading tickets...</h3>
        </div>
    </div>
</div>

<!-- New Ticket Modal -->
<div class="modal" id="newTicketModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> Create Support Ticket</h2>
        </div>
        <form id="newTicketForm">
            <div class="modal-body">
                <div class="form-group">
                    <label for="ticketSubject">Subject *</label>
                    <input type="text" id="ticketSubject" class="form-control" placeholder="Brief description of the issue" required>
                </div>
                
                <div class="form-group">
                    <label for="ticketPriority">Priority *</label>
                    <select id="ticketPriority" class="form-control" required>
                        <option value="low">Low - General inquiry</option>
                        <option value="medium" selected>Medium - Needs attention</option>
                        <option value="high">High - Urgent issue</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="ticketMessage">Message *</label>
                    <textarea id="ticketMessage" class="form-control" placeholder="Describe your issue in detail..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeNewTicketModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Ticket
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Ticket Modal -->
<div class="modal" id="viewTicketModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="viewTicketTitle"><i class="fas fa-ticket-alt"></i> Ticket Details</h2>
        </div>
        <div class="modal-body" id="viewTicketBody">
            <!-- Ticket details will be loaded here -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeViewTicketModal()">Close</button>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
const RESTAURANT_ID = <?php echo $user['restaurant_id'] ?? 'null'; ?>;
const STAFF_ID = '<?php echo htmlspecialchars($user['uuid'] ?? '', ENT_QUOTES); ?>';

// Validate required constants
if (!STAFF_ID || STAFF_ID === 'null' || STAFF_ID === '') {
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('ticketsGrid').innerHTML = `
            <div class="empty-state">
                <i class="fas fa-exclamation-circle" style="color: #dc3545;"></i>
                <h3>Session Error</h3>
                <p>Staff ID not found. Please log in again.</p>
            </div>
        `;
    });
}


function showNewTicketModal() {
    if (!STAFF_ID || STAFF_ID === 'null') {
        showToast('Please log in to create a ticket', 'error');
        return;
    }
    document.getElementById('newTicketModal').classList.add('active');
}

function closeNewTicketModal() {
    document.getElementById('newTicketModal').classList.remove('active');
    document.getElementById('newTicketForm').reset();
}

function closeViewTicketModal() {
    document.getElementById('viewTicketModal').classList.remove('active');
}

document.getElementById('newTicketForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const btn = e.target.querySelector('button[type="submit"]');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    
    const subject = document.getElementById('ticketSubject').value;
    const priority = document.getElementById('ticketPriority').value;
    const message = document.getElementById('ticketMessage').value;
    
    if (!subject || !message) {
        showToast('Please fill in all required fields', 'error');
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        return;
    }
    
    if (!STAFF_ID || STAFF_ID === 'null') {
        showToast('Staff ID not found. Please log in again.', 'error');
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        return;
    }
    
    const data = {
        subject: subject,
        priority: priority,
        message: message,
        restaurant_id: RESTAURANT_ID !== 'null' ? RESTAURANT_ID : null,
        staff_id: STAFF_ID
    };
    
    
    try {
        const response = await fetch(`${BASE_URL}/?req=api&action=create_staff_ticket`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data),
            credentials: 'include'
        });
        
        
        if (!response.ok) {
            const text = await response.text();
            throw new Error(`HTTP ${response.status}: ${text.substring(0, 200)}`);
        }
        
        const result = await response.json();
        
        if (result.status === 'OK') {
            closeNewTicketModal();
            loadTickets();
            showToast('Ticket created successfully!', 'success');
        } else {
            showToast(result.message || 'Failed to create ticket', 'error');
        }
    } catch (err) {
        showToast('Failed to create ticket. Please check console for details.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    }
});

async function loadTickets() {
    const grid = document.getElementById('ticketsGrid');
    grid.innerHTML = '<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><h3>Loading tickets...</h3></div>';
    
    try {
        const response = await fetch(`${BASE_URL}/?req=api&action=get_staff_tickets&staff_id=${STAFF_ID}`, {
            credentials: 'include'
        });
        
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        
        const result = JSON.parse(text);
        
        if (result.status === 'OK' && result.data && result.data.length > 0) {
            grid.innerHTML = result.data.map(ticket => `
                <div class="ticket-card status-${ticket.status}" onclick="viewTicket(${ticket.id})">
                    <div class="ticket-header">
                        <div>
                            <div class="ticket-title">${escapeHtml(ticket.subject)}</div>
                            <span class="priority-badge priority-${ticket.priority}">${ticket.priority.toUpperCase()}</span>
                        </div>
                        <span class="status-badge ${ticket.status}">${ticket.status.replace('_', ' ')}</span>
                    </div>
                    <div class="ticket-meta">
                        <span><i class="fas fa-calendar"></i> ${new Date(ticket.created_at).toLocaleDateString()}</span>
                        <span><i class="fas fa-clock"></i> ${new Date(ticket.created_at).toLocaleTimeString()}</span>
                        ${ticket.reply_count ? `<span><i class="fas fa-comment"></i> ${ticket.reply_count} replies</span>` : ''}
                    </div>
                    <div class="ticket-body">
                        ${escapeHtml(ticket.message).substring(0, 150)}${ticket.message.length > 150 ? '...' : ''}
                    </div>
                </div>
            `).join('');
        } else {
            grid.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Tickets Yet</h3>
                    <p>You haven't created any support tickets yet.</p>
                    <button class="btn btn-primary" onclick="showNewTicketModal()" style="margin-top: 20px;">
                        <i class="fas fa-plus"></i> Create Your First Ticket
                    </button>
                </div>
            `;
        }
    } catch (err) {
        grid.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-exclamation-circle" style="color: #dc3545;"></i>
                <h3>Failed to Load Tickets</h3>
                <p>Please refresh the page to try again.</p>
            </div>
        `;
    }
}

async function viewTicket(ticketId) {
    const modal = document.getElementById('viewTicketModal');
    const body = document.getElementById('viewTicketBody');
    
    body.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i></div>';
    modal.classList.add('active');
    
    try {
        const response = await fetch(`${BASE_URL}/?req=api&action=get_staff_ticket_detail&ticket_id=${ticketId}`, {
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.status === 'OK' && result.data) {
            const ticket = result.data;
            document.getElementById('viewTicketTitle').innerHTML = `<i class="fas fa-ticket-alt"></i> ${escapeHtml(ticket.subject)}`;
            
            body.innerHTML = `
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                        <span class="status-badge ${ticket.status}">${ticket.status.replace('_', ' ')}</span>
                        <span class="priority-badge priority-${ticket.priority}">${ticket.priority.toUpperCase()}</span>
                    </div>
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 15px;">
                        Created on ${new Date(ticket.created_at).toLocaleString()}
                    </div>
                    <div style="background: #f9fafb; padding: 15px; border-radius: 8px; line-height: 1.6;">
                        ${escapeHtml(ticket.message).replace(/\n/g, '<br>')}
                    </div>
                </div>
                
                ${ticket.replies && ticket.replies.length > 0 ? `
                    <div class="ticket-replies">
                        <h3 style="margin: 0 0 15px 0; font-size: 16px;">Replies (${ticket.replies.length})</h3>
                        ${ticket.replies.map(reply => `
                            <div class="reply-item">
                                <div class="reply-header">
                                    <span class="reply-author">
                                        ${reply.is_admin ? '<span class="reply-admin">SUPPORT TEAM</span>' : escapeHtml(reply.replier_name || 'You')}
                                    </span>
                                    <span style="color: #9ca3af;">${new Date(reply.created_at).toLocaleString()}</span>
                                </div>
                                <div class="reply-body">${escapeHtml(reply.message).replace(/\n/g, '<br>')}</div>
                            </div>
                        `).join('')}
                    </div>
                ` : '<p style="color: #6b7280; text-align: center; padding: 20px;">No replies yet. Our support team will respond soon.</p>'}
            `;
        } else {
            body.innerHTML = '<p style="color: #dc3545; text-align: center; padding: 40px;">Failed to load ticket details.</p>';
        }
    } catch (err) {
        body.innerHTML = '<p style="color: #dc3545; text-align: center; padding: 40px;">Failed to load ticket details.</p>';
    }
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#dc3545' : '#3b82f6'};
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10001;
        font-weight: 600;
    `;
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i> ${message}`;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.remove(), 3000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Load tickets on page load
document.addEventListener('DOMContentLoaded', () => {
    loadTickets();
});
</script>

</body>
</html>
