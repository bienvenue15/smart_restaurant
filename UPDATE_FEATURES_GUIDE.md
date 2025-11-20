# Update Features Guide

## Overview
This guide explains how to update table status and menu items without deleting them in the Smart Restaurant Management System.

---

## Table Management

### Update Table Status and Capacity

#### Features:
- **Update Capacity**: Change the number of seats for a table
- **Update Status**: Change table status between:
  - `available` - Table is free and ready for customers
  - `occupied` - Table is currently in use
  - `reserved` - Table is reserved for future use

#### How to Update a Table:

1. **Navigate to Tables Management**
   - Go to Staff Portal → Dashboard
   - Click on "Tables" navigation button

2. **Click Edit Button**
   - Each table card shows an "Edit" button (blue button with pencil icon)
   - Click the Edit button on the table you want to update

3. **Update Table Information**
   - **Table Number**: Read-only (cannot be changed)
   - **Capacity**: Enter new capacity (minimum 1)
   - **Status**: Select from dropdown:
     - Available
     - Occupied
     - Reserved

4. **Save Changes**
   - Click "Update" button to save
   - Click "Cancel" to discard changes

#### Permissions Required:
- `manage_tables` permission
- Only users with this permission can update tables

#### Use Cases:
- **Update Capacity**: When table configuration changes (add/remove chairs)
- **Change to Reserved**: When customer books a table in advance
- **Change to Available**: Manually free up a table
- **Change to Occupied**: Manually mark table as in use

---

## Menu Item Management

### Update Menu Items

#### Features:
- **Update Item Name**: Change the name of the dish
- **Update Category**: Move item to different category
- **Update Price**: Change the price
- **Update Description**: Modify item description
- **Update Availability**: Toggle between Available/Unavailable without deleting

#### How to Update a Menu Item:

1. **Navigate to Menu Management**
   - Go to Staff Portal → Dashboard
   - Click on "Menu" navigation button

2. **Click Edit Button**
   - Each menu item card shows an "Edit" button (blue button with pencil icon)
   - Click the Edit button on the item you want to update

3. **Update Item Information**
   - **Item Name**: Change the name of the dish
   - **Category**: Select new category from dropdown
   - **Price (RWF)**: Enter new price
   - **Availability**: Choose Available or Unavailable
   - **Description**: Update item description

4. **Save Changes**
   - Click "Update" button to save
   - Click "Cancel" to discard changes

#### Permissions Required:
- `manage_menu` permission
- Only users with this permission can update menu items

#### Use Cases:
- **Temporarily Unavailable**: Mark item as unavailable when ingredients run out (instead of deleting)
- **Seasonal Items**: Mark as unavailable during off-season
- **Price Changes**: Update prices during promotions or due to cost changes
- **Menu Reorganization**: Move items between categories
- **Description Updates**: Add allergen info or update descriptions

---

## Benefits of Update vs Delete

### Why Update Instead of Delete?

1. **Preserve Order History**
   - Deleted items break historical order records
   - Updated items maintain data integrity

2. **Temporary Changes**
   - Mark items unavailable instead of deleting
   - Easy to make available again

3. **Data Integrity**
   - Keep audit trail intact
   - Maintain referential integrity in database

4. **Quick Adjustments**
   - Change prices during happy hour
   - Adjust table capacity for special events
   - Mark items temporarily out of stock

---

## API Endpoints

### Table Update Endpoint
```
POST /?req=api&action=staff_update_table

Parameters:
- table_id: int (required)
- capacity: int (required, minimum 1)
- status: string (required, one of: available, occupied, reserved)

Response:
{
    "status": "OK",
    "message": "Table updated successfully"
}
```

### Menu Item Update Endpoint
```
POST /?req=api&action=staff_update_menu_item

Parameters:
- item_id: int (required)
- name: string (required)
- category_id: int (required)
- price: float (required, must be > 0)
- description: string (optional)
- available: int (required, 0 or 1)

Response:
{
    "status": "OK",
    "message": "Menu item updated successfully"
}
```

---

## Audit Trail

All updates are logged in the audit trail with:
- **Action**: update_table or update_menu_item
- **User**: Who made the change
- **Timestamp**: When the change was made
- **Old Values**: Previous data (JSON format)
- **New Values**: Updated data (JSON format)
- **Details**: Human-readable description of changes

### Example Audit Log Entry:
```
Action: update_menu_item
Table: menu_items
Record ID: 15
User: admin@restaurant.com
Timestamp: 2025-11-10 14:30:22
Details: Updated menu item 'Grilled Chicken': price 5000 → 4500, availability available → unavailable
```

---

## Complete CRUD Operations Summary

### Tables
- ✅ **Create**: Add new table with number, capacity, and status
- ✅ **Read**: View all tables with current status and orders
- ✅ **Update**: Change capacity and status (NEW FEATURE)
- ✅ **Delete**: Remove tables (only if no active orders)

### Menu Items
- ✅ **Create**: Add new items with name, category, price, description
- ✅ **Read**: View all menu items grouped by category
- ✅ **Update**: Modify all item properties including availability (NEW FEATURE)
- ✅ **Delete**: Remove items permanently

---

## Troubleshooting

### Cannot Update Table
- **Error**: "Permission denied"
  - Solution: Ensure you have `manage_tables` permission
- **Error**: "Invalid table data"
  - Solution: Check that capacity is at least 1

### Cannot Update Menu Item
- **Error**: "Permission denied"
  - Solution: Ensure you have `manage_menu` permission
- **Error**: "Invalid menu item data"
  - Solution: Verify all required fields are filled (name, category, price > 0)
- **Error**: "Menu item not found"
  - Solution: Item may have been deleted, refresh the page

---

## Best Practices

1. **Use Availability Toggle**
   - Mark items unavailable instead of deleting when temporarily out of stock
   - This preserves order history and menu structure

2. **Update Table Status Appropriately**
   - Use "Reserved" for advance bookings
   - Use "Occupied" for current customers
   - Use "Available" when table is clean and ready

3. **Price Changes**
   - Update prices during off-peak hours
   - Document price changes in system for reference

4. **Regular Review**
   - Periodically review unavailable items
   - Remove items that are permanently discontinued

5. **Audit Trail**
   - Check audit logs to track who made changes
   - Review changes if discrepancies occur

---

## Technical Implementation

### Frontend (JavaScript)
- Location: `assets/js/staff-dashboard.js`
- Functions:
  - `showUpdateTableForm()` - Display table update form
  - `updateTable()` - Submit table updates
  - `showUpdateMenuForm()` - Display menu item update form
  - `updateMenuItem()` - Submit menu item updates

### Backend (PHP)
- Location: `app/controllers/api.php`
- Methods:
  - `staffUpdateTable()` - Process table updates
  - `staffUpdateMenuItem()` - Process menu item updates

### Database
- Tables: `restaurant_tables`, `menu_items`
- Columns Updated:
  - Tables: `capacity`, `status`
  - Menu Items: `name`, `category_id`, `price`, `description`, `available`

---

## Support

For additional support or feature requests, contact your system administrator.
