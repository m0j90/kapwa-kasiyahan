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
    'username'          => '',
    'password'          => '',
    'confirmpassword'   => ''
];

$formValues = [
    'firstname'         => '',
    'lastname'          => '',
    'email'             => '',
    'contact'           => '',
    'address'           => '',
    'username'          => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formValues['firstname'] = trim($_POST['firstname'] ?? '');
    $formValues['lastname'] = trim($_POST['lastname'] ?? '');
    $formValues['email'] = trim($_POST['email'] ?? '');
    $formValues['contact'] = trim($_POST['contact'] ?? '');
    $formValues['address'] = trim($_POST['address'] ?? '');
    $formValues['username'] = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmpassword'] ?? '';

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

    if (empty($password)) {
        $hasError = true;
        $errors['password'] = 'Password is required';
    }

    if (empty($confirmPassword)) {
        $hasError = true;
        $errors['confirmpassword'] = 'Please confirm your password';
    } elseif ($password !== $confirmPassword) {
        $hasError = true;
        $errors['confirmpassword'] = 'Password do not match';
    }

    if (!$hasError) {
        mysqli_query($Connection, "SET time_zone = '+08:00';");

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $accountStatus = 'active';
        $stmt = mysqli_prepare($Connection, "INSERT INTO customers (
            customer_id,
            first_name,
            last_name,
            email,
            contact,
            address,
            username,
            password_hash,
            account_status,
            created_at,
            updated_at
        ) VALUES (0, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        mysqli_stmt_bind_param(
            $stmt,
            'ssssssss',
            $formValues['firstname'],
            $formValues['lastname'],
            $formValues['email'],
            $formValues['contact'],
            $formValues['address'],
            $formValues['username'],
            $passwordHash,
            $accountStatus
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header('Location: customer-list.php?success=1');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer</title>
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
                        <h2 class="fw-bold font-poppins text-dark mb-1">Add Customer</h2>
                        <p class="text-muted small mb-0">Create a new customer account.</p>
                    </div>
                    <div>
                        <a href="customer-list.php" class="btn btn-dark-custom w-100 w-md-auto px-4 py-2.5 rounded-3 fw-semibold small shadow-sm d-inline-flex align-items-center justify-content-center gap-2 text-white text-decoration-none">
                            <i class="bi bi-arrow-left"></i> Back to Customer Management
                        </a>
                    </div>
                </div>

                <div class="auth-card-admin p-4 p-md-5">
                    <h1 class="auth-title mb-4">Customer Information</h1>

                    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasError) { ?>
                        <div class="alert-warning-custom mb-4" role="alert">
                            <i class="bi bi-exclamation-circle-fill fs-5"></i>
                            <span>Please fix the highlighted fields before continuing.</span>
                        </div>
                    <?php } ?>

                    <form action="customer-add.php" method="POST" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="firstname" class="form-label-custom">First Name</label>
                                <input type="text" class="form-control form-control-custom <?php echo !empty($errors['firstname']) ? 'border-danger' : ''; ?>" id="firstname" name="firstname" placeholder="Juan" value="<?php echo htmlspecialchars($formValues['firstname']); ?>" required>
                                <?php if (!empty($errors['firstname'])) { ?>
                                    <div class="input-error-text">(!) <?php echo $errors['firstname']; ?></div>
                                <?php } ?>
                            </div>
                            <div class="col-md-6">
                                <label for="lastname" class="form-label-custom">Last Name</label>
                                <input type="text" class="form-control form-control-custom <?php echo !empty($errors['lastname']) ? 'border-danger' : ''; ?>" id="lastname" name="lastname" placeholder="Dela Cruz" value="<?php echo htmlspecialchars($formValues['lastname']); ?>" required>
                                <?php if (!empty($errors['lastname'])) { ?>
                                    <div class="input-error-text">(!) <?php echo $errors['lastname']; ?></div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="email" class="form-label-custom">Email Address</label>
                                <input type="email" class="form-control form-control-custom <?php echo !empty($errors['email']) ? 'border-danger' : ''; ?>" id="email" name="email" placeholder="name@example.com" value="<?php echo htmlspecialchars($formValues['email']); ?>" required>
                                <?php if (!empty($errors['email'])) { ?>
                                    <div class="input-error-text">(!) <?php echo $errors['email']; ?></div>
                                <?php } ?>
                            </div>
                            <div class="col-md-6">
                                <label for="contact" class="form-label-custom">Contact Number</label>
                                <input type="text" class="form-control form-control-custom <?php echo !empty($errors['contact']) ? 'border-danger' : ''; ?>" id="contact" name="contact" placeholder="09XX XXX XXXX" value="<?php echo htmlspecialchars($formValues['contact']); ?>" required>
                                <?php if (!empty($errors['contact'])) { ?>
                                    <div class="input-error-text">(!) <?php echo $errors['contact']; ?></div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label-custom">Delivery Address</label>
                            <input type="text" class="form-control form-control-custom <?php echo !empty($errors['address']) ? 'border-danger' : ''; ?>" id="address" name="address" placeholder="House no., street, barangay, city" value="<?php echo htmlspecialchars($formValues['address']); ?>" required>
                            <?php if (!empty($errors['address'])) { ?>
                                <div class="input-error-text">(!) <?php echo $errors['address']; ?></div>
                            <?php } ?>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label-custom">Username</label>
                            <input type="text" class="form-control form-control-custom <?php echo !empty($errors['username']) ? 'border-danger' : ''; ?>" id="username" name="username" placeholder="juan_delacruz123" value="<?php echo htmlspecialchars($formValues['username']); ?>" required>
                            <?php if (!empty($errors['username'])) { ?>
                                <div class="input-error-text">(!) <?php echo $errors['username']; ?></div>
                            <?php } ?>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="password" class="form-label-custom">Password</label>
                                <input 
                                    type="password" 
                                    class="form-control form-control-custom <?php echo !empty($errors['password']) ? 'border-danger' : ''; ?>" 
                                    id="password" 
                                    name="password" 
                                    placeholder="••••••••" 
                                    required>
                                <?php if (!empty($errors['password'])) { ?>
                                    <div class="input-error-text">(!) <?php echo $errors['password']; ?></div>
                                <?php } ?>
                            </div>
                            <div class="col-md-6">
                                <label for="confirmpassword" class="form-label-custom">Confirm Password</label>
                                <input 
                                    type="password" 
                                    class="form-control form-control-custom <?php echo !empty($errors['confirmpassword']) ? 'border-danger' : ''; ?>" 
                                    id="confirmpassword" 
                                    name="confirmpassword" 
                                    placeholder="••••••••" 
                                    required>
                                <?php if (!empty($errors['confirmpassword'])) { ?>
                                    <div class="input-error-text">(!) <?php echo $errors['confirmpassword']; ?></div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row justify-content-start align-items-stretch align-items-sm-center gap-2">
                            <button type="submit" class="btn btn-dark-custom">Save Customer</button>
                            <a href="customer-list.php" class="btn btn-outline-custom">Cancel</a>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>