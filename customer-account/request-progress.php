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

$request_id = isset($_GET['request_id']) && is_numeric($_GET['request_id']) ? intval($_GET['request_id']) : 0;
$order_id = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? intval($_GET['order_id']) : 0;

$stage_cols_sql = '';
$col_check = mysqli_query($Connection, "SHOW COLUMNS FROM `return_requests`");
if ($col_check) {
    $existing_cols = [];
    while ($col_row = mysqli_fetch_assoc($col_check)) {
        $existing_cols[] = $col_row['Field'];
    }
    mysqli_free_result($col_check);

    foreach (['under_review_at', 'approved_at', 'rejected_at', 'refunded_at'] as $opt_col) {
        if (in_array($opt_col, $existing_cols, true)) {
            $stage_cols_sql .= ", rr.`{$opt_col}`";
        } else {
            $stage_cols_sql .= ", NULL AS `{$opt_col}`";
        }
    }
}

$request = null;
$base_select = "
    SELECT 
        rr.return_id,
        rr.order_id,
        rr.customer_id,
        rr.reviewed_by,
        rr.reason,
        rr.description,
        rr.return_status,
        rr.refund_amount,
        rr.requested_at{$stage_cols_sql},
        rr.reviewed_at,
        rr.updated_at,
        o.order_number,
        o.order_status
    FROM return_requests rr
    INNER JOIN orders o ON rr.order_id = o.order_id
";

if ($request_id > 0) {
    $stmt = mysqli_prepare($Connection, $base_select . " WHERE rr.return_id = ? AND rr.customer_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ii', $request_id, $customer_id);
} elseif ($order_id > 0) {
    $stmt = mysqli_prepare($Connection, $base_select . " WHERE rr.order_id = ? AND rr.customer_id = ? ORDER BY rr.requested_at DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ii', $order_id, $customer_id);
} else {
    $stmt = mysqli_prepare($Connection, $base_select . " WHERE rr.customer_id = ? ORDER BY rr.requested_at DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $customer_id);
}

if ($stmt) {
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $request = $row;
    }
    mysqli_stmt_close($stmt);
}

$request_type_label = 'Return / Refund';
$clean_description = '';

if ($request) {
    $raw_desc = (string) ($request['description'] ?? '');
    if (preg_match('/^\[(Return \/ Refund|Return Only|Refund Only)\]\s*(.*)$/s', $raw_desc, $matches)) {
        $request_type_label = $matches[1];
        $clean_description = trim($matches[2]);
    } else {
        $clean_description = trim($raw_desc);
    }
}

$req_status = $request ? $request['return_status'] : 'submitted';

$submitted_short = ($request && !empty($request['requested_at'])) ? date('M j, Y • g:i A', strtotime($request['requested_at'])) : '—';
$submitted_full = ($request && !empty($request['requested_at'])) ? date('M j, Y • g:i A', strtotime($request['requested_at'])) : '—';

$under_review_raw = $request['under_review_at'] ?? ($request['reviewed_at'] ?? null);
$under_review_short = !empty($under_review_raw) ? date('M j, Y • g:i A', strtotime($under_review_raw)) : '—';

$decision_raw = ($req_status === 'rejected')
    ? ($request['rejected_at'] ?? ($request['reviewed_at'] ?? null))
    : ($request['approved_at'] ?? ($request['reviewed_at'] ?? null));
$decision_short = !empty($decision_raw) ? date('M j, Y • g:i A', strtotime($decision_raw)) : '—';

$refunded_raw = $request['refunded_at'] ?? (($req_status === 'refunded' && !empty($request['updated_at'])) ? $request['updated_at'] : null);
$refunded_short = !empty($refunded_raw) ? date('M j, Y • g:i A', strtotime($refunded_raw)) : '—';
$refunded_full = !empty($refunded_raw) ? date('M j, Y • g:i A', strtotime($refunded_raw)) : '—';

switch ($req_status) {
    case 'under_review':
        $badge = ['label' => 'Under Review', 'class' => 'badge-status-processing'];
        $banner_class = 'request-banner-review';
        $banner_icon = 'bi-clock-history';
        $banner_text = 'Your request is under review. No refund has been issued yet.';
        $step1 = ['state' => 'completed', 'label' => 'Request Submitted', 'sub' => $submitted_short];
        $step2 = ['state' => 'active', 'label' => 'Under Review', 'sub' => ($under_review_short !== '—' ? $under_review_short : $submitted_short)];
        $step3 = ['state' => '', 'label' => 'Approved / Rejected', 'sub' => '—'];
        $step4 = ['state' => '', 'label' => 'Refunded', 'sub' => '—'];
        break;

    case 'approved':
        $badge = ['label' => 'Approved', 'class' => 'badge-status-shipped'];
        $banner_class = 'request-banner-approved';
        $banner_icon = 'bi-check-circle-fill';
        $banner_text = 'Request approved. The refund still has to be processed — your order is not Refunded yet.';
        $step1 = ['state' => 'completed', 'label' => 'Request Submitted', 'sub' => $submitted_short];
        $step2 = ['state' => 'completed', 'label' => 'Under Review', 'sub' => ($under_review_short !== '—' ? $under_review_short : $decision_short)];
        $step3 = ['state' => 'active', 'label' => 'Approved', 'sub' => $decision_short];
        $step4 = ['state' => '', 'label' => 'Refunded', 'sub' => '—'];
        break;

    case 'rejected':
        $badge = ['label' => 'Rejected', 'class' => 'badge-status-cancelled'];
        $banner_class = 'request-banner-rejected';
        $banner_icon = 'bi-exclamation-circle-fill';
        $banner_text = 'Request rejected. No refund will be issued. See the store’s response below.';
        $step1 = ['state' => 'completed', 'label' => 'Request Submitted', 'sub' => $submitted_short];
        $step2 = ['state' => 'completed', 'label' => 'Under Review', 'sub' => ($under_review_short !== '—' ? $under_review_short : $decision_short)];
        $step3 = ['state' => 'rejected', 'label' => 'Rejected', 'sub' => $decision_short];
        $step4 = ['state' => 'disabled-step', 'label' => 'Refunded', 'sub' => 'Not applicable'];
        break;

    case 'refunded':
        $badge = ['label' => 'Refunded', 'class' => 'badge-status-refunded'];
        $banner_class = 'request-banner-refunded';
        $banner_icon = 'bi-check-circle-fill';
        $banner_text = 'Refund processed. Your order status is now Refunded.';
        $step1 = ['state' => 'completed', 'label' => 'Request Submitted', 'sub' => $submitted_short];
        $step2 = ['state' => 'completed', 'label' => 'Under Review', 'sub' => ($under_review_short !== '—' ? $under_review_short : $decision_short)];
        $step3 = ['state' => 'completed', 'label' => 'Approved', 'sub' => $decision_short];
        $step4 = ['state' => 'completed', 'label' => 'Refunded', 'sub' => $refunded_short];
        break;

    case 'submitted':
    default:
        $badge = ['label' => 'Request Submitted', 'class' => 'badge-status-pending'];
        $banner_class = 'request-banner-info';
        $banner_icon = 'bi-check-circle-fill';
        $banner_text = 'Request submitted. The store will review it. Submitting a request is not a refund.';
        $step1 = ['state' => 'active', 'label' => 'Request Submitted', 'sub' => $submitted_short];
        $step2 = ['state' => '', 'label' => 'Under Review', 'sub' => '—'];
        $step3 = ['state' => '', 'label' => 'Approved / Rejected', 'sub' => '—'];
        $step4 = ['state' => '', 'label' => 'Refunded', 'sub' => '—'];
        break;
}

$just_submitted = isset($_GET['submitted']) && $_GET['submitted'] === '1';
$page_subtitle = $just_submitted ? 'Your request has been sent to the store.' : 'Your request status.';
$track_order_link = ($request && !empty($request['order_id']))
    ? 'track-order.php?order_id=' . intval($request['order_id'])
    : 'track-order.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return / Refund — Request Progress</title>
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
            <p class="text-muted mb-0"><?php echo htmlspecialchars($page_subtitle); ?></p>
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
                            <a href="order-history.php" class="list-group-item list-group-item-action border-0 rounded-3 fw-semibold active py-2 px-3 sidebar-active-nav">Order History</a>
                            <a href="<?php echo htmlspecialchars($track_order_link); ?>" class="list-group-item list-group-item-action border-0 rounded-3 text-secondary py-2 px-3">Track Order</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <?php if (!$request) { ?>
                        <div class="card border shadow-sm rounded-4 p-5 bg-white text-center">
                            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center text-success mx-auto mb-3" style="width: 60px; height: 60px;">
                                <i class="bi bi-arrow-return-left fs-3"></i>
                            </div>
                            <h2 class="h5 fw-bold font-poppins text-dark mb-1">No Return / Refund Request Found</h2>
                            <p class="text-muted small mb-4">You haven’t submitted any return or refund request yet.</p>
                            <a href="order-history.php" class="btn btn-dark-custom btn-sm px-4 py-2 rounded-3 fw-semibold text-decoration-none d-inline-block">
                                Back to Order History
                            </a>
                        </div>
                    <?php } else {
                        $request_number_formatted = '#RR-' . str_pad(intval($request['return_id']), 4, '0', STR_PAD_LEFT);
                        $order_number_formatted = '#' . htmlspecialchars($request['order_number']);
                        $steps = [1 => $step1, 2 => $step2, 3 => $step3, 4 => $step4];
                    ?>
                        <div class="<?php echo $banner_class; ?> mb-4 d-flex align-items-center gap-3">
                            <i class="bi <?php echo $banner_icon; ?> fs-5 flex-shrink-0"></i>
                            <span class="small fw-semibold mb-0"><?php echo htmlspecialchars($banner_text); ?></span>
                        </div>

                        <div class="card border shadow-sm rounded-4 p-4 bg-white mb-4">
                            <h2 class="h5 fw-bold font-poppins text-dark mb-4">Request Progress</h2>

                            <div class="request-progress-track">
                                <?php foreach ($steps as $num => $step) {
                                    $state_class = $step['state'];
                                    $is_done = ($state_class === 'completed');
                                ?>
                                    <div class="request-step-item <?php echo htmlspecialchars($state_class); ?>">
                                        <div class="request-step-circle">
                                            <?php echo $is_done ? '<i class="bi bi-check-lg"></i>' : $num; ?>
                                        </div>
                                        <div>
                                            <span class="request-step-label"><?php echo htmlspecialchars($step['label']); ?></span>
                                        </div>
                                        <span class="request-step-sub"><?php echo htmlspecialchars($step['sub']); ?></span>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="card border shadow-sm rounded-4 p-4 bg-white mb-4">
                            <h2 class="h5 fw-bold font-poppins text-dark mb-4">Request Summary</h2>

                            <div class="d-flex flex-column gap-3 small">
                                <div class="d-flex justify-content-between align-items-center gap-4 pb-2 border-bottom">
                                    <span class="text-muted flex-shrink-0">Request Number</span>
                                    <span class="fw-semibold text-dark text-end"><?php echo htmlspecialchars($request_number_formatted); ?></span>
                                </div>

                                <div class="d-flex justify-content-between align-items-center gap-4 pb-2 border-bottom">
                                    <span class="text-muted flex-shrink-0">Order Number</span>
                                    <span class="fw-semibold text-dark text-end"><?php echo $order_number_formatted; ?></span>
                                </div>

                                <div class="d-flex justify-content-between align-items-center gap-4 pb-2 border-bottom">
                                    <span class="text-muted flex-shrink-0">Request Type</span>
                                    <span class="fw-semibold text-dark text-end"><?php echo htmlspecialchars($request_type_label); ?></span>
                                </div>

                                <div class="d-flex justify-content-between align-items-start gap-4 pb-2 border-bottom">
                                    <span class="text-muted flex-shrink-0">Reason</span>
                                    <span class="fw-semibold text-dark text-end lh-base"><?php echo htmlspecialchars($request['reason']); ?></span>
                                </div>

                                <?php if ($clean_description !== '') { ?>
                                    <div class="d-flex justify-content-between align-items-start gap-4 pb-2 border-bottom">
                                        <span class="text-muted flex-shrink-0">Details</span>
                                        <span class="fw-semibold text-dark text-end lh-base" style="max-width: 72%;"><?php echo nl2br(htmlspecialchars($clean_description)); ?></span>
                                    </div>
                                <?php } ?>

                                <?php if ($request['refund_amount'] !== null) { ?>
                                    <div class="d-flex justify-content-between align-items-center gap-4 pb-2 border-bottom">
                                        <span class="text-muted flex-shrink-0">Estimated Refund</span>
                                        <span class="fw-bold refund-amount-text text-end">&#8369;<?php echo number_format(floatval($request['refund_amount']), 2); ?></span>
                                    </div>
                                <?php } ?>

                                <div class="d-flex justify-content-between align-items-center gap-4 pb-2 border-bottom">
                                    <span class="text-muted flex-shrink-0">Submitted On</span>
                                    <span class="fw-semibold text-dark text-end"><?php echo htmlspecialchars($submitted_full); ?></span>
                                </div>

                                <?php if ($req_status === 'refunded') { ?>
                                    <div class="d-flex justify-content-between align-items-center gap-4 pb-2 border-bottom">
                                        <span class="text-muted flex-shrink-0">Refunded On</span>
                                        <span class="fw-semibold text-dark text-end"><?php echo htmlspecialchars($refunded_full); ?></span>
                                    </div>
                                <?php } ?>

                                <?php if ($req_status === 'rejected') { ?>
                                    <div class="d-flex justify-content-between align-items-start gap-4 pb-2 border-bottom">
                                        <span class="text-muted flex-shrink-0">Store Response</span>
                                        <span class="fw-semibold text-dark text-end lh-base">Request declined after store review.</span>
                                    </div>
                                <?php } ?>

                                <div class="d-flex justify-content-between align-items-center gap-4 pt-1">
                                    <span class="text-muted flex-shrink-0">Current status</span>
                                    <span class="status-pill <?php echo $badge['class']; ?>">
                                        <?php echo htmlspecialchars($badge['label']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column align-items-stretch align-items-sm-start gap-2">
                            <a href="order-history.php" class="btn btn-dark-custom px-4 py-2 rounded-3 fw-semibold small text-center text-decoration-none">
                                Back to Order History
                            </a>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>