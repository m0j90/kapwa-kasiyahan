<?php
session_start();
$current_page = 'order';

date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/../../db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

$search = is_string($_GET['search'] ?? null) ? trim($_GET['search']) : '';

$status_filter = $_GET['status'] ?? 'all';
$allowed_statuses = ['all', 'pending', 'processing', 'shipped', 'delivery', 'completed', 'cancelled', 'refunded'];
if (!in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = 'all';
}

$return_only = isset($_GET['return_queue']) && $_GET['return_queue'] === '1';

// Count orders with active return/refund requests needing admin attention
$open_returns_count = 0;
$count_res = mysqli_query($Connection, "
    SELECT COUNT(DISTINCT order_id) AS open_cnt
    FROM return_requests
    WHERE return_status IN ('submitted', 'under_review')
");
if ($count_res) {
    $count_row = mysqli_fetch_assoc($count_res);
    $open_returns_count = intval($count_row['open_cnt'] ?? 0);
    mysqli_free_result($count_res);
}

$conditions = [];
$params = [];
$types = "";

if ($return_only) {
    $conditions[] = "rr.return_status IN ('submitted', 'under_review')";
}

if ($status_filter !== 'all') {
    if ($status_filter === 'refunded') {
        $conditions[] = "o.order_status = 'refunded'";
    } elseif ($status_filter === 'shipped') {
        $conditions[] = "o.order_status = 'shipped'";
    } elseif ($status_filter === 'delivery') {
        $conditions[] = "o.order_status IN ('out_for_delivery', 'delivered')";
    } elseif ($status_filter === 'completed') {
        $conditions[] = "o.order_status = 'completed'";
    } else {
        $conditions[] = "o.order_status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }
}

if ($search !== '') {
    $conditions[] = "(o.full_name LIKE ? OR o.order_number LIKE ?)";
    $like_term = "%" . $search . "%";
    $params[] = $like_term;
    $params[] = $like_term;
    $types .= "ss";
}

$where_clause = "";
if (!empty($conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $conditions);
}

$orders = [];
$sql = "
    SELECT 
        o.order_id,
        o.order_number,
        o.full_name,
        o.total_amount,
        o.payment_method,
        o.order_status,
        o.placed_at,
        COALESCE(SUM(oi.quantity), 0) AS total_items,
        rr.return_status
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN (
        SELECT r1.order_id, r1.return_status
        FROM return_requests r1
        INNER JOIN (
            SELECT order_id, MAX(return_id) AS max_return_id
            FROM return_requests
            GROUP BY order_id
        ) latest ON r1.return_id = latest.max_return_id
    ) rr ON o.order_id = rr.order_id
    $where_clause
    GROUP BY o.order_id, rr.return_status
    ORDER BY o.placed_at DESC
";

$stmt = mysqli_prepare($Connection, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($Row = mysqli_fetch_assoc($result)) {
    $orders[] = $Row;
}
mysqli_stmt_close($stmt);

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

function getPendingReturnCue(?string $return_status): string {
    if ($return_status === 'submitted') {
        return 'Return requested';
    }
    if ($return_status === 'under_review') {
        return 'Under review';
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
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
                        <h2 class="fw-bold font-poppins text-dark mb-1">Order Management</h2>
                        <p class="text-muted small mb-0">Review and manage customer orders.</p>
                    </div>
                    <?php if ($status_filter !== 'all' || $search !== '' || $return_only) { ?>
                        <div>
                            <a href="order-list.php" class="btn btn-outline-custom btn-sm w-sm-100">
                                <i class="bi bi-x-circle me-1"></i> Clear Filters
                            </a>
                        </div>
                    <?php } ?>
                </div>

                <?php if ($open_returns_count > 0 && !$return_only) { ?>
                    <div class="alert alert-warning border-0 rounded-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 py-3 px-4 mb-4 small">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-arrow-return-left fs-6"></i>
                            <span>
                                <strong><?php echo $open_returns_count; ?></strong>
                                <?php echo ($open_returns_count === 1) ? 'order has' : 'orders have'; ?>
                                an active return/refund request awaiting review.
                            </span>
                        </div>
                        <a href="order-list.php?return_queue=1" class="btn btn-sm btn-dark-custom rounded-3 px-3 py-1 fw-semibold text-decoration-none flex-shrink-0">
                            Show Requests
                        </a>
                    </div>
                <?php } elseif ($return_only) { ?>
                    <div class="alert alert-light border rounded-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 py-3 px-4 mb-4 small">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-funnel text-success fs-6"></i>
                            <span>Showing orders with active return/refund requests.</span>
                        </div>
                        <a href="order-list.php" class="btn btn-sm btn-outline-custom rounded-3 px-3 py-1 fw-semibold text-decoration-none flex-shrink-0">
                            Show All Orders
                        </a>
                    </div>
                <?php } ?>

                <form method="GET" action="order-list.php" class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 mb-4">
                    <?php if ($status_filter !== 'all') { ?>
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <?php } ?>
                    <?php if ($return_only) { ?>
                        <input type="hidden" name="return_queue" value="1">
                    <?php } ?>
                    <div class="input-group overflow-hidden flex-grow-1" style="max-width: 400px; width: 100%;">
                        <span class="input-group-text bg-white border-end-0 text-muted ps-3">
                            <i class="bi bi-search small"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0 ps-1 shadow-none small" placeholder="Search customers or order #..." value="<?php echo htmlspecialchars($search); ?>">
                        <?php if ($search !== '') { ?>
                            <a href="order-list.php<?php echo ($status_filter !== 'all') ? '?status=' . urlencode($status_filter) : ''; ?>" class="input-group-text bg-white border-start-0 text-decoration-none text-muted px-2" title="Clear search">
                                <i class="bi bi-x-lg small"></i>
                            </a>
                        <?php } ?>
                        <button type="submit" class="btn btn-palette-primary px-4 py-2 mx-2 rounded-3 small fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-1 text-white text-decoration-none flex-shrink-0">
                            <span>Search</span>
                        </button>
                    </div>
                </form>

                <div class="card border shadow-sm rounded-4 p-3 p-md-4 bg-white mb-4">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="small fw-semibold text-muted me-2">Status indicators:</span>
                        
                        <a href="?status=pending<?php echo ($search !== '') ? '&search=' . urlencode($search) : ''; ?>" class="status-pill status-pill-filter badge-status-pending <?php echo ($status_filter === 'pending') ? 'active-filter' : ''; ?>">
                            Pending
                        </a>
                        <a href="?status=processing<?php echo ($search !== '') ? '&search=' . urlencode($search) : ''; ?>" class="status-pill status-pill-filter badge-status-processing <?php echo ($status_filter === 'processing') ? 'active-filter' : ''; ?>">
                            Processing
                        </a>
                        <a href="?status=shipped<?php echo ($search !== '') ? '&search=' . urlencode($search) : ''; ?>" class="status-pill status-pill-filter badge-status-shipped <?php echo ($status_filter === 'shipped') ? 'active-filter' : ''; ?>">
                            Shipped
                        </a>
                        <a href="?status=delivery<?php echo ($search !== '') ? '&search=' . urlencode($search) : ''; ?>" class="status-pill status-pill-filter badge-status-out_for_delivery <?php echo ($status_filter === 'delivery') ? 'active-filter' : ''; ?>">
                            Delivery
                        </a>
                        <a href="?status=completed<?php echo ($search !== '') ? '&search=' . urlencode($search) : ''; ?>" class="status-pill status-pill-filter badge-status-completed <?php echo ($status_filter === 'completed') ? 'active-filter' : ''; ?>">
                            Completed
                        </a>
                        <a href="?status=cancelled<?php echo ($search !== '') ? '&search=' . urlencode($search) : ''; ?>" class="status-pill status-pill-filter badge-status-cancelled <?php echo ($status_filter === 'cancelled') ? 'active-filter' : ''; ?>">
                            Cancelled
                        </a>
                        <a href="?status=refunded<?php echo ($search !== '') ? '&search=' . urlencode($search) : ''; ?>" class="status-pill status-pill-filter badge-status-refunded <?php echo ($status_filter === 'refunded') ? 'active-filter' : ''; ?>">
                            Refunded
                        </a>
                    </div>
                </div>

                <?php if (empty($orders)) { ?>
                    <div class="card border shadow-sm rounded-4 p-5 bg-white text-center">
                        <div class="mb-3">
                            <i class="bi bi-inbox text-muted" style="font-size: 2.5rem;"></i>
                        </div>
                        <h3 class="h5 fw-bold font-poppins text-dark mb-1">No Orders Found</h3>
                        <p class="text-muted small mb-3">There are currently no customer orders matching your criteria.</p>
                        <?php if ($status_filter !== 'all' || $search !== '' || $return_only) { ?>
                            <div>
                                <a href="order-list.php" class="btn btn-dark-custom btn-sm">View All Orders</a>
                            </div>
                        <?php } ?>
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
                                            <th class="fw-semibold text-secondary small">Items</th>
                                            <th class="fw-semibold text-secondary small">Total</th>
                                            <th class="fw-semibold text-secondary small">Payment</th>
                                            <th class="fw-semibold text-secondary small">Status</th>
                                            <th class="fw-semibold text-secondary small">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $ord) {
                                            $badge = getOrderBadgeInfo($ord['order_status']);
                                            $return_cue = getPendingReturnCue($ord['return_status'] ?? null);
                                            $formatted_date = date('M j, Y • g:i A', strtotime($ord['placed_at']));
                                            $payment_label = ($ord['payment_method'] === 'online_payment') ? 'Online Payment' : 'Cash on Delivery';
                                        ?>
                                            <tr>
                                                <td class="text-muted fw-medium small">
                                                    <div>#<?php echo htmlspecialchars($ord['order_number']); ?></div>
                                                    <?php if ($return_cue !== '') { ?>
                                                        <div class="text-warning-emphasis fw-semibold" style="font-size: 0.75rem;">
                                                            <i class="bi bi-arrow-return-left me-1"></i><?php echo htmlspecialchars($return_cue); ?>
                                                        </div>
                                                    <?php } ?>
                                                </td>
                                                <td class="fw-bold text-dark small"><?php echo htmlspecialchars($ord['full_name']); ?></td>
                                                <td class="text-muted small"><?php echo $formatted_date; ?></td>
                                                <td class="small"><?php echo intval($ord['total_items']); ?></td>
                                                <td class="fw-semibold text-dark small">&#8369;<?php echo number_format($ord['total_amount'], 2); ?></td>
                                                <td class="small"><?php echo $payment_label; ?></td>
                                                <td class="small">
                                                    <span class="status-pill <?php echo $badge['class']; ?>">
                                                        <?php echo $badge['label']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="order-details.php?order_id=<?php echo intval($ord['order_id']); ?>" class="btn btn-sm btn-palette-info px-2 py-1 rounded-2 small fw-semibold shadow-sm">
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
                            <?php foreach ($orders as $ord) {
                                $badge = getOrderBadgeInfo($ord['order_status']);
                                $return_cue = getPendingReturnCue($ord['return_status'] ?? null);
                                $formatted_date = date('M j, Y • g:i A', strtotime($ord['placed_at']));
                                $payment_label = ($ord['payment_method'] === 'online_payment') ? 'Online Payment' : 'Cash on Delivery';
                            ?>
                                <div class="card border shadow-sm rounded-4 p-4 bg-white">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                        <div>
                                            <span class="fw-bold font-poppins text-dark fs-6 d-block">#<?php echo htmlspecialchars($ord['order_number']); ?></span>
                                            <?php if ($return_cue !== '') { ?>
                                                <span class="text-warning-emphasis fw-semibold small">
                                                    <i class="bi bi-arrow-return-left me-1"></i><?php echo htmlspecialchars($return_cue); ?>
                                                </span>
                                            <?php } ?>
                                        </div>
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
                                            <span class="text-muted">Items</span>
                                            <span class="fw-semibold text-dark"><?php echo intval($ord['total_items']); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Total</span>
                                            <span class="fw-bold text-dark">&#8369;<?php echo number_format($ord['total_amount'], 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Payment</span>
                                            <span class="fw-semibold text-dark"><?php echo $payment_label; ?></span>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end pt-2 border-top">
                                        <a href="order-details.php?order_id=<?php echo intval($ord['order_id']); ?>" class="btn btn-sm btn-palette-info px-2.5 py-1 rounded-2 small fw-semibold shadow-sm d-flex align-items-center gap-1">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>