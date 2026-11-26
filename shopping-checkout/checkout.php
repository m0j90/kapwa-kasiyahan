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

$customer = null;
if ($customer_id > 0) {
    $c_stmt = mysqli_prepare($Connection, "SELECT customer_id, first_name, last_name, contact, address FROM customers WHERE customer_id = ? LIMIT 1");
    mysqli_stmt_bind_param($c_stmt, 'i', $customer_id);
} else {
    $c_stmt = mysqli_prepare($Connection, "SELECT customer_id, first_name, last_name, contact, address FROM customers WHERE username = ? LIMIT 1");
    mysqli_stmt_bind_param($c_stmt, 's', $_SESSION['username']);
}
mysqli_stmt_execute($c_stmt);
$c_res = mysqli_stmt_get_result($c_stmt);
if ($row = mysqli_fetch_assoc($c_res)) {
    $customer = $row;
    $customer_id = intval($row['customer_id']);
    $_SESSION['customer_id'] = $customer_id;
}
mysqli_stmt_close($c_stmt);

$cart_id = 0;
$cart_stmt = mysqli_prepare($Connection, "SELECT cart_id FROM carts WHERE customer_id = ? AND cart_status = 'active' LIMIT 1");
mysqli_stmt_bind_param($cart_stmt, 'i', $customer_id);
mysqli_stmt_execute($cart_stmt);
$cart_res = mysqli_stmt_get_result($cart_stmt);
if ($cart_row = mysqli_fetch_assoc($cart_res)) {
    $cart_id = intval($cart_row['cart_id']);
}
mysqli_stmt_close($cart_stmt);

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

$saved_checkout = $_SESSION['checkout_details'] ?? [];
$default_full_name = $saved_checkout['full_name'] ?? trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
$default_contact = $saved_checkout['contact_number'] ?? ($customer['contact'] ?? '');
$default_address = $saved_checkout['delivery_address'] ?? ($customer['address'] ?? '');
$default_notes = $saved_checkout['order_notes'] ?? '';

$errors = [];
$full_name = $default_full_name;
$contact_number = $default_contact;
$delivery_address = $default_address;
$order_notes = $default_notes;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $order_notes = trim($_POST['order_notes'] ?? '');

    if ($full_name === '') {
        $errors['full_name'] = 'Full name is required.';
    }
    if ($contact_number === '') {
        $errors['contact_number'] = 'Contact number is required.';
    }
    if ($delivery_address === '') {
        $errors['delivery_address'] = 'Delivery address is required.';
    }

    foreach ($cart_items as $item) {
        if (intval($item['quantity']) > intval($item['stock'])) {
            $errors['stock'] = 'Insufficient stock for "' . htmlspecialchars($item['product_name']) . '". Please adjust quantity in your cart.';
            break;
        }
    }

    if (empty($errors)) {
        $_SESSION['checkout_details'] = [
            'cart_id'          => $cart_id,
            'full_name'        => $full_name,
            'contact_number'   => $contact_number,
            'delivery_address' => $delivery_address,
            'order_notes'      => $order_notes,
            'subtotal'         => $subtotal,
            'shipping_fee'     => $shipping_fee,
            'total_amount'     => $total_amount
        ];

        header('Location: payment.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
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
                    <li class="breadcrumb-item"><a href="shopping-cart.php" class="text-decoration-none text-success">Shopping Cart</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Checkout</li>
                </ol>
            </nav>
            <h1 class="fw-bold display-5 font-poppins text-dark">Checkout</h1>
            <p class="text-muted mb-0">Confirm your delivery details.</p>
        </div>
    </header>

    <main class="py-5">
        <div class="container px-4 px-lg-5">
            <div class="d-none d-md-block mb-5">
                <div class="checkout-stepper">
                    <div class="step-item completed">
                        <span class="step-circle">1</span>
                        <span class="step-label">Cart</span>
                    </div>
                    <div class="step-connector completed"></div>
                    <div class="step-item active">
                        <span class="step-circle">2</span>
                        <span class="step-label">Checkout</span>
                    </div>
                    <div class="step-connector"></div>
                    <div class="step-item">
                        <span class="step-circle">3</span>
                        <span class="step-label">Payment</span>
                    </div>
                    <div class="step-connector"></div>
                    <div class="step-item">
                        <span class="step-circle">4</span>
                        <span class="step-label">Confirmation</span>
                    </div>
                </div>
            </div>

            <div class="d-block d-md-none mb-4">
                <p class="small fw-semibold text-dark mb-2">Step 2 of 4 &middot; Checkout</p>
                <div class="mobile-step-bars">
                    <div class="mobile-step-bar filled"></div>
                    <div class="mobile-step-bar filled"></div>
                    <div class="mobile-step-bar"></div>
                    <div class="mobile-step-bar"></div>
                </div>
            </div>

            <?php if (!empty($errors['stock'])) { ?>
                <div class="alert-danger-custom mb-4">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?php echo $errors['stock']; ?></span>
                </div>
            <?php } ?>

            <form method="POST" action="checkout.php" novalidate>
                <div class="row g-4 align-items-start">
                    <div class="col-lg-8">
                        <div class="card border shadow-sm rounded-4 p-4 p-md-5 bg-white">
                            <h2 class="h4 fw-bold font-poppins text-dark mb-4">Delivery Details</h2>

                            <div class="mb-4">
                                <label for="full_name" class="form-label-custom">Full Name</label>
                                <input 
                                    type="text" 
                                    id="full_name" 
                                    name="full_name" 
                                    class="form-control form-control-custom <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>" 
                                    placeholder="Juan Dela Cruz" 
                                    value="<?php echo htmlspecialchars($full_name); ?>" 
                                    required
                                >
                                <?php if (isset($errors['full_name'])) { ?>
                                    <div class="input-error-text"><?php echo $errors['full_name']; ?></div>
                                <?php } ?>
                            </div>

                            <div class="mb-4">
                                <label for="contact_number" class="form-label-custom">Contact Number</label>
                                <input 
                                    type="text" 
                                    id="contact_number" 
                                    name="contact_number" 
                                    class="form-control form-control-custom <?php echo isset($errors['contact_number']) ? 'is-invalid' : ''; ?>" 
                                    placeholder="09XX XXX XXXX" 
                                    value="<?php echo htmlspecialchars($contact_number); ?>" 
                                    required
                                >
                                <?php if (isset($errors['contact_number'])) { ?>
                                    <div class="input-error-text"><?php echo $errors['contact_number']; ?></div>
                                <?php } ?>
                            </div>

                            <div class="mb-4">
                                <label for="delivery_address" class="form-label-custom">Delivery Address</label>
                                <textarea 
                                    id="delivery_address" 
                                    name="delivery_address" 
                                    rows="4" 
                                    class="form-control form-control-custom <?php echo isset($errors['delivery_address']) ? 'is-invalid' : ''; ?>" 
                                    placeholder="House no., street, barangay, city" 
                                    required
                                ><?php echo htmlspecialchars($delivery_address); ?></textarea>
                                <?php if (isset($errors['delivery_address'])) { ?>
                                    <div class="input-error-text"><?php echo $errors['delivery_address']; ?></div>
                                <?php } ?>
                            </div>

                            <div class="mb-3">
                                <label for="order_notes" class="form-label-custom">Order Notes (optional)</label>
                                <textarea 
                                    id="order_notes" 
                                    name="order_notes" 
                                    rows="4" 
                                    class="form-control form-control-custom" 
                                    placeholder="Anything the seller should know?"
                                ><?php echo htmlspecialchars($order_notes); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border shadow-sm rounded-4 p-4 bg-white">
                            <h2 class="h5 fw-bold font-poppins text-dark mb-4">Order Summary</h2>

                            <div class="d-flex flex-column gap-3 mb-3">
                                <?php foreach ($cart_items as $item) { ?>
                                    <div class="d-flex justify-content-between align-items-start gap-2 small">
                                        <span class="text-muted text-truncate" style="max-width: 55%;">
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                        </span>
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

                            <button type="submit" class="btn btn-dark-custom w-100 py-2.5 rounded-3 fw-semibold small shadow-sm mb-2">
                                Continue to Payment
                            </button>

                            <a href="shopping-cart.php" class="btn btn-outline-custom w-100 py-2.5 rounded-3 fw-semibold small shadow-sm text-decoration-none text-center">
                                <i class="bi bi-arrow-left me-1"></i> Back to Cart
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <?php include '../includes/footer.php' ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>