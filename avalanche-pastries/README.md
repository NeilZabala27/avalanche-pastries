# Avalanche Pastries – Order and Inventory Management System

A private web system for a small pastry business, built with PHP, PDO and MariaDB (XAMPP).
It is for the owner only: customers do not log in.

## Modules
| Module | Pages |
|---|---|
| Login | `login.php` (also creates the first admin account), `logout.php` |
| Customers | `customers.php`, `customer_form.php` |
| Products and pre-orders | `products.php`, `product_form.php`, `preorders.php` |
| Orders | `orders.php`, `add_order.php` (also the edit form: `add_order.php?id=5`), `view_order.php` |
| Reports | `reports.php` |
| Inventory | `inventory.php`, `ingredient_form.php`, `wastage.php`, `baking.php`, `delete.php` |

All PHP classes are in `classes/` (one class per file, loaded automatically by `autoload.php`).
Every page uses the same `header.php`, `nav.php`, `footer.php` and `style.css`, so the look and the menu are the same everywhere. The Wastage, Baking records and Add ingredient links only show on the inventory pages.

## How to run
1. Copy this folder to `C:\xampp\htdocs\avalanche-pastries`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. In phpMyAdmin, create an empty database named `avalanche_pastries`, then import `database/avalanche_pastries.sql`.
4. (Optional) run `database/sample_data.sql` once to add test products, a pre-order date and test ingredients.
5. Open `http://localhost/avalanche-pastries/`. The first visit shows the **Create admin account** form on the login page. Make the first account there; you are logged in right away.
6. After that, the same address shows only the normal log-in form (nobody can register again), and then the Orders page.

The database settings (host, name, user, password) are in `config.php`.

## Quick test
1. Add a customer (mobile number like `0912 345 6789`).
2. Add a product (Products page) and a pre-order date (Pre-orders page).
3. Add a pastry order, a pre-order and a customized cake order, then open each from the Orders page.
4. Add an ingredient, record wastage and a baking record, and check the low-stock alert on the Inventory page.
5. Open Reports.

## Notes
- The Orders page can be filtered by pre-order (the dropdown), by customer name, and by month. The Pre-orders page has a "View orders" link for each date.
- The owner types order totals and payment status himself (client request).
- Orders can be viewed, edited and deleted from the Orders page. Deleting an order also deletes its items, payment and cake design.
- The Orders month filter can search by order date (default) or delivery date.
- Money fields step by 1 with the arrows; centavos can still be typed (for example 150.50). Number boxes only accept numbers (no letters), and mobile numbers accept digits only.
- The order form shows the terms and conditions beside the Save button.
- Ingredients can be sorted by name or quantity, ascending or descending. Baking records take any number of ingredient rows (add or remove them like products on an order).
- The pages adjust to phone screens: the menu folds into a Menu button and tables show each row as a small card.
- Payments are entered on the order form (`add_order.php`); there is no separate payment page.
- On the Add / Edit Product form, the quality level is a dropdown (Standard or Premium); the name, flavor, size and price are typed. The quality choices are `QUALITY_LEVELS` at the top of `classes/Product.php`; add a word there to offer a new one.
- If a save fails, the Add Product and Add Ingredient pages show the reason on the page while `APP_DEBUG` is `true` in `config.php` (set it to `false` on a real website). If the reason says `Unknown column`, run `database/fix_old_database.sql` once in phpMyAdmin.
- Expiration warning is `Ingredient::EXPIRY_WARNING_DAYS` (3 days) and still needs the owner's confirmation.

## Putting it on a real website
- Use PHP 8.0 or higher and MariaDB/MySQL.
- Upload all files, create the database, and import `database/avalanche_pastries.sql`.
- In `config.php`, use the database name, user and password from your host. Do not use `root` with an empty password.
- Use HTTPS. Open the site address; `index.php` sends the visitor to the login page (which shows **Create admin account** until the first account exists) or the orders page.
- `classes/` and `database/` have a `.htaccess` file that blocks them from the browser. Keep these files.
