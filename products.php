<?php
session_start();

date_default_timezone_set('Asia/Manila');
include 'db_connect.php';

$categories = ['Button Pins', 'Bookmarks', 'Elastic Band Pen Holders'];
$selectedCategory = trim($_GET['category'] ?? '');

$limit = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

if (!empty($selectedCategory) && in_array($selectedCategory, $categories)) {
    $countStmt = mysqli_prepare($Connection, "SELECT COUNT(*) as total FROM products WHERE category = ?");
    mysqli_stmt_bind_param($countStmt, 's', $selectedCategory);
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    $countRow = mysqli_fetch_assoc($countResult);
    mysqli_stmt_close($countStmt);
} else {
    $countResult = mysqli_query($Connection, "SELECT COUNT(*) as total FROM products");
    $countRow = mysqli_fetch_assoc($countResult);
}
$totalProducts = $countRow['total'] ?? 0;
$totalPages = ceil($totalProducts / $limit);

if (!empty($selectedCategory) && in_array($selectedCategory, $categories)) {
    $stmt = mysqli_prepare($Connection, "SELECT * FROM products WHERE category = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($stmt, 'sii', $selectedCategory, $limit, $offset);
} else {
    $stmt = mysqli_prepare($Connection, "SELECT * FROM products ORDER BY created_at DESC LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($stmt, 'ii', $limit, $offset);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
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
                    <li class="breadcrumb-item active" aria-current="page">Products</li>
                </ol>
            </nav>
            <h1 class="fw-bold display-5 font-poppins text-dark">Products</h1>
            <p class="lead text-muted mb-0">Browse all available products.</p>
        </div>
    </header>

    <main class="py-5">
        <div class="container px-4 px-lg-5">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4 pb-3 border-bottom">
                <div class="text-muted small">
                    Showing <?php echo $totalProducts > 0 ? ($offset + 1) . '–' . min($offset + $limit, $totalProducts) : 0; ?> of <?php echo $totalProducts; ?> products
                </div>
                <form method="GET" action="products.php" class="d-flex align-items-center gap-2" style="width: 100%; max-width: 260px;">
                    <select name="category" class="form-select form-select-sm form-control-custom bg-white py-2" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat) { ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($selectedCategory === $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php } ?>
                    </select>
                </form>
            </div>

            <div class="row g-3 g-lg-4">
                <?php if (mysqli_num_rows($result) > 0) { 
                    while ($Row = mysqli_fetch_assoc($result)) {
                        $stock = (int)$Row['stock'];
                        if ($stock <= 0) {
                            $stockText = 'Out of stock';
                            $badgeClass = 'text-muted';
                        } elseif ($stock <= 3) {
                            $stockText = 'Only ' . $stock . ' left';
                            $badgeClass = 'text-danger fw-semibold';
                        } else {
                            $stockText = 'In stock · ' . $stock . ' available';
                            $badgeClass = 'text-muted';
                        }
                ?>
                    <div class="col-6 col-lg-3">
                        <div class="card h-100 border rounded-3 shadow-sm bg-white overflow-hidden transition-all">
                            <a href="product-details.php?id=<?php echo urlencode($Row['product_id']); ?>" class="text-decoration-none bg-light d-block position-relative" style="height: 220px;">
                                <?php if (!empty($Row['product_image'])) { ?>
                                    <img src="uploads/<?php echo htmlspecialchars($Row['product_image']); ?>" alt="<?php echo htmlspecialchars($Row['product_name']); ?>" class="w-100 h-100 object-fit-cover">
                                <?php } else { ?>
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted small">
                                        No Product Image Available
                                    </div>
                                <?php } ?>
                            </a>

                            <div class="card-body d-flex flex-column p-3">
                                <a href="shopping-checkout/shopping-cart.php?id=<?php echo urlencode($Row['product_id']); ?>" class="text-decoration-none text-dark fw-semibold mb-1 text-truncate">
                                    <?php echo htmlspecialchars($Row['product_name']); ?>
                                </a>
                                <div class="small mb-2 <?php echo $badgeClass; ?>"><?php echo $stockText; ?></div>
                                <div class="fw-bold text-dark mb-3">&#8369;<?php echo number_format((float)$Row['price'], 2); ?></div>

                                <div class="mt-auto">
                                    <form action="product-details.php?id=<?php echo urlencode($Row['product_id']); ?>" method="POST">
                                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($Row['product_id']); ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <input type="hidden" name="add_to_cart" value="1">
                                        <button type="submit" class="btn btn-dark-custom w-100 py-2 small fw-semibold <?php echo $stock <= 0 ? 'disabled' : ''; ?>">
                                            <?php echo $stock <= 0 ? 'Unavailable' : 'Add to Cart'; ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php }
                } else { ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">No products available in this category.</p>
                    </div>
                <?php } ?>
            </div>    

            <?php if ($totalPages > 1) { ?>
                <nav class="mt-5 d-flex justify-content-center" aria-label="Product Navigation">
                    <ul class="pagination">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($selectedCategory) ? '&category=' . urlencode($selectedCategory) : ''; ?>">&lsaquo; Prev</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++) { ?>
                            <li class="page-item <?php echo ($page === $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($selectedCategory) ? '&category=' . urlencode($selectedCategory) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php } ?>
                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($selectedCategory) ? '&category=' . urlencode($selectedCategory) : ''; ?>">Next &rsaquo;</a>
                        </li>
                    </ul>
                </nav>
            <?php } ?>
        </div>
    </main>

    <?php include 'includes/footer.php' ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>