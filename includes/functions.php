<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function sanitizeInput($value)
{
    return trim(strip_tags((string) $value));
}

function formatCurrency($amount)
{
    return '৳' . number_format((float) $amount, 2);
}

function getAllShops($categoryId = null)
{
    $conn = getDbConnection();
    $sql = 'SELECT s.*, c.name AS category_name
            FROM shops s
            LEFT JOIN categories c ON c.id = s.category_id
            WHERE s.status = "active"';

    $params = [];
    $types = '';

    if ($categoryId !== null && $categoryId !== '') {
        $sql .= ' AND s.category_id = ?';
        $params[] = (int) $categoryId;
        $types = 'i';
    }

    $sql .= ' ORDER BY s.name ASC';

    if ($params) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function isShopOpen($shop)
{
    if (!isset($shop['opening_time']) || !isset($shop['closing_time'])) {
        return true;
    }

    $openingTime = trim((string) $shop['opening_time']);
    $closingTime = trim((string) $shop['closing_time']);

    if ($openingTime === '' || $closingTime === '') {
        return true;
    }

    $now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
    $currentSeconds = $now->format('H') * 3600 + $now->format('i') * 60 + $now->format('s');
    $openSeconds = strtotime('today ' . $openingTime) - strtotime('today 00:00:00');
    $closeSeconds = strtotime('today ' . $closingTime) - strtotime('today 00:00:00');

    if ($closeSeconds <= $openSeconds) {
        $closeSeconds += 86400;
        if ($currentSeconds < $openSeconds) {
            $currentSeconds += 86400;
        }
    }

    return $currentSeconds >= $openSeconds && $currentSeconds <= $closeSeconds;
}

function getShopFoodCategories($shopId)
{
    $foods = getShopFoods((int) $shopId);
    $categories = [];

    foreach ($foods as $food) {
        if (!empty($food['category_name'])) {
            $categories[] = $food['category_name'];
        }
    }

    $categories = array_values(array_unique($categories));
    return $categories;
}

function getShopFoods($shopId)
{
    $sql = 'SELECT f.*, c.name AS category_name
            FROM foods f
            LEFT JOIN categories c ON c.id = f.category_id
            WHERE f.shop_id = ? AND f.is_available = 1
            ORDER BY f.name ASC';

    $conn = getDbConnection();
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $shopId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function getFoodById($foodId)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare('SELECT f.*, s.name AS shop_name, s.owner_id, s.status AS shop_status FROM foods f INNER JOIN shops s ON s.id = f.shop_id WHERE f.id = ? AND f.is_available = 1 LIMIT 1');
    $stmt->bind_param('i', $foodId);
    $stmt->execute();
    $result = $stmt->get_result();
    $food = $result->fetch_assoc();
    $stmt->close();

    return $food ?: null;
}

function getCategories()
{
    $conn = getDbConnection();
    $result = $conn->query('SELECT * FROM categories ORDER BY name ASC');
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getCartItems()
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return [];
    }

    return $_SESSION['cart'];
}

function getDeliveryFee()
{
    return 10.0;
}

function getCartTotal()
{
    $total = 0.0;
    $items = getCartItems();

    foreach ($items as $item) {
        $total += (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 0);
    }

    if (!empty($items)) {
        $total += getDeliveryFee();
    }

    return $total;
}

function addItemToCart($foodId, $quantity = 1)
{
    $food = getFoodById((int) $foodId);

    if (!$food) {
        return false;
    }

    $quantity = max(1, (int) $quantity);
    $cart = getCartItems();
    $key = (int) $food['id'];

    if (isset($cart[$key])) {
        $cart[$key]['quantity'] += $quantity;
    } else {
        $cart[$key] = [
            'food_id' => (int) $food['id'],
            'shop_id' => (int) $food['shop_id'],
            'shop_name' => $food['shop_name'],
            'name' => $food['name'],
            'price' => (float) $food['price'],
            'quantity' => $quantity,
        ];
    }

    $_SESSION['cart'] = $cart;
    return true;
}

function removeItemFromCart($foodId)
{
    $cart = getCartItems();
    unset($cart[(int) $foodId]);
    $_SESSION['cart'] = $cart;
}

function clearCart()
{
    $_SESSION['cart'] = [];
}

function ensureRatingsTableExists()
{
    $conn = getDbConnection();
    $conn->query(
        'CREATE TABLE IF NOT EXISTS ratings (
            id INT NOT NULL AUTO_INCREMENT,
            customer_id INT NOT NULL,
            target_type ENUM("shop", "food") NOT NULL,
            target_id INT NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            comment TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_ratings_customer_target (customer_id, target_type, target_id),
            KEY idx_ratings_target (target_type, target_id),
            KEY idx_ratings_customer_id (customer_id),
            CONSTRAINT fk_ratings_customer FOREIGN KEY (customer_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function submitRatingForTarget($customerId, $targetType, $targetId, $rating, $comment)
{
    $targetType = strtolower((string) $targetType);
    if (!in_array($targetType, ['shop', 'food'], true)) {
        return false;
    }

    $targetId = (int) $targetId;
    $customerId = (int) $customerId;
    $rating = max(1, min(5, (int) $rating));
    $comment = trim((string) $comment);

    if ($customerId <= 0 || $targetId <= 0) {
        return false;
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare(
        'INSERT INTO ratings (customer_id, target_type, target_id, rating, comment)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), updated_at = NOW()'
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('isiss', $customerId, $targetType, $targetId, $rating, $comment);
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}

function getAverageRatingForTarget($targetType, $targetId)
{
    $targetType = strtolower((string) $targetType);
    if (!in_array($targetType, ['shop', 'food'], true)) {
        return ['avg_rating' => 0.0, 'review_count' => 0];
    }

    $targetId = (int) $targetId;
    if ($targetId <= 0) {
        return ['avg_rating' => 0.0, 'review_count' => 0];
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare(
        'SELECT AVG(rating) AS avg_rating, COUNT(*) AS review_count
         FROM ratings
         WHERE target_type = ? AND target_id = ?'
    );
    $stmt->bind_param('si', $targetType, $targetId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc() ?: ['avg_rating' => 0.0, 'review_count' => 0];
    $stmt->close();

    return [
        'avg_rating' => (float) ($data['avg_rating'] ?? 0),
        'review_count' => (int) ($data['review_count'] ?? 0),
    ];
}

function getRecentRatingsForTarget($targetType, $targetId, $limit = 5)
{
    $targetType = strtolower((string) $targetType);
    if (!in_array($targetType, ['shop', 'food'], true)) {
        return [];
    }

    $targetId = (int) $targetId;
    $limit = max(1, (int) $limit);

    if ($targetId <= 0) {
        return [];
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare(
        'SELECT r.*, u.full_name AS customer_name
         FROM ratings r
         INNER JOIN users u ON u.id = r.customer_id
         WHERE r.target_type = ? AND r.target_id = ?
         ORDER BY r.created_at DESC
         LIMIT ?'
    );
    $stmt->bind_param('sii', $targetType, $targetId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $ratings = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $ratings;
}

function assignDeliveryForOrder($orderId)
{
    $conn = getDbConnection();

    $orderStatusStmt = $conn->prepare('SELECT status FROM orders WHERE id = ? LIMIT 1');
    $orderStatusStmt->bind_param('i', $orderId);
    $orderStatusStmt->execute();
    $orderStatusRow = $orderStatusStmt->get_result()->fetch_assoc();
    $orderStatusStmt->close();

    if ($orderStatusRow && $orderStatusRow['status'] === 'delivered') {
        return true;
    }

    $existingStmt = $conn->prepare('SELECT delivery_person_id FROM deliveries WHERE order_id = ?');
    $existingStmt->bind_param('i', $orderId);
    $existingStmt->execute();
    $existingRows = $existingStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $existingStmt->close();

    $assignedPersons = [];
    foreach ($existingRows as $row) {
        if (!empty($row['delivery_person_id'])) {
            $assignedPersons[(int) $row['delivery_person_id']] = true;
        }
    }

    $riders = $conn->query(
        'SELECT u.id
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         WHERE r.name = "delivery_person" AND u.status = "active"
         ORDER BY u.id ASC'
    );

    if (!$riders) {
        if (empty($existingRows)) {
            $stmt = $conn->prepare(
                'INSERT INTO deliveries (order_id, status, created_at)
                 VALUES (?, "pending", NOW())'
            );
            $stmt->bind_param('i', $orderId);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        }

        return true;
    }

    $riderRows = $riders->fetch_all(MYSQLI_ASSOC);
    $insertStmt = $conn->prepare(
        'INSERT INTO deliveries (order_id, delivery_person_id, status, accepted_time, created_at)
         VALUES (?, ?, "assigned", NOW(), NOW())'
    );

    $hasInserted = false;
    foreach ($riderRows as $rider) {
        $riderId = (int) $rider['id'];
        if (isset($assignedPersons[$riderId])) {
            continue;
        }

        $insertStmt->bind_param('ii', $orderId, $riderId);
        if ($insertStmt->execute()) {
            $hasInserted = true;
        }
        $insertStmt->reset();
    }

    $insertStmt->close();

    if ($hasInserted || !empty($existingRows)) {
        return true;
    }

    $pendingStmt = $conn->prepare(
        'INSERT INTO deliveries (order_id, status, created_at)
         VALUES (?, "pending", NOW())'
    );
    $pendingStmt->bind_param('i', $orderId);
    $success = $pendingStmt->execute();
    $pendingStmt->close();

    return $success;
}

function createOrderFromCart()
{
    $cartItems = getCartItems();

    if (empty($cartItems)) {
        return false;
    }

    $customerId = $_SESSION['user_id'] ?? 0;
    $conn = getDbConnection();

    if (!$conn->begin_transaction()) {
        return false;
    }

    $groupedItems = [];
    foreach ($cartItems as $item) {
        $shopId = (int) ($item['shop_id'] ?? 0);
        if ($shopId <= 0) {
            $conn->rollback();
            return false;
        }

        $groupedItems[$shopId][] = $item;
    }

    if (empty($groupedItems)) {
        $conn->rollback();
        return false;
    }

    $createdOrderIds = [];

    foreach ($groupedItems as $shopId => $items) {
        $shopTotal = 0.0;

        foreach ($items as $item) {
            $foodId = (int) ($item['food_id'] ?? 0);
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $itemPrice = (float) ($item['price'] ?? 0);
            $itemName = trim((string) ($item['name'] ?? ''));

            if ($foodId <= 0 || $itemName === '') {
                $conn->rollback();
                return false;
            }

            $food = getFoodById($foodId);
            if (!$food) {
                $conn->rollback();
                return false;
            }

            $shopTotal += $itemPrice * $quantity;
        }

        $shopTotal += getDeliveryFee();

        $orderStmt = $conn->prepare('INSERT INTO orders (customer_id, total_amount, status, created_at) VALUES (?, ?, "pending", NOW())');
        $orderStmt->bind_param('id', $customerId, $shopTotal);

        if (!$orderStmt->execute()) {
            $orderStmt->close();
            $conn->rollback();
            return false;
        }

        $orderId = $conn->insert_id;
        $orderStmt->close();
        $createdOrderIds[] = $orderId;

        foreach ($items as $item) {
            $foodId = (int) ($item['food_id'] ?? 0);
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $itemPrice = (float) ($item['price'] ?? 0);
            $itemName = trim((string) ($item['name'] ?? ''));

            $orderItemStmt = $conn->prepare('INSERT INTO order_items (order_id, food_id, shop_id, name, quantity, price) VALUES (?, ?, ?, ?, ?, ?)');
            $orderItemStmt->bind_param('iiisid', $orderId, $foodId, $shopId, $itemName, $quantity, $itemPrice);

            if (!$orderItemStmt->execute()) {
                $orderItemStmt->close();
                $conn->rollback();
                return false;
            }

            $orderItemStmt->close();
        }
    }

    if (!$conn->commit()) {
        $conn->rollback();
        return false;
    }

    clearCart();
    return array_values($createdOrderIds);
}

function getCustomerOrders()
{
    $customerId = $_SESSION['user_id'] ?? 0;
    $conn = getDbConnection();

    $stmt = $conn->prepare('SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($orders as $index => &$order) {
        $itemsStmt = $conn->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $itemsStmt->bind_param('i', $order['id']);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        $order['items'] = $itemsResult->fetch_all(MYSQLI_ASSOC);
        $itemsStmt->close();

        if (empty($order['items'])) {
            unset($orders[$index]);
        }
    }
    unset($order);

    return array_values($orders);
}

function getOwnerShops($ownerId)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare(
        'SELECT s.*, c.name AS category_name
         FROM shops s
         LEFT JOIN categories c ON c.id = s.category_id
         WHERE s.owner_id = ?
         ORDER BY s.created_at DESC'
    );
    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $shops = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $shops;
}

function getOwnerShopById($shopId, $ownerId)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare(
        'SELECT s.*, c.name AS category_name
         FROM shops s
         LEFT JOIN categories c ON c.id = s.category_id
         WHERE s.id = ? AND s.owner_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('ii', $shopId, $ownerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $shop = $result->fetch_assoc();
    $stmt->close();

    return $shop ?: null;
}

function getOwnerShopFoods($shopId, $ownerId)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare(
        'SELECT f.*, c.name AS category_name
         FROM foods f
         LEFT JOIN categories c ON c.id = f.category_id
         INNER JOIN shops s ON s.id = f.shop_id
         WHERE f.shop_id = ? AND s.owner_id = ?
         ORDER BY f.name ASC'
    );
    $stmt->bind_param('ii', $shopId, $ownerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $foods = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $foods;
}

function getOwnerFoods($ownerId)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare(
        'SELECT f.*, s.name AS shop_name, c.name AS category_name
         FROM foods f
         INNER JOIN shops s ON s.id = f.shop_id
         LEFT JOIN categories c ON c.id = f.category_id
         WHERE s.owner_id = ?
         ORDER BY s.name ASC, f.name ASC'
    );
    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $foods = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $foods;
}

function getOwnerOrders($ownerId)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare(
        'SELECT DISTINCT o.*, u.full_name AS customer_name
         FROM orders o
         INNER JOIN users u ON u.id = o.customer_id
         INNER JOIN order_items oi ON oi.order_id = o.id
         INNER JOIN shops s ON s.id = oi.shop_id
         WHERE s.owner_id = ?
         ORDER BY o.created_at DESC'
    );
    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($orders as &$order) {
        $itemsStmt = $conn->prepare(
            'SELECT oi.*, s.name AS shop_name
             FROM order_items oi
             INNER JOIN shops s ON s.id = oi.shop_id
             WHERE oi.order_id = ? AND s.owner_id = ?'
        );
        $itemsStmt->bind_param('ii', $order['id'], $ownerId);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        $order['items'] = $itemsResult->fetch_all(MYSQLI_ASSOC);
        $itemsStmt->close();
    }

    return $orders;
}

function updateOrderStatusForOwner($orderId, $ownerId, $status)
{
    $allowedStatuses = ['pending', 'accepted', 'preparing', 'ready', 'picked_up', 'delivered', 'cancelled'];
    if (!in_array($status, $allowedStatuses, true)) {
        return false;
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare(
        'UPDATE orders o
         SET o.status = ?
         WHERE o.id = ?
         AND EXISTS (
             SELECT 1
             FROM order_items oi
             INNER JOIN shops s ON s.id = oi.shop_id
             WHERE oi.order_id = o.id AND s.owner_id = ?
         )'
    );
    $stmt->bind_param('sii', $status, $orderId, $ownerId);
    $success = $stmt->execute();
    $stmt->close();

    if (!$success) {
        return false;
    }

    if ($status === 'accepted') {
        return assignDeliveryForOrder($orderId);
    }

    return true;
}
