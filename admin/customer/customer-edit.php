<?php
session_start();
$current_page = 'customer';

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../../db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

$hasError = false;
$errors = [
    'firstname'         => '',
    'lastname'          => '',
    'email'             => '',
    'contact'           => '',
    'address'           => '',
    'username'          => ''
];

$customerId = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['customer_id'] ?? '')
    : ($_GET['id'] ?? $_GET['edit'] ?? '');
$customerId = is_scalar($customerId) ? (string) $customerId : '';

$formValues = [
    'customer_id'       => $customerId,
    'firstname'         => '',
    'lastname'          => '',
    'email'             => '',
    'contact'           => '',
    'address'           => '',
    'username'          => ''
];

$stmt = mysqli_prepare($Connection, 'SELECT * FROM customers WHERE customer_id = ?') or die(mysqli_error($Connection));
mysqli_stmt_bind_param($stmt, 's', $customerId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$Row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$Row) {
    http_response_code(404);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $Row) {
    $formValues['firstname'] = trim($_POST['firstname'] ?? '');
    $formValues['lastname'] = trim($_POST['lastname'] ?? '');
    $formValues['email'] = trim($_POST['email'] ?? '');
    $formValues['contact'] = trim($_POST['contact'] ?? '');
    $formValues['address'] = trim($_POST['address'] ?? '');
    $formValues['username'] = trim($_POST['username'] ?? '');

    if (empty($formValues['firstname'])) {
        $hasError = true;
        $errors['firstname'] = 'First name is required';
    }

    if (empty($formValues['lastname'])) {
        $hasError = true;
        $errors['lastname'] = 'Last name is required';
    }

    if (empty($formValues['email'])) {
        $hasError = true;
        $errors['email'] = 'Email address is required';
    } elseif (!filter_var($formValues['email'], FILTER_VALIDATE_EMAIL)) {
        $hasError = true;
        $errors['email'] = 'Enter a valid email address';
    }

    if (empty($formValues['contact'])) {
        $hasError = true;
        $errors['contact'] = 'Contact number is required';
    }

    if (empty($formValues['address'])) {
        $hasError = true;
        $errors['address'] = 'Delivery address is required';
    }

    if (empty($formValues['username'])) {
        $hasError = true;
        $errors['username'] = 'Username is required';
    }

    if (!$hasError) {
        mysqli_query($Connection, "SET time_zone = '+08:00';");

        $accountStatus = 'active';
        $stmt = mysqli_prepare($Connection, "UPDATE customers SET first_name = ?, last_name = ?, email = ?, contact = ?, address = ?, username = ?, account_status = ?, updated_at = NOW() WHERE customer_id = ?");
        mysqli_stmt_bind_param(
            $stmt,
            'ssssssss',
            $formValues['firstname'],
            $formValues['lastname'],
            $formValues['email'],
            $formValues['contact'],
            $formValues['address'],
            $formValues['username'],
            $accountStatus,
            $formValues['customer_id']
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header('Location: customer-edit.php?id=' . rawurlencode($formValues['customer_id']) . '&success=1');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer</title>
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
                        <h2 class="fw-bold font-poppins text-dark mb-1">Edit Customer</h2>
                        <p class="text-muted small mb-0">Update customer details (<?php echo htmlspecialchars($formValues['customer_id']); ?>).</p>
                    </div>
                    <div>
                        <a href="customer-list.php" class="btn btn-dark-custom w-100 w-md-auto px-4 py-2.5 rounded-3 fw-semibold small shadow-sm d-inline-flex align-items-center justify-content-center gap-2 text-white text-decoration-none">
                            <i class="bi bi-arrow-left"></i> Back to Customer Management
                        </a>
                    </div>
                </div>

                <div class="auth-card-admin p-4 p-md-5">
                    <?php if (!$Row) { ?>
                        <div class="alert alert-warning mb-0" role="alert">
                            Customer not found. <a href="customer-list.php" class="alert-link">Return to customer management</a>.
                        </div>
                    <?php } else { ?>
                    <h1 class="auth-title mb-4">Customer Information</h1>

                    <?php if (isset($_GET["success"])) { ?>
                        <div class="alert fade show my-3 text-white border-0 shadow-sm d-flex align-items-center justify-content-between py-3 px-3" role="alert" style="background-color: #28A745;">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                                <span class="mb-0">Customer account updated successfully.</span>
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

                    <form action="customer-edit.php" method="POST" novalidate>
                        <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($customerId); ?>">
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="firstname" class="form-label-custom">First Name</label>
                                <input type="text" class="form-control form-control-custom <?php echo !empty($errors['firstname']) ? 'border-danger' : ''; ?>" id="firstname" name="firstname" placeholder="Juan" value="<?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? $formValues['firstname'] : $Row['first_name']); ?>" required>
                                <?php if (!empty($errors['firstname'])) { ?>
                                    <div class="input-error-text">(!) <?php echo $errors['firstname']; ?></div>
                                <?php } ?>
                            </div>
                            <div class="col-md-6">
                                <label for="lastname" class="form-label-custom">Last Name</label>
                                <input type="text" class="form-control form-control-custom <?php echo !empty($errors['lastname']) ? 'border-danger' : ''; ?>" id="lastname" name="lastname" placeholder="Dela Cruz" value="<?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? $formValues['lastname'] : $Row['last_name']); ?>" required>
                                <?php if (!empty($errors['lastname'])) { ?>
                                    <div class="input-error-text">(!) <?php echo $errors['lastname']; ?></div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="email" class="form-label-custom">Email Address</label>
                                <input type="email" class="form-control form-control-custom <?php echo !empty($errors['email']) ? 'border-danger' : ''; ?>" id="email" name="email" placeholder="name@example.com" value="<?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? $formValues['email'] : $Row['email']); ?>" required>
                                <?php if (!empty($errors['email'])) { ?>
                                    <div class="input-error-text">(!) <?php echo $errors['email']; ?></div>
                                <?php } ?>
                            </div>
                            <div class="col-md-6">
                                <label for="contact" class="form-label-custom">Contact Number</label>
                                <input type="text" class="form-control form-control-custom <?php echo !empty($errors['contact']) ? 'border-danger' : ''; ?>" id="contact" name="contact" placeholder="09XX XXX XXXX" value="<?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? $formValues['contact'] : $Row['contact']); ?>" required>
                                <?php if (!empty($errors['contact'])) { ?>
                                    <div class="input-error-text">(!) <?php echo $errors['contact']; ?></div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label-custom">Delivery Address</label>
                            <input type="text" class="form-control form-control-custom <?php echo !empty($errors['address']) ? 'border-danger' : ''; ?>" id="address" name="address" placeholder="House no., street, barangay, city" value="<?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? $formValues['address'] : $Row['address']); ?>" required>
                            <?php if (!empty($errors['address'])) { ?>
                                <div class="input-error-text">(!) <?php echo $errors['address']; ?></div>
                            <?php } ?>
                        </div>

                        <div class="mb-4">
                            <label for="username" class="form-label-custom">Username</label>
                            <input type="text" class="form-control form-control-custom <?php echo !empty($errors['username']) ? 'border-danger' : ''; ?>" id="username" name="username" placeholder="juan_delacruz123" value="<?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? $formValues['username'] : $Row['username']); ?>" required>
                            <?php if (!empty($errors['username'])) { ?>
                                <div class="input-error-text">(!) <?php echo $errors['username']; ?></div>
                            <?php } ?>
                        </div>

                        <div class="d-flex flex-column flex-sm-row justify-content-start align-items-stretch align-items-sm-center gap-2">
                            <button type="submit" class="btn btn-dark-custom">Save Changes</button>
                            <a href="customer-list.php" class="btn btn-outline-custom">Cancel</a>
                        </div>
                    </form>
                    <?php } ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>