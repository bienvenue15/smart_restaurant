<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Menu - Smart Restaurant'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="<?php echo APP_FAVICON_URL; ?>">
    <link rel="apple-touch-icon" href="<?php echo APP_LOGO_URL; ?>">
</head>
<body class="menu-page">
    
    <!-- Header with Table Info -->
    <header class="menu-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="<?php echo APP_LOGO_URL; ?>" alt="Smart Restaurant logo">
                    <span>Smart Restaurant</span>
                </div>
                <?php if (isset($table) && $table): ?>
                <div class="table-info">
                    <i class="fas fa-chair"></i>
                    <span>Table <?php echo htmlspecialchars($table['table_number']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <!-- Search Bar -->
    <div class="search-container">
        <div class="container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search menu items, dietary preferences..." autocomplete="off">
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <main class="menu-main">
        <div class="container">
            <div class="menu-layout">
                
                <!-- Menu Categories and Items -->
                <div class="menu-content">
                    <div id="menuContainer">
                        <?php if (isset($menu) && !empty($menu)): ?>
                            <?php foreach ($menu as $category): ?>
                                <div class="menu-category" data-category="<?php echo $category['id']; ?>">
                                    <div class="category-header">
                                        <h2 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h2>
                                        <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                                    </div>
                                    
                                    <div class="menu-items-grid">
                                        <?php if (!empty($category['items'])): ?>
                                            <?php foreach ($category['items'] as $item): ?>
                                                <div class="menu-item <?php echo $item['is_available'] ? '' : 'unavailable'; ?>" 
                                                     data-item-id="<?php echo $item['id']; ?>"
                                                     data-item-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                     data-item-price="<?php echo $item['price']; ?>"
                                                     data-item-available="<?php echo $item['is_available']; ?>">
                                                    
                                                    <?php if ($item['is_special']): ?>
                                                        <div class="special-badge">
                                                            <i class="fas fa-star"></i> Special
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="item-image">
                                                        <?php if ($item['image_url']): ?>
                                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                        <?php else: ?>
                                                            <div class="placeholder-image">
                                                                <i class="fas fa-utensils"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="item-details">
                                                        <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                                                        <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                                        
                                                        <?php if ($item['dietary_info']): ?>
                                                            <div class="dietary-tags">
                                                                <?php 
                                                                $dietaryItems = explode(',', $item['dietary_info']);
                                                                foreach ($dietaryItems as $dietary): 
                                                                    $dietary = trim($dietary);
                                                                ?>
                                                                    <span class="dietary-tag"><?php echo htmlspecialchars($dietary); ?></span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <div class="item-footer">
                                                            <div class="item-price">
                                                                <span class="currency">RWF</span>
                                                                <span class="price"><?php echo number_format($item['price'], 0); ?></span>
                                                            </div>
                                                            
                                                            <div class="item-meta">
                                                                <span class="prep-time">
                                                                    <i class="fas fa-clock"></i> <?php echo $item['preparation_time']; ?> min
                                                                </span>
                                                            </div>
                                                        </div>
                                                        
                                                        <?php if ($item['is_available']): ?>
                                                            <button class="btn-add-to-order" onclick="addToCart(<?php echo $item['id']; ?>)">
                                                                <i class="fas fa-plus"></i> Add to Order
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="btn-unavailable" disabled>
                                                                <i class="fas fa-times"></i> Not Available
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-menu">
                                <i class="fas fa-utensils"></i>
                                <p>Menu is currently unavailable. Please contact staff.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Order History Section -->
                    <div id="orderHistory"></div>
                </div>
                
                <!-- Order Widget (Sticky) -->
                <div class="order-widget">
                    <div class="widget-header">
                        <h3><i class="fas fa-shopping-cart"></i> Your Order</h3>
                        <span class="item-count" id="cartItemCount">0</span>
                    </div>
                    
                    <div class="order-items" id="orderItemsList">
                        <div class="empty-cart">
                            <i class="fas fa-shopping-cart"></i>
                            <p>Your order is empty</p>
                            <p class="hint">Start adding items from the menu</p>
                        </div>
                    </div>
                    
                    <div class="order-summary" id="orderSummary" style="display: none;">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span id="subtotalAmount">RWF 0</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span id="totalAmount">RWF 0</span>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <textarea id="specialInstructions" placeholder="Special instructions (optional)..." rows="2"></textarea>
                        <button class="btn btn-primary btn-block" id="btnPlaceOrder" onclick="placeOrder()" disabled>
                            <i class="fas fa-check"></i> Place Order
                        </button>
                        <button class="btn btn-secondary btn-block" id="btnClearOrder" onclick="clearCart()" disabled>
                            <i class="fas fa-trash"></i> Clear Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Floating Waiter Call Button -->
    <button class="floating-btn waiter-call-btn" onclick="openWaiterCallModal()" title="Call Waiter">
        <i class="fas fa-bell"></i>
    </button>
    
    <!-- Waiter Call Modal -->
    <div class="modal" id="waiterCallModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-bell"></i> Call Waiter</h3>
                <button class="close-modal" onclick="closeWaiterCallModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="form-group">
                    <label>Request Type:</label>
                    <select id="requestType" class="form-control">
                        <option value="assistance">Need Assistance</option>
                        <option value="order">Ready to Order</option>
                        <option value="bill">Request Bill</option>
                        <option value="complaint">Complaint</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Message (optional):</label>
                    <textarea id="waiterMessage" class="form-control" rows="3" placeholder="Additional details..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Priority:</label>
                    <div class="priority-options">
                        <label class="radio-label">
                            <input type="radio" name="priority" value="normal" checked>
                            <span>Normal</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="priority" value="high">
                            <span>Urgent</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeWaiterCallModal()">Cancel</button>
                <button class="btn btn-primary" onclick="submitWaiterCall()">
                    <i class="fas fa-bell"></i> Call Waiter
                </button>
            </div>
        </div>
    </div>
    
    <!-- Order Confirmation Modal -->
    <div class="modal" id="orderConfirmModal">
        <div class="modal-content">
            <div class="modal-header success">
                <i class="fas fa-check-circle"></i>
                <h3>Order Placed Successfully!</h3>
            </div>
            
            <div class="modal-body text-center">
                <p>Your order number is:</p>
                <h2 class="order-number" id="confirmOrderNumber">-</h2>
                <p>Total Amount: <strong id="confirmOrderTotal">RWF 0</strong></p>
                <p class="info-text">Your order has been sent to the kitchen. You'll be notified when it's ready.</p>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-primary btn-block" onclick="closeOrderConfirmModal()">
                    <i class="fas fa-check"></i> OK
                </button>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <p>Processing...</p>
    </div>
    
    <!-- Hidden data for JS -->
    <script>
        const TABLE_DATA = <?php echo json_encode($table ?? null); ?>;
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
</body>
</html>
