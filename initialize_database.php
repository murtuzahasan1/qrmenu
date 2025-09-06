<?php
/**
 * Luna dine Database Initializer
 * Creates and populates the SQLite database with sample data
 */

class DatabaseInitializer {
    private $db;

    public function __construct() {
        $this->db = new PDO('sqlite:lunadine.db');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('PRAGMA foreign_keys = ON');
    }

    public function initialize() {
        echo "Initializing Luna dine database...\n";
        
        // Create schema
        $this->createSchema();
        
        // Insert sample data
        $this->insertSampleData();
        
        echo "Database initialization completed successfully!\n";
    }

    private function createSchema() {
        echo "Creating database schema...\n";
        
        $schema = file_get_contents('database_schema.sql');
        $this->db->exec($schema);
        
        echo "Schema created successfully.\n";
    }

    private function insertSampleData() {
        echo "Inserting sample data...\n";

        // Insert branches
        $branches = [
            [
                'name' => 'Luna dine - Dhanmondi',
                'address' => 'House 12, Road 8, Dhanmondi, Dhaka',
                'status' => 'open',
                'phone' => '+8801234567890',
                'settings' => json_encode(['currency' => '৳', 'vat_percentage' => 15, 'currency_symbol' => '৳'])
            ],
            [
                'name' => 'Luna dine - Gulshan',
                'address' => 'Plot 45, Avenue 2, Gulshan, Dhaka',
                'status' => 'open',
                'phone' => '+8801234567891',
                'settings' => json_encode(['currency' => '৳', 'vat_percentage' => 12, 'currency_symbol' => '৳'])
            ],
            [
                'name' => 'Luna dine - Banani',
                'address' => 'Road 11, Block C, Banani, Dhaka',
                'status' => 'closed',
                'phone' => '+8801234567892',
                'settings' => json_encode(['currency' => '৳', 'vat_percentage' => 15, 'currency_symbol' => '৳'])
            ]
        ];

        foreach ($branches as $branch) {
            $stmt = $this->db->prepare("INSERT INTO branches (name, address, status, phone, settings) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$branch['name'], $branch['address'], $branch['status'], $branch['phone'], $branch['settings']]);
        }

        // Insert master menu items
        $masterItems = [
            ['name' => 'Spring Rolls', 'description' => 'Crispy vegetable spring rolls with sweet chili sauce', 'image_url' => 'https://picsum.photos/seed/spring-rolls/300/200.jpg', 'tags' => json_encode(['vegetarian', 'popular', 'appetizer'])],
            ['name' => 'Chicken Biryani', 'description' => 'Aromatic basmati rice with tender chicken and exotic spices', 'image_url' => 'https://picsum.photos/seed/biryani/300/200.jpg', 'tags' => json_encode(['popular', 'chef-special', 'main-course'])],
            ['name' => 'Beef Kacchi', 'description' => 'Traditional beef kacchi biryani with premium basmati rice', 'image_url' => 'https://picsum.photos/seed/kacchi/300/200.jpg', 'tags' => json_encode(['premium', 'traditional', 'main-course'])],
            ['name' => 'Chocolate Cake', 'description' => 'Rich chocolate cake with chocolate ganache', 'image_url' => 'https://picsum.photos/seed/chocolate-cake/300/200.jpg', 'tags' => json_encode(['vegetarian', 'sweet', 'dessert'])],
            ['name' => 'Fresh Lemonade', 'description' => 'Freshly squeezed lemon juice with mint', 'image_url' => 'https://picsum.photos/seed/lemonade/300/200.jpg', 'tags' => json_encode(['refreshing', 'vegetarian', 'beverage'])],
            ['name' => 'Chicken Soup', 'description' => 'Hearty chicken soup with vegetables and herbs', 'image_url' => 'https://picsum.photos/seed/soup/300/200.jpg', 'tags' => json_encode(['soup', 'comfort-food', 'appetizer'])],
            ['name' => 'Grilled Fish', 'description' => 'Fresh grilled fish with herbs and lemon', 'image_url' => 'https://picsum.photos/seed/fish/300/200.jpg', 'tags' => json_encode(['healthy', 'grilled', 'main-course'])],
            ['name' => 'Mango Lassi', 'description' => 'Traditional mango yogurt drink', 'image_url' => 'https://picsum.photos/seed/lassi/300/200.jpg', 'tags' => json_encode(['traditional', 'refreshing', 'beverage'])],
            ['name' => 'Vegetable Fried Rice', 'description' => 'Stir-fried rice with fresh vegetables', 'image_url' => 'https://picsum.photos/seed/fried-rice/300/200.jpg', 'tags' => json_encode(['vegetarian', 'rice', 'main-course'])],
            ['name' => 'Ice Cream', 'description' => 'Vanilla ice cream with chocolate sauce', 'image_url' => 'https://picsum.photos/seed/ice-cream/300/200.jpg', 'tags' => json_encode(['dessert', 'sweet', 'cold'])]
        ];

        foreach ($masterItems as $item) {
            $stmt = $this->db->prepare("INSERT INTO master_menu_items (name, description, image_url, tags) VALUES (?, ?, ?, ?)");
            $stmt->execute([$item['name'], $item['description'], $item['image_url'], $item['tags']]);
        }

        // Insert menu categories for each branch
        $categories = [
            ['branch_id' => 1, 'name' => 'Appetizers', 'display_order' => 1],
            ['branch_id' => 1, 'name' => 'Main Course', 'display_order' => 2],
            ['branch_id' => 1, 'name' => 'Desserts', 'display_order' => 3],
            ['branch_id' => 1, 'name' => 'Beverages', 'display_order' => 4],
            ['branch_id' => 2, 'name' => 'Starters', 'display_order' => 1],
            ['branch_id' => 2, 'name' => 'Main Dishes', 'display_order' => 2],
            ['branch_id' => 2, 'name' => 'Sweets', 'display_order' => 3],
            ['branch_id' => 2, 'name' => 'Drinks', 'display_order' => 4],
            ['branch_id' => 3, 'name' => 'Appetizers', 'display_order' => 1],
            ['branch_id' => 3, 'name' => 'Main Course', 'display_order' => 2],
            ['branch_id' => 3, 'name' => 'Desserts', 'display_order' => 3],
            ['branch_id' => 3, 'name' => 'Beverages', 'display_order' => 4]
        ];

        foreach ($categories as $category) {
            $stmt = $this->db->prepare("INSERT INTO menu_categories (branch_id, name, display_order) VALUES (?, ?, ?)");
            $stmt->execute([$category['branch_id'], $category['name'], $category['display_order']]);
        }

        // Insert branch menu items with different prices and availability
        $branchMenuItems = [
            // Branch 1 (Dhanmondi)
            ['branch_id' => 1, 'master_item_id' => 1, 'category_id' => 1, 'price' => 150, 'is_available' => 1],
            ['branch_id' => 1, 'master_item_id' => 2, 'category_id' => 2, 'price' => 350, 'is_available' => 1],
            ['branch_id' => 1, 'master_item_id' => 3, 'category_id' => 2, 'price' => 450, 'is_available' => 1],
            ['branch_id' => 1, 'master_item_id' => 4, 'category_id' => 3, 'price' => 200, 'is_available' => 1],
            ['branch_id' => 1, 'master_item_id' => 5, 'category_id' => 4, 'price' => 80, 'is_available' => 0], // Sold out
            ['branch_id' => 1, 'master_item_id' => 6, 'category_id' => 1, 'price' => 120, 'is_available' => 1],
            ['branch_id' => 1, 'master_item_id' => 7, 'category_id' => 2, 'price' => 400, 'is_available' => 1],
            ['branch_id' => 1, 'master_item_id' => 8, 'category_id' => 4, 'price' => 100, 'is_available' => 1],
            
            // Branch 2 (Gulshan) - Some items at different prices
            ['branch_id' => 2, 'master_item_id' => 1, 'category_id' => 5, 'price' => 170, 'is_available' => 1],
            ['branch_id' => 2, 'master_item_id' => 2, 'category_id' => 6, 'price' => 380, 'is_available' => 1],
            ['branch_id' => 2, 'master_item_id' => 4, 'category_id' => 7, 'price' => 220, 'is_available' => 1],
            ['branch_id' => 2, 'master_item_id' => 5, 'category_id' => 8, 'price' => 90, 'is_available' => 1],
            ['branch_id' => 2, 'master_item_id' => 9, 'category_id' => 6, 'price' => 250, 'is_available' => 1],
            ['branch_id' => 2, 'master_item_id' => 10, 'category_id' => 7, 'price' => 150, 'is_available' => 1],
            
            // Branch 3 (Banani) - Limited menu since closed
            ['branch_id' => 3, 'master_item_id' => 1, 'category_id' => 9, 'price' => 160, 'is_available' => 0],
            ['branch_id' => 3, 'master_item_id' => 2, 'category_id' => 10, 'price' => 360, 'is_available' => 0],
            ['branch_id' => 3, 'master_item_id' => 4, 'category_id' => 11, 'price' => 210, 'is_available' => 0]
        ];

        foreach ($branchMenuItems as $item) {
            $stmt = $this->db->prepare("INSERT INTO branch_menu_items (branch_id, master_item_id, category_id, price, is_available) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$item['branch_id'], $item['master_item_id'], $item['category_id'], $item['price'], $item['is_available']]);
        }

        // Insert customization groups
        $customizationGroups = [
            ['master_item_id' => 1, 'name' => 'Sauce', 'selection_type' => 'single'],
            ['master_item_id' => 2, 'name' => 'Spice Level', 'selection_type' => 'single'],
            ['master_item_id' => 2, 'name' => 'Extra Toppings', 'selection_type' => 'multiple'],
            ['master_item_id' => 3, 'name' => 'Spice Level', 'selection_type' => 'single'],
            ['master_item_id' => 7, 'name' => 'Cooking Style', 'selection_type' => 'single']
        ];

        foreach ($customizationGroups as $group) {
            $stmt = $this->db->prepare("INSERT INTO customization_groups (master_item_id, name, selection_type) VALUES (?, ?, ?)");
            $stmt->execute([$group['master_item_id'], $group['name'], $group['selection_type']]);
        }

        // Insert customization options
        $customizationOptions = [
            // Spring Rolls sauce options
            ['group_id' => 1, 'name' => 'Sweet Chili', 'additional_price' => 0],
            ['group_id' => 1, 'name' => 'Soy Garlic', 'additional_price' => 10],
            ['group_id' => 1, 'name' => 'Spicy Mayo', 'additional_price' => 10],
            
            // Chicken Biryani spice levels
            ['group_id' => 2, 'name' => 'Mild', 'additional_price' => 0],
            ['group_id' => 2, 'name' => 'Medium', 'additional_price' => 0],
            ['group_id' => 2, 'name' => 'Hot', 'additional_price' => 0],
            
            // Chicken Biryani extra toppings
            ['group_id' => 3, 'name' => 'Extra Chicken', 'additional_price' => 100],
            ['group_id' => 3, 'name' => 'Boiled Egg', 'additional_price' => 30],
            ['group_id' => 3, 'name' => 'Fried Onion', 'additional_price' => 20],
            
            // Beef Kacchi spice levels
            ['group_id' => 4, 'name' => 'Medium', 'additional_price' => 0],
            ['group_id' => 4, 'name' => 'Hot', 'additional_price' => 0],
            ['group_id' => 4, 'name' => 'Extra Hot', 'additional_price' => 0],
            
            // Grilled Fish cooking styles
            ['group_id' => 5, 'name' => 'Grilled', 'additional_price' => 0],
            ['group_id' => 5, 'name' => 'Pan-seared', 'additional_price' => 20],
            ['group_id' => 5, 'name' => 'Herb-crusted', 'additional_price' => 40]
        ];

        foreach ($customizationOptions as $option) {
            $stmt = $this->db->prepare("INSERT INTO customization_options (group_id, name, additional_price) VALUES (?, ?, ?)");
            $stmt->execute([$option['group_id'], $option['name'], $option['additional_price']]);
        }

        // Insert restaurant tables
        $tables = [
            // Branch 1 tables
            ['branch_id' => 1, 'table_identifier' => 'T1', 'capacity' => 4],
            ['branch_id' => 1, 'table_identifier' => 'T2', 'capacity' => 4],
            ['branch_id' => 1, 'table_identifier' => 'T3', 'capacity' => 2],
            ['branch_id' => 1, 'table_identifier' => 'T4', 'capacity' => 6],
            ['branch_id' => 1, 'table_identifier' => 'T5', 'capacity' => 6],
            ['branch_id' => 1, 'table_identifier' => 'T6', 'capacity' => 8],
            ['branch_id' => 1, 'table_identifier' => 'T7', 'capacity' => 2],
            ['branch_id' => 1, 'table_identifier' => 'T8', 'capacity' => 4],
            
            // Branch 2 tables
            ['branch_id' => 2, 'table_identifier' => 'G1', 'capacity' => 4],
            ['branch_id' => 2, 'table_identifier' => 'G2', 'capacity' => 4],
            ['branch_id' => 2, 'table_identifier' => 'G3', 'capacity' => 6],
            ['branch_id' => 2, 'table_identifier' => 'G4', 'capacity' => 8],
            ['branch_id' => 2, 'table_identifier' => 'G5', 'capacity' => 2],
            ['branch_id' => 2, 'table_identifier' => 'G6', 'capacity' => 4],
            
            // Branch 3 tables
            ['branch_id' => 3, 'table_identifier' => 'B1', 'capacity' => 4],
            ['branch_id' => 3, 'table_identifier' => 'B2', 'capacity' => 6],
            ['branch_id' => 3, 'table_identifier' => 'B3', 'capacity' => 4]
        ];

        foreach ($tables as $table) {
            $stmt = $this->db->prepare("INSERT INTO restaurant_tables (branch_id, table_identifier, capacity) VALUES (?, ?, ?)");
            $stmt->execute([$table['branch_id'], $table['table_identifier'], $table['capacity']]);
        }

        // Insert promo codes
        $promoCodes = [
            ['code' => 'LUNA10', 'type' => 'percentage', 'value' => 10, 'is_active' => 1, 'min_order_amount' => 200],
            ['code' => 'SAVE20', 'type' => 'fixed', 'value' => 20, 'is_active' => 1, 'min_order_amount' => 300],
            ['code' => 'WELCOME15', 'type' => 'percentage', 'value' => 15, 'is_active' => 1, 'min_order_amount' => 150],
            ['code' => 'EXPIRED', 'type' => 'percentage', 'value' => 5, 'is_active' => 0, 'min_order_amount' => 100]
        ];

        foreach ($promoCodes as $promo) {
            $stmt = $this->db->prepare("INSERT INTO promo_codes (code, type, value, is_active, min_order_amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$promo['code'], $promo['type'], $promo['value'], $promo['is_active'], $promo['min_order_amount']]);
        }

        // Insert sample orders
        $orders = [
            [
                'order_uid' => 'ORD123456789',
                'branch_id' => 1,
                'table_id' => 1,
                'order_type' => 'dine-in',
                'status' => 'completed',
                'customer_name' => 'John Doe',
                'customer_phone' => '+8801712345678',
                'subtotal' => 500,
                'vat_amount' => 75,
                'discount_amount' => 0,
                'total_amount' => 575,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'completed_at' => date('Y-m-d H:i:s', strtotime('-2 days + 1 hour'))
            ],
            [
                'order_uid' => 'ORD987654321',
                'branch_id' => 2,
                'order_type' => 'takeaway',
                'status' => 'in_kitchen',
                'customer_name' => 'Jane Smith',
                'customer_phone' => '+8801812345678',
                'subtotal' => 350,
                'vat_amount' => 42,
                'discount_amount' => 35,
                'total_amount' => 357,
                'promo_code_id' => 1,
                'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
            ]
        ];

        foreach ($orders as $order) {
            $stmt = $this->db->prepare("INSERT INTO orders (order_uid, branch_id, table_id, order_type, status, customer_name, customer_phone, subtotal, vat_amount, discount_amount, total_amount, promo_code_id, created_at, completed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $order['order_uid'],
                $order['branch_id'],
                $order['table_id'],
                $order['order_type'],
                $order['status'],
                $order['customer_name'],
                $order['customer_phone'],
                $order['subtotal'],
                $order['vat_amount'],
                $order['discount_amount'],
                $order['total_amount'],
                $order['promo_code_id'] ?? null,
                $order['created_at'],
                $order['completed_at'] ?? null
            ]);
        }

        // Insert sample order items
        $orderItems = [
            ['order_id' => 1, 'branch_menu_item_id' => 1, 'quantity' => 2, 'unit_price' => 150, 'customizations' => json_encode([['group' => 'Sauce', 'option' => 'Sweet Chili']])],
            ['order_id' => 1, 'branch_menu_item_id' => 2, 'quantity' => 1, 'unit_price' => 350, 'customizations' => json_encode([['group' => 'Spice Level', 'option' => 'Medium'], ['group' => 'Extra Toppings', 'option' => 'Boiled Egg']])],
            ['order_id' => 2, 'branch_menu_item_id' => 10, 'quantity' => 1, 'unit_price' => 220, 'customizations' => json_encode([])],
            ['order_id' => 2, 'branch_menu_item_id' => 11, 'quantity' => 1, 'unit_price' => 90, 'customizations' => json_encode([])]
        ];

        foreach ($orderItems as $item) {
            $stmt = $this->db->prepare("INSERT INTO order_items (order_id, branch_menu_item_id, quantity, unit_price, customizations) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$item['order_id'], $item['branch_menu_item_id'], $item['quantity'], $item['unit_price'], $item['customizations']]);
        }

        // Insert sample feedback
        $feedback = [
            [
                'order_id' => 1,
                'overall_rating' => 5,
                'food_rating' => 5,
                'service_rating' => 4,
                'item_feedback' => json_encode([
                    ['item_id' => 1, 'rating' => 'thumb_up'],
                    ['item_id' => 2, 'rating' => 'thumb_up']
                ]),
                'comment' => 'Excellent food quality and service!'
            ]
        ];

        foreach ($feedback as $fb) {
            $stmt = $this->db->prepare("INSERT INTO feedback (order_id, overall_rating, food_rating, service_rating, item_feedback, comment) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$fb['order_id'], $fb['overall_rating'], $fb['food_rating'], $fb['service_rating'], $fb['item_feedback'], $fb['comment']]);
        }

        // Insert sample service requests
        $serviceRequests = [
            ['table_id' => 1, 'request_type' => 'water', 'status' => 'fulfilled', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 days + 30 minutes')), 'fulfilled_at' => date('Y-m-d H:i:s', strtotime('-2 days + 35 minutes'))],
            ['table_id' => 2, 'request_type' => 'assistance', 'status' => 'pending', 'created_at' => date('Y-m-d H:i:s', strtotime('-10 minutes'))]
        ];

        foreach ($serviceRequests as $request) {
            $stmt = $this->db->prepare("INSERT INTO service_requests (table_id, request_type, status, created_at, fulfilled_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$request['table_id'], $request['request_type'], $request['status'], $request['created_at'], $request['fulfilled_at'] ?? null]);
        }

        echo "Sample data inserted successfully.\n";
    }
}

// Run the initializer
if (php_sapi_name() === 'cli') {
    $initializer = new DatabaseInitializer();
    $initializer->initialize();
} else {
    echo "This script must be run from the command line.\n";
}
?>