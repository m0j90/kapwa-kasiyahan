<?php
session_start();

include 'db_connect.php';

$errorMessage = '';
$usernameVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameVal = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = mysqli_prepare($Connection, "SELECT customer_id, username, password_hash FROM customers WHERE username = ?");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $usernameVal);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $row['password_hash'])) {
                $_SESSION['customer_id'] = $row['customer_id'];
                $_SESSION['username'] = $row['username'];

                mysqli_stmt_close($stmt);
                header('Location: index.php');
                exit();
            }
        }
        $errorMessage = 'Login failed. Check your username and password and try again.';
        mysqli_stmt_close($stmt);
    } else {
        die(mysqli_error($Connection));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="assets/images/logos/transparent-symbol-logo.png">
    
</head>
<body class="d-flex flex-column min-vh-100">

    <?php 
        include 'includes/navbar.php';
    ?>

    <main class="container d-flex align-items-center justify-content-center flex-grow-1 py-5">
        <div class="auth-card p-4 p-sm-5">
            <h1 class="auth-title mb-1">Customer Login</h1>
            <p class="auth-subtitle mb-4">Enter your username and password.</p>
        
            <?php if (!empty($errorMessage)) { ?>
                <div class="alert-danger-custom mb-4" role="alert">
                    <i class="bi bi-exclamation-circle-fill text-danger fs-5"></i>
                    <span><?php echo htmlspecialchars($errorMessage); ?></span>
                </div>
            <?php } ?>

            <?php if (isset($_GET["success"])) { ?>
                <div class="alert alert-success-custom alert-dismissible fade show w-100 mx-auto m-4 d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0"></i>
                    <div>
                        Account created successfully! Please log in with your username and password.
                    </div>
                    <button
                        type="button"
                        class="btn-close ms-auto"
                        data-bs-dismiss="alert"
                        aria-label="Close"
                    ></button>
                </div>
            <?php } ?>

            <?php if (isset($_GET["logout"])) { ?>
                <div class="alert alert-success-custom alert-dismissible fade show w-100 mx-auto m-4 d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0"></i>
                    <div>
                        You have successfully logged out of your account.
                    </div>
                    <button
                        type="button"
                        class="btn-close ms-auto"
                        data-bs-dismiss="alert"
                        aria-label="Close"
                    ></button>
                </div>
            <?php } ?>

            <?php if (isset($_GET["required"])) { ?>
                <div class="alert alert-danger-custom alert-dismissible fade show w-100 mx-auto m-4 d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle text-danger fs-5 flex-shrink-0"></i>
                    <div>
                        Please log in to continue adding items to your cart.
                    </div>
                </div>
            <?php } ?>

            <form action="login.php" method="POST" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label-custom">Username</label>
                    <input type="text" class="form-control form-control-custom <?php echo !empty($errorMessage) ? 'border-danger' : ''; ?>" id="username" name="username" placeholder="Enter username" value="<?php echo htmlspecialchars($usernameVal); ?>" required>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label-custom">Password</label>
                    <input type="password" class="form-control form-control-custom <?php echo !empty($errorMessage) ? 'border-danger' : ''; ?>" id="password" name="password" placeholder="••••••••" required>
                </div>

                <?php if (!empty($errorMessage)) { ?>
                    <div class="input-error-text">
                        <i class="bi bi-exclamation-triangle"></i> Invalid username or password
                    </div>
                <?php } ?>

                <button type="submit" class="btn btn-primary-custom w-100 mb-4">Log In</button>
            </form>

            <div class="text-center pt-2 border-top">
                <p class="auth-footer-text mb-2">
                    No account yet? <a href="signup.php" class="auth-footer-link">Sign Up</a>
                </p>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>