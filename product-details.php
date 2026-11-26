<?php
session_start();

date_default_timezone_set('Asia/Manila');
include 'db_connect.php';

$productId = $_GET['id'] ?? '';
$productId = is_scalar($productId) ? (string) $productId : '';

$stmt = mysqli_prepare($Connection, "SELECT * FROM products WHERE product_id = ?");
mysqli_stmt_bind_param($stmt, 's', $productId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$relatedResult = null;
if ($product) {
    $category = $product['category'] ?? '';
    $relatedStmt = mysqli_prepare($Connection, "SELECT * FROM products WHERE category = ? AND product_id != ? LIMIT 4");
    mysqli_stmt_bind_param($relatedStmt, 'ss', $category, $productId);
    mysqli_stmt_execute($relatedStmt);
    $relatedResult = mysqli_stmt_get_result($relatedStmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product ? htmlspecialchars($product['product_name']) : 'Product Details'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="assets/images/logos/transparent-symbol-logo.png">
</head>
<body>

    <?php 
        $activePage = 'products';
        include 'includes/navbar.php';
    ?>

    <header class="py-5 bg-white border-bottom">
        <div class="container px-4 px-lg-5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-success">Home</a></li>
                    <li class="breadcrumb-item"><a href="products.php" class="text-decoration-none text-success">Products</a></li>
                    <li class="breadcrumb-item active text-dark" aria-current="page"><?php echo $product ? htmlspecialchars($product['product_name']) : 'Product Details'; ?></li>
                </ol>
            </nav>
            <h1 class="fw-bold display-5 font-poppins text-dark">Product Details</h1>
            <p class="lead text-muted mb-0">Review the item, check availability and add it to your cart.</p>
        </div>
    </header>

    <main class="py-5">
        <div class="container px-4 px-lg-5">
            <?php if (!$product) { ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                    <h2 class="font-poppins fw-bold text-dark mb-2">Product Not Found</h2>
                    <p class="text-muted small mb-3">The product you are looking for does not exist or has been removed.</p>
                    <div>
                        <a href="products.php" class="btn btn-dark-custom px-4 py-2 rounded-3 text-decoration-none">Back to Products</a>
                    </div>
                </div>
            <?php } else {
                $stock = (int)$product['stock'];
                $maxStock = max($stock, 1);
            ?>

                <?php if (isset($_GET['added'])) { ?>
                    <div class="alert bg-white border shadow-sm rounded-3 py-3 px-3 mb-4 d-flex align-items-center justify-content-between" role="alert">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-dark rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 20px; height: 20px;"><i class="bi bi-check small text-white"></i></span>
                            <span class="small fw-semibold text-dark">Added to cart: <?php echo htmlspecialchars($product['product_name']); ?> &times; 1</span>
                        </div>
                        <a href="cart.php" class="btn btn-sm btn-outline-custom py-1 text-decoration-none">View Cart</a>
                    </div>
                <?php } ?>

                <div class="row g-5 mb-5 align-items-start">
                    <div class="col-lg-6">
                        <div class="bg-light rounded-4 overflow-hidden border shadow-sm d-flex align-items-center justify-content-center" style="height: 440px;">
                            <?php if (!empty($product['product_image'])) { ?>
                                <img src="uploads/<?php echo htmlspecialchars($product['product_image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="w-100 h-100 object-fit-cover">
                            <?php } else { ?>
                                <div class="d-flex align-items-center justify-content-center text-muted">No Product Image Available</div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <h2 class="fw-bold font-poppins text-dark mb-2 fs-2"><?php echo htmlspecialchars($product['product_name']); ?></h2>
                        <h3 class="fw-bold text-dark fs-3 mb-3">&#8369;<?php echo number_format((float)$product['price'], 2); ?></h3>

                        <div class="mb-3">
                            <span class="badge bg-white text-dark border px-3 py-2 rounded-3 small fw-medium">
                                <?php echo $stock > 0 ? 'In stock · ' . $stock . ' available' : 'Out of stock'; ?>
                            </span>
                        </div>

                        <p class="text-muted mb-4 small" style="line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($product['description'] ?: 'No description provided.')); ?>
                        </p>

                        <form action="shopping-checkout/shopping-cart.php" method="POST" class="mb-4">
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="add_to_cart" value="1">
                            
                            <div class="mb-4 d-flex align-items-center gap-3">
                                <label class="form-label-custom small fw-semibold mb-0 text-nowrap">Quantity</label>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="input-group" style="width: 130px;">
                                        <button type="button" class="btn btn-outline-secondary border-secondary-subtle px-2 py-1" onclick="decrementQty()">-</button>
                                        <input type="text" id="qtyInput" name="quantity" value="1" class="form-control text-center bg-white border-secondary-subtle fw-semibold py-1" readonly>
                                        <button type="button" class="btn btn-outline-secondary border-secondary-subtle px-2 py-1" onclick="incrementQty(<?php echo $maxStock; ?>)">+</button>
                                    </div>
                                    <span class="text-muted small text-nowrap">Max <?php echo $maxStock; ?></span>
                                </div>
                            </div>

                            <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2">
                                <button type="submit" class="btn btn-dark-custom px-4 py-2.5 fw-semibold <?php echo $stock <= 0 ? 'disabled' : ''; ?>">
                                    Add to Cart
                                </button>
                                <a href="products.php" class="btn btn-outline-custom px-4 py-2.5 fw-semibold text-center text-decoration-none">
                                    Back to Products
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card border rounded-4 p-4 mb-5 shadow-sm bg-white">
                    <h4 class="fw-bold font-poppins text-dark mb-3 fs-5">Product Details</h4>
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0 small">
                            <tbody>
                                <tr class="border-bottom">
                                    <td class="text-muted py-3" style="width: 30%;">Category</td>
                                    <td class="fw-semibold text-dark py-3"><?php echo htmlspecialchars($product['category'] ?? 'Product category'); ?></td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted py-3">Product ID</td>
                                    <td class="fw-semibold text-dark py-3"><?php echo htmlspecialchars($product['product_id']); ?></td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted py-3">Availability</td>
                                    <td class="fw-semibold text-dark py-3"><?php echo $stock > 0 ? 'In stock · ' . $stock . ' available' : 'Out of stock'; ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-3">Unit</td>
                                    <td class="fw-semibold text-dark py-3">Per piece</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold font-poppins text-dark mb-0 fs-4">Related Products</h4>
                        <a href="products.php" class="text-dark small fw-semibold text-decoration-none">View all &rarr;</a>
                    </div>

                    <div class="row g-3 g-lg-4">
                        <?php if ($relatedResult && mysqli_num_rows($relatedResult) > 0) {
                            while ($rel = mysqli_fetch_assoc($relatedResult)) {
                                $relStock = (int)$rel['stock'];
                        ?>
                            <div class="col-6 col-lg-3">
                                <div class="card h-100 border rounded-3 shadow-sm bg-white overflow-hidden">
                                    <a href="product-details.php?id=<?php echo urlencode($rel['product_id']); ?>" class="text-decoration-none bg-light d-block" style="height: 180px;">
                                        <?php if (!empty($rel['product_image'])) { ?>
                                            <img src="uploads/<?php echo htmlspecialchars($rel['product_image']); ?>" alt="<?php echo htmlspecialchars($rel['product_name']); ?>" class="w-100 h-100 object-fit-cover">
                                        <?php } else { ?>
                                            <i class="bi bi-image w-100 h-100 d-flex align-items-center justify-content-center text-muted small"></i>
                                        <?php } ?>
                                    </a>
                                    <div class="card-body d-flex flex-column p-3">
                                        <a href="product-details.php?id=<?php echo urlencode($rel['product_id']); ?>" class="text-decoration-none text-dark fw-semibold mb-1 text-truncate small">
                                            <?php echo htmlspecialchars($rel['product_name']); ?>
                                        </a>
                                        <div class="text-muted small mb-2">In stock</div>
                                        <div class="fw-bold text-dark mb-3 small">&#8369;<?php echo number_format((float)$rel['price'], 2); ?></div>
                                        
                                        <div class="mt-auto">
                                            <form action="shopping-checkout/shopping-cart.php" method="POST">
                                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($rel['product_id']); ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="btn btn-dark-custom w-100 py-1.5 small fw-semibold <?php echo $relStock <= 0 ? 'disabled' : ''; ?>">
                                                    <?php echo $relStock <= 0 ? 'Unavailable' : 'Add'; ?>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } 
                        } else { ?>
                            <div class="col-12 text-muted small">No related items found in this category.</div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </main>

    <?php include 'includes/footer.php' ?>

    <script>
        function incrementQty(max) {
            const input = document.getElementById('qtyInput');
            let val = parseInt(input.value);
            if (val < max) {
                input.value = val + 1;
            }
        }
        function decrementQty() {
            const input = document.getElementById('qtyInput');
            let val = parseInt(input.value);
            if (val > 1) {
                input.value = val - 1;
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>