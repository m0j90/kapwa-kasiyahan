# Kapwa-Kabataan Kasiyahan — Online Store & Order Management System

A full-stack web-based e-commerce storefront and administrative management platform built for **Kapwa-Kabataan Kasiyahan** as an NSTP school project. The application provides customers with a responsive shopping, checkout, order tracking, and return/refund experience, alongside a dedicated role-based Admin Panel for managing customers, products, orders, inquiries, and sub-administrators.

🌐 **Live Demo:** [kapwa-kasiyahan.infinityfree.io](http://kapwa-kasiyahan.infinityfree.io) *(Hosted via InfinityFree)*

## 📋 Table of Contents

* [Features](#-features)
* [Tech Stack](#-tech-stack)
* [Project Structure](#-project-structure)
* [Installation & Setup](#-installation--setup)
* [Design, Development & AI Acknowledgments](#-design-development--ai-acknowledgments)
* [Academic Purpose Disclaimer](#-academic-purpose-disclaimer)

## ✨ Features

### 🛍️ Customer Storefront

* **Product Browsing & Cart Management:** Interactive product catalog (`products.php`), product details (`product-details.php`), and shopping cart (`shopping-checkout/shopping-cart.php`) with quantity steppers and live subtotal calculations.
* **Multi-Step Checkout Flow:** Guided checkout experience (`checkout.php` → `payment.php` → `order-success.php`) supporting **Cash on Delivery (COD)** and **Online Payment** options with transaction reference tracking.
* **Order History & Live Order Tracking:** Visual 5-stage progress stepper (*Pending → Processing → Shipped → Delivery → Completed*), order cancellation for pending orders, and customer delivery confirmation (*Order Received*).
* **Return / Refund Request & Progress Tracking:** Customers can submit return/refund requests (`return-refund.php`) with reasons, detailed descriptions, and amounts on delivered/completed orders, and monitor real-time approval or refund progress (`request-progress.php`).
* **Account & Profile Management:** Registration (`signup.php`), login (`login.php`), personal info (`account-info.php`), profile editing (`edit-profile.php`), account settings (`account-settings.php`), and customer inquiry messaging (`contact.php` & `messages.php`).

### 🛠️ Administrator & Sub-Administrator Panel

* **Dashboard Overview:** Real-time metrics for Total Customers, Total Products, Total Orders, Pending Orders, Unread Messages, and quick management shortcuts (`admin/dashboard.php`).
* **Order & Return/Refund Management:** Update order fulfillment statuses (`update-status.php`), cancel orders, and review/update customer return & refund requests (*Request Submitted → Under Review → Approved / Rejected → Refunded*) in `admin/order/order-details.php`.
* **Product & Inventory Management:** Add, edit, view, and delete products (`admin/product/`) with stock status indicators (*In Stock*, *Low Stock*, *Out of Stock*) and image uploads.
* **Customer & Sub-Admin Management:** Add, edit, view, and list customer accounts (`admin/customer/`) and manage role-based Sub-Administrator access (`admin/sub-admin/`).
* **Messages Inbox:** View and respond to customer inquiries (`admin/messages/inbox.php` & `conversation.php`).
* **Responsive Mobile & Desktop Views:** Mobile-friendly collapsible navigation, responsive tables/cards, and clean layout spacing across viewports.

## 🧰 Tech Stack

* **Frontend:** HTML5, CSS3 (`assets/style.css`), Bootstrap 5.3.3, Bootstrap Icons 1.11.3, Google Fonts (*Poppins* & *Inter*)
* **Backend:** PHP (Procedural with `mysqli` prepared statements, session-based authentication, `Asia/Manila` timezone)
* **Database:** MySQL / MariaDB (`database/` SQL schema)
* **Hosting:** InfinityFree Web Hosting

## 📂 Project Structure

```text
nstp-ecommerce/
├── admin/                        # Admin & Sub-Admin Dashboard & Management Modules
│   ├── account-settings/         # Admin profile & account settings
│   │   ├── account-settings.php
│   │   ├── edit-profile.php
│   │   └── personal-information.php
│   ├── customer/                 # Customer management CRUD
│   │   ├── customer-add.php
│   │   ├── customer-edit.php
│   │   ├── customer-list.php
│   │   └── customer-view.php
│   ├── messages/                 # Customer inquiry inbox & threads
│   │   ├── conversation.php
│   │   └── inbox.php
│   ├── order/                    # Order list, order details & status updates
│   │   ├── order-details.php
│   │   ├── order-list.php
│   │   └── update-status.php
│   ├── product/                  # Product CRUD & stock management
│   │   ├── product-add.php
│   │   ├── product-edit.php
│   │   ├── product-list.php
│   │   └── product-view.php
│   ├── sub-admin/                # Sub-admin management (Admin-only)
│   ├── dashboard.php             # Admin overview dashboard
│   ├── index.php                 # Admin login
│   └── logout.php                # Admin logout
├── assets/                       # Custom stylesheets & brand assets
│   ├── images/                   # Brand logos, favicon & product mockups
│   └── style.css                 # Custom theme, status pills, steppers & responsive rules
├── customer-account/             # Customer Account & Order Portal
│   ├── account-info.php          # Personal information
│   ├── account-settings.php      # Account settings hub
│   ├── edit-profile.php          # Edit customer profile
│   ├── order-details.php         # Detailed receipt & return/refund actions
│   ├── order-history.php         # Filterable order history & request progress links
│   ├── request-progress.php      # Return/refund status timeline
│   ├── return-refund.php         # Return/refund request submission form
│   └── track-order.php           # Live order tracking stepper
├── database/                     # MySQL database schema & SQL dump files
├── includes/                     # Reusable UI components
│   ├── admin-header.php          # Responsive fixed admin top navigation
│   ├── admin-sidebar.php         # Desktop admin sidebar navigation
│   ├── footer.php                # Storefront footer
│   └── navbar.php                # Storefront navigation bar
├── shopping-checkout/            # Cart & Multi-Step Checkout Flow
│   ├── checkout.php              # Shipping & order summary
│   ├── order-success.php         # Order confirmation screen
│   ├── payment.php               # Payment method selection & processing
│   └── shopping-cart.php         # Shopping cart management
├── uploads/                      # Uploaded product images
├── about.php                     # About Kapwa-Kabataan Kasiyahan
├── contact.php                   # Contact & inquiry form
├── db_connect.php                # MySQL database connection configuration
├── index.php                     # Storefront landing page
├── login.php                     # Customer login
├── logout.php                    # Customer logout
├── messages.php                  # Customer message center
├── product-details.php           # Single product details page
├── products.php                  # Product catalog page
├── signup.php                    # Customer registration
└── README.md                     # Project documentation
```

## ⚙️ Installation & Setup

1. **Clone the Repository:**

   ```bash
   git clone https://github.com/your-username/nstp-ecommerce.git
   ```

2. **Database Configuration:**

   * Create a MySQL database via phpMyAdmin (XAMPP/Laragon locally, or VistaPanel on InfinityFree).
   * Import the SQL file located inside the `database/` folder into your database.
   * Update `db_connect.php` with your database host, username, password, and database name:

     ```php
     $host = "localhost";
     $user = "root";
     $pass = "";
     $dbname = "your_database_name";
     ```

3. **Run Locally or Deploy:**

   * **Local:** Place the `nstp-ecommerce` folder inside `htdocs/` (XAMPP) or `www/` (WAMP/Laragon) and visit `http://localhost/nstp-ecommerce/`.
   * **InfinityFree:** Upload all files inside `nstp-ecommerce/` into your subdomain's `htdocs/` directory via FTP or File Manager and ensure `uploads/` has write permissions.

## 🎨 Design, Development & AI Acknowledgments

The following tools and AI assistants were utilized throughout the planning, UI/UX design, branding, and development of this project:

* **Gemini AI:** Used for UI/UX design assistance, interactive functionality, debugging and fixing errors, responsive layout adjustments (mobile and desktop views), code suggestions, and generating product mockup images.
* **Figma & Claude AI:** Used for creating low-fidelity wireframe design layouts for both desktop and mobile screen views.
* **ChatGPT:** Used to establish the brand's color palette (forest green `#0a6e06`, dark evergreen `#011f01`, soft sage `#eef1e9`, and neutral grays) and typography system (*Poppins* for headings and *Inter* for body text).
* **VistaPrint AI Logomaker:** Used to design the official **Kapwa-Kabataan Kasiyahan** combination mark logo and transparent symbol favicon.

## 📚 Academic Purpose Disclaimer

This project was developed strictly for **NSTP school and educational purposes** to demonstrate full-stack web development, relational database design, responsive UI/UX implementation, and e-commerce workflow management.
