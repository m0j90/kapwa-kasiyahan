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

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['order_status'] ?? '';
    $allowed_statuses = ['pending', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'completed', 'cancelled', 'refunded'];

    if (in_array($new_status, $allowed_statuses, true)) {
        $now = date('Y-m-d H:i:s');

        switch ($new_status) {
            case 'processing':
                $sql = "UPDATE orders SET order_status = ?, processing_at = COALESCE(processing_at, ?), updated_at = ? WHERE order_id = ?";
                $update_stmt = mysqli_prepare($Connection, $sql);
                mysqli_stmt_bind_param($update_stmt, 'sssi', $new_status, $now, $now, $order_id);
                break;

            case 'shipped':
                $sql = "UPDATE orders SET order_status = ?, shipped_at = COALESCE(shipped_at, ?), updated_at = ? WHERE order_id = ?";
                $update_stmt = mysqli_prepare($Connection, $sql);
                mysqli_stmt_bind_param($update_stmt, 'sssi', $new_status, $now, $now, $order_id);
                break;

            case 'out_for_delivery':
            case 'delivered':
                $sql = "UPDATE orders SET order_status = ?, delivered_at = COALESCE(delivered_at, ?), updated_at = ? WHERE order_id = ?";
                $update_stmt = mysqli_prepare($Connection, $sql);
                mysqli_stmt_bind_param($update_stmt, 'sssi', $new_status, $now, $now, $order_id);
                break;

            case 'completed':
                $sql = "UPDATE orders SET order_status = ?, completed_at = COALESCE(completed_at, ?), updated_at = ? WHERE order_id = ?";
                $update_stmt = mysqli_prepare($Connection, $sql);
                mysqli_stmt_bind_param($update_stmt, 'sssi', $new_status, $now, $now, $order_id);
                break;

            default:
                $sql = "UPDATE orders SET order_status = ?, updated_at = ? WHERE order_id = ?";
                $update_stmt = mysqli_prepare($Connection, $sql);
                mysqli_stmt_bind_param($update_stmt, 'ssi', $new_status, $now, $order_id);
                break;
        }

        if (mysqli_stmt_execute($update_stmt)) {
            if ($new_status === 'refunded') {
                $has_refunded_at = false;
                $col_chk = mysqli_query($Connection, "SHOW COLUMNS FROM `return_requests` LIKE 'refunded_at'");
                if ($col_chk) {
                    $has_refunded_at = (mysqli_num_rows($col_chk) > 0);
                    mysqli_free_result($col_chk);
                }

                $sync_sql = $has_refunded_at
                    ? "UPDATE return_requests SET return_status = 'refunded', reviewed_at = COALESCE(reviewed_at, ?), refunded_at = COALESCE(refunded_at, ?), updated_at = ? WHERE order_id = ? AND return_status != 'rejected'"
                    : "UPDATE return_requests SET return_status = 'refunded', reviewed_at = COALESCE(reviewed_at, ?), updated_at = ? WHERE order_id = ? AND return_status != 'rejected'";

                $sync_rr = mysqli_prepare($Connection, $sync_sql);
                if ($has_refunded_at) {
                    mysqli_stmt_bind_param($sync_rr, 'sssi', $now, $now, $now, $order_id);
                } else {
                    mysqli_stmt_bind_param($sync_rr, 'ssi', $now, $now, $order_id);
                }
                mysqli_stmt_execute($sync_rr);
                mysqli_stmt_close($sync_rr);
            }

            $formatted_name = ucwords(str_replace('_', ' ', $new_status));
            $_SESSION['admin_success_msg'] = "Status updated to {$formatted_name}. The customer sees the change in Order Tracking.";
            mysqli_stmt_close($update_stmt);
            header('Location: order-details.php?order_id=' . $order_id);
            exit();
        } else {
            $error_msg = "Failed to update order status. Please try again.";
            mysqli_stmt_close($update_stmt);
        }
    } else {
        $error_msg = "Invalid status selected.";
    }
}

$order = null;
$stmt = mysqli_prepare($Connection, "SELECT order_id, order_number, full_name, order_status FROM orders WHERE order_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $order_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($Row = mysqli_fetch_assoc($res)) {
    $order = $Row;
}
mysqli_stmt_close($stmt);

if (!$order) {
    header('Location: order-list.php');
    exit();
}

$current_status_label = ucwords(str_replace('_', ' ', $order['order_status']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Order Status — <?php echo htmlspecialchars($order['order_number']); ?></title>
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
                        <h2 class="fw-bold font-poppins text-dark mb-1">Update Order Status</h2>
                        <p class="text-muted small mb-0">#<?php echo htmlspecialchars($order['order_number']); ?> &middot; <?php echo htmlspecialchars($order['full_name']); ?></p>
                    </div>
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <a href="order-details.php?order_id=<?php echo $order_id; ?>" class="btn btn-outline-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm text-center text-decoration-none">
                            <i class="bi bi-arrow-left me-2"></i> Back
                        </a>
                    </div>
                </div>

                <?php if (!empty($error_msg)) { ?>
                    <div class="alert-danger-custom mb-4" role="alert">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span><?php echo htmlspecialchars($error_msg); ?></span>
                    </div>
                <?php } ?>

                <div class="card border shadow-sm rounded-4 p-4 bg-white">
                    <form method="POST" action="update-status.php?order_id=<?php echo $order_id; ?>">
                        <h3 class="h5 fw-bold font-poppins text-dark mb-3">Change Status</h3>
                        
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <span class="text-muted small">Current status</span>
                            <span class="badge bg-secondary text-white px-3 py-2 rounded-2 fw-semibold text-uppercase small">
                                <?php echo htmlspecialchars($current_status_label); ?>
                            </span>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-custom small" for="statusSelect">New Status</label>
                            <select name="order_status" id="statusSelect" class="form-select form-control-custom bg-white">
                                <option value="pending" <?php echo ($order['order_status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo ($order['order_status'] === 'processing') ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo ($order['order_status'] === 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                <option value="out_for_delivery" <?php echo (in_array($order['order_status'], ['out_for_delivery', 'delivered'], true)) ? 'selected' : ''; ?>>Delivery</option>
                                <option value="completed" <?php echo ($order['order_status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo ($order['order_status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="refunded" <?php echo ($order['order_status'] === 'refunded') ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <span class="form-label-custom small d-block mb-2">Status options</span>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="status-pill badge-status-pending" onclick="setStatus('pending')">Pending</button>
                                <button type="button" class="status-pill badge-status-processing" onclick="setStatus('processing')">Processing</button>
                                <button type="button" class="status-pill badge-status-shipped" onclick="setStatus('shipped')">Shipped</button>
                                <button type="button" class="status-pill badge-status-out_for_delivery" onclick="setStatus('out_for_delivery')">Delivery</button>
                                <button type="button" class="status-pill badge-status-completed" onclick="setStatus('completed')">Completed</button>
                                <button type="button" class="status-pill badge-status-cancelled" onclick="setStatus('cancelled')">Cancelled</button>
                                <button type="button" class="status-pill badge-status-refunded" onclick="setStatus('refunded')">Refunded</button>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <button type="submit" class="btn btn-dark-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm text-center">
                                Save Status
                            </button>
                            <a href="order-details.php?order_id=<?php echo $order_id; ?>" class="btn btn-outline-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm text-center text-decoration-none">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        function setStatus(statusVal) {
            document.getElementById('statusSelect').value = statusVal;
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>