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
$order_id = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    header('Location: order-history.php');
    exit();
}

$feedback_msg = '';
$feedback_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $check_stmt = mysqli_prepare($Connection, "SELECT order_status FROM orders WHERE order_id = ? AND customer_id = ? LIMIT 1");
    mysqli_stmt_bind_param($check_stmt, 'ii', $order_id, $customer_id);
    mysqli_stmt_execute($check_stmt);
    $check_res = mysqli_stmt_get_result($check_stmt);
    $current_row = mysqli_fetch_assoc($check_res);
    mysqli_stmt_close($check_stmt);

    if ($current_row && $current_row['order_status'] === 'pending') {
        $cancel_stmt = mysqli_prepare($Connection, "UPDATE orders SET order_status = 'cancelled', updated_at = NOW() WHERE order_id = ? AND customer_id = ?");
        mysqli_stmt_bind_param($cancel_stmt, 'ii', $order_id, $customer_id);
        if (mysqli_stmt_execute($cancel_stmt)) {
            $feedback_msg = 'Your order has been cancelled.';
            $feedback_type = 'success';
        } else {
            $feedback_msg = 'Unable to cancel your order right now. Please try again.';
            $feedback_type = 'danger';
        }
        mysqli_stmt_close($cancel_stmt);
    } else {
        $feedback_msg = 'Only pending orders can be cancelled.';
        $feedback_type = 'danger';
    }
}

$order = null;
$ord_stmt = mysqli_prepare($Connection, "
    SELECT 
        o.order_id,
        o.order_number,
        o.full_name,
        o.contact_number,
        o.delivery_address,
        o.order_notes,
        o.subtotal,
        o.shipping_fee,
        o.total_amount,
        o.payment_method,
        o.order_status,
        o.placed_at,
        o.processing_at,
        o.shipped_at,
        o.delivered_at,
        o.completed_at,
        p.payment_status,
        p.transaction_reference
    FROM orders o
    LEFT JOIN payments p ON o.order_id = p.order_id
    WHERE o.order_id = ? AND o.customer_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($ord_stmt, 'ii', $order_id, $customer_id);
mysqli_stmt_execute($ord_stmt);
$ord_res = mysqli_stmt_get_result($ord_stmt);
if ($Row = mysqli_fetch_assoc($ord_res)) {
    $order = $Row;
}
mysqli_stmt_close($ord_stmt);

if (!$order) {
    header('Location: order-history.php');
    exit();
}

$existing_return = null;
$rr_stmt = mysqli_prepare($Connection, "
    SELECT return_id, return_status
    FROM return_requests
    WHERE order_id = ? AND customer_id = ?
    ORDER BY return_id DESC
    LIMIT 1
");
mysqli_stmt_bind_param($rr_stmt, 'ii', $order_id, $customer_id);
mysqli_stmt_execute($rr_stmt);
$rr_res = mysqli_stmt_get_result($rr_stmt);
$existing_return = mysqli_fetch_assoc($rr_res);
mysqli_stmt_close($rr_stmt);

$order_items = [];
$items_stmt = mysqli_prepare($Connection, "
    SELECT 
        product_name,
        product_image,
        unit_price,
        quantity,
        line_total
    FROM order_items
    WHERE order_id = ?
");
mysqli_stmt_bind_param($items_stmt, 'i', $order_id);
mysqli_stmt_execute($items_stmt);
$items_res = mysqli_stmt_get_result($items_stmt);
while ($item = mysqli_fetch_assoc($items_res)) {
    $order_items[] = $item;
}
mysqli_stmt_close($items_stmt);

function getCustomerOrderBadgeInfo(?string $order_status): array {
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

$badge = getCustomerOrderBadgeInfo($order['order_status']);
$placed_datetime = !empty($order['placed_at']) ? date('M j, Y • g:i A', strtotime($order['placed_at'])) : '—';
$payment_method_label = ($order['payment_method'] === 'online_payment') ? 'Online Payment' : 'Cash on Delivery';

if ($order['payment_method'] === 'online_payment') {
    $ref = !empty($order['transaction_reference']) ? ' (' . htmlspecialchars($order['transaction_reference']) . ')' : '';
    $payment_status_label = 'Paid' . $ref;
} else {
    $payment_status_label = in_array($order['order_status'], ['delivered', 'completed'], true)
        ? 'Paid upon delivery'
        : 'To be paid on delivery';
}

$request_progress_url = 'request-progress.php?order_id=' . intval($order['order_id']);
$return_refund_url = 'return-refund.php?order_id=' . intval($order['order_id']);
$can_request_return = !$existing_return && in_array($order['order_status'], ['delivered', 'completed'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo htmlspecialchars($order['order_number']); ?> — Details</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="../assets/images/logos/transparent-symbol-logo.png">
</head>
<body class="bg-light text-dark">

    <?php include '../includes/navbar.php'; ?>

    <header class="py-5 bg-white border-bottom">
        <div class="container px-4 px-lg-5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2 small">
                    <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none text-success">Home</a></li>
                    <li class="breadcrumb-item"><a href="account-settings.php" class="text-decoration-none text-success">Account Settings</a></li>
                    <li class="breadcrumb-item"><a href="order-history.php" class="text-decoration-none text-success">Order History</a></li>
                    <li class="breadcrumb-item active" aria-current="page">#<?php echo htmlspecialchars($order['order_number']); ?></li>
                </ol>
            </nav>
            <h1 class="fw-bold display-5 font-poppins text-dark">Order Details</h1>
            <p class="text-muted mb-0">Review your purchased items, payment summary, and delivery details.</p>
        </div>
    </header>

    <main class="py-5">
        <div class="container px-4 px-lg-5">
            <div class="row g-4">
                <div class="col-lg-3 d-none d-lg-block">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                        <div class="list-group list-group-flush gap-1">
                            <a href="account-settings.php" class="list-group-item list-group-item-action border-0 rounded-3 text-secondary py-2 px-3">Account Settings</a>
                            <a href="account-info.php" class="list-group-item list-group-item-action border-0 rounded-3 text-secondary py-2 px-3">Personal Information</a>
                            <a href="order-history.php" class="list-group-item list-group-item-action border-0 rounded-3 fw-semibold active py-2 px-3" style="background-color: #eef1e9; color: #0a6e06;">Order History</a>
                            <a href="track-order.php?order_id=<?php echo intval($order['order_id']); ?>" class="list-group-item list-group-item-action border-0 rounded-3 text-secondary py-2 px-3">Track Order</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="d-block d-lg-none mb-3">
                        <a href="order-history.php" class="text-decoration-none text-success fw-semibold small">
                            <i class="bi bi-arrow-left me-1"></i> Back to Order History
                        </a>
                    </div>

                    <?php if (!empty($feedback_msg)) { ?>
                        <div class="alert <?php echo ($feedback_type === 'success') ? 'alert-success-custom' : 'alert-danger-custom'; ?> mb-4" role="alert">
                            <i class="bi <?php echo ($feedback_type === 'success') ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'; ?>"></i>
                            <span><?php echo htmlspecialchars($feedback_msg); ?></span>
                        </div>
                    <?php } ?>

                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <h4 class="fw-bold font-poppins text-dark mb-0">Order #<?php echo htmlspecialchars($order['order_number']); ?></h4>
                                    <span class="status-pill <?php echo $badge['class']; ?>">
                                        <?php echo htmlspecialchars($badge['label']); ?>
                                    </span>
                                </div>
                                <p class="text-muted small mb-0">Placed on <?php echo htmlspecialchars($placed_datetime); ?></p>
                            </div>

                            <div class="d-flex flex-column flex-sm-row flex-wrap gap-2">
                                <a href="track-order.php?order_id=<?php echo intval($order['order_id']); ?>" class="btn btn-dark-custom px-4 py-2 rounded-3 fw-semibold small text-center text-decoration-none">
                                    <i class="bi bi-geo-alt me-1"></i> Track Order
                                </a>
                                <span class="d-lg-none">
                                    <?php if ($existing_return) { ?>
                                        <a href="<?php echo htmlspecialchars($request_progress_url); ?>" class="btn btn-outline-custom px-4 py-2 rounded-3 fw-semibold small text-center text-decoration-none w-100">
                                            <i class="bi bi-arrow-return-left me-1"></i> Request Progress
                                        </a>
                                    <?php } elseif ($can_request_return) { ?>
                                        <a href="<?php echo htmlspecialchars($return_refund_url); ?>" class="btn-outline-custom rounded-3 fw-semibold small text-center text-decoration-none w-100">
                                            Request Return / Refund
                                        </a>
                                    <?php } ?>
                                </span>
                                <?php if ($order['order_status'] === 'pending') { ?>
                                    <button type="button" class="btn btn-palette-danger px-4 py-2 rounded-3 fw-semibold small text-center text-white" data-bs-toggle="modal" data-bs-target="#cancelCustomerOrderModal">
                                        Cancel Order
                                    </button>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <h5 class="fw-bold font-poppins text-dark mb-4">Order Items (<?php echo count($order_items); ?>)</h5>
                                    <div class="d-flex flex-column gap-3 mb-4">
                                        <?php foreach ($order_items as $item) {
                                            $img_path = !empty($item['product_image']) ? '../uploads/' . ltrim($item['product_image'], '/') : '';
                                        ?>
                                            <div class="d-flex justify-content-between align-items-center gap-3 pb-3 border-bottom">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="product-thumb rounded-3 overflow-hidden flex-shrink-0">
                                                        <?php if (!empty($img_path)) { ?>
                                                            <img src="<?php echo htmlspecialchars($img_path); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                        <?php } else { ?>
                                                            <i class="bi bi-box-seam fs-5"></i>
                                                        <?php } ?>
                                                    </div>
                                                    <div>
                                                        <span class="fw-semibold text-dark d-block small"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                                        <span class="text-muted small">&#8369;<?php echo number_format(floatval($item['unit_price']), 2); ?> &times; <?php echo intval($item['quantity']); ?></span>
                                                    </div>
                                                </div>
                                                <span class="fw-bold text-dark small">&#8369;<?php echo number_format(floatval($item['line_total']), 2); ?></span>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>

                                <div class="pt-2">
                                    <div class="d-flex justify-content-between small text-muted mb-2">
                                        <span>Subtotal</span>
                                        <span class="fw-medium text-dark">&#8369;<?php echo number_format(floatval($order['subtotal']), 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted mb-3">
                                        <span>Shipping Fee</span>
                                        <span class="fw-medium text-dark">&#8369;<?php echo number_format(floatval($order['shipping_fee']), 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                        <span class="fw-bold text-dark fs-5 font-poppins">Total</span>
                                        <span class="fw-bold fs-5 font-poppins" style="color: #0a6e06;">&#8369;<?php echo number_format(floatval($order['total_amount']), 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                                <h5 class="fw-bold font-poppins text-dark mb-4">Delivery Details</h5>
                                <div class="d-flex flex-column gap-3 small">
                                    <div class="pb-2 border-bottom">
                                        <span class="text-muted d-block mb-1">Recipient</span>
                                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($order['full_name']); ?></span>
                                    </div>
                                    <div class="pb-2 border-bottom">
                                        <span class="text-muted d-block mb-1">Contact Number</span>
                                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($order['contact_number']); ?></span>
                                    </div>
                                    <div class="<?php echo !empty($order['order_notes']) ? 'pb-2 border-bottom' : ''; ?>">
                                        <span class="text-muted d-block mb-1">Delivery Address</span>
                                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                                    </div>
                                    <?php if (!empty($order['order_notes'])) { ?>
                                        <div>
                                            <span class="text-muted d-block mb-1">Order Notes</span>
                                            <span class="fw-medium text-dark"><?php echo htmlspecialchars($order['order_notes']); ?></span>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>

                            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                                <h5 class="fw-bold font-poppins text-dark mb-4">Payment Information</h5>
                                <div class="d-flex flex-column gap-3 small">
                                    <div class="pb-2 border-bottom">
                                        <span class="text-muted d-block mb-1">Payment Method</span>
                                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($payment_method_label); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-muted d-block mb-1">Payment Status</span>
                                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($payment_status_label); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-none d-lg-flex justify-content-start gap-2">
                        <?php if ($existing_return) { ?>
                            <a href="<?php echo htmlspecialchars($request_progress_url); ?>" class="btn btn-dark-custom px-4 py-2 rounded-3 fw-semibold small text-center text-decoration-none">
                                <i class="bi bi-arrow-return-left me-1"></i> Request Progress
                            </a>
                        <?php } elseif ($can_request_return) { ?>
                            <a href="<?php echo htmlspecialchars($return_refund_url); ?>" class="btn-dark-custom rounded-3 fw-semibold small text-center text-decoration-none">
                                Request Return / Refund
                            </a>
                        <?php } ?>
                        <a href="order-history.php" class="btn btn-outline-custom px-4 py-2 rounded-3 fw-semibold small text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i> Back to Order History
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php if ($order['order_status'] === 'pending') { ?>
    <div class="modal fade" id="cancelCustomerOrderModal" tabindex="-1" aria-labelledby="cancelCustomerOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4 p-3 bg-white">
                <form method="POST" action="order-details.php?order_id=<?php echo intval($order['order_id']); ?>">
                    <input type="hidden" name="cancel_order" value="1">
                    <div class="modal-header border-0 pb-0">
                        <h3 class="modal-title h4 fw-bold font-poppins text-dark" id="cancelCustomerOrderModalLabel">Cancel this order?</h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-2">
                        <p class="text-muted small mb-4">Are you sure you want to cancel this order? This action cannot be undone.</p>

                        <div class="bg-light rounded-3 p-3 mb-4">
                            <div class="d-flex justify-content-between align-items-center small">
                                <span class="text-muted">Order Number</span>
                                <span class="fw-semibold text-dark">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-sm-end">
                            <button type="submit" class="btn btn-palette-danger w-sm-100 px-4 py-2 rounded-3 fw-semibold small shadow-sm order-1 order-sm-2">
                                Yes, Cancel Order
                            </button>
                            <button type="button" class="btn btn-outline-custom w-sm-100 px-4 py-2 rounded-3 fw-semibold small shadow-sm order-2 order-sm-1" data-bs-dismiss="modal">
                                Keep Order
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php } ?>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>