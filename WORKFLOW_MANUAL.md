# SmugFlex POS — Complete User Manual & Workflow Guide

> **Version:** 1.0.0  
> **System:** SmugFlex POS  
> **Company:** SmugFlex Ventures  
> **Currency:** Nigerian Naira (₦)

---

## TABLE OF CONTENTS

1. [Getting Started](#1-getting-started)
2. [Dashboard](#2-dashboard)
3. [Point of Sale (POS)](#3-point-of-sale-pos)
4. [Products](#4-products)
5. [Categories, Brands & Units](#5-categories-brands--units)
6. [Customers](#6-customers)
7. [Suppliers](#7-suppliers)
8. [Purchases](#8-purchases)
9. [Sales](#9-sales)
10. [Returns](#10-returns)
11. [Expenses](#11-expenses)
12. [Inventory](#12-inventory)
13. [Warehouses & Branches](#13-warehouses--branches)
14. [Users & Roles](#14-users--roles)
15. [Reports](#15-reports)
16. [Settings](#16-settings)
17. [Notifications & Activity Log](#17-notifications--activity-log)
18. [Profile & Account](#18-profile--account)
19. [Theme & Responsive Design](#19-theme--responsive-design)
20. [Default Credentials & Security](#20-default-credentials--security)

---

## 1. GETTING STARTED

### 1.1 Accessing the System

Open your browser and navigate to:
```
https://smugflex-pos-mrfb.vercel.app
```

You will be redirected to the login page.

### 1.2 Logging In

1. Enter your **email address** and **password**
2. Click **"Sign In"**
3. On success, you are redirected to the **Dashboard**

**Default Admin Credentials:**
- Email: `admin@smugflex.com`
- Password: `password`

> **Important:** Change the default password immediately after first login.

### 1.3 Logging Out

1. Click your **name/avatar** in the top-right corner
2. Click **"Logout"**
3. You are redirected to the login page

### 1.4 Navigation

The **sidebar** on the left provides access to all modules:

| Section | Modules |
|---------|---------|
| Main | Dashboard, POS Terminal |
| Catalog | Products, Categories, Brands, Units |
| People | Customers, Suppliers |
| Operations | Purchases, Sales, Returns, Expenses |
| Stock | Inventory, Warehouses, Branches |
| Administration | Users, Roles, Reports, Settings |

On **mobile**, tap the **hamburger icon** (☰) in the top-left to open the sidebar. Tap outside to close it.

---

## 2. DASHBOARD

**URL:** `/` (home page)

The dashboard provides a real-time overview of your business.

### 2.1 Stat Cards

Four cards at the top show:

| Card | Shows |
|------|-------|
| Today's Sales | Total revenue and number of sales for today |
| Monthly Sales | Total revenue and number of sales for the current month |
| Monthly Expenses | Total expenses for the current month |
| Monthly Profit | Net profit (sales minus expenses) for the current month |

Below these: **Total Products**, **Total Customers**, **Total Suppliers**, and **Low Stock** count.

### 2.2 Recent Sales Table

Shows the last 10 sales with:
- Invoice number
- Customer name
- Total amount
- Payment status (Paid, Partial, Unpaid)
- Date

Click any row to view the full sale details.

### 2.3 Top Products

Lists the best-selling products by quantity sold.

### 2.4 Refreshing Data

The dashboard refreshes automatically when you navigate to it. To manually refresh, navigate away and back, or use browser refresh.

---

## 3. POINT OF SALE (POS)

**URL:** `/pos`

The POS terminal is where you process live customer transactions.

### 3.1 Layout

The POS screen is split into two sections:

- **Left:** Product grid showing available products with images, names, prices, and stock levels
- **Right:** Cart area with customer selection, item list, totals, and payment buttons

### 3.2 Adding Products to Cart

**Method 1 — Click to Add:**
1. Browse the product grid on the left
2. Click any product to add 1 unit to the cart
3. The product appears in the cart on the right

**Method 2 — Search:**
1. Type in the **search bar** above the product grid
2. Products filter by name or SKU as you type
3. Click a product from the results to add it

### 3.3 Managing the Cart

Once products are in the cart:

- **Change Quantity:** Click the **+** or **−** buttons next to each item
- **Remove Item:** Click the **trash icon** (🗑) next to the item
- **Clear Entire Cart:** Click **"Clear"** button

### 3.4 Selecting a Customer

1. Click the **"Select Customer"** dropdown
2. Type to search by name
3. Select a customer (or leave blank for "Walk-in Customer")

> Selecting a customer enables wallet payments and tracks purchase history.

### 3.5 Applying Discount

Enter a discount amount in the **"Discount"** field. This is subtracted from the subtotal.

### 3.6 Completing a Sale

1. Click **"Pay"** or **"Pay Now"**
2. A payment modal appears showing:
   - Subtotal
   - Discount
   - Tax (configurable from settings)
   - **Total Due**
3. Select a **payment method:**
   - **Cash** — Enter amount tendered, system calculates change
   - **Card** — Record card payment
   - **Transfer** — Record bank transfer
   - **Wallet** — Deducts from customer's wallet balance
4. Click **"Complete Sale"**

On success:
- A receipt is generated
- Stock is decremented
- The sale appears in the Sales list
- Cart is cleared

### 3.7 Holding a Sale

If a customer needs to step away:

1. Click **"Hold"**
2. The cart is saved temporarily
3. To resume: the held sale will be available when you return to POS

> **Note:** Held sales are stored in browser memory and lost on page refresh.

### 3.8 Walk-in Customers

If no customer is selected, the sale is processed as a walk-in (anonymous) transaction.

---

## 4. PRODUCTS

**URL:** `/products`

### 4.1 Viewing Products

The products page shows a table with:
- Product name
- SKU
- Category
- Buying price / Selling price
- Stock quantity
- Status (Active/Inactive)

**Search:** Type in the search bar to filter by name, SKU, or barcode.

**Pagination:** Use the page controls at the bottom to navigate between pages.

### 4.2 Creating a Product

1. Click **"Add Product"**
2. Fill in the form:

| Field | Required | Description |
|-------|----------|-------------|
| Name | Yes | Product name |
| SKU | Yes | Stock Keeping Unit (auto-generated if blank) |
| Barcode | No | Product barcode |
| Category | Yes | Select from dropdown |
| Brand | No | Select from dropdown |
| Unit | Yes | Select from dropdown (e.g., pcs, kg, litre) |
| Buying Price | Yes | Cost price |
| Selling Price | Yes | Retail price |
| Tax Rate | No | Tax percentage (default from settings) |
| Description | No | Product description |
| Warehouse | Yes | Initial stock warehouse |
| Opening Stock | Yes | Initial quantity |

3. Click **"Create Product"**
4. You are redirected to the products list

### 4.3 Editing a Product

1. On the products list, click the **edit icon** (✏️) on the product row
2. Modify any fields
3. Click **"Update Product"**

### 4.4 Deleting a Product

1. On the products list, click the **delete icon** (🗑) on the product row
2. Confirm the deletion in the dialog
3. The product is soft-deleted (removed from list)

### 4.5 Product Stock

Stock is tracked per warehouse in the `product_stocks` table. When a sale is made, stock decreases automatically. When a purchase is received, stock increases.

---

## 5. CATEGORIES, BRANDS & UNITS

### 5.1 Categories

**URL:** `/categories`

Categories organize your products (e.g., Electronics, Clothing, Food).

**Adding a Category:**
1. Click **"Add Category"**
2. Enter **Name** and **Description**
3. Click **"Save"**

**Editing:** Click the edit icon on the row, modify, save.  
**Deleting:** Click the delete icon, confirm.  
**Product Count:** Each category shows how many products belong to it.

### 5.2 Brands

**URL:** `/brands`

Brands represent product manufacturers or suppliers (e.g., Samsung, Nike).

Same CRUD operations as Categories: Add, Edit, Delete.

### 5.3 Units

**URL:** `/units`

Units define how products are measured (e.g., pcs, kg, litre, box, dozen).

Same CRUD operations as Categories: Add, Edit, Delete.

---

## 6. CUSTOMERS

**URL:** `/customers`

### 6.1 Viewing Customers

The customer list shows:
- Name
- Email
- Phone
- Wallet balance
- Status (Active/Inactive)

**Search:** Filter by name, email, or phone.

### 6.2 Creating a Customer

1. Click **"Add Customer"**
2. Fill in:

| Field | Required | Description |
|-------|----------|-------------|
| First Name | Yes | Customer first name |
| Last Name | Yes | Customer last name |
| Email | No | Email address |
| Phone | No | Phone number |
| Address | No | Street address |
| City | No | City |
| State | No | State |

3. Click **"Create Customer"**

### 6.3 Editing a Customer

1. Click the **edit icon** on the customer row
2. Modify fields
3. Click **"Update Customer"**

### 6.4 Deleting a Customer

1. Click the **delete icon**
2. Confirm deletion

### 6.5 Customer Wallet

Each customer has a digital wallet for prepaid transactions.

**Viewing Balance:** The wallet balance is displayed on the customer list and customer detail page.

**Top-up Wallet:**
1. Navigate to the customer detail or use the API
2. Enter the top-up amount
3. Confirm — balance increases

**Deduct from Wallet:**
1. Enter the deduction amount
2. Confirm — balance decreases

**Wallet Payments in POS:**
When a customer with wallet balance makes a sale, they can choose "Wallet" as the payment method. The amount is deducted from their wallet.

---

## 7. SUPPLIERS

**URL:** `/suppliers`

### 7.1 Viewing Suppliers

The supplier list shows:
- Company name
- Contact person
- Email
- Phone
- Status

### 7.2 Creating a Supplier

1. Click **"Add Supplier"**
2. Fill in:

| Field | Required | Description |
|-------|----------|-------------|
| Company Name | Yes | Supplier company name |
| Contact Person | No | Primary contact name |
| Email | No | Email address |
| Phone | No | Phone number |
| Address | No | Street address |
| City | No | City |
| State | No | State |

3. Click **"Create Supplier"**

### 7.3 Editing a Supplier

1. Click the **edit icon** on the supplier row
2. Modify fields
3. Click **"Update Supplier"**

### 7.4 Supplier Payments

Track payments made to suppliers:
- Navigate to supplier detail
- Record payment with amount and method
- Payment history is maintained

---

## 8. PURCHASES

**URL:** `/purchases`

Purchases track inventory bought from suppliers.

### 8.1 Viewing Purchases

The purchase list shows:
- Reference number
- Supplier name
- Total amount
- Payment status (Pending, Partial, Paid)
- Purchase status (Pending, Received, Cancelled)
- Date

Click any row to view the full purchase detail.

### 8.2 Creating a Purchase

1. Click **"Create Purchase"**
2. Fill in:

| Field | Required | Description |
|-------|----------|-------------|
| Supplier | Yes | Select from dropdown |
| Warehouse | Yes | Destination warehouse for stock |
| Branch | Yes | Branch making the purchase |
| Notes | No | Additional notes |

3. Add **line items:**
   - Select a product from dropdown
   - Enter quantity
   - Enter unit cost
   - Line total is calculated automatically

4. Add more items as needed
5. Click **"Create Purchase"**

The purchase is created with status **"Pending"** and payment status **"Pending"**.

### 8.3 Purchase Detail

Click a purchase reference number to view:
- Supplier info
- Warehouse and branch
- List of items with quantities and costs
- Total amount
- Payment history
- Summary (items count, amounts paid, balance due)

### 8.4 Receiving a Purchase

When goods arrive from the supplier:

1. Navigate to the purchase detail page
2. Click **"Receive"** or **"Mark as Received"**
3. The purchase status changes to **"Received"**
4. **Stock is automatically incremented** in the specified warehouse

### 8.5 Recording Purchase Payments

To record a payment to the supplier:

1. Navigate to the purchase detail page
2. Click **"Add Payment"**
3. Enter payment amount and method
4. The payment is recorded and balance is updated

---

## 9. SALES

**URL:** `/sales`

### 9.1 Viewing Sales

The sales list shows:
- Invoice number
- Customer name
- Total amount
- Payment status (Paid, Partial, Unpaid)
- Sale status (Completed, Voided, Returned)
- Date

**Search:** Filter by invoice number or customer name.

### 9.2 Viewing a Sale

1. Click the **invoice number** or view icon on the sale row
2. The sale detail page shows:
   - Invoice number and date
   - Customer info
   - Items list (product, quantity, price, total)
   - Discount and tax amounts
   - Payment method and amount
   - Sale status

### 9.3 Voiding a Sale

1. Navigate to the sale detail page
2. Click **"Void Sale"**
3. Confirm the action
4. The sale status changes to **"Voided"**
5. Stock is restored

> Only completed sales can be voided.

### 9.4 Returning a Sale

To process a return for a completed sale:

1. Navigate to the sale detail page
2. Click **"Return"** or **"Process Return"**
3. Select the items being returned and quantities
4. Confirm the return
5. A return record is created
6. Stock is restored

---

## 10. RETURNS

**URL:** `/returns`

The returns page lists all sale returns across the system.

### 10.1 Viewing Returns

The return list shows:
- Original sale invoice number
- Return date
- Items returned
- Refund amount
- Reason

Click any row to see the full return details.

---

## 11. EXPENSES

**URL:** `/expenses`

### 11.1 Viewing Expenses

The expenses list shows:
- Category
- Amount
- Payment method
- Date
- Description

**Search:** Filter by category or description.

### 11.2 Creating an Expense

1. Click **"Add Expense"**
2. Fill in:

| Field | Required | Description |
|-------|----------|-------------|
| Category | Yes | Select from dropdown (e.g., Rent, Utilities, Salaries) |
| Amount | Yes | Expense amount in ₦ |
| Payment Method | Yes | Cash, Card, Transfer |
| Date | Yes | Date of expense |
| Description | No | Details about the expense |

3. Click **"Create Expense"**

### 11.3 Editing an Expense

1. Click the **edit icon** on the expense row
2. Modify fields
3. Click **"Update Expense"**

### 11.4 Deleting an Expense

1. Click the **delete icon**
2. Confirm deletion

### 11.5 Expense Categories

Manage expense categories under the expense categories section:
- Rent, Utilities, Salaries, Transportation, Marketing, Maintenance, Office Supplies, Other

---

## 12. INVENTORY

**URL:** `/inventory`

### 12.1 Viewing Stock Levels

The inventory page shows:
- Product name
- SKU
- Warehouse
- Current stock quantity
- Category

**Search:** Filter by product name or SKU.

### 12.2 Stock Adjustments

To manually adjust stock (e.g., for damaged or found items):

1. Use the API endpoint: `POST /inventory/adjust`
2. Provide product_id, warehouse_id, adjustment quantity, and reason
3. Stock is updated and a movement record is created

### 12.3 Stock Transfers

To move stock between warehouses:

1. Use the API endpoint: `POST /inventory/transfer`
2. Provide product_id, from_warehouse_id, to_warehouse_id, and quantity
3. Stock is decremented from source and incremented at destination

### 12.4 Low Stock Alerts

Products with stock below the threshold are flagged. View them on the inventory page or via the low-stock API endpoint.

---

## 13. WAREHOUSES & BRANCHES

### 13.1 Warehouses

**URL:** `/warehouses`

Warehouses are physical locations where stock is stored.

**Adding a Warehouse:**
1. Click **"Add Warehouse"**
2. Enter name, address, and branch association
3. Click **"Save"**

**Editing/Deleting:** Use the icons on each row.

### 13.2 Branches

**URL:** `/branches`

Branches are business locations (e.g., Main Store, Downtown Branch).

**Adding a Branch:**
1. Click **"Add Branch"**
2. Enter branch name and address
3. Click **"Save"**

Each branch can have multiple warehouses. Users are assigned to specific branches.

---

## 14. USERS & ROLES

### 14.1 Users

**URL:** `/users`

**Viewing Users:**
The user list shows:
- Name (first + last)
- Email
- Role
- Branch
- Status (Active/Inactive)

**Creating a User:**
1. Click **"Add User"**
2. Fill in:

| Field | Required | Description |
|-------|----------|-------------|
| First Name | Yes | User first name |
| Last Name | Yes | User last name |
| Email | Yes | Login email |
| Password | Yes | Login password |
| Phone | No | Phone number |
| Role | Yes | Select from dropdown |
| Branch | Yes | Assigned branch |

3. Click **"Create User"**

**Editing a User:**
1. Click the **edit icon** on the user row
2. Modify fields (password is optional — leave blank to keep current)
3. Click **"Update User"**

**Toggling User Status:**
1. Click the **status toggle** icon
2. User is activated or deactivated

**Assigning a Role:**
Roles are assigned during creation or updated via edit.

### 14.2 Roles

**URL:** `/roles`

Roles define what users can access in the system.

**Predefined Roles:**
| Role | Access Level |
|------|-------------|
| Super Admin | Full access to everything |
| Admin | Full access except system settings |
| Manager | Products, sales, purchases, reports, customers |
| Cashier | POS terminal, products, customers |
| Sales Rep | POS, products, customers |
| Warehouse Manager | Inventory, products, purchases |
| Accountant | Expenses, reports, sales |
| Auditor | Read-only access to all modules |

**Creating a Role:**
1. Click **"Add Role"**
2. Enter role name and description
3. Click **"Save"**

**Setting Permissions:**
1. Click the **permissions icon** (🔑) on the role row
2. A list of all permissions appears (grouped by module)
3. Check/uncheck permissions as needed
4. Click **"Save Permissions"**

Permissions include: view, create, update, delete for each module (products, sales, customers, etc.)

**Editing/Deleting Roles:** Use the icons on each row.

> **Note:** Built-in roles (Super Admin, Admin, etc.) cannot be deleted.

---

## 15. REPORTS

**URL:** `/reports`

### 15.1 Report Types

| Tab | Description |
|-----|-------------|
| Daily | Sales, expenses, and profit for a specific day |
| Weekly | Aggregated data for a week |
| Monthly | Aggregated data for a month |
| Yearly | Aggregated data for a year |

### 15.2 Generating a Report

1. Select the report type (Daily, Weekly, Monthly, Yearly)
2. Use the **date picker** to select the date or period
3. The report loads automatically showing:
   - Total sales
   - Total expenses
   - Net profit
   - Top selling products
   - Sales breakdown

### 15.3 Available Report Endpoints

| Report | Endpoint | Description |
|--------|----------|-------------|
| Daily | `GET /reports/daily?date=YYYY-MM-DD` | Single day report |
| Weekly | `GET /reports/weekly?date=YYYY-MM-DD` | Week containing the date |
| Monthly | `GET /reports/monthly?date=YYYY-MM-DD` | Month containing the date |
| Yearly | `GET /reports/yearly?date=YYYY` | Year report |
| Sales | `GET /reports/sales` | Sales breakdown |
| Profit | `GET /reports/profit` | Profit analysis |
| Inventory | `GET /reports/inventory` | Stock valuation |
| Expenses | `GET /reports/expenses` | Expense breakdown |

---

## 16. SETTINGS

**URL:** `/settings`

### 16.1 Company Information

View and update your company details:
- Company name
- Address
- City, State, Country
- Phone, Email
- Tax rate (used in POS calculations)
- Currency (default: NGN ₦)

### 16.2 Updating Settings

1. Click **"Edit"** or modify fields directly
2. Click **"Save"**
3. Changes take effect immediately

---

## 17. NOTIFICATIONS & ACTIVITY LOG

### 17.1 Notifications

**URL:** `/notifications`

System notifications alert you to important events:
- Low stock alerts
- New sales
- Purchase receipts

**Marking as Read:**
- Click the **checkmark** on a notification to mark it as read
- Click **"Mark All Read"** to clear all unread notifications

### 17.2 Activity Log

**URL:** `/activity-log`

The activity log is an audit trail showing:
- Who performed an action
- What action was performed
- When it happened

Examples: "John created product 'Widget'", "Jane voided sale #INV-0042"

This is read-only — actions are logged automatically by the system.

---

## 18. PROFILE & ACCOUNT

**URL:** `/profile`

### 18.1 Viewing Profile

Your profile page shows:
- Full name
- Email
- Phone
- Role
- Branch
- Account status

### 18.2 Changing Password

1. Navigate to Profile
2. Click **"Change Password"**
3. Enter your current password
4. Enter the new password
5. Confirm the new password
6. Click **"Update Password"**

---

## 19. THEME & RESPONSIVE DESIGN

### 19.1 Dark/Light Theme

Toggle between dark and light mode:
1. Click the **sun/moon icon** (☀️/🌙) in the top-right corner
2. The theme changes instantly
3. Your preference is saved in browser storage

### 19.2 Mobile Responsive

The entire application is responsive and works on:
- **Desktop** (1200px+) — Full sidebar + content
- **Tablet** (768px–1024px) — Collapsible sidebar
- **Mobile** (≤768px) — Hidden sidebar (hamburger menu), stacked layouts

**Key mobile behaviors:**
- Sidebar becomes a slide-out drawer
- Search bar is hidden in topbar
- Tables scroll horizontally
- Form grids stack vertically
- Modals take full width
- POS layout stacks cart below products

---

## 20. DEFAULT CREDENTIALS & SECURITY

### 20.1 Default Login

| User | Email | Password | Role |
|------|-------|----------|------|
| Admin | admin@smugflex.com | password | Super Admin |
| Cashier | cashier@smugflex.com | password | Cashier |

> **Change these passwords immediately in production.**

### 20.2 Authentication Flow

1. Login with email + password
2. Backend validates and returns a JWT token
3. Token is stored in browser localStorage
4. Every API request includes: `Authorization: Bearer <token>`
5. On token expiry (24 hours), a refresh token is used
6. If refresh fails, you are redirected to login

### 20.3 Security Best Practices

- Change default passwords
- Use HTTPS in production
- Assign appropriate roles (don't give everyone Super Admin)
- Monitor the activity log for suspicious actions
- Regular database backups

---

## APPENDIX A: COMPLETE API REFERENCE

### Base URL
```
Production: https://smug.com.gracelandroyalacademy.com.ng/api/v1
```

### Authentication
```
Header: Authorization: Bearer <token>
```

### Response Format
```json
{
  "success": true,
  "data": { ... },
  "message": "Success",
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  }
}
```

### All Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| **Auth** | | |
| POST | /auth/login | Login (public) |
| POST | /auth/logout | Logout |
| POST | /auth/refresh | Refresh token |
| GET | /auth/me | Current user info |
| POST | /auth/change-password | Change password |
| **Dashboard** | | |
| GET | /dashboard | Dashboard stats |
| GET | /dashboard/sales-chart | Sales chart data |
| GET | /dashboard/profit-chart | Profit chart data |
| **Users** | | |
| GET | /users | List users |
| POST | /users | Create user |
| GET | /users/{id} | Get user |
| PUT | /users/{id} | Update user |
| DELETE | /users/{id} | Delete user |
| PUT | /users/{id}/status | Toggle status |
| PUT | /users/{id}/assign-role | Assign role |
| **Roles** | | |
| GET | /roles | List roles |
| POST | /roles | Create role |
| GET | /roles/{id} | Get role with permissions |
| PUT | /roles/{id} | Update role |
| DELETE | /roles/{id} | Delete role |
| PUT | /roles/{id}/permissions | Set permissions |
| **Permissions** | | |
| GET | /permissions | List all permissions |
| **Products** | | |
| GET | /products | List products (paginated, searchable) |
| POST | /products | Create product |
| GET | /products/barcode/{barcode} | Barcode lookup |
| GET | /products/{id} | Get product |
| PUT | /products/{id} | Update product |
| DELETE | /products/{id} | Delete product |
| GET | /products/{id}/stock-history | Stock movement history |
| **Categories** | | |
| GET | /categories | List categories |
| POST | /categories | Create category |
| GET | /categories/tree | Category tree |
| PUT | /categories/{id} | Update category |
| DELETE | /categories/{id} | Delete category |
| **Brands** | | |
| GET | /brands | List brands |
| POST | /brands | Create brand |
| PUT | /brands/{id} | Update brand |
| DELETE | /brands/{id} | Delete brand |
| **Units** | | |
| GET | /units | List units |
| POST | /units | Create unit |
| PUT | /units/{id} | Update unit |
| DELETE | /units/{id} | Delete unit |
| **Customers** | | |
| GET | /customers | List customers (paginated, searchable) |
| POST | /customers | Create customer |
| GET | /customers/{id} | Get customer |
| PUT | /customers/{id} | Update customer |
| DELETE | /customers/{id} | Delete customer |
| GET | /customers/{id}/wallet | Wallet info |
| POST | /customers/{id}/wallet/topup | Top up wallet |
| POST | /customers/{id}/wallet/deduct | Deduct wallet |
| GET | /customers/{id}/statement | Wallet statement |
| **Suppliers** | | |
| GET | /suppliers | List suppliers |
| POST | /suppliers | Create supplier |
| GET | /suppliers/{id} | Get supplier |
| PUT | /suppliers/{id} | Update supplier |
| DELETE | /suppliers/{id} | Delete supplier |
| POST | /suppliers/{id}/payments | Add payment |
| **Purchases** | | |
| GET | /purchases | List purchases (paginated) |
| POST | /purchases | Create purchase |
| GET | /purchases/{id} | Get purchase detail |
| PUT | /purchases/{id} | Update purchase |
| POST | /purchases/{id}/receive | Receive purchase (stock++) |
| POST | /purchases/{id}/payment | Add payment |
| **Sales** | | |
| GET | /sales | List sales (paginated, filterable) |
| POST | /sales | Create sale |
| GET | /sales/held | Get held sales |
| POST | /sales/hold | Hold a sale |
| GET | /sales/{id} | Get sale detail |
| POST | /sales/{id}/void | Void sale |
| POST | /sales/{id}/resume | Resume held sale |
| POST | /sales/{id}/return | Return sale |
| GET | /sales/{id}/receipt | Get receipt data |
| **Returns** | | |
| GET | /returns | List all returns |
| **Expenses** | | |
| GET | /expenses | List expenses |
| POST | /expenses | Create expense |
| PUT | /expenses/{id} | Update expense |
| DELETE | /expenses/{id} | Delete expense |
| GET | /expense-categories | List expense categories |
| **Inventory** | | |
| GET | /inventory | Stock levels (paginated) |
| POST | /inventory/adjust | Adjust stock |
| POST | /inventory/transfer | Transfer stock |
| GET | /inventory/low-stock | Low stock items |
| **Warehouses** | | |
| GET | /warehouses | List warehouses |
| POST | /warehouses | Create warehouse |
| PUT | /warehouses/{id} | Update warehouse |
| DELETE | /warehouses/{id} | Delete warehouse |
| **Branches** | | |
| GET | /branches | List branches |
| POST | /branches | Create branch |
| PUT | /branches/{id} | Update branch |
| DELETE | /branches/{id} | Delete branch |
| **Reports** | | |
| GET | /reports/daily | Daily report |
| GET | /reports/weekly | Weekly report |
| GET | /reports/monthly | Monthly report |
| GET | /reports/yearly | Yearly report |
| GET | /reports/sales | Sales report |
| GET | /reports/profit | Profit report |
| GET | /reports/inventory | Inventory report |
| GET | /reports/expenses | Expense report |
| **Settings** | | |
| GET | /settings/company | Get company info |
| PUT | /settings/company | Update company info |
| **Notifications** | | |
| GET | /notifications | List notifications |
| PUT | /notifications/read-all | Mark all read |
| PUT | /notifications/{id}/read | Mark one read |
| **Activity** | | |
| GET | /activity-logs | List activity logs |

---

## APPENDIX B: DATABASE SCHEMA

### Tables (35+)

| Table | Purpose |
|-------|---------|
| roles | User roles (Super Admin → Auditor) |
| permissions | Granular permissions per module |
| role_permissions | Role-permission mapping |
| branches | Business locations |
| warehouses | Stock locations |
| users | System users |
| categories | Product categories |
| brands | Product brands |
| units | Measurement units |
| products | Products catalog |
| product_variants | Product variants |
| product_stocks | Stock per warehouse |
| stock_movements | Stock audit trail |
| customers | Customer database |
| customer_wallets | Customer wallets |
| customer_wallet_transactions | Wallet transaction history |
| suppliers | Supplier database |
| purchases | Purchase orders |
| purchase_items | Purchase line items |
| purchase_payments | Purchase payments |
| sales | Sales transactions |
| sale_items | Sale line items |
| sale_payments | Sale payments |
| sale_returns | Returns |
| return_items | Return line items |
| expense_categories | Expense categories |
| expenses | Expense records |
| coupons | Discount coupons |
| gift_cards | Gift cards |
| reward_transactions | Reward points history |
| shifts | Cash register shifts |
| attendance | Employee attendance |
| payroll | Employee payroll |
| notifications | System notifications |
| activity_logs | Audit trail |
| company | Company settings |
| settings | Application settings |
| backups | Backup records |

### Seed Data

- 8 roles with 55+ permissions
- 2 users (Super Admin + Cashier)
- 2 branches, 2 warehouses
- 8 categories, 8 brands, 12 units
- 8 sample products with stock
- 5 customers with funded wallets
- 3 suppliers
- 8 expense categories
- 17 application settings
- Company profile (SmugFlex Ventures, NGN currency)

---

*Document last updated: July 14, 2026*
