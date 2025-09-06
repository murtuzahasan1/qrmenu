<?php
/**
 * Luna dine REST API
 * Single-file PHP REST API for digital menu system
 */

class LunaDineAPI {
    private $db;
    private $method;
    private $endpoint;
    private $params;
    private $input;

    public function __construct() {
        // Initialize database connection
        try {
            $this->db = new PDO('sqlite:lunadine.db');
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->exec('PRAGMA foreign_keys = ON');
        } catch (PDOException $e) {
            $this->sendError('Database connection failed: ' . $e->getMessage(), 500);
        }

        // Parse request
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->endpoint = $this->getEndpoint();
        $this->params = $_GET;
        $this->input = json_decode(file_get_contents('php://input'), true) ?: [];

        // Set headers
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        // Handle preflight requests
        if ($this->method === 'OPTIONS') {
            exit(0);
        }
    }

    public function run() {
        try {
            switch ($this->endpoint) {
                case '/api/branches':
                    $this->handleBranches();
                    break;
                case '/api/settings':
                    $this->handleSettings();
                    break;
                case '/api/menu':
                    $this->handleMenu();
                    break;
                case '/api/tables':
                    $this->handleTables();
                    break;
                case '/api/order_status':
                    $this->handleOrderStatus();
                    break;
                case '/api/orders':
                    $this->handleOrders();
                    break;
                case '/api/promocode':
                    $this->handlePromoCode();
                    break;
                case '/api/feedback':
                    $this->handleFeedback();
                    break;
                case '/api/service_request':
                    $this->handleServiceRequest();
                    break;
                default:
                    $this->sendError('Endpoint not found', 404);
            }
        } catch (Exception $e) {
            $this->sendError('Internal server error: ' . $e->getMessage(), 500);
        }
    }

    private function getEndpoint() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return rtrim($uri, '/');
    }

    private function sendResponse($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    private function sendError($message, $status = 400) {
        http_response_code($status);
        echo json_encode(['error' => $message]);
        exit;
    }

    private function validateRequiredParams($required) {
        foreach ($required as $param) {
            if (!isset($this->params[$param])) {
                $this->sendError("Missing required parameter: $param");
            }
        }
    }

    // GET /api/branches
    private function handleBranches() {
        if ($this->method !== 'GET') {
            $this->sendError('Method not allowed', 405);
        }

        $stmt = $this->db->prepare("SELECT id, name, address, status, phone FROM branches ORDER BY id");
        $stmt->execute();
        $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->sendResponse($branches);
    }

    // GET /api/settings?branch_id={id}
    private function handleSettings() {
        if ($this->method !== 'GET') {
            $this->sendError('Method not allowed', 405);
        }

        $this->validateRequiredParams(['branch_id']);
        $branchId = $this->params['branch_id'];

        $stmt = $this->db->prepare("SELECT settings FROM branches WHERE id = ?");
        $stmt->execute([$branchId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $this->sendError('Branch not found', 404);
        }

        $settings = json_decode($result['settings'], true);
        $settings['branch_id'] = (int)$branchId;

        $this->sendResponse($settings);
    }

    // GET /api/menu?branch_id={id}
    private function handleMenu() {
        if ($this->method !== 'GET') {
            $this->sendError('Method not allowed', 405);
        }

        $this->validateRequiredParams(['branch_id']);
        $branchId = $this->params['branch_id'];

        // Get categories
        $stmt = $this->db->prepare("
            SELECT id, name, display_order 
            FROM menu_categories 
            WHERE branch_id = ? 
            ORDER BY display_order
        ");
        $stmt->execute([$branchId]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get menu items with customizations
        $stmt = $this->db->prepare("
            SELECT 
                bmi.id as branch_menu_item_id,
                bmi.price,
                bmi.is_available,
                mmi.id as master_item_id,
                mmi.name,
                mmi.description,
                mmi.image_url,
                mmi.tags,
                mc.id as category_id,
                mc.name as category_name
            FROM branch_menu_items bmi
            JOIN master_menu_items mmi ON bmi.master_item_id = mmi.id
            JOIN menu_categories mc ON bmi.category_id = mc.id
            WHERE bmi.branch_id = ?
            ORDER BY mc.display_order, mmi.name
        ");
        $stmt->execute([$branchId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get customizations for each item
        foreach ($items as &$item) {
            $item['tags'] = json_decode($item['tags'], true) ?: [];
            $item['customizations'] = [];

            $stmt = $this->db->prepare("
                SELECT 
                    cg.id as group_id,
                    cg.name as group_name,
                    cg.selection_type,
                    co.id as option_id,
                    co.name as option_name,
                    co.additional_price
                FROM customization_groups cg
                LEFT JOIN customization_options co ON cg.id = co.group_id
                WHERE cg.master_item_id = ?
                ORDER BY cg.id, co.id
            ");
            $stmt->execute([$item['master_item_id']]);
            $customizationRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Group customizations by group
            $currentGroup = null;
            foreach ($customizationRows as $row) {
                if (!$currentGroup || $currentGroup['id'] != $row['group_id']) {
                    if ($currentGroup) {
                        $item['customizations'][] = $currentGroup;
                    }
                    $currentGroup = [
                        'id' => $row['group_id'],
                        'name' => $row['group_name'],
                        'type' => $row['selection_type'],
                        'options' => []
                    ];
                }
                if ($row['option_id']) {
                    $currentGroup['options'][] = [
                        'id' => $row['option_id'],
                        'name' => $row['option_name'],
                        'price' => (float)$row['additional_price']
                    ];
                }
            }
            if ($currentGroup) {
                $item['customizations'][] = $currentGroup;
            }
        }

        // Group items by category
        $menu = [];
        foreach ($categories as $category) {
            $categoryItems = array_filter($items, function($item) use ($category) {
                return $item['category_id'] == $category['id'];
            });

            $menu[] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'items' => array_values($categoryItems)
            ];
        }

        $this->sendResponse(['categories' => $menu]);
    }

    // GET /api/tables?branch_id={id}
    private function handleTables() {
        if ($this->method !== 'GET') {
            $this->sendError('Method not allowed', 405);
        }

        $this->validateRequiredParams(['branch_id']);
        $branchId = $this->params['branch_id'];

        $stmt = $this->db->prepare("SELECT id, table_identifier, capacity FROM restaurant_tables WHERE branch_id = ? ORDER BY table_identifier");
        $stmt->execute([$branchId]);
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->sendResponse($tables);
    }

    // GET /api/order_status?order_uid={uid}
    private function handleOrderStatus() {
        if ($this->method !== 'GET') {
            $this->sendError('Method not allowed', 405);
        }

        $this->validateRequiredParams(['order_uid']);
        $orderUid = $this->params['order_uid'];

        $stmt = $this->db->prepare("
            SELECT order_uid, status, estimated_completion_time 
            FROM orders 
            WHERE order_uid = ?
        ");
        $stmt->execute([$orderUid]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $this->sendError('Order not found', 404);
        }

        $this->sendResponse([
            'order_id' => $order['order_uid'],
            'status' => $order['status'],
            'estimated_completion_time' => $order['estimated_completion_time']
        ]);
    }

    // POST /api/orders
    private function handleOrders() {
        if ($this->method !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }

        $required = ['branch_id', 'order_type', 'items'];
        foreach ($required as $field) {
            if (!isset($this->input[$field])) {
                $this->sendError("Missing required field: $field");
            }
        }

        // Validate order type
        $validOrderTypes = ['dine-in', 'takeaway', 'delivery'];
        if (!in_array($this->input['order_type'], $validOrderTypes)) {
            $this->sendError('Invalid order type');
        }

        // Generate unique order ID
        $orderUid = 'ORD' . strtoupper(uniqid()) . rand(1000, 9999);

        // Calculate totals
        $subtotal = 0;
        foreach ($this->input['items'] as $item) {
            if (!isset($item['branch_menu_item_id']) || !isset($item['quantity'])) {
                $this->sendError('Invalid item data');
            }
            
            $stmt = $this->db->prepare("SELECT price FROM branch_menu_items WHERE id = ?");
            $stmt->execute([$item['branch_menu_item_id']]);
            $priceData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$priceData) {
                $this->sendError('Invalid menu item');
            }
            
            $subtotal += $priceData['price'] * $item['quantity'];
        }

        // Get branch settings for VAT calculation
        $stmt = $this->db->prepare("SELECT settings FROM branches WHERE id = ?");
        $stmt->execute([$this->input['branch_id']]);
        $branchData = $stmt->fetch(PDO::FETCH_ASSOC);
        $settings = json_decode($branchData['settings'], true);
        $vatPercentage = $settings['vat_percentage'] ?? 15;
        $vatAmount = ($subtotal * $vatPercentage) / 100;

        // Handle promo code
        $discountAmount = 0;
        $promoCodeId = null;
        if (isset($this->input['promo_code'])) {
            $stmt = $this->db->prepare("
                SELECT id, type, value, min_order_amount 
                FROM promo_codes 
                WHERE code = ? AND is_active = 1
            ");
            $stmt->execute([$this->input['promo_code']]);
            $promo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($promo && $subtotal >= $promo['min_order_amount']) {
                $promoCodeId = $promo['id'];
                if ($promo['type'] === 'percentage') {
                    $discountAmount = ($subtotal * $promo['value']) / 100;
                } else {
                    $discountAmount = $promo['value'];
                }
                $discountAmount = min($discountAmount, $subtotal); // Don't discount more than subtotal
            }
        }

        $totalAmount = $subtotal + $vatAmount - $discountAmount;

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Insert order
            $stmt = $this->db->prepare("
                INSERT INTO orders (
                    order_uid, branch_id, table_id, order_type, status, 
                    customer_name, customer_phone, customer_address,
                    subtotal, vat_amount, discount_amount, total_amount,
                    promo_code_id, estimated_completion_time
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $estimatedCompletion = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $stmt->execute([
                $orderUid,
                $this->input['branch_id'],
                $this->input['table_id'] ?? null,
                $this->input['order_type'],
                'placed',
                $this->input['customer_name'] ?? null,
                $this->input['customer_phone'] ?? null,
                $this->input['customer_address'] ?? null,
                $subtotal,
                $vatAmount,
                $discountAmount,
                $totalAmount,
                $promoCodeId,
                $estimatedCompletion
            ]);

            $orderId = $this->db->lastInsertId();

            // Insert order items
            foreach ($this->input['items'] as $item) {
                $stmt = $this->db->prepare("SELECT price FROM branch_menu_items WHERE id = ?");
                $stmt->execute([$item['branch_menu_item_id']]);
                $priceData = $stmt->fetch(PDO::FETCH_ASSOC);
                $unitPrice = $priceData['price'];

                $stmt = $this->db->prepare("
                    INSERT INTO order_items (
                        order_id, branch_menu_item_id, quantity, unit_price, customizations
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $orderId,
                    $item['branch_menu_item_id'],
                    $item['quantity'],
                    $unitPrice,
                    json_encode($item['customizations'] ?? [])
                ]);
            }

            $this->db->commit();

            $this->sendResponse([
                'order_id' => $orderUid,
                'status' => 'placed',
                'estimated_completion_time' => $estimatedCompletion
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // POST /api/promocode
    private function handlePromoCode() {
        if ($this->method !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }

        if (!isset($this->input['code'])) {
            $this->sendError('Promo code is required');
        }

        $stmt = $this->db->prepare("
            SELECT code, type, value, min_order_amount 
            FROM promo_codes 
            WHERE code = ? AND is_active = 1
        ");
        $stmt->execute([$this->input['code']]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$promo) {
            $this->sendError('Invalid or expired promo code', 404);
        }

        $this->sendResponse([
            'code' => $promo['code'],
            'type' => $promo['type'],
            'discount' => $promo['value'],
            'min_order_amount' => $promo['min_order_amount']
        ]);
    }

    // POST /api/feedback
    private function handleFeedback() {
        if ($this->method !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }

        $required = ['order_id', 'ratings'];
        foreach ($required as $field) {
            if (!isset($this->input[$field])) {
                $this->sendError("Missing required field: $field");
            }
        }

        if (!isset($this->input['ratings']['overall']) || $this->input['ratings']['overall'] < 1 || $this->input['ratings']['overall'] > 5) {
            $this->sendError('Overall rating is required and must be between 1 and 5');
        }

        // Get order ID from order_uid
        $stmt = $this->db->prepare("SELECT id FROM orders WHERE order_uid = ?");
        $stmt->execute([$this->input['order_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $this->sendError('Order not found', 404);
        }

        $stmt = $this->db->prepare("
            INSERT INTO feedback (
                order_id, overall_rating, food_rating, service_rating, item_feedback, comment
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $order['id'],
            $this->input['ratings']['overall'],
            $this->input['ratings']['food'] ?? null,
            $this->input['ratings']['service'] ?? null,
            json_encode($this->input['item_feedback'] ?? []),
            $this->input['comment'] ?? null
        ]);

        $this->sendResponse(['success' => true]);
    }

    // POST /api/service_request
    private function handleServiceRequest() {
        if ($this->method !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }

        $required = ['branch_id', 'table_id', 'request_type'];
        foreach ($required as $field) {
            if (!isset($this->input[$field])) {
                $this->sendError("Missing required field: $field");
            }
        }

        $validRequestTypes = ['assistance', 'water', 'bill'];
        if (!in_array($this->input['request_type'], $validRequestTypes)) {
            $this->sendError('Invalid request type');
        }

        // Validate table exists and belongs to branch
        $stmt = $this->db->prepare("
            SELECT id FROM restaurant_tables 
            WHERE id = ? AND branch_id = ?
        ");
        $stmt->execute([$this->input['table_id'], $this->input['branch_id']]);
        $table = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$table) {
            $this->sendError('Invalid table for this branch', 404);
        }

        $stmt = $this->db->prepare("
            INSERT INTO service_requests (table_id, request_type, status)
            VALUES (?, ?, 'pending')
        ");
        $stmt->execute([$this->input['table_id'], $this->input['request_type']]);

        $this->sendResponse(['success' => true]);
    }
}

// Run the API
$api = new LunaDineAPI();
$api->run();
?>