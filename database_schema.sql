-- Luna dine Database Schema
-- SQLite database schema for digital menu system

-- Branches table - Stores each restaurant location and its specific settings
CREATE TABLE branches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    address TEXT NOT NULL,
    status TEXT NOT NULL CHECK(status IN ('open', 'closed')),
    phone TEXT NOT NULL,
    settings TEXT DEFAULT '{"currency":"৳","vat_percentage":15,"currency_symbol":"৳"}'
);

-- Master menu items table - Central catalog of all possible food and drink items
CREATE TABLE master_menu_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT,
    image_url TEXT,
    tags TEXT DEFAULT '[]'
);

-- Menu categories table - Branch-specific categories to organize the menu
CREATE TABLE menu_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    display_order INTEGER DEFAULT 0,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
);

-- Branch menu items table - Link between master item and branch with pricing
CREATE TABLE branch_menu_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    master_item_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    price REAL NOT NULL,
    is_available INTEGER NOT NULL DEFAULT 1,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (master_item_id) REFERENCES master_menu_items(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES menu_categories(id) ON DELETE CASCADE
);

-- Customization groups table - Defines types of customization for items
CREATE TABLE customization_groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    master_item_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    selection_type TEXT NOT NULL CHECK(selection_type IN ('single', 'multiple')),
    FOREIGN KEY (master_item_id) REFERENCES master_menu_items(id) ON DELETE CASCADE
);

-- Customization options table - Defines specific choices within groups
CREATE TABLE customization_options (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    additional_price REAL DEFAULT 0.0,
    FOREIGN KEY (group_id) REFERENCES customization_groups(id) ON DELETE CASCADE
);

-- Restaurant tables table - Physical tables for dine-in orders
CREATE TABLE restaurant_tables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    table_identifier TEXT NOT NULL,
    capacity INTEGER NOT NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
);

-- Promo codes table - Stores valid promotional codes
CREATE TABLE promo_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    type TEXT NOT NULL CHECK(type IN ('percentage', 'fixed')),
    value REAL NOT NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    min_order_amount REAL DEFAULT 0.0
);

-- Orders table - Captures core details of customer orders
CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_uid TEXT NOT NULL UNIQUE,
    branch_id INTEGER NOT NULL,
    table_id INTEGER,
    order_type TEXT NOT NULL CHECK(order_type IN ('dine-in', 'takeaway', 'delivery')),
    status TEXT NOT NULL DEFAULT 'placed' CHECK(status IN ('placed', 'in_kitchen', 'ready', 'completed', 'cancelled')),
    customer_name TEXT,
    customer_phone TEXT,
    customer_address TEXT,
    subtotal REAL NOT NULL DEFAULT 0.0,
    vat_amount REAL NOT NULL DEFAULT 0.0,
    discount_amount REAL NOT NULL DEFAULT 0.0,
    total_amount REAL NOT NULL DEFAULT 0.0,
    promo_code_id INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    estimated_completion_time TEXT,
    completed_at TEXT,
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (table_id) REFERENCES restaurant_tables(id),
    FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id)
);

-- Order items table - Records each specific item within an order
CREATE TABLE order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    branch_menu_item_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL,
    unit_price REAL NOT NULL,
    customizations TEXT DEFAULT '[]',
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_menu_item_id) REFERENCES branch_menu_items(id)
);

-- Feedback table - Stores detailed customer feedback
CREATE TABLE feedback (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    overall_rating INTEGER CHECK(overall_rating BETWEEN 1 AND 5),
    food_rating INTEGER CHECK(food_rating BETWEEN 1 AND 5),
    service_rating INTEGER CHECK(service_rating BETWEEN 1 AND 5),
    item_feedback TEXT DEFAULT '[]',
    comment TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Service requests table - Logs dine-in service requests
CREATE TABLE service_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_id INTEGER NOT NULL,
    request_type TEXT NOT NULL CHECK(request_type IN ('assistance', 'water', 'bill')),
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'fulfilled', 'cancelled')),
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    fulfilled_at TEXT,
    FOREIGN KEY (table_id) REFERENCES restaurant_tables(id)
);

-- Indexes for performance optimization
CREATE INDEX idx_menu_categories_branch_id ON menu_categories(branch_id);
CREATE INDEX idx_branch_menu_items_branch_id ON branch_menu_items(branch_id);
CREATE INDEX idx_branch_menu_items_master_item_id ON branch_menu_items(master_item_id);
CREATE INDEX idx_branch_menu_items_category_id ON branch_menu_items(category_id);
CREATE INDEX idx_customization_groups_master_item_id ON customization_groups(master_item_id);
CREATE INDEX idx_customization_options_group_id ON customization_options(group_id);
CREATE INDEX idx_orders_branch_id ON orders(branch_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created_at ON orders(created_at);
CREATE INDEX idx_orders_order_uid ON orders(order_uid);
CREATE INDEX idx_order_items_order_id ON order_items(order_id);
CREATE INDEX idx_restaurant_tables_branch_id ON restaurant_tables(branch_id);
CREATE UNIQUE INDEX idx_table_identifier_unique ON restaurant_tables(branch_id, table_identifier);
CREATE INDEX idx_promo_codes_code ON promo_codes(code);
CREATE INDEX idx_promo_codes_is_active ON promo_codes(is_active);
CREATE INDEX idx_feedback_order_id ON feedback(order_id);
CREATE INDEX idx_service_requests_table_id ON service_requests(table_id);
CREATE INDEX idx_service_requests_status ON service_requests(status);