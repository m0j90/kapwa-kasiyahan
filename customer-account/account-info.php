<?php 
session_start();

if (!isset($_SESSION['customer_id']) && !isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../db_connect.php';

$customer_id = $_SESSION['customer_id'] ?? 0;
$username_session = $_SESSION['username'] ?? '';

$stmt = mysqli_prepare($Connection, "SELECT first_name, last_name, email, contact, address, username FROM customers WHERE customer_id = ? OR username = ?");
$customer = null;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'is', $customer_id, $username_session);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $customer = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

$first_name = $customer['first_name'] ?? 'Unknown';
$last_name = $customer['last_name'] ?? 'Unknown';
$email = $customer['email'] ?? 'name@example.com';
$contact = $customer['contact'] ?? '09XX XXX XXXX';
$address = $customer['address'] ?? 'Unknown';
$username = $customer['username'] ?? 'Unknown';
$fullname = trim($first_name . ' ' . $last_name);

$activePage = 'settings';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Information</title>
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
                    <li class="breadcrumb-item active" aria-current="page">Personal Information</li>
                </ol>
            </nav>
            <h1 class="fw-bold display-5 font-poppins text-dark">Personal Information</h1>
            <p class="text-muted mb-0">Your saved profile details.</p>
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
                        <a href="account-settings.php" class="text-decoration-none text-success fw-semibold small">
                            <i class="bi bi-arrow-left me-1"></i> Account Settings
                        </a>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pb-4 mb-4 border-bottom">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 64px; height: 64px;">
                                    <i class="bi bi-person-fill fs-2"></i>
                                </div>
                                <div>
                                    <h2 class="h5 fw-bold font-poppins text-dark mb-1"><?php echo htmlspecialchars($fullname); ?></h2>
                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($email); ?></p>
                                </div>
                            </div>
                            <div>
                                <a href="edit-profile.php" class="btn btn-dark-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm text-decoration-none text-center w-100 w-md-auto">
                                    Edit Profile
                                </a>
                            </div>
                        </div>

                        <div class="d-flex flex-column gap-3 small">
                            <div class="d-flex justify-content-between pb-2 border-bottom">
                                <span class="text-muted small fw-medium">First Name</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($first_name); ?></span>
                            </div>
                            <div class="d-flex justify-content-between pb-2 border-bottom">
                                <span class="text-muted small fw-medium">Last Name</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($last_name); ?></span>
                            </div>
                            <div class="d-flex justify-content-between pb-2 border-bottom">
                                <span class="text-muted small fw-medium">Email</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($email); ?></span>
                            </div>
                            <div class="d-flex justify-content-between pb-2 border-bottom">
                                <span class="text-muted small fw-medium">Contact Number</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($contact); ?></span>
                            </div>
                            <div class="d-flex justify-content-between pb-2 border-bottom">
                                <span class="text-muted small fw-medium">Delivery Address</span>
                                <span class="fw-semibold text-dark text-end" style="max-width: 60%;"><?php echo htmlspecialchars($address); ?></span>
                            </div>
                            <div class="d-flex justify-content-between pb-2 border-bottom">
                                <span class="text-muted small fw-medium">Username</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($username); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php' ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>