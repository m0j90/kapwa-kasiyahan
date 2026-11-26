<?php 
session_start();
$activePage = 'settings';

if (!isset($_SESSION['customer_id']) && !isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../db_connect.php';

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

$customerId = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['customer_id'] ?? $_SESSION['customer_id'])
    : ($_GET['id'] ?? $_GET['edit'] ?? $_SESSION['customer_id']);

$customerId = intval($customerId);

$stmt = mysqli_prepare($Connection, 'SELECT * FROM customers WHERE customer_id = ?') or die(mysqli_error($Connection));
mysqli_stmt_bind_param($stmt, 'i', $customerId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$Row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$Row) {
    http_response_code(404);
}

$formValues = [
    'customer_id'       => $customerId,
    'firstname'         => $_SERVER['REQUEST_METHOD'] === 'POST' ? trim($_POST['firstname'] ?? '') : ($Row['first_name'] ?? ''),
    'lastname'          => $_SERVER['REQUEST_METHOD'] === 'POST' ? trim($_POST['lastname'] ?? '') : ($Row['last_name'] ?? ''),
    'email'             => $_SERVER['REQUEST_METHOD'] === 'POST' ? trim($_POST['email'] ?? '') : ($Row['email'] ?? ''),
    'contact'           => $_SERVER['REQUEST_METHOD'] === 'POST' ? trim($_POST['contact'] ?? '') : ($Row['contact'] ?? ''),
    'address'           => $_SERVER['REQUEST_METHOD'] === 'POST' ? trim($_POST['address'] ?? '') : ($Row['address'] ?? ''),
    'username'          => $_SERVER['REQUEST_METHOD'] === 'POST' ? trim($_POST['username'] ?? '') : ($Row['username'] ?? '')
];

$password = $_SERVER['REQUEST_METHOD'] === 'POST' ? trim($_POST['password'] ?? '') : '';
$confirmPassword = $_SERVER['REQUEST_METHOD'] === 'POST' ? trim($_POST['confirmpassword'] ?? '') : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $Row) {
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
        $errors['confirmpassword'] = 'Passwords do not match';
    }

    if (!$hasError) {
        $check_stmt = mysqli_prepare($Connection, "SELECT customer_id FROM customers WHERE (username = ? OR email = ?) AND customer_id != ?");
        mysqli_stmt_bind_param($check_stmt, 'ssi', $formValues['username'], $formValues['email'], $customerId);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $hasError = true;
            $errors['username'] = 'Username or email is already registered by another account';
        }
        mysqli_stmt_close($check_stmt);
    }

    if (!$hasError) {
        mysqli_query($Connection, "SET time_zone = '+08:00';");

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $accountStatus = 'active';
        
        $stmt = mysqli_prepare($Connection, "UPDATE customers SET first_name = ?, last_name = ?, email = ?, contact = ?, address = ?, username = ?, password_hash = ?, account_status = ?, updated_at = NOW() WHERE customer_id = ?");
        
        mysqli_stmt_bind_param(
            $stmt,
            'ssssssssi',
            $formValues['firstname'],
            $formValues['lastname'],
            $formValues['email'],
            $formValues['contact'],
            $formValues['address'],
            $formValues['username'],
            $passwordHash,
            $accountStatus,
            $customerId
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header('Location: edit-profile.php?id=' . rawurlencode($customerId) . '&success=1');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
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
                    <li class="breadcrumb-item"><a href="account-settings.php" class="text-decoration-none text-success">Account Settings</a></li>
                    <li class="breadcrumb-item"><a href="account-info.php" class="text-decoration-none text-success">Personal Information</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Profile</li>
                </ol>
            </nav>
            <h1 class="fw-bold display-5 font-poppins text-dark">Edit Profile</h1>
            <p class="text-muted mb-0">Update your details and save your changes.</p>
        </div>
    </header>

    <main class="py-5">
        <div class="container px-4 px-lg-5">
            <div class="row g-4">
                <div class="col-lg-3 d-none d-lg-block">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                        <div class="list-group list-group-flush gap-1">
                            <a href="account-settings.php" class="list-group-item list-group-item-action border-0 rounded-3 text-secondary py-2.5 px-3">Account Settings</a>
                            <a href="account-info.php" class="list-group-item list-group-item-action border-0 rounded-3 fw-semibold active py-2.5 px-3" style="background-color: #f1f5f9; color: #011f01;">Personal Information</a>
                            <a href="order-history.php" class="list-group-item list-group-item-action border-0 rounded-3 text-secondary py-2.5 px-3">Order History</a>
                            <a href="track-order.php" class="list-group-item list-group-item-action border-0 rounded-3 text-secondary py-2.5 px-3">Track Order</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="d-block d-lg-none mb-3">
                        <a href="account-info.php" class="text-decoration-none text-success fw-semibold small">
                            <i class="bi bi-arrow-left me-1"></i> Personal Information
                        </a>
                    </div>

                    <div class="auth-card-admin p-4 p-md-5">
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

                        <form action="edit-profile.php" method="POST" novalidate>
                            <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($customerId); ?>">
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
                                <button type="submit" class="btn btn-dark-custom">Save Changes</button>
                                <a href="account-info.php" class="btn btn-outline-custom">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php' ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>