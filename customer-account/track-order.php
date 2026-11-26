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

$order = null;
if ($order_id > 0) {
    $stmt = mysqli_prepare($Connection, "SELECT * FROM orders WHERE order_id = ? AND customer_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ii', $order_id, $customer_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($Row = mysqli_fetch_assoc($res)) {
        $order = $Row;
    }
    mysqli_stmt_close($stmt);
}

if (!$order) {
    $stmt = mysqli_prepare($Connection, "
        SELECT * FROM orders
        WHERE customer_id = ?
        ORDER BY FIELD(order_status, 'pending', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'completed', 'cancelled', 'refunded'), placed_at DESC
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, 'i', $customer_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($Row = mysqli_fetch_assoc($res)) {
        $order = $Row;
    }
    mysqli_stmt_close($stmt);
}

$existing_return = null;
if ($order) {
    $rr_stmt = mysqli_prepare($Connection, "
        SELECT return_id, return_status
        FROM return_requests
        WHERE order_id = ? AND customer_id = ?
        ORDER BY return_id DESC
        LIMIT 1
    ");
    $active_oid = intval($order['order_id']);
    mysqli_stmt_bind_param($rr_stmt, 'ii', $active_oid, $customer_id);
    mysqli_stmt_execute($rr_stmt);
    $rr_res = mysqli_stmt_get_result($rr_stmt);
    $existing_return = mysqli_fetch_assoc($rr_res);
    mysqli_stmt_close($rr_stmt);
}

$feedback_msg = '';
$feedback_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $order) {
    $action = $_POST['order_action'] ?? '';
    $target_order_id = intval($order['order_id']);

    if ($action === 'mark_received' && in_array($order['order_status'], ['shipped', 'out_for_delivery', 'delivered'], true)) {
        $now = date('Y-m-d H:i:s');
        $upd = mysqli_prepare($Connection, "
            UPDATE orders
            SET order_status = 'completed',
                delivered_at = COALESCE(delivered_at, ?),
                completed_at = COALESCE(completed_at, ?),
                updated_at = ?
            WHERE order_id = ? AND customer_id = ?
        ");
        mysqli_stmt_bind_param($upd, 'sssii', $now, $now, $now, $target_order_id, $customer_id);
        if (mysqli_stmt_execute($upd)) {
            $order['order_status'] = 'completed';
            $order['delivered_at'] = $order['delivered_at'] ?: $now;
            $order['completed_at'] = $order['completed_at'] ?: $now;
            $feedback_msg = 'Order marked as received and completed.';
            $feedback_type = 'success';
        } else {
            $feedback_msg = 'Unable to update order status right now. Please try again.';
            $feedback_type = 'danger';
        }
        mysqli_stmt_close($upd);
    }
}

$order_items = [];
if ($order) {
    $item_stmt = mysqli_prepare($Connection, "SELECT product_name, product_image, unit_price, quantity, line_total FROM order_items WHERE order_id = ?");
    mysqli_stmt_bind_param($item_stmt, 'i', $order['order_id']);
    mysqli_stmt_execute($item_stmt);
    $item_res = mysqli_stmt_get_result($item_stmt);
    while ($item = mysqli_fetch_assoc($item_res)) {
        $order_items[] = $item;
    }
    mysqli_stmt_close($item_stmt);
}

$status = $order ? $order['order_status'] : 'pending';

switch ($status) {
    case 'processing':
        $current_step = 2;
        $badge = ['label' => 'Processing', 'class' => 'badge-status-processing'];
        $page_heading = 'Order Tracking';
        $page_subtitle = 'Follow your order from Pending to Completed.';
        $breadcrumb_current = 'Track Order';
        break;
    case 'shipped':
        $current_step = 3;
        $badge = ['label' => 'Shipped', 'class' => 'badge-status-shipped'];
        $page_heading = 'Shipping Status';
        $page_subtitle = 'Your order has left the store.';
        $breadcrumb_current = 'Shipping';
        break;
    case 'out_for_delivery':
        $current_step = 4;
        $badge = ['label' => 'Out for Delivery', 'class' => 'badge-status-out_for_delivery'];
        $page_heading = 'Delivery Status';
        $page_subtitle = 'Your order is on its way to you.';
        $breadcrumb_current = 'Delivery';
        break;
    case 'delivered':
        $current_step = 4;
        $badge = ['label' => 'Delivered', 'class' => 'badge-status-delivered'];
        $page_heading = 'Delivery Status';
        $page_subtitle = 'Your order has been delivered.';
        $breadcrumb_current = 'Delivery';
        break;
    case 'completed':
        $current_step = 5;
        $badge = ['label' => 'Completed', 'class' => 'badge-status-completed'];
        $page_heading = 'Completed Order';
        $page_subtitle = 'This order has been delivered.';
        $breadcrumb_current = $order ? ('#' . htmlspecialchars($order['order_number'])) : 'Completed Order';
        break;
    case 'cancelled':
        $current_step = 1;
        $badge = ['label' => 'Cancelled', 'class' => 'badge-status-cancelled'];
        $page_heading = 'Order Tracking';
        $page_subtitle = 'Follow your order from Pending to Completed.';
        $breadcrumb_current = 'Track Order';
        break;
    case 'pending':
    default:
        $current_step = 1;
        $badge = ['label' => 'Pending', 'class' => 'badge-status-pending'];
        $page_heading = 'Order Tracking';
        $page_subtitle = 'Follow your order from Pending to Completed.';
        $breadcrumb_current = 'Track Order';
        break;
}

$is_delivery_view = in_array($status, ['out_for_delivery', 'delivered'], true);
$is_completed_view = ($status === 'completed');

$placed_datetime = ($order && !empty($order['placed_at']))
    ? date('M j, Y • g:i A', strtotime($order['placed_at']))
    : '—';

$processing_datetime = ($order && !empty($order['processing_at']))
    ? date('M j, Y • g:i A', strtotime($order['processing_at']))
    : '—';

$shipped_datetime = ($order && !empty($order['shipped_at']))
    ? date('M j, Y • g:i A', strtotime($order['shipped_at']))
    : '—';

$delivered_datetime = ($order && !empty($order['delivered_at']))
    ? date('M j, Y • g:i A', strtotime($order['delivered_at']))
    : '—';

$completed_datetime = ($order && !empty($order['completed_at']))
    ? date('M j, Y • g:i A', strtotime($order['completed_at']))
    : '—';

$delivered_on_date = ($order && !empty($order['delivered_at']))
    ? date('M j, Y', strtotime($order['delivered_at']))
    : (($order && !empty($order['completed_at']))
        ? date('M j, Y', strtotime($order['completed_at']))
        : '—');

$return_refund_url = $order ? ('return-refund.php?order_id=' . intval($order['order_id'])) : 'return-refund.php';
$request_progress_url = $order ? ('request-progress.php?order_id=' . intval($order['order_id'])) : 'request-progress.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_heading); ?></title>
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
                    <?php if ($is_completed_view) { ?>
                        <li class="breadcrumb-item"><a href="order-history.php" class="text-decoration-none text-success">Order History</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo $breadcrumb_current; ?></li>
                    <?php } elseif ($is_delivery_view || $status === 'shipped') { ?>
                        <li class="breadcrumb-item"><a href="track-order.php<?php echo $order ? '?order_id=' . intval($order['order_id']) : ''; ?>" class="text-decoration-none text-success">Track Order</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo $breadcrumb_current; ?></li>
                    <?php } else { ?>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo $breadcrumb_current; ?></li>
                    <?php } ?>
                </ol>
            </nav>
            <h1 class="fw-bold display-5 font-poppins text-dark"><?php echo htmlspecialchars($page_heading); ?></h1>
            <p class="text-muted mb-0"><?php echo htmlspecialchars($page_subtitle); ?></p>
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
                            <a href="order-history.php" class="list-group-item list-group-item-action border-0 rounded-3 text-secondary py-2 px-3 <?php echo $is_completed_view ? 'fw-semibold active' : ''; ?>" style="<?php echo $is_completed_view ? 'background-color: #eef1e9; color: #0a6e06;' : ''; ?>">Order History</a>
                            <a href="track-order.php" class="list-group-item list-group-item-action border-0 rounded-3 text-secondary py-2 px-3 <?php echo !$is_completed_view ? 'fw-semibold active' : ''; ?>" style="<?php echo !$is_completed_view ? 'background-color: #eef1e9; color: #0a6e06;' : ''; ?>">Track Order</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="d-block d-lg-none mb-3">
                        <?php if ($is_completed_view) { ?>
                            <a href="order-history.php" class="text-decoration-none text-success fw-semibold small">
                                <i class="bi bi-arrow-left me-1"></i> Order History
                            </a>
                        <?php } elseif ($is_delivery_view || $status === 'shipped') { ?>
                            <a href="track-order.php<?php echo $order ? '?order_id=' . intval($order['order_id']) : ''; ?>" class="text-decoration-none text-success fw-semibold small">
                                <i class="bi bi-arrow-left me-1"></i> Order Tracking
                            </a>
                        <?php } else { ?>
                            <a href="account-settings.php" class="text-decoration-none text-success fw-semibold small">
                                <i class="bi bi-arrow-left me-1"></i> Account Settings
                            </a>
                        <?php } ?>
                    </div>

                    <?php if (!empty($feedback_msg)) { ?>
                        <div class="alert <?php echo ($feedback_type === 'success') ? 'alert-success-custom' : 'alert-danger-custom'; ?> mb-4" role="alert">
                            <i class="bi <?php echo ($feedback_type === 'success') ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'; ?>"></i>
                            <span><?php echo htmlspecialchars($feedback_msg); ?></span>
                        </div>
                    <?php } ?>

                    <?php if (!$order) { ?>
                        <div class="card border-0 shadow-sm rounded-4 p-5 bg-white text-center">
                            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center text-success mx-auto mb-3" style="width: 60px; height: 60px;">
                                <i class="bi bi-geo-alt fs-3"></i>
                            </div>
                            <h5 class="fw-bold font-poppins text-dark mb-1">No Active Orders to Track</h5>
                            <p class="text-muted small mb-4">You do not have any active orders currently in progress.</p>
                            <a href="order-history.php" class="btn btn-dark-custom btn-sm px-4 py-2 rounded-3 fw-semibold text-white text-decoration-none d-inline-block">View Order History</a>
                        </div>
                    <?php } else {
                        $payment_label = ($order['payment_method'] === 'online_payment') ? 'Online Payment' : 'Cash on Delivery';
                    ?>

                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                                <div>
                                    <h4 class="fw-bold font-poppins text-dark mb-1">Order #<?php echo htmlspecialchars($order['order_number']); ?></h4>
                                    <p class="text-muted small mb-0">
                                        Placed on <?php echo $placed_datetime; ?> &middot; <?php echo $payment_label; ?> &middot; Total &#8369;<?php echo number_format(floatval($order['total_amount']), 2); ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="status-pill <?php echo $badge['class']; ?> fs-6 px-3 py-2">
                                        <?php echo htmlspecialchars($badge['label']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?php if ($status === 'out_for_delivery') { ?>
                        <div class="tracking-banner-delivery mb-4">
                            <div class="d-flex align-items-center gap-3">
                                <span class="tracking-banner-dot-delivery"></span>
                                <span class="fw-semibold small" style="color: #011f01;">Out for delivery. Please keep your phone available for the courier.</span>
                            </div>
                        </div>
                        <?php } ?>

                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4 order-progress-container">
                            <h5 class="fw-bold font-poppins text-dark mb-4">Order Progress</h5>
                            <div class="progress-stepper-track">
                                <?php
                                $steps = [
                                    1 => 'Pending',
                                    2 => 'Processing',
                                    3 => 'Shipped',
                                    4 => 'Delivery',
                                    5 => 'Completed'
                                ];
                                foreach ($steps as $num => $label) {
                                    $is_completed = ($current_step > $num);
                                    $is_active = ($current_step === $num);

                                    if ($is_completed) {
                                        $item_class = 'completed';
                                    } elseif ($is_active) {
                                        $item_class = 'active';
                                    } else {
                                        $item_class = '';
                                    }

                                    if ($num === 1) {
                                        $sub_text = $placed_datetime;
                                    } elseif ($num === 2) {
                                        $sub_text = $processing_datetime;
                                    } elseif ($num === 3) {
                                        $sub_text = $shipped_datetime;
                                    } elseif ($num === 4) {
                                        $sub_text = $delivered_datetime;
                                    } else {
                                        $sub_text = $completed_datetime;
                                    }
                                ?>
                                <div class="progress-step-item <?php echo $item_class; ?>">
                                    <div class="progress-step-circle">
                                        <?php echo $is_completed ? '<i class="bi bi-check-lg"></i>' : $num; ?>
                                    </div>
                                    <div class="progress-step-info">
                                        <span class="progress-step-label"><?php echo htmlspecialchars($label); ?></span>
                                    </div>
                                    <span class="progress-step-sub"><?php echo htmlspecialchars($sub_text); ?></span>
                                </div>
                                <?php } ?>
                            </div>

                            <?php if ($status === 'completed') { ?>
                            <div class="tracking-banner-completed d-flex align-items-center gap-3 mb-0 mt-2">
                                <i class="bi bi-check-circle-fill fs-5 flex-shrink-0" style="color: #0a6e06;"></i>
                                <span class="small fw-semibold" style="color: #011f01;">Order completed on <?php echo htmlspecialchars($completed_datetime); ?>. Thank you for shopping with us!</span>
                            </div>
                            <?php } elseif ($status === 'delivered') { ?>
                            <div class="tracking-banner-completed d-flex align-items-center gap-3 mb-0 mt-2">
                                <i class="bi bi-box-seam-fill fs-5 flex-shrink-0" style="color: #0a6e06;"></i>
                                <span class="small fw-semibold" style="color: #011f01;">Your order has been delivered on <?php echo htmlspecialchars($delivered_datetime); ?>.</span>
                            </div>
                            <?php } elseif ($status === 'shipped') { ?>
                            <div class="tracking-banner-completed d-flex align-items-center gap-3 mb-0 mt-2">
                                <i class="bi bi-truck fs-5 flex-shrink-0" style="color: #0a6e06;"></i>
                                <span class="small fw-semibold" style="color: #011f01;">Your order has left the store on <?php echo htmlspecialchars($shipped_datetime); ?> and is now on its way.</span>
                            </div>
                            <?php } elseif ($status === 'cancelled') { ?>
                            <div class="tracking-banner-danger d-flex align-items-center gap-3 mb-0 mt-2">
                                <i class="bi bi-x-circle-fill fs-5 flex-shrink-0" style="color: #e74c3c;"></i>
                                <span class="small fw-semibold" style="color: #922b21;">This order has been cancelled.</span>
                            </div>
                            <?php } elseif ($status === 'pending' || $status === 'processing') { ?>
                            <div class="tracking-banner-warning d-flex align-items-center gap-3 mb-0 mt-2">
                                <i class="bi bi-clock-history fs-5 flex-shrink-0" style="color: #6b6012;"></i>
                                <span class="small fw-semibold" style="color: #6b6012;">The store is preparing your order. Delivery status updates will appear as your order progresses.</span>
                            </div>
                            <?php } ?>
                        </div>

                        <?php if ($is_completed_view) { ?>
                            <div class="row g-4">
                                <div class="col-lg-6 order-2 order-lg-1">
                                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                                        <div>
                                            <h5 class="fw-bold font-poppins text-dark mb-4">Items in this Order</h5>
                                            <div class="d-flex flex-column gap-3 small mb-3">
                                                <?php foreach ($order_items as $item) { ?>
                                                <div class="d-flex justify-content-between align-items-center pb-2">
                                                    <div class="d-flex align-items-center gap-1">
                                                        <span class="text-dark fw-medium"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                                        <span class="text-muted">&times; <?php echo intval($item['quantity']); ?></span>
                                                    </div>
                                                    <span class="fw-semibold text-dark">&#8369;<?php echo number_format(floatval($item['line_total']), 2); ?></span>
                                                </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                        <div class="pt-3 border-top">
                                            <div class="d-flex justify-content-between small text-muted mb-1">
                                                <span>Subtotal</span>
                                                <span>&#8369;<?php echo number_format(floatval($order['subtotal']), 2); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between small text-muted mb-3">
                                                <span>Shipping Fee</span>
                                                <span>&#8369;<?php echo number_format(floatval($order['shipping_fee']), 2); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                                <span class="fw-bold text-dark font-poppins">Total</span>
                                                <span class="fw-bold fs-5 font-poppins" style="color: #0a6e06;">&#8369;<?php echo number_format(floatval($order['total_amount']), 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6 order-1 order-lg-2">
                                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                                        <h5 class="fw-bold font-poppins text-dark mb-4">Delivery Summary</h5>
                                        <div class="d-flex flex-column gap-3 small">
                                            <div class="d-flex justify-content-between align-items-start pb-2 border-bottom">
                                                <span class="text-muted">Delivered On</span>
                                                <span class="fw-semibold text-dark text-end"><?php echo htmlspecialchars($delivered_on_date); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-start pb-2 border-bottom">
                                                <span class="text-muted">Payment</span>
                                                <span class="fw-semibold text-dark text-end"><?php echo htmlspecialchars($payment_label); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-start">
                                                <span class="text-muted">Address</span>
                                                <span class="fw-semibold text-dark text-end" style="max-width: 220px;"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex flex-column flex-sm-row gap-2 align-items-stretch align-items-sm-center">
                                <a href="order-history.php" class="btn btn-outline-custom px-4 py-2 rounded-3 fw-semibold small text-decoration-none text-center">
                                    Back to Order History
                                </a>
                                <?php if ($existing_return) { ?>
                                    <a href="<?php echo htmlspecialchars($request_progress_url); ?>" class="btn btn-dark-custom px-4 py-2 rounded-3 fw-semibold small text-center text-decoration-none">
                                        Request Progress
                                    </a>
                                <?php } else { ?>
                                    <a href="<?php echo htmlspecialchars($return_refund_url); ?>" class="btn-refund-outline rounded-3 fw-semibold small text-center text-decoration-none">
                                        Request Return / Refund
                                    </a>
                                <?php } ?>
                            </div>

                        <?php } elseif ($is_delivery_view) { ?>
                            <div class="row g-4">
                                <div class="col-lg-6">
                                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                                        <h5 class="fw-bold font-poppins text-dark mb-4">Delivery Details</h5>
                                        <div class="d-flex flex-column gap-3 small">
                                            <div class="d-flex justify-content-between align-items-center pb-2 border-bottom">
                                                <span class="text-muted">Status</span>
                                                <span class="status-pill <?php echo $badge['class']; ?>"><?php echo htmlspecialchars($badge['label']); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-start pb-2 border-bottom">
                                                <span class="text-muted">Address</span>
                                                <span class="fw-semibold text-dark text-end" style="max-width: 220px;"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-start pb-2 border-bottom">
                                                <span class="text-muted">Contact</span>
                                                <span class="fw-semibold text-dark text-end"><?php echo htmlspecialchars($order['contact_number']); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-start">
                                                <span class="text-muted">Delivered At</span>
                                                <span class="fw-semibold text-dark text-end"><?php echo htmlspecialchars($delivered_datetime); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                                        <div>
                                            <h5 class="fw-bold font-poppins text-dark mb-4">Items in this Order</h5>
                                            <div class="d-flex flex-column gap-3 small mb-3">
                                                <?php foreach ($order_items as $item) { ?>
                                                <div class="d-flex justify-content-between align-items-center pb-2">
                                                    <div class="d-flex align-items-center gap-1">
                                                        <span class="text-dark fw-medium"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                                        <span class="text-muted">&times; <?php echo intval($item['quantity']); ?></span>
                                                    </div>
                                                    <span class="fw-semibold text-dark">&#8369;<?php echo number_format(floatval($item['line_total']), 2); ?></span>
                                                </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                        <div class="pt-3 border-top">
                                            <div class="d-flex justify-content-between small text-muted mb-1">
                                                <span>Subtotal</span>
                                                <span>&#8369;<?php echo number_format(floatval($order['subtotal']), 2); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between small text-muted mb-3">
                                                <span>Shipping Fee</span>
                                                <span>&#8369;<?php echo number_format(floatval($order['shipping_fee']), 2); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                                <span class="fw-bold text-dark font-poppins">Total</span>
                                                <span class="fw-bold fs-5 font-poppins" style="color: #0a6e06;">&#8369;<?php echo number_format(floatval($order['total_amount']), 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex flex-column flex-sm-row gap-2 align-items-stretch align-items-sm-center">
                                <form method="POST" action="track-order.php?order_id=<?php echo intval($order['order_id']); ?>" class="m-0">
                                    <button type="submit" name="order_action" value="mark_received" class="btn btn-dark-custom w-100 px-4 py-2 rounded-3 fw-semibold small text-center">
                                        Order Received
                                    </button>
                                </form>
                            </div>

                        <?php } else { ?>
                            <div class="row g-4">
                                <div class="col-lg-6">
                                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                                        <h5 class="fw-bold font-poppins text-dark mb-4">Delivery Information</h5>
                                        <div class="d-flex flex-column gap-3 small">
                                            <div class="d-flex justify-content-between pb-2 border-bottom">
                                                <span class="text-muted">Name</span>
                                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($order['full_name']); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between pb-2 border-bottom">
                                                <span class="text-muted">Contact</span>
                                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($order['contact_number']); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between pb-2 border-bottom">
                                                <span class="text-muted">Address</span>
                                                <span class="fw-semibold text-dark text-end" style="max-width: 220px;"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                                        <div>
                                            <h5 class="fw-bold font-poppins text-dark mb-4">Items in this Order</h5>
                                            <div class="d-flex flex-column gap-3 small mb-3">
                                                <?php foreach ($order_items as $item) { ?>
                                                <div class="d-flex justify-content-between align-items-center pb-2">
                                                    <div class="d-flex align-items-center gap-1">
                                                        <span class="text-dark fw-medium"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                                        <span class="text-muted">&times; <?php echo intval($item['quantity']); ?></span>
                                                    </div>
                                                    <span class="fw-semibold text-dark">&#8369;<?php echo number_format(floatval($item['line_total']), 2); ?></span>
                                                </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                        <div class="pt-3 border-top">
                                            <div class="d-flex justify-content-between small text-muted mb-1">
                                                <span>Subtotal</span>
                                                <span>&#8369;<?php echo number_format(floatval($order['subtotal']), 2); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between small text-muted mb-3">
                                                <span>Shipping Fee</span>
                                                <span>&#8369;<?php echo number_format(floatval($order['shipping_fee']), 2); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                                <span class="fw-bold text-dark font-poppins">Total</span>
                                                <span class="fw-bold fs-5 font-poppins" style="color: #0a6e06;">&#8369;<?php echo number_format(floatval($order['total_amount']), 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-between align-items-center">
                                <a href="order-history.php" class="btn btn-outline-custom px-4 py-2 rounded-3 fw-semibold small text-decoration-none">
                                    <i class="bi bi-arrow-left me-1"></i> Back to Order History
                                </a>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>