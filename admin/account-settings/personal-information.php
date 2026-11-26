<?php
session_start();
$current_page = 'settings';

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../../db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

$admin_id = $_SESSION['admin_id'] ?? 1;

$stmt = mysqli_prepare($Connection, "SELECT admin_id, username, first_name, last_name, email, contact, role, created_at FROM admins WHERE admin_id = ?");
$admin = null;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $admin_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $admin = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

$first_name = $admin['first_name'] ?? 'Unknown';
$last_name = $admin['last_name'] ?? 'Unknown';
$email = $admin['email'] ?? 'admin@example.com';
$contact = $admin['contact'] ?? '09XX XXX XXXX';
$username = $admin['username'] ?? 'Unknown';
$role_raw = $admin['role'] ?? 'Unknown';
$account_type = ($role_raw === 'admin') ? 'Administrator' : 'Sub-Administrator';
$fullname = trim($first_name . ' ' . $last_name);
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
                        <h2 class="fw-bold font-poppins text-dark mb-1">Personal Information</h2>
                        <p class="text-secondary small mb-0">Your saved administrator details.</p>    
                    </div>
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <a href="account-settings.php" class="btn btn-dark-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm text-center text-white text-decoration-none">
                            <i class="bi bi-arrow-left me-2"></i> Back
                        </a>
                    </div>
                </div>

                <div class="auth-card-admin p-4 p-md-5 bg-white shadow-sm rounded-4 border-0">
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
                            <span class="text-muted">First Name</span>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($first_name); ?></span>
                        </div>
                        <div class="d-flex justify-content-between pb-2 border-bottom">
                            <span class="text-muted">Last Name</span>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($last_name); ?></span>
                        </div>
                        <div class="d-flex justify-content-between pb-2 border-bottom">
                            <span class="text-muted">Email</span>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($email); ?></span>
                        </div>
                        <div class="d-flex justify-content-between pb-2 border-bottom">
                            <span class="text-muted">Contact Number</span>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($contact); ?></span>
                        </div>
                        <div class="d-flex justify-content-between pb-2 border-bottom">
                            <span class="text-muted">Username</span>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($username); ?></span>
                        </div>
                        <div class="d-flex justify-content-between pb-2 border-bottom">
                            <span class="text-muted">Account Type</span>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($account_type); ?></span>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>