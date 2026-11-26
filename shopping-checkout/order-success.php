<?php 
session_start();
$activePage = 'cart';

if (!isset($_SESSION['customer_id']) && !isset($_SESSION['username'])) {
    header('Location: ../login.php?required=1');
    exit();
}

date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/../db_connect.php';

$customer_id = intval($_SESSION['customer_id'] ?? 0);
$order_id = intval($_SESSION['placed_order_id'] ?? 0);

if ($order_id === 0) {
    header('Location: ../products.php');
    exit();
}

$order = null;
$sql = "
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
    WHERE o.order_id = ? AND o.customer_id = ?
    LIMIT 1
";
$stmt = mysqli_prepare($Connection, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $order_id, $customer_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($res)) {
    $order = $row;
}
mysqli_stmt_close($stmt);

if (!$order) {
    header('Location: ../products.php');
    exit();
}

$formatted_date = date('F d, Y', strtotime($order['placed_at']));
$payment_method_label = ($order['payment_method'] === 'online_payment') ? 'Online Payment' : 'Cash on Delivery';

if ($order['payment_method'] === 'online_payment') {
    $payment_status_label = 'Paid (' . htmlspecialchars($order['transaction_reference'] ?? 'Online') . ')';
} else {
    $payment_status_label = 'To be paid on delivery';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
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

    <main class="py-5">
        <div class="container px-4 px-lg-5">
            <div class="d-none d-md-block mb-5">
                <div class="checkout-stepper">
                    <div class="step-item completed"><span class="step-circle">1</span><span class="step-label">Cart</span></div>
                    <div class="step-connector completed"></div>
                    <div class="step-item completed"><span class="step-circle">2</span><span class="step-label">Checkout</span></div>
                    <div class="step-connector completed"></div>
                    <div class="step-item completed"><span class="step-circle">3</span><span class="step-label">Payment</span></div>
                    <div class="step-connector completed"></div>
                    <div class="step-item active"><span class="step-circle">4</span><span class="step-label">Confirmation</span></div>
                </div>
            </div>

            <div class="d-block d-md-none mb-4">
                <p class="small fw-semibold text-dark mb-2">Step 4 of 4 &middot; Confirmation</p>
                <div class="mobile-step-bars">
                    <div class="mobile-step-bar filled"></div>
                    <div class="mobile-step-bar filled"></div>
                    <div class="mobile-step-bar filled"></div>
                    <div class="mobile-step-bar filled"></div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-9 col-lg-7">
                    <div class="card border shadow-sm rounded-4 p-4 p-md-5 bg-white text-center">
                        <div class="mx-auto mb-4">
                            <div class="success-icon-circle mx-auto shadow-sm">
                                OK
                            </div>
                        </div>

                        <h1 class="h3 fw-bold font-poppins text-dark mb-2">Order Placed Successfully</h1>
                        <p class="text-muted small mb-4">Thank you! Your order has been received and is now Pending.</p>

                        <div class="text-start mb-4">
                            <div class="d-flex justify-content-between py-2 border-bottom small">
                                <span class="text-muted">Order Number</span>
                                <span class="fw-bold text-dark">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom small">
                                <span class="text-muted">Order Date</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($formatted_date); ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom small">
                                <span class="text-muted">Payment Method</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($payment_method_label); ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom small">
                                <span class="text-muted">Payment Status</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($payment_status_label); ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom small">
                                <span class="text-muted">Total Amount</span>
                                <span class="fw-bold text-dark">&#8369;<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom small">
                                <span class="text-muted flex-shrink-0">Delivery Address</span>
                                <span class="fw-semibold text-dark text-end" style="max-width: 60%;"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-sm-center">
                            <a href="../customer-account/track-order.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-dark-custom w-sm-100 py-2.5 rounded-3 fw-semibold small order-1 order-sm-1 shadow-sm text-decoration-none text-center">
                                Track Order
                            </a>
                            <a href="../products.php" class="btn btn-outline-custom w-sm-100 py-2.5 rounded-3 fw-semibold small order-2 order-sm-2 shadow-sm text-decoration-none text-center">
                                Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php' ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>