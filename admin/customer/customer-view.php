<?php
session_start();
$current_page = 'customer';

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../../db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

$customer_id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : 0;

if ($customer_id <= 0) {
    header('Location: customer-list.php');
    exit();
}

$customer = null;
$cust_stmt = mysqli_prepare($Connection, "SELECT * FROM customers WHERE customer_id = ? LIMIT 1");
mysqli_stmt_bind_param($cust_stmt, 'i', $customer_id);
mysqli_stmt_execute($cust_stmt);
$cust_res = mysqli_stmt_get_result($cust_stmt);
if ($Row = mysqli_fetch_assoc($cust_res)) {
    $customer = $Row;
}
mysqli_stmt_close($cust_stmt);

if (!$customer) {
    header('Location: customer-list.php');
    exit();
}

$orders = [];
$ord_stmt = mysqli_prepare($Connection, "
    SELECT order_id, order_number, total_amount, order_status, placed_at 
    FROM orders 
    WHERE customer_id = ? 
    ORDER BY placed_at DESC
");
mysqli_stmt_bind_param($ord_stmt, 'i', $customer_id);
mysqli_stmt_execute($ord_stmt);
$ord_res = mysqli_stmt_get_result($ord_stmt);
while ($ord = mysqli_fetch_assoc($ord_res)) {
    $orders[] = $ord;
}
mysqli_stmt_close($ord_stmt);

$full_name = htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']);
$registered_date = date('M j, Y • g:i A', strtotime($customer['created_at']));

function getOrderBadgeInfo(?string $order_status): array {
    switch ($order_status) {
        case 'processing':
            return ['label' => 'Processing', 'class' => 'badge-status-processing'];
        case 'shipped':
        case 'out_for_delivery':
            return ['label' => 'Shipped', 'class' => 'badge-status-shipped'];
        case 'completed':
        case 'delivered':
            return ['label' => 'Completed', 'class' => 'badge-status-completed'];
        case 'cancelled':
            return ['label' => 'Cancelled', 'class' => 'badge-status-cancelled'];
        case 'refunded':
            return ['label' => 'Refunded', 'class' => 'badge-status-refunded'];
        case 'pending':
        default:
            return ['label' => 'Pending', 'class' => 'badge-status-pending'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details — <?php echo $full_name; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="../../assets/images/logos/transparent-symbol-logo.png">
</head>
<body>

    <?php include '../../includes/admin-header.php'; ?>

    <div class="admin-wrapper">
        <div class="admin-sidebar-container d-none d-md-block">
            <?php include '../../includes/admin-sidebar.php'; ?>
        </div>

        <div class="admin-content-area">
            <main class="p-4 p-md-5">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <h2 class="fw-bold font-poppins text-dark mb-1">Customer Details</h2>
                        <p class="text-muted small mb-0"><?php echo $Row['customer_id']; ?> &middot; <?php echo $full_name; ?></p>
                    </div>
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <a href="customer-edit.php?id=<?php echo $customer_id; ?>" class="btn btn-outline-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm text-center text-decoration-none">
                            Edit Customer
                        </a>
                        <button type="button" class="btn btn-palette-danger px-4 py-2 rounded-3 fw-semibold small shadow-sm text-center text-white text-decoration-none" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            Delete Customer
                        </button>
                        <a href="customer-list.php" class="btn btn-dark-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm text-center text-decoration-none">
                            <i class="bi bi-arrow-left me-2"></i> Back
                        </a>
                    </div>
                </div>

                <div class="card border shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h2 class="h4 fw-bold font-poppins text-dark mb-4">Customer Information</h2>

                    <div class="d-flex flex-column gap-3 small">
                        <div class="pb-2 border-bottom">
                            <span class="text-muted d-block mb-1">Customer ID</span>
                            <span class="fw-bold text-dark"><?php echo $Row['customer_id']; ?></span>
                        </div>
                        <div class="pb-2 border-bottom">
                            <span class="text-muted d-block mb-1">Full Name</span>
                            <span class="fw-bold text-dark"><?php echo $full_name; ?></span>
                        </div>
                        <div class="pb-2 border-bottom">
                            <span class="text-muted d-block mb-1">Email</span>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($customer['email']); ?></span>
                        </div>
                        <div class="pb-2 border-bottom">
                            <span class="text-muted d-block mb-1">Contact Number</span>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($customer['contact']); ?></span>
                        </div>
                        <div class="pb-2 border-bottom">
                            <span class="text-muted d-block mb-1">Delivery Address</span>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></span>
                        </div>
                        <div class="pb-2 border-bottom">
                            <span class="text-muted d-block mb-1">Username</span>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($customer['username'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pb-2 border-bottom">
                            <span class="text-muted d-block mb-1">Registered On</span>
                            <span class="fw-semibold text-dark"><?php echo $registered_date; ?></span>
                        </div>
                    </div>
                </div>

                <div class="card border shadow-sm rounded-4 p-4 bg-white">
                    <h3 class="h5 fw-bold font-poppins text-dark mb-4">Order History</h3>
                    <?php if (empty($orders)) { ?>
                        <div class="text-center py-4">
                            <p class="text-muted small mb-0">No orders found for this customer.</p>
                        </div>
                    <?php } else { ?>
                        <div class="d-none d-lg-block">
                            <div class="table-responsive">
                                <table class="table order-table mb-0">
                                    <thead class="table-light text-uppercase fs-7 text-muted border-bottom">
                                        <tr>
                                            <th class="fw-semibold text-secondary small">Order #</th>
                                            <th class="fw-semibold text-secondary small">Date</th>
                                            <th class="fw-semibold text-secondary small">Total</th>
                                            <th class="fw-semibold text-secondary small">Status</th>
                                            <th class="fw-semibold text-secondary small">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $ord) { 
                                            $badge = getOrderBadgeInfo($ord['order_status']);
                                            $ord_date = date('M j, Y • g:i A', strtotime($ord['placed_at']));
                                        ?>
                                            <tr>
                                                <td class="text-muted fw-medium small">#<?php echo htmlspecialchars($ord['order_number']); ?></td>
                                                <td class="text-muted small"><?php echo $ord_date; ?></td>
                                                <td class="fw-semibold text-dark small">&#8369;<?php echo number_format(floatval($ord['total_amount']), 2); ?></td>
                                                <td class="small">
                                                    <span class="status-pill <?php echo $badge['class']; ?>">
                                                        <?php echo $badge['label']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="../order/order-details.php?order_id=<?php echo intval($ord['order_id']); ?>" class="btn btn-sm btn-palette-info px-2 py-1 rounded-2 small fw-semibold shadow-sm">
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="d-block d-lg-none">
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($orders as $ord) { 
                                    $badge = getOrderBadgeInfo($ord['order_status']);
                                    $ord_date = date('M j, Y • g:i A', strtotime($ord['placed_at']));
                                ?>
                                    <div class="border rounded-3 p-3 bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="fw-bold font-poppins text-dark">#<?php echo htmlspecialchars($ord['order_number']); ?></span>
                                            <span class="status-pill <?php echo $badge['class']; ?>">
                                                <?php echo $badge['label']; ?>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between small text-muted mb-2">
                                            <span>Date</span>
                                            <span class="fw-semibold text-dark"><?php echo $ord_date; ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between small text-muted mb-3">
                                            <span>Total</span>
                                            <span class="fw-bold text-dark">&#8369;<?php echo number_format(floatval($ord['total_amount']), 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-end pt-2 border-top">
                                            <a href="../order/order-details.php?order_id=<?php echo intval($ord['order_id']); ?>" class="btn btn-sm btn-palette-info px-2.5 py-1 rounded-2 small fw-semibold shadow-sm d-flex align-items-center gap-1">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
            <div class="modal-content border-0 shadow-lg rounded-4 p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="modal-title fw-bold font-poppins text-dark mb-0" id="deleteModalLabel">Delete this customer?</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="mb-4">
                    <p class="text-muted small mb-3">This permanently removes the customer account. This cannot be undone.</p>
                    
                    <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 small">
                        <span class="text-muted">Customer</span>
                        <span class="fw-bold text-dark"><?php echo $full_name . ' (' . $Row['customer_id'] . ')'; ?></span>
                    </div>
                </div>

                <div class="delete-actions-wrapper">
                    <button type="button" class="btn btn-outline-custom px-4 rounded-3 fw-semibold small shadow-none btn-cancel-custom" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <a href="customer-list.php?del=<?php echo $customer_id; ?>" class="btn btn-palette-danger px-4 rounded-3 fw-semibold small text-white text-decoration-none shadow-sm btn-delete-custom">
                        Yes, Delete Customer
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>