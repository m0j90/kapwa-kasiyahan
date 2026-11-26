<?php
session_start();
$current_page = 'product';

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../../db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

$productId = $_GET['id'] ?? '';
$productId = is_scalar($productId) ? (string) $productId : '';

$stmt = mysqli_prepare($Connection, 'SELECT * FROM products WHERE product_id = ?') or die(mysqli_error($Connection));
mysqli_stmt_bind_param($stmt, 's', $productId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$Row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$Row) {
    http_response_code(404);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details</title>
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
                <?php if (!$Row) { ?>
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center text-muted mb-3 mx-auto" style="width: 56px; height: 56px;">
                            <i class="bi bi-exclamation-triangle fs-3"></i>
                        </div>
                        <h6 class="fw-semibold text-dark mb-1 font-poppins">Product not found</h6>
                        <p class="text-muted small mb-3">The product you are looking for does not exist or has been removed.</p>
                        <div>
                            <a href="product-list.php" class="btn btn-palette-primary btn-sm px-4 py-2 rounded-3 fw-semibold text-decoration-none">
                                Back to Product Management
                            </a>
                        </div>
                    </div>
                <?php } else {
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

                    $date_timestamp = strtotime($Row['created_at']);
                ?>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <h2 class="fw-bold font-poppins text-dark mb-1">Product Details</h2>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($Row['product_id'] . ' · ' . $Row['product_name']); ?></p>
                    </div>
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <a href="product-edit.php?id=<?php echo htmlspecialchars($Row['product_id']); ?>" class="btn btn-outline-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm text-center text-decoration-none">
                            Edit Product
                        </a>
                        <a href="#" class="btn btn-palette-danger px-4 py-2 rounded-3 fw-semibold small shadow-sm text-center text-white text-decoration-none" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            Delete Product
                        </a>
                        <a href="product-list.php" class="btn btn-dark-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm text-center text-white text-decoration-none">
                            <i class="bi bi-arrow-left me-2"></i> Back
                        </a>
                    </div>
                </div>

                <div class="auth-card-admin p-4 p-md-5 bg-white">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-5 d-flex justify-content-center">
                            <div class="product-details-img-container">
                                <?php if (!empty($Row['product_image'])) { ?>
                                    <img src="../../uploads/<?php echo htmlspecialchars($Row['product_image']); ?>" alt="<?php echo htmlspecialchars($Row['product_name']) ?>">
                                <?php } else { ?>
                                    <div class="d-flex align-items-center justify-content-center text-muted small">No Product Image Available</div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <h3 class="fw-bold font-poppins text-dark mb-3"><?php echo htmlspecialchars($Row['product_name']); ?></h3>

                            <div class="mb-4">
                                <span class="<?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                                <?php if (!empty($Row['category'])) { ?>
                                    <span class="badge bg-light text-secondary border fw-medium ms-2 px-2 py-1" style="font-size: 0.75rem;"><?php echo htmlspecialchars($Row['category']); ?></span>
                                <?php } ?>
                            </div>

                            <div class="d-flex flex-column gap-3 small">
                                <div class="d-flex justify-content-between pb-2 border-bottom">
                                    <span class="text-muted">Product ID</span>
                                    <span class="fw-semibold text-dark"><?php echo htmlspecialchars($Row['product_id']); ?></span>
                                </div>
                        
                                <div class="d-flex justify-content-between pb-2 border-bottom">
                                    <span class="text-muted">Price</span>
                                    <span class="fw-semibold text-dark">&#8369;<?php echo number_format((float)$Row['price'], 2); ?></span>
                                </div>
                            
                                <div class="d-flex justify-content-between pb-2 border-bottom">
                                    <span class="text-muted">Stock Quantity</span>
                                    <span class="fw-semibold text-dark"><?php echo $stock; ?></span>
                                </div>

                                <div class="d-flex justify-content-between pb-2 border-bottom">
                                    <span class="text-muted">Description</span>
                                    <span class="fw-semibold text-dark" style="max-width: 60%;"><?php echo nl2br(htmlspecialchars($Row['description'] ?: 'No description provided.')); ?></span>
                                </div>

                                <div class="d-flex justify-content-between pb-2 border-bottom">
                                    <span class="text-muted">Added On</span>
                                    <span class="fw-semibold text-dark"><?php echo date('M j, Y • g:i A', $date_timestamp); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </main>
        </div>
    </div>

    <?php if ($Row) { ?>
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
            <div class="modal-content border-0 shadow-lg rounded-4 p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="modal-title fw-bold font-poppins text-dark mb-0" id="deleteModalLabel">Delete this product?</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="mb-4">
                    <p class="text-muted small mb-3">This permanently removes the product from the store. This cannot be undone.</p>
                    
                    <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 small">
                        <span class="text-muted">Product</span>
                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($Row['product_name'] . ' (' . $Row['product_id'] . ')'); ?></span>
                    </div>
                </div>

                <div class="delete-actions-wrapper">
                    <button type="button" class="btn btn-outline-custom px-4 rounded-3 fw-semibold small shadow-none btn-cancel-custom" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <a href="product-list.php?del=<?php echo urlencode($Row['product_id']); ?>" class="btn btn-palette-danger px-4 rounded-3 fw-semibold small text-white text-decoration-none shadow-sm btn-delete-custom">
                        Yes, Delete Product
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>

    <script>
        function updateFileName(input) {
            const display = document.getElementById('file-name-display');
            const previewContainer = document.getElementById('imagePreviewContainer');

            if (input.files && input.files[0]) {
                const file = input.files[0];
                display.textContent = "Selected: " + file.name;

                const reader = new FileReader();
                reader.onload = function(e) {
                    previewContainer.innerHTML = `<img src="${e.target.result}" alt="Product Preview" style="width: 100%; height: 100%; object-fit: cover; border-radius: 0.375rem;">`;
                }
                reader.readAsDataURL(file);
            } else {
                display.textContent = "";
                previewContainer.innerHTML = `<span class="product-image-preview-text small text-muted">No image selected</span>`;
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>