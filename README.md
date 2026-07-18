# CRM System — Customer Relationship Management (PHP + MySQL)

A complete, working CRM web application built with **PHP (procedural, mysqli)**,
**MySQL**, and plain **HTML/CSS/JS** (no framework required — runs on any
standard PHP + MySQL stack such as **XAMPP / WAMP / MAMP**).

---

## 1. Features Included

| # | Module | Files |
|---|--------|-------|
| 1 | Login Page | `login.php`, `logout.php` |
| 2 | Dashboard (stats + recent activity) | `dashboard.php` |
| 3 | Customer Management (Add/Edit/Delete/Search/List) | `customers.php` |
| 4 | Employee Management (Register/List/Update/Delete) | `employees.php` |
| 5 | Product & Service Management | `products.php` |
| 6 | Order Management (Place/Update Status/History/Invoice) | `orders.php` |
| 7 | Reports (Monthly Sales, Customer Growth, Product Sales, Revenue) | `reports.php` |
| 8 | Global Search (Customer/Order/Product) | `search.php` |
| 9 | Profile Page (view profile, change password, logout) | `profile.php` |
| 10 | Database schema + sample data | `database.sql` |

---

## 2. Requirements

- PHP 7.4+ (works with PHP 8.x too)
- MySQL 5.7+ / MariaDB
- Any local server stack: **XAMPP**, **WAMP**, **MAMP**, or `php -S` + MySQL

---

## 3. Setup Instructions

### Step 1 — Copy project files
Copy the whole `crm` folder into your server's web root:
- XAMPP: `C:\xampp\htdocs\crm`
- WAMP: `C:\wamp64\www\crm`
- Linux/Mac: `/var/www/html/crm`

### Step 2 — Create the database
1. Open **phpMyAdmin** (or MySQL Workbench / CLI).
2. Create a database (or let the script do it — `database.sql` includes
   `CREATE DATABASE IF NOT EXISTS crm_db;`).
3. Import `database.sql`:
   - phpMyAdmin → select `crm_db` → **Import** tab → choose `database.sql` → Go.
   - CLI: `mysql -u root -p < database.sql`

### Step 3 — Configure DB connection
Open `config.php` and update credentials if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // your MySQL password
define('DB_NAME', 'crm_db');
```

### Step 4 — Fix the default admin password hash (one-time)
Because bcrypt hashes are unique per generation, visit this URL **once** in
your browser after importing the database:
```
http://localhost/crm/reset_password.php
```
This sets the admin password correctly. Afterwards, **delete `reset_password.php`**.

### Step 5 — Login
Go to:
```
http://localhost/crm/login.php
```
Default credentials:
- **Username:** `admin`
- **Password:** `admin123`

---

## 4. Database Tables (see `database.sql`)

- `admin_users` — login credentials for admin/profile
- `customers` — customer records (Customer ID, Name, Email, Phone, Status)
- `employees` — employee records (Employee ID, Name, Designation, Salary, etc.)
- `products` — product/service catalog (Price, Stock)
- `orders` — order header (linked to customer, status, total)
- `order_items` — line items per order (linked to product) — powers invoices
- `activity_log` — feeds the "Recent Activities" widget on the dashboard

### Section 10 — Database Output (Screenshots)
This deliverable asks for **screenshots of your database tables** from
phpMyAdmin/MySQL Workbench. Since this is a text-based handoff, screenshots
can't be generated here — after importing `database.sql`, open phpMyAdmin,
click into each table (`customers`, `employees`, `products`, `orders`), and
capture a screenshot of the **Browse** tab for each to include in your report.

---

## 5. Notes on Design Choices

- **Security:** Passwords are hashed with `password_hash()`/`password_verify()`
  (bcrypt). All SQL queries use **prepared statements** to prevent SQL injection.
  All output is escaped with `htmlspecialchars()` via the `h()` helper to
  prevent XSS.
- **IDs/Codes:** Customer/Employee/Product/Order codes (`C001`, `E001`, `P001`,
  `ORD001`) auto-increment based on the last record.
- **Stock handling:** Placing an order automatically deducts stock from the
  `products` table and logs the transaction in `activity_log`.
- **Invoices:** Click "Invoice" next to any order in Order History to view a
  printable invoice (`window.print()` button included).
- **No external frameworks required** — Bootstrap/jQuery are intentionally
  omitted so the whole thing runs offline with zero dependencies beyond PHP + MySQL.

---

## 6. Folder Structure

```
crm/
├── config.php
├── login.php
├── logout.php
├── reset_password.php
├── dashboard.php
├── customers.php
├── employees.php
├── products.php
├── orders.php
├── reports.php
├── search.php
├── profile.php
├── database.sql
├── includes/
│   ├── sidebar.php
│   └── topbar.php
└── assets/
    └── css/
        └── style.css
```

## 7. Extending This Project
- Add role-based access (multiple admins/employees with different permissions)
- Add pagination to large tables (customers/orders/products)
- Add PDF export for invoices (e.g. via `dompdf`)
- Add charts to Reports page (e.g. Chart.js) for visual sales trends
