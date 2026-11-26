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
    $ord_stmt = mysqli_prepare($Connection, "
        SELECT order_id, order_number, order_status, total_amount
        FROM orders
        WHERE order_id = ? AND customer_id = ?
        LIMIT 1
    ");
    mysqli_stmt_bind_param($ord_stmt, 'ii', $order_id, $customer_id);
    mysqli_stmt_execute($ord_stmt);
    $ord_res = mysqli_stmt_get_result($ord_stmt);
    if ($Row = mysqli_fetch_assoc($ord_res)) {
        $order = $Row;
    }
    mysqli_stmt_close($ord_stmt);
}

$order_number_input = $order ? '#' . $order['order_number'] : '';
$request_type_input = 'Return / Refund';
$reason_input = '';
$details_input = '';

$errors = [];
$is_submitted = false;
$submitted_order_id = $order ? intval($order['order_id']) : 0;
$submitted_order_number = $order ? $order['order_number'] : '';

$allowed_types = ['Return / Refund', 'Return Only', 'Refund Only'];
$allowed_reasons = [
    'Damaged or defective item',
    'Wrong item received',
    'Missing item or parts',
    'Item not as described',
    'Changed mind / No longer needed',
    'Other'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_order_num = trim($_POST['order_number'] ?? '');
    $clean_order_num = ltrim($raw_order_num, '#');
    $order_number_input = $raw_order_num;

    $request_type_input = trim($_POST['request_type'] ?? 'Return / Refund');
    $reason_input = trim($_POST['reason'] ?? '');
    $details_input = trim($_POST['details'] ?? '');

    if ($clean_order_num === '') {
        $errors['order_number'] = 'Please enter your order number.';
    }

    if (!in_array($request_type_input, $allowed_types, true)) {
        $errors['request_type'] = 'Please select a valid request type.';
    }

    if ($reason_input === '' || !in_array($reason_input, $allowed_reasons, true)) {
        $errors['reason'] = 'Please select a reason for your request.';
    }

    if ($details_input === '') {
        $errors['details'] = 'Please describe the problem with your order.';
    }

    $matched_order = null;
    if (empty($errors['order_number'])) {
        $verify_stmt = mysqli_prepare($Connection, "
            SELECT order_id, order_number, order_status, total_amount
            FROM orders
            WHERE order_number = ? AND customer_id = ?
            LIMIT 1
        ");
        mysqli_stmt_bind_param($verify_stmt, 'si', $clean_order_num, $customer_id);
        mysqli_stmt_execute($verify_stmt);
        $verify_res = mysqli_stmt_get_result($verify_stmt);
        $matched_order = mysqli_fetch_assoc($verify_res);
        mysqli_stmt_close($verify_stmt);

        if (!$matched_order) {
            $errors['order_number'] = 'Order number not found under your account.';
        } elseif (!in_array($matched_order['order_status'], ['delivered', 'completed'], true)) {
            $errors['order_number'] = 'Return/refund requests are only available for delivered or completed orders.';
        }
    }

    if (empty($errors) && $matched_order) {
        $target_order_id = intval($matched_order['order_id']);
        $target_order_num = $matched_order['order_number'];
        $refund_amount = floatval($matched_order['total_amount']);
        $formatted_description = '[' . $request_type_input . '] ' . $details_input;

        $dup_stmt = mysqli_prepare($Connection, "
            SELECT return_id
            FROM return_requests
            WHERE order_id = ?
              AND customer_id = ?
              AND return_status IN ('submitted', 'under_review', 'approved')
            LIMIT 1
        ");
        mysqli_stmt_bind_param($dup_stmt, 'ii', $target_order_id, $customer_id);
        mysqli_stmt_execute($dup_stmt);
        $dup_res = mysqli_stmt_get_result($dup_stmt);
        $existing_req = mysqli_fetch_assoc($dup_res);
        mysqli_stmt_close($dup_stmt);

        if ($existing_req) {
            $errors['general'] = 'An active return/refund request already exists for Order #' . htmlspecialchars($target_order_num) . '.';
        } else {
            $ins_stmt = mysqli_prepare($Connection, "
                INSERT INTO return_requests (
                    order_id,
                    customer_id,
                    reason,
                    description,
                    return_status,
                    refund_amount
                ) VALUES (?, ?, ?, ?, 'submitted', ?)
            ");
            mysqli_stmt_bind_param(
                $ins_stmt,
                'iissd',
                $target_order_id,
                $customer_id,
                $reason_input,
                $formatted_description,
                $refund_amount
            );

            if (mysqli_stmt_execute($ins_stmt)) {
                $new_request_id = intval(mysqli_insert_id($Connection));
                mysqli_stmt_close($ins_stmt);
                header('Location: request-progress.php?request_id=' . $new_request_id . '&submitted=1');
                exit();
            } else {
                $errors['general'] = 'Failed to submit your request. Please try again.';
            }
            mysqli_stmt_close($ins_stmt);
        }
    }
}

$cancel_url = ($order_id > 0) ? 'order-details.php?order_id=' . $order_id : 'order-history.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return / Refund Request</title>
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
                    <li class="breadcrumb-item active" aria-current="page">Return / Refund</li>
                </ol>
            </nav>
            <h1 class="fw-bold display-5 font-poppins text-dark">Return / Refund</h1>
            <p class="text-muted mb-0">Tell us what went wrong with your order.</p>
        </div>
    </header>

    <main class="py-5">
        <div class="container px-4 px-lg-5">
            <div class="row g-4">
                <div class="col-lg-3 d-none d-lg-block">
                    <div class="card border shadow-sm rounded-4 p-3 bg-white">
                        <div class="list-group list-group-flush gap-1">
                            <a href="account-settings.php" class="list-group-item list-group-item-action border-0 rounded-3 text-secondary py-2 px-3">Account Settings</a>
                            <a href="account-info.php" class="list-group-item list-group-item-action border-0 rounded-3 text-secondary py-2 px-3">Personal Information</a>
                            <a href="order-history.php" class="list-group-item list-group-item-action border-0 rounded-3 fw-semibold active py-2 px-3" style="background-color: #eef1e9; color: #0a6e06; border: 1px solid #0a6e06 !important;">Order History</a>
                            <a href="track-order.php<?php echo ($order_id > 0) ? '?order_id=' . $order_id : ''; ?>" class="list-group-item list-group-item-action border-0 rounded-3 text-secondary py-2 px-3">Track Order</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="card border shadow-sm rounded-4 p-4 p-md-5 bg-white">
                        <?php if ($is_submitted) { ?>
                            <div class="text-center py-4">
                                <div class="success-icon-circle mx-auto mb-3">
                                    <i class="bi bi-check-lg fs-2"></i>
                                </div>
                                <h2 class="h4 fw-bold font-poppins text-dark mb-2">Request Submitted</h2>
                                <p class="text-muted small mx-auto mb-4" style="max-width: 460px;">
                                    Your <strong><?php echo htmlspecialchars($request_type_input); ?></strong> request for Order <strong>#<?php echo htmlspecialchars($submitted_order_number); ?></strong> has been submitted and is awaiting store review.
                                </p>
                                <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                                    <?php if ($submitted_order_id > 0) { ?>
                                        <a href="order-details.php?order_id=<?php echo $submitted_order_id; ?>" class="btn btn-dark-custom px-4 py-2 rounded-3 fw-semibold small text-decoration-none">
                                            Back to Order Details
                                        </a>
                                    <?php } ?>
                                    <a href="order-history.php" class="btn btn-outline-custom px-4 py-2 rounded-3 fw-semibold small text-decoration-none">
                                        View Order History
                                    </a>
                                </div>
                            </div>
                        <?php } else { ?>
                            <h2 class="h4 fw-bold font-poppins text-dark mb-4">Request Details</h2>
                            <div class="rounded-3 p-3 mb-4 d-flex align-items-center gap-3" style="background-color: #f4f6f9; border: 1.5px solid #383b3d;">
                                <i class="bi bi-info-circle-fill fs-5 flex-shrink-0" style="color: #0a6e06;"></i>
                                <span class="small fw-medium text-dark mb-0">
                                    Requests are reviewed by the store.
                                </span>
                            </div>

                            <?php if (!empty($errors['general'])) { ?>
                                <div class="alert-danger-custom mb-4" role="alert">
                                    <i class="bi bi-exclamation-circle-fill"></i>
                                    <span><?php echo htmlspecialchars($errors['general']); ?></span>
                                </div>
                            <?php } ?>

                            <form method="POST" action="return-refund.php<?php echo ($order_id > 0) ? '?order_id=' . $order_id : ''; ?>" novalidate>
                                <div class="mb-3">
                                    <label for="orderNumberInput" class="form-label-custom">Order Number</label>
                                    <input
                                        type="text"
                                        id="orderNumberInput"
                                        name="order_number"
                                        class="form-control form-control-custom <?php echo !empty($errors['order_number']) ? 'is-invalid' : ''; ?>"
                                        placeholder="#ORD-0001"
                                        value="<?php echo htmlspecialchars($order_number_input); ?>"
                                        <?php echo ($order !== null) ? 'readonly' : ''; ?>
                                        required
                                    >
                                    <?php if (!empty($errors['order_number'])) { ?>
                                        <div class="input-error-text"><?php echo htmlspecialchars($errors['order_number']); ?></div>
                                    <?php } ?>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-6">
                                        <label for="requestTypeSelect" class="form-label-custom">Request Type</label>
                                        <select
                                            id="requestTypeSelect"
                                            name="request_type"
                                            class="form-select form-control-custom bg-white <?php echo !empty($errors['request_type']) ? 'is-invalid' : ''; ?>"
                                            required
                                        >
                                            <?php foreach ($allowed_types as $type) { ?>
                                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($request_type_input === $type) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($type); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                        <?php if (!empty($errors['request_type'])) { ?>
                                            <div class="input-error-text"><?php echo htmlspecialchars($errors['request_type']); ?></div>
                                        <?php } ?>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="reasonSelect" class="form-label-custom">Reason</label>
                                        <select
                                            id="reasonSelect"
                                            name="reason"
                                            class="form-select form-control-custom bg-white <?php echo !empty($errors['reason']) ? 'is-invalid' : ''; ?>"
                                            required
                                        >
                                            <option value="" disabled <?php echo ($reason_input === '') ? 'selected' : ''; ?>>Select a reason</option>
                                            <?php foreach ($allowed_reasons as $reason) { ?>
                                                <option value="<?php echo htmlspecialchars($reason); ?>" <?php echo ($reason_input === $reason) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($reason); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                        <?php if (!empty($errors['reason'])) { ?>
                                            <div class="input-error-text"><?php echo htmlspecialchars($errors['reason']); ?></div>
                                        <?php } ?>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="detailsTextarea" class="form-label-custom">Details</label>
                                    <textarea
                                        id="detailsTextarea"
                                        name="details"
                                        rows="5"
                                        class="form-control form-control-custom <?php echo !empty($errors['details']) ? 'is-invalid' : ''; ?>"
                                        placeholder="Describe the problem with your order..."
                                        required
                                    ><?php echo htmlspecialchars($details_input); ?></textarea>
                                    <?php if (!empty($errors['details'])) { ?>
                                        <div class="input-error-text"><?php echo htmlspecialchars($errors['details']); ?></div>
                                    <?php } ?>
                                </div>

                                <div class="d-flex flex-column flex-sm-row gap-2">
                                    <button type="submit" class="btn btn-dark-custom px-4 py-2 rounded-3 fw-semibold small text-center">
                                        Submit Request
                                    </button>
                                    <a href="<?php echo htmlspecialchars($cancel_url); ?>" class="btn btn-outline-custom px-4 py-2 rounded-3 fw-semibold small text-center text-decoration-none">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>