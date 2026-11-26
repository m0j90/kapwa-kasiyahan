<?php
session_start();
$current_page = 'product';

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../../db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

$categories = ['Button Pins', 'Bookmarks', 'Elastic Band Pen Holders'];

$hasError = false;
$errors = [
    'product_name'      => '',
    'category'          => '',
    'description'       => '',
    'price'             => '',
    'stock'             => '',
    'image'             => ''
];

$productId = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['product_id'] ?? '')
    : ($_GET['id'] ?? $_GET['edit'] ?? '');
$productId = is_scalar($productId) ? (string) $productId : '';

$formValues = [
    'product_id'        => $productId,
    'product_name'      => '',
    'category'          => '',
    'description'       => '',
    'price'             => '',
    'stock'             => ''
];

$stmt = mysqli_prepare($Connection, 'SELECT * FROM products WHERE product_id = ?') or die(mysqli_error($Connection));
mysqli_stmt_bind_param($stmt, 's', $productId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$Row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$Row) {
    http_response_code(404);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $Row) {
    $formValues['product_name'] = trim($_POST['product_name'] ?? '');
    $formValues['category'] = trim($_POST['category'] ?? '');
    $formValues['description'] = trim($_POST['description'] ?? '');
    $formValues['price'] = trim($_POST['price'] ?? '');
    $formValues['stock'] = trim($_POST['stock'] ?? '');

    if (empty($formValues['product_name'])) {
        $hasError = true;
        $errors['product_name'] = 'Product name is required';
    }

    if (empty($formValues['category'])) {
        $hasError = true;
        $errors['category'] = 'Please select a product category';
    } elseif (!in_array($formValues['category'], $categories)) {
        $hasError = true;
        $errors['category'] = 'Invalid product category selected';
    }

    if ($formValues['price'] === '' || !is_numeric($formValues['price']) || (float)$formValues['price'] < 0) {
        $hasError = true;
        $errors['price'] = 'Enter a valid price';
    }

    if ($formValues['stock'] === '' || !is_numeric($formValues['stock']) || (int)$formValues['stock'] < 0) {
        $hasError = true;
        $errors['stock'] = 'Enter a valid stock quantity';
    }

    $imageName = $Row['product_image'] ?? '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['product_image']['tmp_name'];
        $fileName = $_FILES['product_image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadDir = __DIR__ . '/../../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $imageName = md5(time() . $fileName) . '.' . $fileExtension;
            $destPath = $uploadDir . $imageName;
            move_uploaded_file($fileTmpPath, $destPath);
        } else {
            $hasError = true;
            $errors['image'] = 'Invalid image format. Allowed formats: jpg, jpeg, png, webp';
        }
    }

    if (!$hasError) {
        mysqli_query($Connection, "SET time_zone = '+08:00';");

        $stmt = mysqli_prepare($Connection, "UPDATE products SET product_image = ?, category = ?, product_name = ?, description = ?, price = ?, stock = ?, updated_at = NOW() WHERE product_id = ?");

        $priceVal = (float)$formValues['price'];
        $stockVal = (int)$formValues['stock'];

        mysqli_stmt_bind_param(
            $stmt,
            'ssssdii',
            $imageName,
            $formValues['category'],
            $formValues['product_name'],
            $formValues['description'],
            $priceVal,
            $stockVal,
            $formValues['product_id']
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header('Location: product-edit.php?id=' . rawurlencode($formValues['product_id']) . '&success=1');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
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
                        <h2 class="fw-bold font-poppins text-dark mb-1">Edit Product</h2>
                        <p class="text-muted small mb-0">Update product details (<?php echo htmlspecialchars($formValues['product_id']); ?>).</p>
                    </div>
                    <div>
                        <a href="product-list.php" class="btn btn-dark-custom w-100 w-md-auto px-4 py-2.5 rounded-3 fw-semibold small shadow-sm d-inline-flex align-items-center justify-content-center gap-2 text-white text-decoration-none">
                            <i class="bi bi-arrow-left"></i> Back to Product Management
                        </a>
                    </div>
                </div>

                <div class="auth-card-admin p-4 p-md-5">
                    <?php if (!$Row) { ?>
                        <div class="alert alert-warning mb-0" role="alert">
                            Product not found. <a href="product-list.php" class="alert-link">Go back to product management.</a>
                        </div>
                    <?php } else { ?>
                    <h1 class="auth-title mb-4">Product Information</h1>

                    <?php if (isset($_GET['success'])) { ?>
                        <div class="alert fade show my-3 text-white border-0 shadow-sm d-flex align-items-center justify-content-between py-3 px-3" role="alert" style="background-color: #28a745;">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                                <span class="small">Product updated successfully.</span>
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

                    <form action="product-edit.php" method="POST" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($productId); ?>">
                        <div class="mb-3">
                            <label for="product_name" class="form-label-custom">Product Name</label>
                            <input type="text" class="form-control form-control-custom <?php echo !empty($errors['product_name']) ? 'border-danger' : ''; ?>" id="product_name" name="product_name" placeholder="Enter product name" value="<?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? $formValues['product_name'] : $Row['product_name']); ?>" required>
                            <?php if (!empty($errors['product_name'])) { ?>
                                <div class="input-error-text">(!) <?php echo $errors['product_name']; ?></div>
                            <?php } ?>
                        </div>

                        <div class="mb-3">
                            <label for="category" class="form-label-custom">Category</label>
                            <?php
                            $selectedCategory = $_SERVER['REQUEST_METHOD'] === 'POST' ? $formValues['category'] : ($Row['category'] ?? '');
                            ?>
                            <select class="form-control form-control-custom <?php echo !empty($errors['category']) ? 'border-danger' : ''; ?>" id="category" name="category" required>
                                <option value="" disabled <?php echo empty($selectedCategory) ? 'selected' : ''; ?>>Select product category</option>
                                <?php foreach ($categories as $cat) { ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($selectedCategory === $cat) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <?php if (!empty($errors['category'])) { ?>
                                <div class="input-error-text">(!) <?php echo $errors['category']; ?></div>
                            <?php } ?>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label-custom">Product Description</label>
                            <textarea class="form-control form-control-custom" id="description" name="description" rows="5" placeholder="Describe the product..."><?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? $formValues['description'] : ($Row['description'] ?? '')); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom d-block">Product Image</label>
                            <div class="d-flex flex-column flex-sm-row justify-content-start align-items-stretch align-items-sm-center gap-3">
                                <div class="product-image-preview-box overflow-hidden" id="imagePreviewContainer">
                                    <?php if (!empty($Row['product_image'])) { ?>
                                        <img src="../../uploads/<?php echo htmlspecialchars($Row['product_image']) ?>" alt="Current Product Image" style="width: 100%; height: 100%; object-fit: cover; border-radius: 0.375rem;">
                                    <?php } else { ?>
                                        <span class="product-image-preview-text">No image selected</span>
                                    <?php } ?>
                                </div>
                                <div>
                                    <input type="file" name="product_image" id="product_image" class="form-control form-control-custom d-none" accept="image/*" onchange="updateFileName(this)">

                                    <label for="product_image" class="btn btn-outline-custom w-100 w-md-auto cursor-pointer d-flex align-items-center justify-content-center">
                                        <i class="bi bi-file-earmark-image me-2"></i> Change Image
                                    </label>
                                    <div id="file-name-display" class="form-text small text-muted mt-1"></div>
                                </div>
                            </div>
                            <?php if (!empty($errors['image'])) { ?>
                                <div class="input-error-text mt-1">(!) <?php echo $errors['image']; ?></div>
                            <?php } ?>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="price" class="form-label-custom">Price (&#8369;)</label>
                                <input type="number" step="0.01" min="0" class="form-control form-control-custom <?php echo !empty($errors['price']) ? 'border-danger' : ''; ?>" id="price" name="price" placeholder="0.00" value="<?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? $formValues['price'] : $Row['price']); ?>" required>
                                <?php if (!empty($errors['price'])) { ?>
                                    <div class="input-error-text">(!) <?php echo $errors['price']; ?></div>
                                <?php } ?>
                            </div>
                            <div class="col-md-6">
                                <label for="stock" class="form-label-custom">Stock Quantity</label>
                                <input type="number" min="0" class="form-control form-control-custom <?php echo !empty($errors['stock']) ? 'border-danger' : ''; ?>" id="stock" name="stock" placeholder="0" value="<?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? $formValues['stock'] : $Row['stock']); ?>" required>
                                <?php if (!empty($errors['stock'])) { ?>
                                    <div class="input-error-text">(!) <?php echo $errors['stock']; ?></div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row justify-content-start align-items-stretch align-items-sm-center gap-2">
                            <button type="submit" class="btn btn-dark-custom">Save Changes</button>
                            <a href="product-list.php" class="btn btn-outline-custom">Cancel</a>
                        </div>
                    </form>
                    <?php } ?>
                </div>
            </main>
        </div>
    </div>

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