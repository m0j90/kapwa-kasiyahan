<?php
session_start();
$current_page = 'dashboard';

require_once __DIR__ . '/../db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$admin_role = $_SESSION['admin_role'] ?? 'admin'; 
$is_super_admin = ($admin_role === 'admin');

$total_customers = 0;
$res_cust = mysqli_query($Connection, "SELECT COUNT(*) AS count FROM customers");
if ($res_cust && $Row = mysqli_fetch_assoc($res_cust)) {
    $total_customers = intval($Row['count']);
}

$total_products = 0;
$res_prod = mysqli_query($Connection, "SELECT COUNT(*) AS count FROM products");
if ($res_prod && $Row = mysqli_fetch_assoc($res_prod)) {
    $total_products = intval($Row['count']);
}

$total_orders = 0;
$res_ord = mysqli_query($Connection, "SELECT COUNT(*) AS count FROM orders");
if ($res_ord && $Row = mysqli_fetch_assoc($res_ord)) {
    $total_orders = intval($Row['count']);
}

$pending_orders = 0;
$res_pend = mysqli_query($Connection, "SELECT COUNT(*) AS count FROM orders WHERE order_status = 'pending'");
if ($res_pend && $Row = mysqli_fetch_assoc($res_pend)) {
    $pending_orders = intval($Row['count']);
}

$unread_messages = 0;
$res_msg = mysqli_query($Connection, "SELECT COUNT(*) AS count FROM conversation_messages WHERE sender_type = 'customer' AND admin_read_at IS NULL");
if ($res_msg && $Row = mysqli_fetch_assoc($res_msg)) {
    $unread_messages = intval($Row['count']);
}

$recent_orders = [];
$recent_sql = "
    SELECT 
        o.order_id,
        o.order_number,
        o.full_name,
        o.total_amount,
        o.order_status,
        o.placed_at,
        COALESCE(SUM(oi.quantity), 0) AS total_items
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    GROUP BY o.order_id
    ORDER BY o.placed_at DESC
    LIMIT 5
";
$recent_res = mysqli_query($Connection, $recent_sql);
if ($recent_res) {
    while ($ord = mysqli_fetch_assoc($recent_res)) {
        $recent_orders[] = $ord;
    }
}

function getOrderBadgeInfo(?string $order_status): array {
    switch ($order_status) {
        case 'processing':
            return ['label' => 'Processing', 'class' => 'badge-status-processing'];
        case 'shipped':
            return ['label' => 'Shipped', 'class' => 'badge-status-shipped'];
        case 'out_for_delivery':
        case 'delivered':
            return ['label' => 'Delivery', 'class' => 'badge-status-out_for_delivery'];
        case 'completed':
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
    <title>Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="../assets/images/logos/transparent-symbol-logo.png">
</head>
<body>

    <?php include '../includes/admin-header.php'; ?>

    <div class="admin-wrapper">
        <div class="admin-sidebar-container d-none d-lg-block">
            <?php include '../includes/admin-sidebar.php'; ?>
        </div>

        <div class="admin-content-area">
            <main class="p-4 p-md-5">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <h2 class="fw-bold font-poppins text-dark mb-1">Dashboard</h2>
                        <p class="text-muted small mb-0">Overview of customers, products, orders and messages.</p>
                    </div>
                </div>

                <div class="row g-3 mb-5">
                    <div class="col-6 col-md-4 col-xl">
                        <div class="card border shadow-sm rounded-4 p-4 bg-white h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small">Total Customers</span>
                            </div>
                            <h3 class="fw-bold font-poppins text-dark mb-0 fs-2"><?php echo str_pad($total_customers, 3, '0', STR_PAD_LEFT); ?></h3>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl">
                        <div class="card border shadow-sm rounded-4 p-4 bg-white h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small">Total Products</span>
                            </div>
                            <h3 class="fw-bold font-poppins text-dark mb-0 fs-2"><?php echo str_pad($total_products, 3, '0', STR_PAD_LEFT); ?></h3>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl">
                        <div class="card border shadow-sm rounded-4 p-4 bg-white h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small">Total Orders</span>
                            </div>
                            <h3 class="fw-bold font-poppins text-dark mb-0 fs-2"><?php echo str_pad($total_orders, 3, '0', STR_PAD_LEFT); ?></h3>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl">
                        <div class="card border shadow-sm rounded-4 p-4 bg-white h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small">Pending Orders</span>
                            </div>
                            <h3 class="fw-bold font-poppins text-dark mb-0 fs-2"><?php echo str_pad($pending_orders, 2, '0', STR_PAD_LEFT); ?></h3>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 col-xl">
                        <div class="card border shadow-sm rounded-4 p-4 bg-white h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small">Unread Messages</span>
                            </div>
                            <h3 class="fw-bold font-poppins text-dark mb-0 fs-2"><?php echo str_pad($unread_messages, 2, '0', STR_PAD_LEFT); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="mb-5">
                    <h3 class="h4 fw-bold font-poppins text-dark mb-3">Management Shortcuts</h3>
                    <div class="row g-3">
                        <div class="col-12 col-sm-6 col-lg-<?php echo $is_super_admin ? '4' : '4'; ?>">
                            <div class="card border shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="px-3 py-2 rounded-3 bg-success-subtle text-success d-flex justify-content-center mb-3" style="width: fit-content;">
                                        <i class="bi bi-people fs-3"></i>
                                    </div>
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div>
                                            <h4 class="h5 fw-bold font-poppins text-dark mb-0">Customer Management</h4>
                                            <span class="text-muted small">Customer list</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <a href="customer/customer-list.php" class="btn btn-outline-custom btn-sm px-4 mb-2">Open</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-<?php echo $is_super_admin ? '4' : '4'; ?>">
                            <div class="card border shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="px-3 py-2 rounded-3 bg-success-subtle text-success d-flex justify-content-center mb-3" style="width: fit-content;">
                                        <i class="bi bi-box-seam fs-3"></i>
                                    </div>
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div>
                                            <h4 class="h5 fw-bold font-poppins text-dark mb-0">Product Management</h4>
                                            <span class="text-muted small">Product list</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <a href="product/product-list.php" class="btn btn-outline-custom btn-sm px-4 mb-2">Open</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-<?php echo $is_super_admin ? '4' : '4'; ?>">
                            <div class="card border shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="px-3 py-2 rounded-3 bg-success-subtle text-success d-flex justify-content-center mb-3" style="width: fit-content;">
                                        <i class="bi bi-receipt fs-3"></i>
                                    </div>
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div>
                                            <h4 class="h5 fw-bold font-poppins text-dark mb-0">Order Management</h4>
                                            <span class="text-muted small">Order list</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <a href="order/order-list.php" class="btn btn-outline-custom btn-sm px-4 mb-2">Open</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-<?php echo $is_super_admin ? '4' : '4'; ?>">
                            <div class="card border shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="px-3 py-2 rounded-3 bg-success-subtle text-success d-flex justify-content-center mb-3" style="width: fit-content;">
                                        <i class="bi bi-chat-left-text fs-3"></i>
                                    </div>
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div>
                                            <h4 class="h5 fw-bold font-poppins text-dark mb-0">Messages</h4>
                                            <span class="text-muted small">Admin inbox</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <a href="messages/inbox.php" class="btn btn-outline-custom btn-sm px-4 mb-2">Open</a>
                                </div>
                            </div>
                        </div>

                        <?php if ($is_super_admin) { ?>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <div class="card border shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="px-3 py-2 rounded-3 bg-success-subtle text-success d-flex justify-content-center mb-3" style="width: fit-content;">
                                        <i class="bi bi-shield-lock fs-3"></i>
                                    </div>
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div>
                                            <h4 class="h5 fw-bold font-poppins text-dark mb-0">Sub-Admin Management</h4>
                                            <span class="text-muted small">Sub-admin list</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <a href="sub-admin/subadmin-list.php" class="btn btn-outline-custom btn-sm px-4 mb-2">Open</a>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                
                <div>
                    <h3 class="h4 fw-bold font-poppins text-dark mb-3">Recent Orders</h3>
                    <?php if (empty($recent_orders)) { ?>
                        <div class="card border shadow-sm rounded-4 p-4 bg-white text-center">
                            <p class="text-muted small mb-0">No recent orders found.</p>
                        </div>
                    <?php } else { ?>
                        <div class="d-none d-lg-block">
                            <div class="card border shadow-sm rounded-4 bg-white overflow-hidden">
                                <div class="table-responsive">
                                    <table class="table order-table mb-0">
                                        <thead class="table-light text-uppercase fs-7 text-muted border-bottom">
                                            <tr>
                                                <th class="fw-semibold text-secondary small">Order #</th>
                                                <th class="fw-semibold text-secondary small">Customer</th>
                                                <th class="fw-semibold text-secondary small">Date</th>
                                                <th class="fw-semibold text-secondary small">Total</th>
                                                <th class="fw-semibold text-secondary small">Status</th>
                                                <th class="fw-semibold text-secondary small">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_orders as $ord) {
                                                $badge = getOrderBadgeInfo($ord['order_status']);
                                                $formatted_date = date('M j, Y • g:i A', strtotime($ord['placed_at']));
                                            ?>
                                                <tr>
                                                    <td class="text-muted fw-medium small">#<?php echo htmlspecialchars($ord['order_number']); ?></td>
                                                    <td class="fw-bold text-dark small"><?php echo htmlspecialchars($ord['full_name']); ?></td>
                                                    <td class="text-muted small"><?php echo $formatted_date; ?></td>
                                                    <td class="fw-semibold text-dark small">&#8369;<?php echo number_format($ord['total_amount'], 2); ?></td>
                                                    <td class="small">
                                                        <span class="status-pill <?php echo $badge['class']; ?>">
                                                            <?php echo $badge['label']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="order/order-details.php?order_id=<?php echo intval($ord['order_id']); ?>" class="btn btn-sm btn-palette-info px-2 py-1 rounded-2 small fw-semibold shadow-sm">
                                                            View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="d-block d-lg-none">
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($recent_orders as $ord) {
                                    $badge = getOrderBadgeInfo($ord['order_status']);
                                    $formatted_date = date('M j, Y • g:i A', strtotime($ord['placed_at']));
                                ?>
                                    <div class="card border shadow-sm rounded-4 p-4 bg-white">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="fw-bold font-poppins text-dark fs-6">#<?php echo htmlspecialchars($ord['order_number']); ?></span>
                                            <span class="status-pill <?php echo $badge['class']; ?>">
                                                <?php echo $badge['label']; ?>
                                            </span>
                                        </div>

                                        <div class="d-flex flex-column gap-2 small mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Customer</span>
                                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($ord['full_name']); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Date</span>
                                                <span class="fw-semibold text-dark"><?php echo $formatted_date; ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Total</span>
                                                <span class="fw-bold text-dark">&#8369;<?php echo number_format($ord['total_amount'], 2); ?></span>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end pt-2 border-top">
                                            <a href="order/order-details.php?order_id=<?php echo intval($ord['order_id']); ?>" class="btn btn-sm btn-palette-info px-2.5 py-1 rounded-2 small fw-semibold shadow-sm d-flex align-items-center gap-1">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>