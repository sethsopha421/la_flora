<?php
session_start();
require_once '../includes/database.php';

// Check if user is admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$export_type = isset($_GET['type']) ? $_GET['type'] : 'csv';

// Get orders for export
$orders_query = "SELECT o.*, u.name as customer_name, u.email 
                 FROM orders o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 ORDER BY o.created_at DESC";
$orders_result = mysqli_query($conn, $orders_query);

if ($export_type === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=orders_export_' . date('Y-m-d') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, ['Order ID', 'Order Number', 'Customer Name', 'Email', 'Total Amount', 
                      'Status', 'Payment Status', 'Payment Method', 'Created Date']);
    
    // Add data rows
    while ($order = mysqli_fetch_assoc($orders_result)) {
        fputcsv($output, [
            $order['id'],
            $order['order_number'],
            $order['customer_name'],
            $order['email'],
            '$' . number_format($order['total_amount'], 2),
            ucfirst($order['status']),
            ucfirst($order['payment_status']),
            ucfirst($order['payment_method']),
            date('Y-m-d H:i:s', strtotime($order['created_at']))
        ]);
    }
    
    fclose($output);
    exit();
    
} elseif ($export_type === 'print') {
    // HTML print view
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Orders Report - LA FLORA</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .print-header { text-align: center; margin-bottom: 30px; }
            .print-header h1 { color: #2A5934; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .total-row { font-weight: bold; }
            .print-footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body onload="window.print()">
        <div class="print-header">
            <h1>LA FLORA - Orders Report</h1>
            <p>Generated on: <?php echo date('F j, Y \a\t g:i A'); ?></p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_amount = 0;
                mysqli_data_seek($orders_result, 0);
                while ($order = mysqli_fetch_assoc($orders_result)): 
                    $total_amount += $order['total_amount'];
                ?>
                <tr>
                    <td>#<?php echo $order['order_number']; ?></td>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                    <td><?php echo ucfirst($order['status']); ?></td>
                    <td><?php echo ucfirst($order['payment_status']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
                <tr class="total-row">
                    <td colspan="2">Total</td>
                    <td>$<?php echo number_format($total_amount, 2); ?></td>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>
        
        <div class="print-footer">
            <p>LA FLORA Flower Shop - Order Management System</p>
            <p>This is an auto-generated report</p>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>