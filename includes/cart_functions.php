<?php
// includes/cart_functions.php

class CartModel {
    private $conn;
    private $table_name = "cart";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Get cart items for a user
    public function getCartItems($user_id) {
        $query = "SELECT c.*, p.name, p.price, p.image_url, p.category, p.stock_quantity
                  FROM " . $this->table_name . " c
                  LEFT JOIN products p ON c.product_id = p.id
                  WHERE c.user_id = :user_id
                  ORDER BY c.created_at DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error getting cart items: " . $e->getMessage());
            return [];
        }
    }
    
    // Get cart item count
    public function getCartCount($user_id) {
        $query = "SELECT COALESCE(SUM(quantity), 0) as total_items 
                  FROM " . $this->table_name . " 
                  WHERE user_id = :user_id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total_items'];
        } catch(PDOException $e) {
            error_log("Error getting cart count: " . $e->getMessage());
            return 0;
        }
    }
    
    // Add item to cart
    public function addToCart($user_id, $product_id, $quantity = 1) {
        // Check if item already exists
        $query = "SELECT id, quantity FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND product_id = :product_id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Update existing item
                $query = "UPDATE " . $this->table_name . " 
                          SET quantity = quantity + :quantity 
                          WHERE user_id = :user_id AND product_id = :product_id";
            } else {
                // Insert new item
                $query = "INSERT INTO " . $this->table_name . " 
                          (user_id, product_id, quantity) 
                          VALUES (:user_id, :product_id, :quantity)";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':quantity', $quantity);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error adding to cart: " . $e->getMessage());
            return false;
        }
    }
}

// Simple session-based cart for non-logged-in users
class SessionCart {
    public static function getCount() {
        if (!isset($_SESSION['cart'])) {
            return 0;
        }
        
        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'] ?? 1;
        }
        return $count;
    }
    
    public static function getItems() {
        return $_SESSION['cart'] ?? [];
    }
    
    public static function addItem($product_id, $quantity = 1, $product_data = []) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'quantity' => $quantity,
                'product_data' => $product_data
            ];
        }
    }
}