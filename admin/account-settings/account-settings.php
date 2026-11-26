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

$stmt = mysqli_prepare($Connection, "SELECT first_name, last_name, email, role FROM admins WHERE admin_id = ?");
$current_admin = null;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $admin_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $current_admin = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

$fullname = $current_admin ? trim($current_admin['first_name'] . ' ' . $current_admin['last_name']) : 'Unknown Administrator';
$email = $current_admin ? $current_admin['email'] : 'admin@example.com';
$role_title = ($current_admin && $current_admin['role'] === 'admin') ? 'Administrator' : 'Sub-Administrator';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
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
                <div class="mb-4">
                    <h2 class="fw-bold font-poppins text-dark mb-1">Account Settings</h2>
                    <p class="text-secondary small mb-0">Manage your administrator profile.</p>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-4 mb-4 bg-white">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 64px; height: 64px;">
                                <i class="bi bi-person-fill fs-2"></i>
                            </div>
                            <div>
                                <h2 class="h5 fw-bold font-poppins text-dark mb-1"><?php echo htmlspecialchars($fullname); ?></h2>
                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($email); ?> &middot; <span class="text-success fw-medium"><?php echo htmlspecialchars($role_title); ?></span></p>
                            </div>
                        </div>
                        <div>
                            <a href="edit-profile.php" class="btn btn-outline-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm w-100 w-md-auto text-decoration-none text-center">
                                Edit Profile
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="rounded-3 bg-light d-inline-flex align-items-center justify-content-center text-secondary mb-3" style="width: 48px; height: 48px;">
                                    <i class="bi bi-person-badge fs-5"></i>
                                </div>
                                <h3 class="h5 fw-bold font-poppins text-dark mb-2">Personal Information</h3>
                                <p class="text-muted small mb-4">View your name, contact details and username.</p>
                            </div>
                            <div>
                                <a href="personal-information.php" class="btn btn-outline-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm text-decoration-none d-inline-block">
                                    Open
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="rounded-3 bg-light d-inline-flex align-items-center justify-content-center text-secondary mb-3" style="width: 48px; height: 48px;">
                                    <i class="bi bi-pencil-square fs-5"></i>
                                </div>
                                <h3 class="h5 fw-bold font-poppins text-dark mb-2">Edit Profile</h3>
                                <p class="text-muted small mb-4">Update your details and security settings.</p>
                            </div>
                            <div>
                                <a href="edit-profile.php" class="btn btn-outline-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm text-decoration-none d-inline-block">
                                    Open
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>