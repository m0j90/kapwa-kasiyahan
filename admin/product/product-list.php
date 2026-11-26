<?php
session_start();
$current_page = 'product';

require_once __DIR__ . '/../../db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

$search = is_string($_GET['search'] ?? null) ? trim($_GET['search']) : '';

if (isset($_GET['del'])) {
    mysqli_query($Connection, "DELETE FROM products WHERE product_id = '" . mysqli_real_escape_string($Connection, $_GET['del']) . "'") or die(mysqli_error($Connection));
    header('Location: product-list.php?deleted=1');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
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
                        <h2 class="fw-bold font-poppins text-dark mb-1">Product Management</h2>
                        <p class="text-muted small mb-0">Manage the products shown in the store.</p>
                    </div>
                    <div>
                        <a href="product-add.php" class="btn btn-dark-custom w-100 w-md-auto px-4 py-2.5 rounded-3 fw-semibold small shadow-sm d-inline-flex align-items-center justify-content-center gap-2 text-white text-decoration-none">
                            <i class="bi bi-plus-lg"></i> Add Product
                        </a>
                    </div>
                </div>

                <form method="GET" action="product-list.php" class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 mb-4">
                    <div class="input-group overflow-hidden flex-grow-1" style="max-width: 400px; width: 100%;">
                        <span class="input-group-text bg-white border-end-0 text-muted ps-3">
                            <i class="bi bi-search small"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0 ps-1 shadow-none small" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                        <?php if ($search !== '') { ?>
                            <a href="product-list.php" class="input-group-text bg-white border-start-0 text-decoration-none text-muted px-2" title="Clear search">
                                <i class="bi bi-x-lg small"></i>
                            </a>
                        <?php } ?>
                        <button type="submit" class="btn btn-palette-primary px-4 py-2 mx-2 rounded-3 small fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-1 text-white text-decoration-none flex-shrink-0">
                            <span>Search</span>
                        </button>
                    </div>
                </form>

                <?php if (isset($_GET["success"])) { ?>
                    <div class="alert fade show my-3 text-white border-0 shadow-sm d-flex align-items-center justify-content-between py-3 px-3" role="alert" style="background-color: #28A745;">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                            <span class="mb-0">Product created successfully.</span>
                        </div>
                        <button
                            type="button"
                            class="btn-close btn-close-white shadow-none m-0 p-0"
                            style="width: 1rem; height: 1rem;"
                            data-bs-dismiss="alert"
                            aria-label="Close"
                        ></button>
                    </div>
                <?php } ?>

                <?php if (isset($_GET["deleted"])) { ?>
                    <div class="alert fade show my-3 text-white border-0 shadow-sm d-flex align-items-center justify-content-between py-3 px-3" role="alert" style="background-color: #E74C3C;">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-trash3-fill fs-5 me-2"></i>
                            <span class="mb-0">Product deleted successfully.</span>
                        </div>
                        <button
                            type="button"
                            class="btn-close btn-close-white shadow-none m-0 p-0"
                            style="width: 1rem; height: 1rem;"
                            data-bs-dismiss="alert"
                            aria-label="Close"
                        ></button>
                    </div>
                <?php } ?>

                <?php 
                $escapedSearch = mysqli_real_escape_string($Connection, $search);
                $Query = mysqli_query($Connection, "SELECT * FROM products WHERE product_name LIKE '%{$escapedSearch}%' ORDER BY product_id DESC") or die(mysqli_error($Connection));
                $total_products = mysqli_num_rows($Query);

                if($total_products > 0) {
                ?>

                    <div class="card border-0 shadow-sm rounded-4 d-none d-lg-block mb-3">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-uppercase fs-7 text-muted border-bottom">
                                        <tr>
                                            <th class="py-3 px-4 fw-semibold text-secondary small">Image</th>
                                            <th class="py-3 px-3 fw-semibold text-secondary small">ID</th>
                                            <th class="py-3 px-3 fw-semibold text-secondary small">Product</th>
                                            <th class="py-3 px-3 fw-semibold text-secondary small">Price</th>
                                            <th class="py-3 px-3 fw-semibold text-secondary small">Stock</th>
                                            <th class="py-3 px-3 fw-semibold text-secondary small">Status</th>
                                            <th class="py-3 px-4 fw-semibold text-secondary small text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($Row = mysqli_fetch_array($Query)) {
                                            $stock = (int)$Row['stock'];
                                            if ($stock <= 0) {
                                                $badge_class = 'badge-out-stock';
                                                $status_text = 'Out of Stock';
                                            } elseif ($stock <= 5) {
                                                $badge_class = 'badge-low-stock';
                                                $status_text = 'Low Stock';
                                            } else {
                                                $badge_class = 'badge-in-stock';
                                                $status_text = 'In Stock';
                                            }
                                        ?>
                                            <tr>
                                                <td class="py-3 px-4">
                                                    <?php if (!empty($Row['product_image'])) { ?>
                                                        <img src="../../uploads/<?php echo htmlspecialchars($Row['product_image']); ?>" alt="<?php echo htmlspecialchars($Row['product_name']); ?>" class="product-thumb" style="object-fit: cover;">
                                                    <?php } else { ?>
                                                        <div class="product-thumb">
                                                            <i class="bi bi-image text-muted"></i>
                                                        </div>
                                                    <?php } ?>
                                                </td>
                                                <td class="py-3 px-3 fw-medium text-secondary small"><?php echo htmlspecialchars($Row['product_id']); ?></td>
                                                <td class="py-3 px-3 fw-semibold text-dark small"><?php echo htmlspecialchars($Row['product_name']); ?></td>
                                                <td class="py-3 px-3 text-muted small">&#8369;<?php echo number_format((float)$Row['price'], 2); ?></td>
                                                <td class="py-3 px-3 text-muted small"><?php echo $stock; ?></td>
                                                <td class="py-2 px-2">
                                                    <span class="<?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                                                </td>
                                                <td class="py-3 px-4 text-end">
                                                    <div class="d-flex justify-content-end gap-1">
                                                        <a href="product-view.php?id=<?php echo $Row['product_id']; ?>" class="btn btn-sm btn-palette-info px-2 py-1 rounded-2 small fw-semibold shadow-sm" title="View Details">
                                                            <span class="d-none d-xl-inline">View</span>
                                                        </a>
                                                        <a href="product-edit.php?id=<?php echo $Row['product_id']; ?>" class="btn btn-sm btn-palette-primary px-2 py-1 rounded-2 small fw-semibold shadow-sm" title="Edit Product">
                                                            <span class="d-none d-xl-inline">Edit</span>
                                                        </a>
                                                        <a href="#" class="btn btn-sm btn-palette-danger px-2 py-1 rounded-2 small fw-semibold shadow-sm d-inline-flex align-items-center gap-1 text-white text-decoration-none" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $Row['product_id']; ?>" title="Delete Product">
                                                            <span class="d-none d-xl-inline">Delete</span>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                    <div class="d-flex d-lg-none flex-column gap-3 mb-3">
                        <?php 
                        mysqli_data_seek($Query, 0);
                        if($total_products > 0) {
                            while($Row = mysqli_fetch_assoc($Query)) {
                                $stock = (int)$Row['stock'];
                                if ($stock <= 0) {
                                    $badge_class = 'badge-out-stock';
                                    $status_text = 'Out of Stock';
                                } elseif ($stock <= 5) {
                                    $badge_class = 'badge-low-stock';
                                    $status_text = 'Low Stock';
                                } else {
                                    $badge_class = 'badge-in-stock';
                                    $status_text = 'In Stock';
                                }
                        ?>
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                            <div class="d-flex justify-content-between align-items-start mb-2 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-3">
                                    <?php if (!empty($Row['product_image'])) { ?>
                                        <img src="../../uploads/<?php echo htmlspecialchars($Row['product_image']); ?>" alt="<?php echo htmlspecialchars($Row['product_name']); ?>" class="product-thumb" style="object-fit: cover;">
                                    <?php } else { ?>
                                        <div class="product-thumb">
                                            <i class="bi bi-image text-muted"></i>
                                        </div>
                                    <?php } ?>
                                    <h6 class="fw-bold font-poppins text-dark mb-0"><?php echo htmlspecialchars($Row['product_name']); ?></h6>
                                </div>
                                <span class="<?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                            </div>
                            <div class="d-flex flex-column gap-1.5 small text-muted mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>ID</span>
                                    <span class="fw-medium text-dark"><?php echo htmlspecialchars($Row['product_id']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Price</span>
                                    <span class="fw-medium text-dark">&#8369;<?php echo number_format((float)$Row['price'], 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Stock</span>
                                    <span class="fw-medium text-dark"><?php echo $stock; ?></span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                                <a href="product-view.php?id=<?php echo $Row['product_id']; ?>" class="btn btn-sm btn-palette-info px-2.5 py-1 rounded-2 small fw-semibold shadow-sm d-flex align-items-center gap-1">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <a href="product-edit.php?id=<?php echo $Row['product_id']; ?>" class="btn btn-sm btn-palette-primary px-2.5 py-1 rounded-2 small fw-semibold shadow-sm d-flex align-items-center gap-1">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="#" class="btn btn-sm btn-palette-danger px-2.5 py-1 rounded-2 small fw-semibold shadow-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $Row['product_id']; ?>">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                        <?php } ?>
                    </div>

                <?php } else { ?>
                        <div class="card border-0 shadow-sm rounded-4 my-3">
                            <div class="card-body text-center py-5">
                                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center text-muted mb-3" style="width: 56px; height: 56px;">
                                    <i class="bi bi-box-seam fs-3"></i>
                                </div>
                                <h6 class="fw-semibold text-dark mb-1 font-poppins">No products found</h6>
                                <p class="text-muted small mb-3">There are currently no registered products in the store.</p>
                                <a href="product-add.php" class="btn btn-palette-primary btn-sm px-3 py-2 rounded-3 fw-semibold text-decoration-none">
                                    <i class="bi bi-plus-lg me-1"></i> Add First Product
                                </a>
                            </div>
                        </div>
                <?php } ?>

                <div class="mt-3 text-muted small">
                    Showing <?php echo $total_products; ?> products
                </div>
            </main>
        </div>
    </div>

    <?php
    $ModalQuery = mysqli_query($Connection, "SELECT product_id, product_name FROM products WHERE product_name LIKE '%{$escapedSearch}%' ORDER BY product_id DESC") or die(mysqli_error($Connection));
    while ($Row = mysqli_fetch_assoc($ModalQuery)) {
    ?>
    <div class="modal fade" id="deleteModal<?php echo $Row['product_id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $Row['product_id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
            <div class="modal-content border-0 shadow-lg rounded-4 p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="modal-title fw-bold font-poppins text-dark mb-0" id="deleteModalLabel<?php echo $Row['product_id']; ?>">Delete this product?</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="mb-4">
                    <p class="text-muted small mb-3">This permanently removes the product. This cannot be undone.</p>

                    <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 small">
                        <span class="text-muted">Product</span>
                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($Row['product_name'] . ' (' . $Row['product_id'] . ')'); ?></span>
                    </div>
                </div>

                <div class="delete-actions-wrapper">
                    <button type="button" class="btn btn-outline-custom px-4 rounded-3 fw-semibold small shadow-none btn-cancel-custom" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <a href="product-list.php?del=<?php echo $Row['product_id']; ?>" class="btn btn-palette-danger px-4 rounded-3 fw-semibold small text-white text-decoration-none shadow-sm btn-delete-custom">
                        Yes, Delete Product
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>