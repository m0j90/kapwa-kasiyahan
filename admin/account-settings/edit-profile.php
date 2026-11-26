<?php
session_start();
$current_page = 'settings';

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
    'username'          => '',
    'password'          => '',
    'confirmpassword'   => ''
];

$adminId = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['admin_id'] ?? $_SESSION['admin_id'])
    : ($_GET['id'] ?? $_GET['edit'] ?? $_SESSION['admin_id']);

$adminId = intval($adminId);

$stmt = mysqli_prepare($Connection, 'SELECT * FROM admins WHERE admin_id = ?') or die(mysqli_error($Connection));
mysqli_stmt_bind_param($stmt, 'i', $adminId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$Row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$Row) {
    http_response_code(404);
}

$formValues = [
    'admin_id'          => $adminId,
    'firstname'         => $_SERVER['REQUEST_METHOD'] === 'POST' ? trim($_POST['firstname'] ?? '') : ($Row['first_name'] ?? ''),
    'lastname'          => $_SERVER['REQUEST_METHOD'] === 'POST' ? trim($_POST['lastname'] ?? '') : ($Row['last_name'] ?? ''),
    'email'             => $_SERVER['REQUEST_METHOD'] === 'POST' ? trim($_POST['email'] ?? '') : ($Row['email'] ?? ''),
    'contact'           => $_SERVER['REQUEST_METHOD'] === 'POST' ? trim($_POST['contact'] ?? '') : ($Row['contact'] ?? ''),
    'username'          => $_SERVER['REQUEST_METHOD'] === 'POST' ? trim($_POST['username'] ?? '') : ($Row['username'] ?? '')
];

$password = $_SERVER['REQUEST_METHOD'] === 'POST' ? trim($_POST['password'] ?? '') : '';
$confirmPassword = $_SERVER['REQUEST_METHOD'] === 'POST' ? trim($_POST['confirmpassword'] ?? '') : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $check_stmt = mysqli_prepare($Connection, "SELECT admin_id FROM admins WHERE (username = ? OR email = ?) AND admin_id != ?");
        mysqli_stmt_bind_param($check_stmt, 'ssi', $formValues['username'], $formValues['email'], $adminId);
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

        $stmt = mysqli_prepare($Connection, "UPDATE admins SET
            username = ?,
            password_hash = ?,
            first_name = ?,
            last_name = ?,
            email = ?,
            contact = ?,
            account_status = ?,
            updated_at = NOW() WHERE admin_id = ?");
        
        mysqli_stmt_bind_param(
            $stmt,
            'sssssssi',
            $formValues['username'],
            $passwordHash,
            $formValues['firstname'],
            $formValues['lastname'],
            $formValues['email'],
            $formValues['contact'],
            $accountStatus,
            $adminId
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header('Location: edit-profile.php?id=' . rawurlencode($adminId) . '&success=1');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Admin Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="../../assets/images/logos/transparent-symbol-logo.png">
</head>
<body class="bg-light text-dark">

    <?php include '../../includes/admin-header.php'; ?>

    <div class="admin-wrapper">
        <div class="admin-sidebar-container d-none d-md-block">
            <?php include '../../includes/admin-sidebar.php'; ?>
        </div>

        <div class="admin-content-area">
            <main class="p-4 p-md-5">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <h2 class="fw-bold font-poppins text-dark mb-1">Edit Profile</h2>
                        <p class="text-muted small mb-0">Update your administrator details.</p>
                    </div>
                    <div>
                        <a href="personal-information.php" class="btn btn-dark-custom w-100 w-md-auto px-4 py-2.5 rounded-3 fw-semibold small shadow-sm d-inline-flex align-items-center justify-content-center gap-2 text-white text-decoration-none">
                            <i class="bi bi-arrow-left"></i> Back to Personal Information
                        </a>
                    </div>
                </div>

                <div class="auth-card-admin p-4 p-md-5 bg-white shadow-sm rounded-4 border-0">
                    <?php if (isset($_GET["success"])) { ?>
                        <div class="alert alert-success-custom alert-dismissible fade show my-3 py-3 px-3 shadow-sm d-flex align-items-center justify-content-between" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-success fs-5 me-2"></i>
                                <span class="mb-0">Your profile details have been updated successfully.</span>
                            </div>
                            <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php } ?>

                    <form action="edit-profile.php?id=<?php echo htmlspecialchars($adminId); ?>" method="POST" novalidate>
                        <input type="hidden" name="admin_id" value="<?php echo htmlspecialchars($adminId); ?>">
                            
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
                            <a href="personal-information.php" class="btn btn-outline-custom">Cancel</a>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>