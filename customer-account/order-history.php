<?php 
session_start();

if (!isset($_SESSION['customer_id']) && !isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/../db_connect.php';

$activePage = 'settings';
$customer_id = intval($_SESSION['customer_id'] ?? 0);

$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$allowed_statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled', 'refunded'];

$orders = [];
$sql = "
    SELECT 
        o.order_id,
        o.order_number,
        o.total_amount,
        o.payment_method,
        o.order_status,
        o.placed_at,
        COALESCE(SUM(oi.quantity), 0) AS total_items,
        rr.return_id,
        rr.return_status
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN (
        SELECT r1.return_id, r1.order_id, r1.return_status
        FROM return_requests r1
        INNER JOIN (
            SELECT order_id, MAX(return_id) AS max_return_id
            FROM return_requests
            GROUP BY order_id
        ) latest ON r1.return_id = latest.max_return_id
    ) rr ON o.order_id = rr.order_id
    WHERE o.customer_id = ?
";

if (in_array($status_filter, $allowed_statuses, true)) {
    if ($status_filter === 'shipped') {
        $sql .= " AND (o.order_status = 'shipped' OR o.order_status = 'out_for_delivery')";
    } elseif ($status_filter === 'completed') {
        $sql .= " AND (o.order_status = 'completed' OR o.order_status = 'delivered')";
    } else {
        $sql .= " AND o.order_status = '" . mysqli_real_escape_string($Connection, $status_filter) . "'";
    }
}

$sql .= " GROUP BY o.order_id, rr.return_id, rr.return_status ORDER BY o.placed_at DESC";

$stmt = mysqli_prepare($Connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $customer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($Row = mysqli_fetch_assoc($result)) {
    $orders[] = $Row;
}
mysqli_stmt_close($stmt);

function getOrderBadgeInfo(string $order_status) {
    switch ($order_status) {
        case 'processing':
            return ['label' => 'Processing', 'class' => 'badge-status-processing', 'action' => 'Track', 'link' => 'track-order.php'];
        case 'shipped':
        case 'out_for_delivery':
            return ['label' => 'Shipped', 'class' => 'badge-status-shipped', 'action' => 'Track', 'link' => 'track-order.php'];
        case 'completed':
        case 'delivered':
            return ['label' => 'Completed', 'class' => 'badge-status-completed', 'action' => 'View', 'link' => 'order-details.php'];
        case 'cancelled':
            return ['label' => 'Cancelled', 'class' => 'badge-status-cancelled', 'action' => 'View', 'link' => 'order-details.php'];
        case 'refunded':
            return ['label' => 'Refunded', 'class' => 'badge-status-refunded', 'action' => 'View', 'link' => 'order-details.php'];
        case 'pending':
        default:
            return ['label' => 'Pending', 'class' => 'badge-status-pending', 'action' => 'Track', 'link' => 'track-order.php'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="../assets/images/logos/transparent-symbol-logo.png">
</head>
<body class="bg-light text-dark">

    <?php 
        include '../includes/navbar.php';
    ?>

    <header class="py-5 bg-white border-bottom">
        <div class="container px-4 px-lg-5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none text-success">Home</a></li>
                    <li class="breadcrumb-item"><a href="account-settings.php" class="text-decoration-none text-success">Account Settings</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Order History</li>
                </ol>
            </nav>
            <h1 class="fw-bold display-5 font-poppins text-dark">Order History</h1>
            <p class="text-muted mb-0">All of your orders in one place.</p>
        </div>
    </header>

    <main class="py-5">
        <div class="container px-4 px-lg-5">
            <div class="row g-4">
                <div class="col-lg-3 d-none d-lg-block">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                        <div class="list-group list-group-flush gap-1">
                            <a href="account-settings.php" class="list-group-item list-group-item-action border-0 rounded-3 text-secondary py-2.5 px-3">Account Settings</a>
                            <a href="account-info.php" class="list-group-item list-group-item-action border-0 rounded-3 text-secondary py-2.5 px-3">Personal Information</a>
                            <a href="order-history.php" class="list-group-item list-group-item-action border-0 rounded-3 fw-semibold active py-2.5 px-3" style="background-color: #f1f5f9; color: #011f01;">Order History</a>
                            <a href="track-order.php" class="list-group-item list-group-item-action border-0 rounded-3 text-secondary py-2.5 px-3">Track Order</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="d-block d-lg-none mb-3">
                        <a href="account-settings.php" class="text-decoration-none text-success fw-semibold small">
                            <i class="bi bi-arrow-left me-1"></i> Account Settings
                        </a>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="text-muted small">Showing <?php echo count($orders); ?> orders</span>
                        <div class="dropdown">
                            <button class="btn btn-outline-custom dropdown-toggle px-4 py-2 rounded-3 small fw-semibold bg-white shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Filter by status
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border">
                                <li><a class="dropdown-item small py-2 <?php echo $status_filter === '' ? 'active bg-success text-white' : ''; ?>" href="order-history.php">All Orders</a></li>
                                <li><a class="dropdown-item small py-2 <?php echo $status_filter === 'pending' ? 'active bg-success text-white' : ''; ?>" href="order-history.php?status=pending">Pending</a></li>
                                <li><a class="dropdown-item small py-2 <?php echo $status_filter === 'processing' ? 'active bg-success text-white' : ''; ?>" href="order-history.php?status=processing">Processing</a></li>
                                <li><a class="dropdown-item small py-2 <?php echo $status_filter === 'shipped' ? 'active bg-success text-white' : ''; ?>" href="order-history.php?status=shipped">Shipped</a></li>
                                <li><a class="dropdown-item small py-2 <?php echo $status_filter === 'completed' ? 'active bg-success text-white' : ''; ?>" href="order-history.php?status=completed">Completed</a></li>
                                <li><a class="dropdown-item small py-2 <?php echo $status_filter === 'cancelled' ? 'active bg-success text-white' : ''; ?>" href="order-history.php?status=cancelled">Cancelled</a></li>
                                <li><a class="dropdown-item small py-2 <?php echo $status_filter === 'refunded' ? 'active bg-success text-white' : ''; ?>" href="order-history.php?status=refunded">Refunded</a></li>
                            </ul>
                        </div>
                    </div>

                    <?php if (empty($orders)) { ?>
                        <div class="card border shadow-sm rounded-4 p-5 bg-white text-center">
                            <div class="rounded-circle bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 60px; height: 60px;">
                                <i class="bi bi-receipt fs-3"></i>
                            </div>
                            <h5 class="fw-bold font-poppins text-dark mb-1">No orders found</h5>
                            <p class="text-muted small mb-4">You haven't placed any orders matching this filter yet.</p>
                            <a href="../products.php" class="btn btn-dark-custom btn-sm px-4 py-2 rounded-3 fw-semibold text-white text-decoration-none d-inline-block">Start Shopping</a>
                        </div>
                    <?php } else { ?>
                        <div class="card border shadow-sm rounded-4 bg-white overflow-hidden d-none d-lg-block mb-4">
                            <div class="table-responsive">
                                <table class="table order-table mb-0">
                                    <thead class="table-light text-uppercase fs-7 text-muted border-bottom">
                                        <tr>
                                            <th class="fw-semibold text-secondary small">Order #</th>
                                            <th class="fw-semibold text-secondary small">Date</th>
                                            <th class="fw-semibold text-secondary small">Items</th>
                                            <th class="fw-semibold text-secondary small">Total</th>
                                            <th class="fw-semibold text-secondary small">Payment</th>
                                            <th class="fw-semibold text-secondary small">Status</th>
                                            <th class="fw-semibold text-secondary small text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $ord) {
                                            $badge = getOrderBadgeInfo($ord['order_status']);
                                            $ord_date = date('M d, Y', strtotime($ord['placed_at']));
                                            $payment_label = ($ord['payment_method'] === 'online_payment') ? 'Online Payment' : 'Cash on Delivery';
                                            $has_return_req = !empty($ord['return_id']);
                                        ?>
                                            <tr>
                                                <td class="fw-bold text-dark small">#<?php echo htmlspecialchars($ord['order_number']); ?></td>
                                                <td class="text-muted small"><?php echo $ord_date; ?></td>
                                                <td class="small"><?php echo intval($ord['total_items']); ?></td>
                                                <td class="fw-semibold text-dark small">&#8369;<?php echo number_format(floatval($ord['total_amount']), 2); ?></td>
                                                <td class="text-muted small"><?php echo $payment_label; ?></td>
                                                <td class="small">
                                                    <span class="status-pill <?php echo $badge['class']; ?>">
                                                        <?php echo $badge['label']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="d-inline-flex align-items-center justify-content-end gap-2">
                                                        <a href="<?php echo $badge['link']; ?>?order_id=<?php echo intval($ord['order_id']); ?>" class="btn btn-sm btn-outline-custom px-3 py-1 rounded-2 fw-semibold small shadow-sm text-decoration-none">
                                                            <?php echo $badge['action']; ?>
                                                        </a>
                                                    </div>
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
                                    $ord_date = date('M d, Y', strtotime($ord['placed_at']));
                                    $payment_label = ($ord['payment_method'] === 'online_payment') ? 'Online Payment' : 'Cash on Delivery';
                                    $has_return_req = !empty($ord['return_id']);
                                ?>
                                    <div class="card border shadow-sm rounded-4 p-4 bg-white">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="fw-bold font-poppins text-dark fs-6">#<?php echo htmlspecialchars($ord['order_number']); ?></span>
                                            <span class="status-pill <?php echo $badge['class']; ?>">
                                                <?php echo $badge['label']; ?>
                                            </span>
                                        </div>

                                        <div class="d-flex flex-column gap-2 small text-muted mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span>Date &amp; Items</span>
                                                <span class="fw-medium text-dark"><?php echo $ord_date; ?> &middot; <?php echo intval($ord['total_items']); ?> items</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Payment Method</span>
                                                <span class="fw-medium text-dark"><?php echo $payment_label; ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center pt-1">
                                                <span class="text-dark fw-semibold">Total Amount</span>
                                                <span class="fw-bold font-poppins text-dark fs-6">&#8369;<?php echo number_format(floatval($ord['total_amount']), 2); ?></span>
                                            </div>
                                        </div>
                                        <div class="pt-3 border-top d-flex flex-column gap-2">
                                            <?php if ($has_return_req) { ?>
                                                <a href="request-progress.php?order_id=<?php echo intval($ord['order_id']); ?>" class="btn btn-sm btn-outline-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm text-decoration-none w-100 text-center">
                                                    Request Progress
                                                </a>
                                            <?php } ?>
                                            <a href="<?php echo $badge['link']; ?>?order_id=<?php echo intval($ord['order_id']); ?>" class="btn btn-sm btn-dark-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm text-white text-decoration-none w-100 text-center">
                                                <?php echo $badge['action']; ?> Order
                                            </a>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php' ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>