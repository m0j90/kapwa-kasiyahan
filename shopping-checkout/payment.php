<?php 
session_start();
$activePage = 'cart';

if (!isset($_SESSION['customer_id']) && !isset($_SESSION['username'])) {
    header('Location: ../login.php?required=1');
    exit();
}

if (!isset($_SESSION['checkout_details'])) {
    header('Location: checkout.php');
    exit();
}

date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/../db_connect.php';

$customer_id = intval($_SESSION['customer_id'] ?? 0);
$checkout = $_SESSION['checkout_details'];
$cart_id = intval($checkout['cart_id']);

$cart_items = [];
$subtotal = 0.00;
$shipping_fee = 80.00;

if ($cart_id > 0) {
    $items_sql = "
        SELECT 
            ci.cart_item_id,
            ci.quantity,
            p.product_id,
            p.product_name,
            p.product_image,
            p.price,
            p.stock
        FROM cart_items ci
        INNER JOIN products p ON ci.product_id = p.product_id
        WHERE ci.cart_id = ?
        ORDER BY ci.added_at DESC
    ";
    $stmt = mysqli_prepare($Connection, $items_sql);
    mysqli_stmt_bind_param($stmt, 'i', $cart_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($item = mysqli_fetch_assoc($res)) {
        $line_total = floatval($item['price']) * intval($item['quantity']);
        $item['line_total'] = $line_total;
        $subtotal += $line_total;
        $cart_items[] = $item;
    }
    mysqli_stmt_close($stmt);
}

if (empty($cart_items)) {
    header('Location: shopping-cart.php');
    exit();
}

$total_amount = $subtotal + $shipping_fee;

$error_msg = '';
$payment_method = $_POST['payment_method'] ?? '';
$payment_channel = $_POST['payment_channel'] ?? '';
$account_name = $_POST['account_name'] ?? '';
$account_number = $_POST['account_number'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order'])) {
    if (empty($payment_method)) {
        $error_msg = 'Please select a payment method before placing your order.';
    } elseif ($payment_method === 'online_payment' && (empty($payment_channel) || empty(trim($account_name)) || empty(trim($account_number)))) {
        $error_msg = 'Please complete all required online payment fields before placing your order.';
    } else {
        mysqli_begin_transaction($Connection);

        try {
            $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            $order_status = 'pending';

            $ord_stmt = mysqli_prepare($Connection, "
                INSERT INTO orders (customer_id, order_number, full_name, contact_number, delivery_address, order_notes, subtotal, shipping_fee, total_amount, payment_method, order_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$ord_stmt) {
                throw new Exception("Prepare failed (orders): " . mysqli_error($Connection));
            }
            mysqli_stmt_bind_param(
                $ord_stmt, 
                'isssssdddss', 
                $customer_id, 
                $order_number, 
                $checkout['full_name'], 
                $checkout['contact_number'], 
                $checkout['delivery_address'], 
                $checkout['order_notes'], 
                $subtotal, 
                $shipping_fee, 
                $total_amount, 
                $payment_method, 
                $order_status
            );
            if (!mysqli_stmt_execute($ord_stmt)) {
                throw new Exception("Execute failed (orders): " . mysqli_stmt_error($ord_stmt));
            }
            $order_id = mysqli_insert_id($Connection);
            mysqli_stmt_close($ord_stmt);

            foreach ($cart_items as $item) {
                $oi_stmt = mysqli_prepare($Connection, "
                    INSERT INTO order_items (order_id, product_id, product_name, product_image, unit_price, quantity, line_total) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$oi_stmt) {
                    throw new Exception("Prepare failed (order_items): " . mysqli_error($Connection));
                }
                mysqli_stmt_bind_param(
                    $oi_stmt, 
                    'iissdid', 
                    $order_id, 
                    $item['product_id'], 
                    $item['product_name'], 
                    $item['product_image'], 
                    $item['price'], 
                    $item['quantity'], 
                    $item['line_total']
                );
                if (!mysqli_stmt_execute($oi_stmt)) {
                    throw new Exception("Execute failed (order_items): " . mysqli_stmt_error($oi_stmt));
                }
                mysqli_stmt_close($oi_stmt);

                $stk_stmt = mysqli_prepare($Connection, "UPDATE products SET stock = stock - ? WHERE product_id = ?");
                mysqli_stmt_bind_param($stk_stmt, 'ii', $item['quantity'], $item['product_id']);
                mysqli_stmt_execute($stk_stmt);
                mysqli_stmt_close($stk_stmt);
            }

            $payment_status = ($payment_method === 'online_payment') ? 'paid' : 'pending';
            $transaction_ref = ($payment_method === 'online_payment') ? 'REF-' . strtoupper(uniqid()) : null;
            $paid_at = ($payment_method === 'online_payment') ? date('Y-m-d H:i:s') : null;

            $pay_stmt = mysqli_prepare($Connection, "
                INSERT INTO payments (order_id, payment_method, amount, payment_status, transaction_reference, paid_at) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if (!$pay_stmt) {
                throw new Exception("Prepare failed (payments): " . mysqli_error($Connection));
            }
            mysqli_stmt_bind_param($pay_stmt, 'isdsss', $order_id, $payment_method, $total_amount, $payment_status, $transaction_ref, $paid_at);
            if (!mysqli_stmt_execute($pay_stmt)) {
                throw new Exception("Execute failed (payments): " . mysqli_stmt_error($pay_stmt));
            }
            mysqli_stmt_close($pay_stmt);

            $del_cart_items = mysqli_prepare($Connection, "DELETE FROM cart_items WHERE cart_id = ?");
            mysqli_stmt_bind_param($del_cart_items, 'i', $cart_id);
            mysqli_stmt_execute($del_cart_items);
            mysqli_stmt_close($del_cart_items);

            $upd_cart = mysqli_prepare($Connection, "UPDATE carts SET cart_status = 'converted' WHERE cart_id = ?");
            mysqli_stmt_bind_param($upd_cart, 'i', $cart_id);
            mysqli_stmt_execute($upd_cart);
            mysqli_stmt_close($upd_cart);

            mysqli_commit($Connection);

            unset($_SESSION['checkout_details']);
            $_SESSION['placed_order_id'] = $order_id;
            $_SESSION['placed_order_number'] = $order_number;

            header('Location: order-success.php');
            exit();

        } catch (Exception $e) {
            mysqli_rollback($Connection);
            echo '<div class="alert alert-danger m-5"><strong>Debug Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="pageTitle">Choose Payment Method</title>
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
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none text-success">Home</a></li>
                    <li class="breadcrumb-item"><a href="shopping-cart.php" class="text-decoration-none text-success">Shopping Cart</a></li>
                    <li class="breadcrumb-item"><a href="checkout.php" class="text-decoration-none text-success">Checkout</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Payment</li>
                </ol>
            </nav>
            <h1 id="headerTitle" class="fw-bold display-5 font-poppins text-dark">Choose Payment Method</h1>
            <p id="headerDesc" class="text-muted mb-0">Select how you want to pay for this order.</p>
        </div>
    </header>

    <main class="py-5">
        <div class="container px-4 px-lg-5">
            <div class="d-none d-md-block mb-5">
                <div class="checkout-stepper">
                    <div class="step-item completed"><span class="step-circle">1</span><span class="step-label">Cart</span></div>
                    <div class="step-connector completed"></div>
                    <div class="step-item completed"><span class="step-circle">2</span><span class="step-label">Checkout</span></div>
                    <div class="step-connector completed"></div>
                    <div class="step-item active"><span class="step-circle">3</span><span class="step-label">Payment</span></div>
                    <div class="step-connector"></div>
                    <div class="step-item"><span class="step-circle">4</span><span class="step-label">Confirmation</span></div>
                </div>
            </div>

            <div class="d-block d-md-none mb-4">
                <p class="small fw-semibold text-dark mb-2">Step 3 of 4 &middot; Payment</p>
                <div class="mobile-step-bars">
                    <div class="mobile-step-bar filled"></div>
                    <div class="mobile-step-bar filled"></div>
                    <div class="mobile-step-bar filled"></div>
                    <div class="mobile-step-bar"></div>
                </div>
            </div>

            <?php if (!empty($error_msg)) { ?>
                <div class="alert-danger-custom mb-4" id="errorAlertBox">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span id="errorAlertText"><?php echo $error_msg; ?></span>
                </div>
            <?php } else { ?>
                <div class="alert-danger-custom mb-4 d-none" id="errorAlertBox">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span id="errorAlertText"></span>
                </div>
            <?php } ?>

            <form id="paymentForm" method="POST" action="payment.php">
                <input type="hidden" name="confirm_order" value="1">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-8">
                        <div class="card border shadow-sm rounded-4 p-4 p-md-5 bg-white">
                            <h2 class="h4 fw-bold font-poppins text-dark mb-4">Payment Method</h2>

                            <div class="d-flex flex-column gap-3">
                                <div class="payment-option-card <?php echo ($payment_method === 'online_payment') ? 'selected' : ''; ?>" id="cardOnline" onclick="selectPaymentMethod('online_payment')">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input mt-1" type="radio" name="payment_method" id="methodOnline" value="online_payment" <?php echo ($payment_method === 'online_payment') ? 'checked' : ''; ?>>
                                        <label class="form-check-label w-100 ps-2 cursor-pointer" for="methodOnline">
                                            <span class="fw-bold font-poppins text-dark d-block">Online Payment</span>
                                            <span class="text-muted small">Enter your payment details to complete your payment.</span>
                                        </label>
                                    </div>

                                    <div class="mt-4 pt-3 border-top <?php echo ($payment_method === 'online_payment') ? '' : 'd-none'; ?>" id="onlineFields">
                                        <div class="alert bg-light border border-dashed rounded-3 p-3 mb-3 small text-muted">
                                            <i class="bi bi-info-circle-fill me-1"></i> Demo mode: No real transaction will be processed and your account will not be charged.
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label-custom small">Payment Channel</label>
                                            <select name="payment_channel" id="paymentChannel" class="form-select form-control-custom bg-white">
                                                <option value="">Select a channel</option>
                                                <option value="GCash" <?php echo ($payment_channel === 'GCash') ? 'selected' : ''; ?>>GCash</option>
                                                <option value="Maya" <?php echo ($payment_channel === 'Maya') ? 'selected' : ''; ?>>Maya</option>
                                                <option value="Credit Card" <?php echo ($payment_channel === 'Credit Card') ? 'selected' : ''; ?>>Credit Card</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label-custom small">Account Name</label>
                                            <input type="text" name="account_name" id="accountName" class="form-control form-control-custom" placeholder="Name on the account" value="<?php echo htmlspecialchars($account_name); ?>">
                                        </div>

                                        <div class="mb-0">
                                            <label class="form-label-custom small">Account / Card Number</label>
                                            <input type="text" name="account_number" id="accountNumber" class="form-control form-control-custom" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" value="<?php echo htmlspecialchars($account_number); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="payment-option-card <?php echo ($payment_method === 'cash_on_delivery') ? 'selected' : ''; ?>" id="cardCOD" onclick="selectPaymentMethod('cash_on_delivery')">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input mt-1" type="radio" name="payment_method" id="methodCOD" value="cash_on_delivery" <?php echo ($payment_method === 'cash_on_delivery') ? 'checked' : ''; ?>>
                                        <label class="form-check-label w-100 ps-2 cursor-pointer" for="methodCOD">
                                            <span class="fw-bold font-poppins text-dark d-block">Cash on Delivery</span>
                                            <span class="text-muted small">Pay in cash when your order arrives.</span>
                                        </label>
                                    </div>

                                    <div class="mt-3 pt-3 border-top <?php echo ($payment_method === 'cash_on_delivery') ? '' : 'd-none'; ?>" id="codFields">
                                        <div class="bg-light border rounded-3 p-3 small text-dark fw-medium">
                                            Prepare &#8369;<?php echo number_format($total_amount, 2); ?> in cash. Pay the courier when your order is delivered.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border shadow-sm rounded-4 p-4 bg-white">
                            <h2 class="h5 fw-bold font-poppins text-dark mb-4">Order Summary</h2>

                            <div class="d-flex flex-column gap-3 mb-3">
                                <?php foreach ($cart_items as $item) { ?>
                                    <div class="d-flex justify-content-between align-items-start gap-2 small">
                                        <span class="text-muted text-truncate" style="max-width: 55%;"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                        <div class="d-flex align-items-center gap-3 flex-shrink-0">
                                            <span class="text-dark fw-medium">&times; <?php echo intval($item['quantity']); ?></span>
                                            <span class="text-dark fw-semibold">&#8369;<?php echo number_format($item['line_total'], 2); ?></span>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3 small">
                                <span class="text-muted">Subtotal</span>
                                <span class="fw-semibold text-dark">&#8369;<?php echo number_format($subtotal, 2); ?></span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pb-3 mb-3 border-bottom small">
                                <span class="text-muted">Shipping Fee</span>
                                <span class="fw-semibold text-dark">&#8369;<?php echo number_format($shipping_fee, 2); ?></span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="fw-bold text-dark fs-5">Total</span>
                                <span class="fw-bold text-dark fs-5">&#8369;<?php echo number_format($total_amount, 2); ?></span>
                            </div>

                            <button type="button" id="placeOrderBtn" class="btn btn-dark-custom w-100 py-2.5 rounded-3 fw-semibold small shadow-sm mb-2" onclick="validateAndOpenModal()">
                                Place Order
                            </button>

                            <a href="checkout.php" class="btn btn-outline-custom w-100 py-2.5 rounded-3 fw-semibold small shadow-sm text-decoration-none text-center">
                                <i class="bi bi-arrow-left me-1"></i> Back to Checkout
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <?php include '../includes/footer.php' ?>

    <div class="modal fade" id="confirmOrderModal" tabindex="-1" aria-labelledby="confirmOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4 p-3 bg-white">
                <div class="modal-header border-0 pb-0">
                    <h3 class="modal-title h4 fw-bold font-poppins text-dark" id="confirmOrderModalLabel">Place this order?</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="text-muted small mb-4">Once placed, your order will be sent to the store as Pending.</p>
                                    
                    <div class="bg-light rounded-3 p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2 small">
                            <span class="text-muted">Payment method</span>
                            <span id="modalMethodText" class="fw-semibold text-dark">-</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <span class="fw-bold text-dark">Total</span>
                            <span class="fw-bold text-dark fs-5">&#8369;<?php echo number_format($total_amount, 2); ?></span>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-sm-end">
                        <button type="button" onclick="document.getElementById('paymentForm').submit();" class="btn btn-dark-custom w-sm-100 py-2.5 rounded-3 fw-semibold small order-1 order-sm-2 shadow-sm">
                            Yes, Place Order
                        </button>
                        <button type="button" class="btn btn-outline-custom w-sm-100 py-2.5 rounded-3 fw-semibold small order-2 order-sm-1 shadow-sm" data-bs-dismiss="modal">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const radioOnline = document.getElementById('methodOnline');
            const radioCOD = document.getElementById('methodCOD');
            if (radioOnline.checked) {
                selectPaymentMethod('online_payment', false);
            } else if (radioCOD.checked) {
                selectPaymentMethod('cash_on_delivery', false);
            }
        });

        function selectPaymentMethod(method) {
            const radioOnline = document.getElementById('methodOnline');
            const radioCOD = document.getElementById('methodCOD');
            const cardOnline = document.getElementById('cardOnline');
            const cardCOD = document.getElementById('cardCOD');
            const onlineFields = document.getElementById('onlineFields');
            const codFields = document.getElementById('codFields');
            const headerTitle = document.getElementById('headerTitle');
            const headerDesc = document.getElementById('headerDesc');
            const pageTitle = document.getElementById('pageTitle');
            const placeOrderBtn = document.getElementById('placeOrderBtn');
            const modalMethodText = document.getElementById('modalMethodText');

            if (method === 'online_payment') {
                radioOnline.checked = true;
                cardOnline.classList.add('selected');
                cardCOD.classList.remove('selected');
                onlineFields.classList.remove('d-none');
                codFields.classList.add('d-none');

                headerTitle.textContent = 'Online Payment';
                headerDesc.textContent = 'Enter your payment details to complete your payment.';
                pageTitle.textContent = 'Online Payment';
                modalMethodText.textContent = 'Online Payment';
            } else if (method === 'cash_on_delivery') {
                radioCOD.checked = true;
                cardCOD.classList.add('selected');
                cardOnline.classList.remove('selected');
                codFields.classList.remove('d-none');
                onlineFields.classList.add('d-none');

                headerTitle.textContent = 'Cash on Delivery';
                headerDesc.textContent = 'Pay in cash when your order arrives.';
                pageTitle.textContent = 'Cash on Delivery';
                modalMethodText.textContent = 'Cash on Delivery';
            }

            placeOrderBtn.removeAttribute('disabled');
        }

        function validateAndOpenModal() {
            const radioOnline = document.getElementById('methodOnline').checked;
            const radioCOD = document.getElementById('methodCOD').checked;
            const errorBox = document.getElementById('errorAlertBox');
            const errorText = document.getElementById('errorAlertText');

            if (!radioOnline && !radioCOD) {
                errorText.textContent = 'Please select a payment method before placing your order.';
                errorBox.classList.remove('d-none');
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            if (radioOnline) {
                const channel = document.getElementById('paymentChannel').value;
                const name = document.getElementById('accountName').value.trim();
                const number = document.getElementById('accountNumber').value.trim();

                if (!channel || !name || !number) {
                    errorText.textContent = 'Please complete all required online payment fields before placing your order.';
                    errorBox.classList.remove('d-none');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }
            }

            errorBox.classList.add('d-none');
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmOrderModal'));
            confirmModal.show();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>