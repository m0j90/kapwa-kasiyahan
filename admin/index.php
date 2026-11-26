<?php
session_start();

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../db_connect.php';

$error_message = '';
$username = '';
$role = 'sub-admin';
$show_validation_errors = false;
$errors = [
    'role' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'sub_admin';

    $valid_roles = ['admin', 'sub-admin'];
    if (!in_array($role, $valid_roles)) {
        $errors['role'] = 'Please select a valid role';
        $show_validation_errors = true;
    }

    if (empty($username) || empty($password)) {
        $error_message = "Login failed. Check your administrator username and password and try again.";
        $show_validation_errors = true;
    } else {
        $stmt = mysqli_prepare($Connection, "SELECT admin_id, username, password_hash, first_name, last_name, role, account_status FROM admins WHERE username = ? AND role = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $username, $role);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($Row = mysqli_fetch_assoc($result)) {
                if ($Row['account_status'] === 'active' && password_verify($password, $Row['password_hash'])) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $Row['admin_id'];
                    $_SESSION['admin_username'] = $Row['username'];
                    $_SESSION['admin_role'] = $Row['role'];
                    $_SESSION['admin_name'] = $Row['first_name'] . ' ' . $Row['last_name'];

                    mysqli_stmt_close($stmt);
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error_message = "Login failed or account is inactive. Please check your login details.";
                    $show_validation_errors = true;
                }
            } else {
                $error_message = "Login failed. Check your administrator username and password and try again.";
                $show_validation_errors = true;
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = "Database query preparation failed.";
            $show_validation_errors = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="../assets/images/logos/transparent-symbol-logo.png">
</head>
<body class="bg-light text-dark d-flex align-items-center justify-content-center min-vh-100">

    <main class="container d-flex align-items-center justify-content-center flex-grow-1 py-5">
        <div class="auth-card p-4 p-md-5">
            <div class="card-body p-0">
                <h1 class="auth-title mb-2">Administrator Login</h1>
                <p class="auth-subtitle mb-4">Enter your administrator username and password.</p>

                <?php if (!empty($error_message)) { ?>
                    <div class="alert-danger-custom mb-4" role="alert">
                        <i class="bi bi-x-circle-fill text-danger me-2 fs-5 flex-shrink-0"></i>
                        <div class="small"><?php echo htmlspecialchars($error_message); ?></div>
                    </div>
                <?php } ?>

                <?php if (isset($_GET["logout"])) { ?>
                    <div class="alert alert-success-custom alert-dismissible fade show w-100 mx-auto m-4 d-flex align-items-center gap-2" role="alert">
                        <i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0"></i>
                        <div>
                            You have successfully logged out of your admin account.
                        </div>
                        <button
                            type="button"
                            class="btn-close ms-auto"
                            data-bs-dismiss="alert"
                            aria-label="Close"
                        ></button>
                    </div>
                <?php } ?>

                <form action="index.php" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label-custom">Username</label>
                        <input type="text"
                            class="form-control form-control-custom <?php echo ($show_validation_errors && empty($username)) ? 'border-danger' : ''; ?>"
                            id="username"
                            name="username"
                            placeholder="Enter admin username"
                            value="<?php echo htmlspecialchars($username); ?>">
                        <?php if ($show_validation_errors && empty($username)) { ?>
                            <div class="input-error-text">(!) Check your username</div>
                        <?php } ?>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label-custom">Admin Role</label>
                        <select class="form-select form-control-custom <?php echo !empty($errors['role']) ? 'border-danger' : ''; ?>" id="role" name="role" required>
                            <?php 
                            $role_options = [
                                'sub_admin' => 'Sub-Admin',
                                'admin'     => 'Admin'
                            ];
                            foreach ($role_options as $val => $label) {
                                $selected = ($role === $val) ? 'selected' : '';
                                echo '<option value="' . $val . '" ' . $selected . '>' . $label . '</option>';
                            }
                            ?>
                        </select>
                        <?php if (!empty($errors['role'])) { ?>
                            <div class="input-error-text">(!) <?php echo $errors['role']; ?></div>
                        <?php } ?>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label-custom">Password</label>
                        <input type="password"
                            class="form-control form-control-custom <?php echo ($show_validation_errors) ? 'border-danger' : ''; ?>"
                            id="password"
                            name="password"
                            placeholder="********">
                        <?php if ($show_validation_errors) { ?>
                            <div class="input-error-text">(!) Invalid username or password</div>
                        <?php } ?>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 mb-3">Log In</button>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>