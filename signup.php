<?php
session_start();

date_default_timezone_set('Asia/Manila');

include 'db_connect.php';

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

        header('Location: login.php?success=1');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create a Customer Account</title>
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
        <div class="auth-card-wide p-4 p-sm-5">
            <h1 class="auth-title mb-1">Create a Customer Account</h1>
            <p class="auth-subtitle mb-4">Fill in your details to register. All fields are required.</p>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasError) { ?>
                <div class="alert-warning-custom mb-4" role="alert">
                    <i class="bi bi-exclamation-circle-fill fs-5"></i>
                    <span>Please fix the highlighted fields before continuing.</span>
                </div>
            <?php } ?>

            <form action="signup.php" method="POST" novalidate>
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

                <button type="submit" class="btn btn-primary-custom mb-4 w-100">Create Account</button>
            </form>

            <div class="text-center pt-2 border-top">
                <p class="auth-footer-text mb-0">
                    Already registered? <a href="login.php" class="auth-footer-link">Log In</a>
                </p>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>