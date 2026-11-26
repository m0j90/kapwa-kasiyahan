<?php
session_start();
$current_page = 'order';

date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/../../db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

$order_id = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    header('Location: order-list.php');
    exit();
}

$success_msg = isset($_SESSION['admin_success_msg']) ? $_SESSION['admin_success_msg'] : '';
unset($_SESSION['admin_success_msg']);
$error_msg = '';

$existing_rr_cols = [];
$col_res = mysqli_query($Connection, "SHOW COLUMNS FROM `return_requests`");
if ($col_res) {
    while ($col_row = mysqli_fetch_assoc($col_res)) {
        $existing_rr_cols[] = $col_row['Field'];
    }
    mysqli_free_result($col_res);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel_order'])) {
        $cancel_status = 'cancelled';
        $cancel_stmt = mysqli_prepare($Connection, "UPDATE orders SET order_status = ?, updated_at = NOW() WHERE order_id = ?");
        mysqli_stmt_bind_param($cancel_stmt, 'si', $cancel_status, $order_id);
        if (mysqli_stmt_execute($cancel_stmt)) {
            $_SESSION['admin_success_msg'] = "Order #" . $order_id . " has been successfully cancelled.";
            header('Location: order-list.php');
            exit();
        } else {
            $error_msg = "Failed to cancel order. Please try again.";
        }
        mysqli_stmt_close($cancel_stmt);
    } elseif (isset($_POST['update_return_status'])) {
        $new_req_status = $_POST['return_status'] ?? '';
        $allowed_req_statuses = ['submitted', 'under_review', 'approved', 'rejected', 'refunded'];
        $admin_id = intval($_SESSION['admin_id'] ?? 0);
        $admin_param = $admin_id > 0 ? $admin_id : null;

        if (in_array($new_req_status, $allowed_req_statuses, true)) {
            $now = date('Y-m-d H:i:s');
            $set_parts = [
                "return_status = ?",
                "reviewed_by = COALESCE(?, reviewed_by)",
                "reviewed_at = ?",
                "updated_at = ?"
            ];
            $bind_types = "siss";
            $bind_vals = [$new_req_status, $admin_param, $now, $now];

            $stage_col_map = [
                'under_review' => 'under_review_at',
                'approved' => 'approved_at',
                'rejected' => 'rejected_at',
                'refunded' => 'refunded_at'
            ];

            if (isset($stage_col_map[$new_req_status]) && in_array($stage_col_map[$new_req_status], $existing_rr_cols, true)) {
                $col_name = $stage_col_map[$new_req_status];
                $set_parts[] = "`{$col_name}` = COALESCE(`{$col_name}`, ?)";
                $bind_types .= "s";
                $bind_vals[] = $now;
            }

            $bind_types .= "i";
            $bind_vals[] = $order_id;

            $req_sql = "UPDATE return_requests SET " . implode(", ", $set_parts) . " WHERE order_id = ?";
            $req_stmt = mysqli_prepare($Connection, $req_sql);
            mysqli_stmt_bind_param($req_stmt, $bind_types, ...$bind_vals);

            if (mysqli_stmt_execute($req_stmt)) {
                if ($new_req_status === 'refunded') {
                    $ord_ref_stmt = mysqli_prepare($Connection, "
                        UPDATE orders
                        SET order_status = 'refunded', updated_at = ?
                        WHERE order_id = ?
                    ");
                    mysqli_stmt_bind_param($ord_ref_stmt, 'si', $now, $order_id);
                    mysqli_stmt_execute($ord_ref_stmt);
                    mysqli_stmt_close($ord_ref_stmt);
                }

                $formatted_req_status = ucwords(str_replace('_', ' ', $new_req_status));
                $_SESSION['admin_success_msg'] = "Return/refund request updated to {$formatted_req_status}.";
                mysqli_stmt_close($req_stmt);
                header('Location: order-details.php?order_id=' . $order_id);
                exit();
            } else {
                $error_msg = "Failed to update return/refund request status.";
                mysqli_stmt_close($req_stmt);
            }
        } else {
            $error_msg = "Invalid return/refund status selected.";
        }
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
        p.payment_status,
        p.transaction_reference
    FROM orders o
    LEFT JOIN payments p ON o.order_id = p.order_id
    WHERE o.order_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($ord_stmt, 'i', $order_id);
mysqli_stmt_execute($ord_stmt);
$ord_res = mysqli_stmt_get_result($ord_stmt);
if ($Row = mysqli_fetch_assoc($ord_res)) {
    $order = $Row;
}
mysqli_stmt_close($ord_stmt);

if (!$order) {
    header('Location: order-list.php');
    exit();
}

$return_request = null;
$rr_stmt = mysqli_prepare($Connection, "
    SELECT return_id, reason, description, return_status, refund_amount, requested_at, reviewed_at
    FROM return_requests
    WHERE order_id = ?
    ORDER BY return_id DESC
    LIMIT 1
");
mysqli_stmt_bind_param($rr_stmt, 'i', $order_id);
mysqli_stmt_execute($rr_stmt);
$rr_res = mysqli_stmt_get_result($rr_stmt);
$return_request = mysqli_fetch_assoc($rr_res);
mysqli_stmt_close($rr_stmt);

$items = [];
$items_stmt = mysqli_prepare($Connection, "
    SELECT 
        product_name,
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
    $items[] = $item;
}
mysqli_stmt_close($items_stmt);

$placed_date = date('F d, Y', strtotime($order['placed_at']));
$payment_method_label = ($order['payment_method'] === 'online_payment') ? 'Online Payment' : 'Cash on Delivery';
$payment_status_label = ($order['payment_method'] === 'online_payment') ? 'Paid (' . htmlspecialchars($order['transaction_reference'] ?? 'Online') . ')' : 'To be paid on delivery';

function getOrderBadgeInfo(string $order_status): array {
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

function getReturnBadgeInfo(string $return_status): array {
    switch ($return_status) {
        case 'under_review':
            return ['label' => 'Under Review', 'class' => 'badge-status-processing'];
        case 'approved':
            return ['label' => 'Approved', 'class' => 'badge-status-shipped'];
        case 'rejected':
            return ['label' => 'Rejected', 'class' => 'badge-status-cancelled'];
        case 'refunded':
            return ['label' => 'Refunded', 'class' => 'badge-status-refunded'];
        case 'submitted':
        default:
            return ['label' => 'Request Submitted', 'class' => 'badge-status-pending'];
    }
}

$current_badge = getOrderBadgeInfo($order['order_status']);
$return_badge = $return_request ? getReturnBadgeInfo($return_request['return_status']) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details — <?php echo htmlspecialchars($order['order_number']); ?></title>
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
                        <h2 class="fw-bold font-poppins text-dark mb-1">Order Details</h2>
                        <p class="text-muted small mb-0">#<?php echo htmlspecialchars($order['order_number']); ?> &middot; Placed on <?php echo $placed_date; ?></p>
                    </div>
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <a href="update-status.php?order_id=<?php echo $order_id; ?>" class="btn btn-dark-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm text-center text-decoration-none">
                            Update Status
                        </a>
                        <button type="button" class="btn btn-palette-danger px-4 py-2 rounded-3 fw-semibold small shadow-sm text-center text-white text-decoration-none" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                            Cancel Order
                        </button>
                        <a href="order-list.php" class="btn btn-outline-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm text-center text-decoration-none">
                            <i class="bi bi-arrow-left me-2"></i> Back
                        </a>
                    </div>
                </div>

                <?php if (!empty($success_msg)) { ?>
                    <div class="alert alert-success-custom d-flex align-items-center p-3 mb-4" role="alert">
                        <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                        <div><?php echo $success_msg; ?></div>
                    </div>
                <?php } ?>

                <?php if (!empty($error_msg)) { ?>
                    <div class="alert-danger-custom mb-4" role="alert">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span><?php echo htmlspecialchars($error_msg); ?></span>
                    </div>
                <?php } ?>

                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="card border shadow-sm rounded-4 p-4 bg-white mb-4">
                            <h3 class="h5 fw-bold font-poppins text-dark mb-3">Order Status</h3>
                            <div class="d-flex align-items-center gap-3">
                                <span class="text-muted small">Current status</span>
                                <span class="status-pill <?php echo $current_badge['class']; ?>">
                                    <?php echo $current_badge['label']; ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($return_request) { ?>
                            <div class="card border shadow-sm rounded-4 p-4 bg-white mb-4">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                    <h3 class="h5 fw-bold font-poppins text-dark mb-0">Return / Refund Request</h3>
                                    <span class="status-pill <?php echo $return_badge['class']; ?>">
                                        <?php echo htmlspecialchars($return_badge['label']); ?>
                                    </span>
                                </div>

                                <div class="d-flex flex-column gap-3 small mb-4">
                                    <div class="d-flex justify-content-between align-items-center gap-4 pb-2 border-bottom">
                                        <span class="text-muted flex-shrink-0">Request #</span>
                                        <span class="fw-semibold text-dark text-end">#RR-<?php echo str_pad(intval($return_request['return_id']), 4, '0', STR_PAD_LEFT); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-start gap-4 pb-2 border-bottom">
                                        <span class="text-muted flex-shrink-0">Reason</span>
                                        <span class="fw-semibold text-dark text-end lh-base"><?php echo htmlspecialchars($return_request['reason']); ?></span>
                                    </div>
                                    <?php if (!empty($return_request['description'])) { ?>
                                        <div class="d-flex justify-content-between align-items-start gap-4 pb-2 border-bottom">
                                            <span class="text-muted flex-shrink-0">Details</span>
                                            <span class="fw-normal text-dark text-end lh-base" style="max-width: 72%;"><?php echo nl2br(htmlspecialchars($return_request['description'])); ?></span>
                                        </div>
                                    <?php } ?>
                                    <?php if ($return_request['refund_amount'] !== null) { ?>
                                        <div class="d-flex justify-content-between align-items-center gap-4 pb-2 border-bottom">
                                            <span class="text-muted flex-shrink-0">Refund Amount</span>
                                            <span class="fw-bold text-dark text-end">&#8369;<?php echo number_format(floatval($return_request['refund_amount']), 2); ?></span>
                                        </div>
                                    <?php } ?>
                                    <div class="d-flex justify-content-between align-items-center gap-4">
                                        <span class="text-muted flex-shrink-0">Submitted On</span>
                                        <span class="fw-semibold text-dark text-end"><?php echo date('M j, Y • g:i A', strtotime($return_request['requested_at'])); ?></span>
                                    </div>
                                </div>

                                <form method="POST" action="order-details.php?order_id=<?php echo $order_id; ?>" class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center">
                                    <input type="hidden" name="update_return_status" value="1">
                                    <select name="return_status" class="form-select form-control-custom bg-white" style="max-width: 240px;">
                                        <option value="submitted" <?php echo ($return_request['return_status'] === 'submitted') ? 'selected' : ''; ?>>Request Submitted</option>
                                        <option value="under_review" <?php echo ($return_request['return_status'] === 'under_review') ? 'selected' : ''; ?>>Under Review</option>
                                        <option value="approved" <?php echo ($return_request['return_status'] === 'approved') ? 'selected' : ''; ?>>Approved</option>
                                        <option value="rejected" <?php echo ($return_request['return_status'] === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                        <option value="refunded" <?php echo ($return_request['return_status'] === 'refunded') ? 'selected' : ''; ?>>Refunded</option>
                                    </select>
                                    <button type="submit" class="btn btn-dark-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm">
                                        Update Request
                                    </button>
                                </form>
                            </div>
                        <?php } ?>

                        <div class="card border shadow-sm rounded-4 p-4 bg-white">
                            <h3 class="h5 fw-bold font-poppins text-dark mb-4">Items</h3>
                            
                            <div class="d-flex flex-column gap-3 mb-4">
                                <?php foreach ($items as $item) { ?>
                                    <div class="d-flex justify-content-between align-items-center pb-2 border-bottom small">
                                        <div>
                                            <span class="fw-semibold text-dark d-block"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                            <span class="text-muted">&times; <?php echo intval($item['quantity']); ?></span>
                                        </div>
                                        <span class="fw-semibold text-dark">&#8369;<?php echo number_format(floatval($item['line_total']), 2); ?></span>
                                    </div>
                                <?php } ?>
                            </div>

                            <div class="d-flex flex-column gap-2 small pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Subtotal</span>
                                    <span class="fw-semibold text-dark">&#8369;<?php echo number_format(floatval($order['subtotal'] ?? 0), 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Shipping Fee</span>
                                    <span class="fw-semibold text-dark">&#8369;<?php echo number_format(floatval($order['shipping_fee'] ?? 0), 2); ?></span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-3">
                                <span class="fw-bold text-dark fs-5">Total</span>
                                <span class="fw-bold text-success fs-5">&#8369;<?php echo number_format(floatval($order['total_amount']), 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card border shadow-sm rounded-4 p-4 bg-white">
                            <h3 class="h5 fw-bold font-poppins text-dark mb-4">Customer & Delivery</h3>
                            <div class="d-flex flex-column gap-3 small">
                                <div class="pb-2 border-bottom">
                                    <span class="text-muted d-block mb-1">Customer</span>
                                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($order['full_name']); ?></span>
                                </div>
                                <div class="pb-2 border-bottom">
                                    <span class="text-muted d-block mb-1">Contact</span>
                                    <span class="fw-semibold text-dark"><?php echo htmlspecialchars($order['contact_number']); ?></span>
                                </div>
                                <div class="pb-2 border-bottom">
                                    <span class="text-muted d-block mb-1">Address</span>
                                    <span class="fw-semibold text-dark"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                                </div>
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
            </main>
        </div>
    </div>

    <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4 p-3 bg-white">
                <form method="POST" action="order-details.php?order_id=<?php echo $order_id; ?>">
                    <input type="hidden" name="cancel_order" value="1">
                    <div class="modal-header border-0 pb-0">
                        <h3 class="modal-title h4 fw-bold font-poppins text-dark" id="cancelOrderModalLabel">Cancel this order?</h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-2">
                        <p class="text-muted small mb-4">The order status will change to Cancelled and the customer will see it in Order History.</p>
                        
                        <div class="bg-light rounded-3 p-3 mb-4">
                            <div class="d-flex justify-content-between align-items-center small">
                                <span class="text-muted">Order</span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>