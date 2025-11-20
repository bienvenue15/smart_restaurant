// Staff Dashboard Dynamic Sections JavaScript
// Navigation functions
function loadOrders() {
    showSection('orders-section');
    loadOrdersData();
}

function loadWaiterCalls() {
    showSection('calls-section');
    loadCallsData();
}

function loadTables() {
    showSection('tables-section');
    loadTablesData();
}

function loadMenu() {
    showSection('menu-section');
    loadMenuData();
}

function loadCashManagement() {
    window.location.href = BASE_URL + '/?req=staff&action=cash_management';
}

function loadReports() {
    window.location.href = BASE_URL + '/?req=staff&action=reports';
}

// Show section and hide dashboard
function showSection(sectionId) {
    // Hide dashboard content
    document.querySelector('.stats-grid').style.display = 'none';
    document.querySelector('.content-grid').style.display = 'none';
    
    // Hide all sections
    document.querySelectorAll('.dynamic-section').forEach(section => {
        section.style.display = 'none';
    });
    
    // Show selected section
    const section = document.getElementById(sectionId);
    if (section) {
        section.style.display = 'block';
    } else {
        // Create section if it doesn't exist
        createSection(sectionId);
    }
    
    // Update navigation active state
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
}

function showDashboard() {
    // Show dashboard content
    document.querySelector('.stats-grid').style.display = 'grid';
    document.querySelector('.content-grid').style.display = 'grid';
    
    // Hide all sections
    document.querySelectorAll('.dynamic-section').forEach(section => {
        section.style.display = 'none';
    });
    
    // Update navigation
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector('.nav-item').classList.add('active');
}

function createSection(sectionId) {
    const mainContent = document.querySelector('.main-content');
    const section = document.createElement('div');
    section.id = sectionId;
    section.className = 'dynamic-section';
    section.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>Loading...</p></div>';
    mainContent.appendChild(section);
}

// Load Orders Data
function loadOrdersData() {
    const section = document.getElementById('orders-section');
    section.innerHTML = `
        <div class="section-header">
            <div>
                <button class="btn-back" onclick="showDashboard()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <h2 style="margin-top: 15px;"><i class="fas fa-shopping-cart"></i> Orders Management</h2>
            </div>
            <div class="section-filters">
                <select id="orderStatusFilter" onchange="filterOrders()" class="filter-select">
                    <option value="all">All Orders</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="preparing">Preparing</option>
                    <option value="ready">Ready</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
        </div>
        <div id="ordersContainer" class="loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading orders...</p>
        </div>
    `;
    
    // Fetch orders from API
    fetch(BASE_URL + '/?req=api&action=staff_get_orders')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'OK') {
                displayOrders(data.data);
            } else {
                section.querySelector('#ordersContainer').innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Failed to load orders</p></div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            section.querySelector('#ordersContainer').innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Error loading orders</p></div>';
        });
}

function displayOrders(orders) {
    const container = document.getElementById('ordersContainer');
    if (!orders || orders.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-check-circle"></i><p>No orders found</p></div>';
        return;
    }
    
    let html = '<div class="orders-grid">';
    orders.forEach(order => {
        const statusButtons = getOrderStatusButtons(order);
        const refundButton = (order.status === 'completed' || order.status === 'ready') ? 
            `<button class="btn-small btn-danger" onclick="requestRefund(${order.id})">
                <i class="fas fa-undo"></i> Refund
            </button>` : '';
        
        html += `
            <div class="order-card order-status-${order.status}">
                <div class="order-card-header">
                    <div>
                        <div class="order-number">${order.order_number}</div>
                        <div class="order-time">${formatTime(order.created_at)}</div>
                    </div>
                    <span class="status-badge status-${order.status}">${order.status}</span>
                </div>
                <div class="order-card-body">
                    <div class="order-info">
                        <i class="fas fa-chair"></i> Table ${order.table_number}
                    </div>
                    <div class="order-info">
                        <i class="fas fa-shopping-bag"></i> ${order.item_count} items
                    </div>
                    <div class="order-info">
                        <i class="fas fa-money-bill-wave"></i> RWF ${formatNumber(order.total_amount)}
                    </div>
                </div>
                <div class="order-card-actions">
                    <button class="btn-small btn-primary" onclick="viewOrderDetails(${order.id})">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                    ${statusButtons}
                    ${refundButton}
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function getOrderStatusButtons(order) {
    let buttons = '';
    if (order.status === 'pending') {
        buttons = `<button class="btn-small btn-success" onclick="updateOrderStatus(${order.id}, 'confirmed')">
            <i class="fas fa-check"></i> Confirm
        </button>`;
    } else if (order.status === 'confirmed') {
        buttons = `<button class="btn-small btn-warning" onclick="updateOrderStatus(${order.id}, 'preparing')">
            <i class="fas fa-fire"></i> Preparing
        </button>`;
    } else if (order.status === 'preparing') {
        buttons = `<button class="btn-small btn-info" onclick="updateOrderStatus(${order.id}, 'ready')">
            <i class="fas fa-check-double"></i> Ready
        </button>`;
    } else if (order.status === 'ready') {
        buttons = `<button class="btn-small btn-success" onclick="updateOrderStatus(${order.id}, 'completed')">
            <i class="fas fa-flag-checkered"></i> Complete
        </button>`;
    }
    return buttons;
}

function filterOrders() {
    loadOrdersData();
}

function viewOrderDetails(orderId) {
    alert('View order details for Order #' + orderId);
}

function updateOrderStatus(orderId, newStatus) {
    if (!confirm('Change order status to ' + newStatus + '?')) return;
    
    fetch(BASE_URL + '/?req=api&action=staff_update_order', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'order_id=' + orderId + '&status=' + newStatus
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'OK') {
            loadOrdersData();
        } else {
            alert(data.message || 'Failed to update order');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update order status');
    });
}

// Request refund for order
function requestRefund(orderId) {
    const reason = prompt('Enter refund reason:');
    if (!reason || reason.trim() === '') {
        alert('Refund reason is required');
        return;
    }
    
    if (!confirm('Request refund for this order? This requires manager approval.')) return;
    
    fetch(BASE_URL + '/?req=api&action=staff_request_refund', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'order_id=' + orderId + '&reason=' + encodeURIComponent(reason)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'OK') {
            alert('Refund request submitted for approval');
            loadOrdersData();
        } else {
            alert(data.message || 'Failed to request refund');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to request refund');
    });
}

// Load Waiter Calls Data
function loadCallsData() {
    const section = document.getElementById('calls-section');
    section.innerHTML = `
        <div class="section-header">
            <div>
                <button class="btn-back" onclick="showDashboard()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <h2 style="margin-top: 15px;"><i class="fas fa-bell"></i> Waiter Calls</h2>
            </div>
        </div>
        <div id="callsContainer" class="loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading calls...</p>
        </div>
    `;
    
    fetch(BASE_URL + '/?req=api&action=staff_get_calls')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'OK') {
                displayCalls(data.data);
            } else {
                section.querySelector('#callsContainer').innerHTML = '<div class="empty-state"><i class="fas fa-check-circle"></i><p>No pending calls</p></div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            section.querySelector('#callsContainer').innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Error loading calls</p></div>';
        });
}

function displayCalls(calls) {
    const container = document.getElementById('callsContainer');
    if (!calls || calls.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-check-circle"></i><p>No pending waiter calls</p></div>';
        return;
    }
    
    let html = '<div class="calls-grid">';
    calls.forEach(call => {
        const assignedInfo = call.assigned_to ? `
            <div class="call-assigned">
                <i class="fas fa-user"></i> Assigned to: ${call.assigned_staff}
            </div>
        ` : '';
        
        const actions = call.status === 'pending' ? `
            <button class="btn-small btn-primary" onclick="assignCall(${call.id})">
                <i class="fas fa-hand-paper"></i> Assign to Me
            </button>
        ` : (call.status === 'assigned' ? `
            <button class="btn-small btn-success" onclick="completeCall(${call.id})">
                <i class="fas fa-check"></i> Complete
            </button>
        ` : '');
        
        html += `
            <div class="call-card call-${call.status}">
                <div class="call-header">
                    <div class="call-table">
                        <i class="fas fa-chair"></i> Table ${call.table_number}
                    </div>
                    <span class="call-status status-${call.status}">${call.status}</span>
                </div>
                <div class="call-body">
                    <div class="call-time">
                        <i class="fas fa-clock"></i> ${formatTime(call.created_at)}
                    </div>
                    ${assignedInfo}
                </div>
                <div class="call-actions">
                    ${actions}
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function assignCall(callId) {
    fetch(BASE_URL + '/?req=api&action=staff_assign_call', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'call_id=' + callId
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'OK') {
            loadCallsData();
        } else {
            alert(data.message || 'Failed to assign call');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to assign call');
    });
}

function completeCall(callId) {
    fetch(BASE_URL + '/?req=api&action=staff_complete_call', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'call_id=' + callId
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'OK') {
            loadCallsData();
        } else {
            alert(data.message || 'Failed to complete call');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to complete call');
    });
}

// Load Tables Data
function loadTablesData() {
    const section = document.getElementById('tables-section');
    section.innerHTML = `
        <div class="section-header">
            <div>
                <button class="btn-back" onclick="showDashboard()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <h2 style="margin-top: 15px;"><i class="fas fa-chair"></i> Tables Management</h2>
            </div>
            <div>
                <button class="btn btn-success" onclick="showAddTableForm()">
                    <i class="fas fa-plus"></i> Add Table
                </button>
            </div>
        </div>
        <div id="addTableFormContainer" style="display: none; margin-bottom: 20px;">
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-plus-circle"></i> Add New Table</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 10px; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Table Number</label>
                        <input type="number" id="newTableNumber" class="form-input" placeholder="e.g., 10" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Capacity</label>
                        <input type="number" id="newTableCapacity" class="form-input" placeholder="e.g., 4" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Status</label>
                        <select id="newTableStatus" class="form-input" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                            <option value="available">Available</option>
                            <option value="reserved">Reserved</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 5px;">
                        <button class="btn btn-primary" onclick="addTable()">
                            <i class="fas fa-save"></i> Save
                        </button>
                        <button class="btn btn-secondary" onclick="hideAddTableForm()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div id="tablesContainer" class="loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading tables...</p>
        </div>
    `;
    
    fetch(BASE_URL + '/?req=api&action=staff_get_tables')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'OK') {
                displayTables(data.data);
            } else {
                section.querySelector('#tablesContainer').innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Failed to load tables</p></div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            section.querySelector('#tablesContainer').innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Error loading tables</p></div>';
        });
}

function displayTables(tables) {
    const container = document.getElementById('tablesContainer');
    if (!tables || tables.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>No tables found</p></div>';
        return;
    }
    
    let html = '<div class="tables-grid">';
    tables.forEach(table => {
        const orderInfo = table.current_order ? `
            <div class="table-order">
                <i class="fas fa-shopping-cart"></i> Order: ${table.current_order}
            </div>
        ` : '';
        
        const resetButton = table.status === 'occupied' ? `
            <button class="btn-small btn-warning" onclick="resetTable(${table.id})">
                <i class="fas fa-redo"></i> Reset
            </button>
        ` : '';
        
        const deleteButton = table.status === 'available' ? `
            <button class="btn-small btn-danger" onclick="deleteTable(${table.id}, '${table.table_number}')">
                <i class="fas fa-trash"></i> Delete
            </button>
        ` : '';
        
        const updateButton = `
            <button class="btn-small btn-info" onclick="showUpdateTableForm(${table.id}, '${table.table_number}', ${table.capacity}, '${table.status}')">
                <i class="fas fa-edit"></i> Edit
            </button>
        `;
        
        html += `
            <div class="table-card table-${table.status}">
                <div class="table-header">
                    <div class="table-number">Table ${table.table_number}</div>
                    <span class="table-status status-${table.status}">${table.status}</span>
                </div>
                <div class="table-body">
                    <div class="table-info">
                        <i class="fas fa-users"></i> Capacity: ${table.capacity}
                    </div>
                    ${orderInfo}
                </div>
                <div class="table-actions">
                    ${updateButton}
                    ${resetButton}
                    ${deleteButton}
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function resetTable(tableId) {
    if (!confirm('Reset this table? This will clear the current order.')) return;
    
    fetch(BASE_URL + '/?req=api&action=staff_reset_table', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'table_id=' + tableId
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'OK') {
            loadTablesData();
        } else {
            alert(data.message || 'Failed to reset table');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to reset table');
    });
}

// Show add table form
function showAddTableForm() {
    document.getElementById('addTableFormContainer').style.display = 'block';
    const updateForm = document.getElementById('updateTableFormContainer');
    if (updateForm) updateForm.style.display = 'none';
}

// Hide add table form
function hideAddTableForm() {
    document.getElementById('addTableFormContainer').style.display = 'none';
    document.getElementById('newTableNumber').value = '';
    document.getElementById('newTableCapacity').value = '';
    document.getElementById('newTableStatus').value = 'available';
}

// Show update table form
function showUpdateTableForm(tableId, tableNumber, capacity, status) {
    document.getElementById('addTableFormContainer').style.display = 'none';
    const container = document.getElementById('updateTableFormContainer');
    
    if (!container) {
        // Create update form if it doesn't exist
        const section = document.getElementById('tables-section');
        const formHTML = `
            <div id="updateTableFormContainer" style="display: none; margin-bottom: 20px;">
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h3 style="margin-bottom: 15px;"><i class="fas fa-edit"></i> Update Table</h3>
                    <input type="hidden" id="updateTableId" value="">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Table Number *</label>
                            <input type="text" id="updateTableNumber" class="form-input" readonly style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; background: #f5f5f5;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Capacity *</label>
                            <input type="number" id="updateTableCapacity" class="form-input" min="1" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Status *</label>
                            <select id="updateTableStatus" class="form-input" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                                <option value="available">Available</option>
                                <option value="occupied">Occupied</option>
                                <option value="reserved">Reserved</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top: 15px; display: flex; gap: 10px; justify-content: flex-end;">
                        <button class="btn btn-secondary" onclick="hideUpdateTableForm()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="btn btn-primary" onclick="updateTable()">
                            <i class="fas fa-save"></i> Update
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        section.querySelector('.section-header').insertAdjacentHTML('afterend', formHTML);
    }
    
    // Set form values
    document.getElementById('updateTableId').value = tableId;
    document.getElementById('updateTableNumber').value = tableNumber;
    document.getElementById('updateTableCapacity').value = capacity;
    document.getElementById('updateTableStatus').value = status;
    document.getElementById('updateTableFormContainer').style.display = 'block';
}

// Hide update table form
function hideUpdateTableForm() {
    document.getElementById('updateTableFormContainer').style.display = 'none';
}

// Update table
function updateTable() {
    const tableId = document.getElementById('updateTableId').value;
    const capacity = document.getElementById('updateTableCapacity').value;
    const status = document.getElementById('updateTableStatus').value;
    
    if (!capacity || capacity < 1) {
        alert('Please enter a valid capacity');
        return;
    }
    
    fetch(BASE_URL + '/?req=api&action=staff_update_table', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'table_id=' + encodeURIComponent(tableId) + 
              '&capacity=' + encodeURIComponent(capacity) + 
              '&status=' + encodeURIComponent(status)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'OK') {
            alert(data.message || 'Table updated successfully');
            hideUpdateTableForm();
            loadTablesData();
        } else {
            alert(data.message || 'Failed to update table');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update table');
    });
}

// Add new table
function addTable() {
    const tableNumber = document.getElementById('newTableNumber').value;
    const capacity = document.getElementById('newTableCapacity').value;
    const status = document.getElementById('newTableStatus').value;
    
    if (!tableNumber || !capacity) {
        alert('Please fill in all fields');
        return;
    }
    
    fetch(BASE_URL + '/?req=api&action=staff_add_table', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'table_number=' + tableNumber + '&capacity=' + capacity + '&status=' + status
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'OK') {
            alert('Table added successfully');
            hideAddTableForm();
            loadTablesData();
        } else {
            alert(data.message || 'Failed to add table');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add table');
    });
}

// Delete table
function deleteTable(tableId, tableNumber) {
    if (!confirm('Delete Table ' + tableNumber + '? This action cannot be undone.')) return;
    
    fetch(BASE_URL + '/?req=api&action=staff_delete_table', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'table_id=' + tableId
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'OK') {
            alert('Table deleted successfully');
            loadTablesData();
        } else {
            alert(data.message || 'Failed to delete table');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to delete table');
    });
}

// Load Menu Data
function loadMenuData() {
    const section = document.getElementById('menu-section');
    section.innerHTML = `
        <div class="section-header">
            <div>
                <button class="btn-back" onclick="showDashboard()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <h2 style="margin-top: 15px;"><i class="fas fa-book"></i> Menu Items</h2>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <select id="menuCategoryFilter" onchange="filterMenu()" class="filter-select">
                    <option value="all">All Categories</option>
                </select>
                <button class="btn btn-success" onclick="showAddMenuForm()">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            </div>
        </div>
        <div id="addMenuFormContainer" style="display: none; margin-bottom: 20px;">
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-plus-circle"></i> Add New Menu Item</h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Item Name *</label>
                        <input type="text" id="newItemName" class="form-input" placeholder="e.g., Grilled Chicken" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Category *</label>
                        <select id="newItemCategory" class="form-input" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                            <option value="">Select Category</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Price (RWF) *</label>
                        <input type="number" id="newItemPrice" class="form-input" placeholder="e.g., 5000" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Availability</label>
                        <select id="newItemAvailable" class="form-input" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                            <option value="1">Available</option>
                            <option value="0">Unavailable</option>
                        </select>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Description</label>
                        <textarea id="newItemDescription" class="form-input" rows="3" placeholder="Item description..." style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;"></textarea>
                    </div>
                    <div style="grid-column: 1 / -1; display: flex; gap: 10px; justify-content: flex-end;">
                        <button class="btn btn-secondary" onclick="hideAddMenuForm()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="btn btn-primary" onclick="addMenuItem()">
                            <i class="fas fa-save"></i> Save Item
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div id="menuContainer" class="loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading menu...</p>
        </div>
    `;
    
    // Load categories for dropdown
    loadMenuCategories();
    
    fetch(BASE_URL + '/?req=api&action=staff_get_menu')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'OK') {
                displayMenu(data.data);
            } else {
                section.querySelector('#menuContainer').innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Failed to load menu</p></div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            section.querySelector('#menuContainer').innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Error loading menu</p></div>';
        });
}

function displayMenu(menuData) {
    const container = document.getElementById('menuContainer');
    if (!menuData || menuData.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>No menu items found</p></div>';
        return;
    }
    
    // Group by category
    const categories = {};
    menuData.forEach(item => {
        const cat = item.category || 'Other';
        if (!categories[cat]) categories[cat] = [];
        categories[cat].push(item);
    });
    
    let html = '';
    Object.keys(categories).forEach(category => {
        html += `
            <div class="menu-category">
                <h3 class="category-title"><i class="fas fa-utensils"></i> ${category}</h3>
                <div class="menu-items-grid">
        `;
        
        categories[category].forEach(item => {
            const availClass = item.available ? '' : 'unavailable';
            const availText = item.available ? 'Available' : 'Unavailable';
            const availIconClass = item.available ? 'available' : 'unavailable';
            
            html += `
                <div class="menu-item-card ${availClass}">
                    <div class="menu-item-header">
                        <div class="menu-item-name">${item.name}</div>
                        <div class="menu-item-price">RWF ${formatNumber(item.price)}</div>
                    </div>
                    <div class="menu-item-body">
                        <p class="menu-item-desc">${item.description || 'No description'}</p>
                    </div>
                    <div class="menu-item-footer">
                        <span class="availability ${availIconClass}">
                            <i class="fas fa-circle"></i> ${availText}
                        </span>
                        <div style="display: flex; gap: 5px;">
                            <button class="btn-small btn-info" onclick="showUpdateMenuForm(${item.id}, '${item.name.replace(/'/g, "\\'")}', ${item.category_id}, ${item.price}, '${(item.description || '').replace(/'/g, "\\'")}', ${item.available})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-small btn-danger" onclick="deleteMenuItem(${item.id}, '${item.name.replace(/'/g, "\\'")}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function filterMenu() {
    loadMenuData();
}

// Load menu categories for dropdown
function loadMenuCategories() {
    fetch(BASE_URL + '/?req=api&action=get_menu')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'OK' && data.data) {
                const select = document.getElementById('newItemCategory');
                if (select) {
                    data.data.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.id;
                        option.textContent = category.name;
                        select.appendChild(option);
                    });
                }
            }
        })
        .catch(error => console.error('Error loading categories:', error));
}

// Show add menu item form
function showAddMenuForm() {
    document.getElementById('addMenuFormContainer').style.display = 'block';
    const updateForm = document.getElementById('updateMenuFormContainer');
    if (updateForm) updateForm.style.display = 'none';
}

// Hide add menu item form
function hideAddMenuForm() {
    document.getElementById('addMenuFormContainer').style.display = 'none';
    document.getElementById('newItemName').value = '';
    document.getElementById('newItemCategory').value = '';
    document.getElementById('newItemPrice').value = '';
    document.getElementById('newItemDescription').value = '';
    document.getElementById('newItemAvailable').value = '1';
}

// Show update menu item form
function showUpdateMenuForm(itemId, name, categoryId, price, description, available) {
    document.getElementById('addMenuFormContainer').style.display = 'none';
    const container = document.getElementById('updateMenuFormContainer');
    
    if (!container) {
        // Create update form if it doesn't exist
        const section = document.getElementById('menu-section');
        const formHTML = `
            <div id="updateMenuFormContainer" style="display: none; margin-bottom: 20px;">
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h3 style="margin-bottom: 15px;"><i class="fas fa-edit"></i> Update Menu Item</h3>
                    <input type="hidden" id="updateItemId" value="">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Item Name *</label>
                            <input type="text" id="updateItemName" class="form-input" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Category *</label>
                            <select id="updateItemCategory" class="form-input" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                                <option value="">Select Category</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Price (RWF) *</label>
                            <input type="number" id="updateItemPrice" class="form-input" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Availability</label>
                            <select id="updateItemAvailable" class="form-input" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                                <option value="1">Available</option>
                                <option value="0">Unavailable</option>
                            </select>
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Description</label>
                            <textarea id="updateItemDescription" class="form-input" rows="3" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;"></textarea>
                        </div>
                        <div style="grid-column: 1 / -1; display: flex; gap: 10px; justify-content: flex-end;">
                            <button class="btn btn-secondary" onclick="hideUpdateMenuForm()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button class="btn btn-primary" onclick="updateMenuItem()">
                                <i class="fas fa-save"></i> Update
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        const addFormContainer = document.getElementById('addMenuFormContainer');
        addFormContainer.insertAdjacentHTML('afterend', formHTML);
        
        // Load categories for the update form dropdown
        loadUpdateMenuCategories();
    }
    
    // Set form values
    document.getElementById('updateItemId').value = itemId;
    document.getElementById('updateItemName').value = name;
    document.getElementById('updateItemCategory').value = categoryId;
    document.getElementById('updateItemPrice').value = price;
    document.getElementById('updateItemDescription').value = description;
    document.getElementById('updateItemAvailable').value = available;
    document.getElementById('updateMenuFormContainer').style.display = 'block';
}

// Hide update menu item form
function hideUpdateMenuForm() {
    document.getElementById('updateMenuFormContainer').style.display = 'none';
}

// Load categories for update form
function loadUpdateMenuCategories() {
    fetch(BASE_URL + '/?req=api&action=get_menu')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'OK' && data.data) {
                const select = document.getElementById('updateItemCategory');
                if (select) {
                    // Clear existing options except first
                    while (select.options.length > 1) {
                        select.remove(1);
                    }
                    
                    data.data.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.id;
                        option.textContent = category.name;
                        select.appendChild(option);
                    });
                }
            }
        })
        .catch(error => console.error('Error loading categories:', error));
}

// Update menu item
function updateMenuItem() {
    const itemId = document.getElementById('updateItemId').value;
    const name = document.getElementById('updateItemName').value;
    const categoryId = document.getElementById('updateItemCategory').value;
    const price = document.getElementById('updateItemPrice').value;
    const description = document.getElementById('updateItemDescription').value;
    const available = document.getElementById('updateItemAvailable').value;
    
    if (!name || !categoryId || !price) {
        alert('Please fill in all required fields');
        return;
    }
    
    fetch(BASE_URL + '/?req=api&action=staff_update_menu_item', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'item_id=' + encodeURIComponent(itemId) + 
              '&name=' + encodeURIComponent(name) + 
              '&category_id=' + encodeURIComponent(categoryId) + 
              '&price=' + encodeURIComponent(price) + 
              '&description=' + encodeURIComponent(description) + 
              '&available=' + encodeURIComponent(available)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'OK') {
            alert(data.message || 'Menu item updated successfully');
            hideUpdateMenuForm();
            loadMenuData();
        } else {
            alert(data.message || 'Failed to update menu item');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update menu item');
    });
}

// Add new menu item
function addMenuItem() {
    const name = document.getElementById('newItemName').value;
    const category = document.getElementById('newItemCategory').value;
    const price = document.getElementById('newItemPrice').value;
    const description = document.getElementById('newItemDescription').value;
    const available = document.getElementById('newItemAvailable').value;
    
    if (!name || !category || !price) {
        alert('Please fill in all required fields (Name, Category, Price)');
        return;
    }
    
    fetch(BASE_URL + '/?req=api&action=staff_add_menu_item', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'name=' + encodeURIComponent(name) + 
              '&category_id=' + category + 
              '&price=' + price + 
              '&description=' + encodeURIComponent(description) + 
              '&available=' + available
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'OK') {
            alert('Menu item added successfully');
            hideAddMenuForm();
            loadMenuData();
        } else {
            alert(data.message || 'Failed to add menu item');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add menu item');
    });
}

// Delete menu item
function deleteMenuItem(itemId, itemName) {
    if (!confirm('Delete "' + itemName + '"? This action cannot be undone.')) return;
    
    fetch(BASE_URL + '/?req=api&action=staff_delete_menu_item', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'item_id=' + itemId
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'OK') {
            alert('Menu item deleted successfully');
            loadMenuData();
        } else {
            alert(data.message || 'Failed to delete menu item');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to delete menu item');
    });
}

// Helper functions
function formatTime(datetime) {
    const date = new Date(datetime);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatNumber(num) {
    return new Intl.NumberFormat('en-US').format(num);
}
