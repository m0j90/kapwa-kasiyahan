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

if ($customer_id === 0 && !empty($_SESSION['username'])) {
    $u_stmt = mysqli_prepare($Connection, "SELECT customer_id FROM customers WHERE username = ? LIMIT 1");
    mysqli_stmt_bind_param($u_stmt, 's', $_SESSION['username']);
    mysqli_stmt_execute($u_stmt);
    $u_res = mysqli_stmt_get_result($u_stmt);
    if ($u_row = mysqli_fetch_assoc($u_res)) {
        $customer_id = intval($u_row['customer_id']);
        $_SESSION['customer_id'] = $customer_id;
    }
    mysqli_stmt_close($u_stmt);
}

$cart_id = 0;
$c_stmt = mysqli_prepare($Connection, "SELECT cart_id FROM carts WHERE customer_id = ? AND cart_status = 'active' LIMIT 1");
mysqli_stmt_bind_param($c_stmt, 'i', $customer_id);
mysqli_stmt_execute($c_stmt);
$c_res = mysqli_stmt_get_result($c_stmt);
if ($c_row = mysqli_fetch_assoc($c_res)) {
    $cart_id = intval($c_row['cart_id']);
}
mysqli_stmt_close($c_stmt);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['add_to_cart'])) {
        $product_id = intval($_POST['product_id'] ?? 0);
        $add_qty = max(1, intval($_POST['quantity'] ?? 1));

        if ($product_id > 0 && $customer_id > 0) {
            if ($cart_id === 0) {
                $cart_ins = mysqli_prepare($Connection, "INSERT INTO carts (customer_id, cart_status) VALUES (?, 'active')");
                mysqli_stmt_bind_param($cart_ins, 'i', $customer_id);
                mysqli_stmt_execute($cart_ins);
                $cart_id = mysqli_insert_id($Connection);
                mysqli_stmt_close($cart_ins);
            }

            $chk_item = mysqli_prepare($Connection, "SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
            mysqli_stmt_bind_param($chk_item, 'ii', $cart_id, $product_id);
            mysqli_stmt_execute($chk_item);
            $chk_res = mysqli_stmt_get_result($chk_item);
            
            if ($existing = mysqli_fetch_assoc($chk_res)) {
                $new_quantity = $existing['quantity'] + $add_qty;
                $upd_item = mysqli_prepare($Connection, "UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
                mysqli_stmt_bind_param($upd_item, 'ii', $new_quantity, $existing['cart_item_id']);
                mysqli_stmt_execute($upd_item);
                mysqli_stmt_close($upd_item);
            } else {
                $ins_item = mysqli_prepare($Connection, "INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($ins_item, 'iii', $cart_id, $product_id, $add_qty);
                mysqli_stmt_execute($ins_item);
                mysqli_stmt_close($ins_item);
            }
            mysqli_stmt_close($chk_item);
        }

        header('Location: shopping-cart.php');
        exit();
    }

    if ($cart_id > 0 && isset($_POST['action'])) {
        $action = $_POST['action'];
        $cart_item_id = intval($_POST['cart_item_id'] ?? 0);

        if ($cart_item_id > 0) {
            if ($action === 'remove') {
                $del_stmt = mysqli_prepare($Connection, "DELETE FROM cart_items WHERE cart_item_id = ? AND cart_id = ?");
                mysqli_stmt_bind_param($del_stmt, 'ii', $cart_item_id, $cart_id);
                mysqli_stmt_execute($del_stmt);
                mysqli_stmt_close($del_stmt);
            } else {
                $item_stmt = mysqli_prepare($Connection, "
                    SELECT ci.quantity, p.stock 
                    FROM cart_items ci 
                    INNER JOIN products p ON ci.product_id = p.product_id 
                    WHERE ci.cart_item_id = ? AND ci.cart_id = ?
                ");
                if ($item_stmt) {
                    mysqli_stmt_bind_param($item_stmt, 'ii', $cart_item_id, $cart_id);
                    mysqli_stmt_execute($item_stmt);
                    $item_res = mysqli_stmt_get_result($item_stmt);
                    $item_data = mysqli_fetch_assoc($item_res);
                    mysqli_stmt_close($item_stmt);

                    if ($item_data) {
                        $current_qty = intval($item_data['quantity']);
                        $max_stock = intval($item_data['stock']);
                        $new_qty = $current_qty;

                        if ($action === 'increase') {
                            $new_qty = min($max_stock, $current_qty + 1);
                        } elseif ($action === 'decrease') {
                            $new_qty = $current_qty - 1;
                        } elseif ($action === 'set_qty') {
                            $input_qty = intval($_POST['quantity'] ?? 1);
                            $new_qty = max(0, min($max_stock, $input_qty));
                        }

                        if ($new_qty <= 0) {
                            $del_stmt = mysqli_prepare($Connection, "DELETE FROM cart_items WHERE cart_item_id = ? AND cart_id = ?");
                            mysqli_stmt_bind_param($del_stmt, 'ii', $cart_item_id, $cart_id);
                            mysqli_stmt_execute($del_stmt);
                            mysqli_stmt_close($del_stmt);
                        } else {
                            $upd_stmt = mysqli_prepare($Connection, "UPDATE cart_items SET quantity = ? WHERE cart_item_id = ? AND cart_id = ?");
                            mysqli_stmt_bind_param($upd_stmt, 'iii', $new_qty, $cart_item_id, $cart_id);
                            mysqli_stmt_execute($upd_stmt);
                            mysqli_stmt_close($upd_stmt);
                        }
                    }
                }
            }
        }

        header('Location: shopping-cart.php');
        exit();
    }
}

$cart_items = [];
$subtotal = 0.00;
$shipping_fee = 80.00;

if ($cart_id > 0) {
    $sql = "
        SELECT 
            ci.cart_item_id,
            ci.quantity,
            p.product_id,
            p.product_name,
            p.product_image,
            p.price,
            p.stock AS stock_quantity
        FROM cart_items ci
        INNER JOIN products p ON ci.product_id = p.product_id
        WHERE ci.cart_id = ?
        ORDER BY ci.added_at DESC
    ";
    $stmt = mysqli_prepare($Connection, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $cart_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($Row = mysqli_fetch_assoc($res)) {
            $line_total = floatval($Row['price']) * intval($Row['quantity']);
            $Row['line_total'] = $line_total;
            $subtotal += $line_total;
            $cart_items[] = $Row;
        }
        mysqli_stmt_close($stmt);
    }
}

$total_amount = $subtotal + $shipping_fee;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
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
                    <li class="breadcrumb-item active" aria-current="page">Shopping Cart</li>
                </ol>
            </nav>
            <h1 class="fw-bold display-5 font-poppins text-dark">Shopping Cart</h1>
            <p class="text-muted mb-0">Review the items in your cart before checkout.</p>
        </div>
    </header>

    <main class="py-5">
        <div class="container px-4 px-lg-5">
            <?php if (empty($cart_items)) { ?>
                <div class="row justify-content-center py-4">
                    <div class="col-md-7 col-lg-5">
                        <div class="card border shadow-sm rounded-4 p-5 bg-white text-center">
                            <div class="rounded-circle bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 72px; height: 72px;">
                                <i class="bi bi-bag-x fs-2"></i>
                            </div>
                            <h2 class="h4 fw-bold font-poppins text-dark mb-2">Your cart is empty</h2>
                            <p class="text-muted small mb-4">Browse our products and add items to see them here.</p>
                            <div>
                                <a href="../products.php" class="btn btn-dark-custom px-4 py-2.5 rounded-3 fw-semibold small shadow-sm text-decoration-none">
                                    Browse Products
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } else { ?>
                <div class="row g-4 align-items-start">
                    <div class="col-lg-8">
                        <div class="card border shadow-sm rounded-4 bg-white overflow-hidden">
                            <div class="d-none d-md-grid bg-light border-bottom px-4 py-3 text-muted small fw-semibold" style="grid-template-columns: 96px 2fr 1fr 1.3fr 1fr auto; align-items: center;">
                                <div></div>
                                <div>Product</div>
                                <div>Price</div>
                                <div>Quantity</div>
                                <div>Total</div>
                                <div class="text-end" style="width: 55px;"></div>
                            </div>

                            <div class="divide-y">
                                <?php foreach ($cart_items as $index => $item) {
                                    $stock = intval($item['stock_quantity']);
                                    $qty = intval($item['quantity']);
                                    $isLowStock = ($stock > 0 && $stock <= 5);
                                    $borderClass = ($index < count($cart_items) - 1) ? 'border-bottom' : '';
                                ?>
                                    <div class="p-4 <?php echo $borderClass; ?>">
                                        
                                        <div class="d-none d-md-grid align-items-center" style="grid-template-columns: 96px 2fr 1fr 1.3fr 1fr auto;">
                                            <div class="cart-item-img">
                                                <?php if (!empty($item['product_image'])) { ?>
                                                    <img src="../uploads/<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                                <?php } else { ?>
                                                    <div class="product-thumb">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                <?php } ?>
                                            </div>

                                            <div class="pe-3">
                                                <h3 class="h6 fw-bold font-poppins text-dark mb-1">
                                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                                </h3>
                                                <?php if ($isLowStock) { ?>
                                                    <span class="text-warning-emphasis small fw-medium">Only <?php echo $stock; ?> left</span>
                                                <?php } else { ?>
                                                    <span class="text-muted small">In stock &middot; <?php echo $stock; ?> available</span>
                                                <?php } ?>
                                            </div>

                                            <div class="fw-medium text-dark">
                                                &#8369;<?php echo number_format($item['price'], 2); ?>
                                            </div>

                                            <div>
                                                <form method="POST" action="shopping-cart.php" class="d-inline">
                                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                                    <div class="qty-stepper">
                                                        <button type="submit" name="action" value="decrease" aria-label="Decrease quantity">&minus;</button>
                                                        <input type="number" name="quantity" value="<?php echo $qty; ?>" min="1" max="<?php echo $stock; ?>" onchange="this.form.action.value='set_qty'; this.form.submit();" aria-label="Item quantity">
                                                        <button type="submit" name="action" value="increase" <?php echo ($qty >= $stock) ? 'disabled' : ''; ?> aria-label="Increase quantity">&plus;</button>
                                                    </div>
                                                </form>
                                            </div>

                                            <div class="fw-bold text-dark">
                                                &#8369;<?php echo number_format($item['line_total'], 2); ?>
                                            </div>

                                            <div class="text-end" style="width: 55px;">
                                                <form method="POST" action="shopping-cart.php" class="d-inline">
                                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                                    <button type="submit" name="action" value="remove" class="btn-remove-link">Remove</button>
                                                </form>
                                            </div>
                                        </div>

                                        <div class="d-flex d-md-none gap-3">
                                            <div class="cart-item-img">
                                                <?php if (!empty($item['product_image'])) { ?>
                                                    <img src="../uploads/<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                                <?php } else { ?>
                                                    <div class="product-thumb">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                <?php } ?>
                                            </div>

                                            <div class="flex-grow-1">
                                                <h3 class="h6 fw-bold font-poppins text-dark mb-1">
                                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                                </h3>
                                                <div class="mb-2">
                                                    <?php if ($isLowStock) { ?>
                                                        <span class="text-warning-emphasis small fw-medium">Only <?php echo $stock; ?> left</span>
                                                    <?php } else { ?>
                                                        <span class="text-muted small">In stock &middot; <?php echo $stock; ?> available</span>
                                                    <?php } ?>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <form method="POST" action="shopping-cart.php" class="d-inline">
                                                        <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                                        <div class="qty-stepper">
                                                            <button type="submit" name="action" value="decrease" aria-label="Decrease quantity">&minus;</button>
                                                            <input type="number" name="quantity" value="<?php echo $qty; ?>" min="1" max="<?php echo $stock; ?>" readonly>
                                                            <button type="submit" name="action" value="increase" <?php echo ($qty >= $stock) ? 'disabled' : ''; ?> aria-label="Increase quantity">&plus;</button>
                                                        </div>
                                                    </form>

                                                    <span class="fw-bold text-dark fs-6">
                                                        &#8369;<?php echo number_format($item['line_total'], 2); ?>
                                                    </span>
                                                </div>

                                                <form method="POST" action="shopping-cart.php" class="d-inline">
                                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                                    <button type="submit" name="action" value="remove" class="btn-remove-link">Remove</button>
                                                </form>
                                            </div>
                                        </div>

                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="d-none d-lg-block mt-4">
                            <a href="../products.php" class="btn btn-outline-custom px-4 py-2.5 rounded-3 fw-semibold small shadow-sm text-decoration-none">
                                <i class="bi bi-arrow-left me-1"></i> Continue Shopping
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border shadow-sm rounded-4 p-4 bg-white">
                            <h2 class="h5 fw-bold font-poppins text-dark mb-4">Order Summary</h2>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted small">Subtotal</span>
                                <span class="fw-semibold text-dark">&#8369;<?php echo number_format($subtotal, 2); ?></span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pb-3 mb-3 border-bottom">
                                <span class="text-muted small">Shipping Fee</span>
                                <span class="fw-semibold text-dark">&#8369;<?php echo number_format($shipping_fee, 2); ?></span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="fw-bold text-dark fs-5">Total</span>
                                <span class="fw-bold text-dark fs-5">&#8369;<?php echo number_format($total_amount, 2); ?></span>
                            </div>

                            <a href="checkout.php" class="btn btn-dark-custom w-100 py-2.5 rounded-3 fw-semibold small shadow-sm text-decoration-none text-center">
                                Proceed to Checkout
                            </a>
                        </div>

                        <div class="d-block d-lg-none mt-3">
                            <a href="../products.php" class="btn btn-outline-custom bg-white text-dark w-100 py-2.5 rounded-3 fw-semibold small shadow-sm text-decoration-none text-center">
                                <i class="bi bi-arrow-left me-1"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </main>

    <?php include '../includes/footer.php' ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>