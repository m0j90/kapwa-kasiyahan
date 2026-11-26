<?php
session_start();
$current_page = 'sub-admin';

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../../db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

$adminId = $_GET['id'] ?? '';
$adminId = is_scalar($adminId) ? (string) $adminId : '';

$stmt = mysqli_prepare($Connection, 'SELECT * FROM admins WHERE admin_id = ?') or die(mysqli_error($Connection));
mysqli_stmt_bind_param($stmt, 's', $adminId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$Row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$Row) {
    http_response_code(404);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sub-Admin Details</title>
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
                <?php if (!$Row) { ?>
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center text-muted mb-3 mx-auto" style="width: 56px; height: 56px;">
                            <i class="bi bi-exclamation-triangle fs-3"></i>
                        </div>
                        <h6 class="fw-semibold text-dark mb-1 font-poppins">Sub-admin not found</h6>
                        <p class="text-muted small mb-3">The sub-admin you are looking for does not exist or has been removed.</p>
                        <div>
                            <a href="subadmin-list.php" class="btn btn-palette-primary btn-sm px-4 py-2 rounded-3 fw-semibold text-decoration-none">
                                Back to Sub-Admin Management
                            </a>
                        </div>
                    </div>
                <?php } else {
                    $fullname = trim($Row['first_name'] . ' ' . $Row['last_name']);
                    $date_added = date('F d, Y', strtotime($Row['created_at']));
                ?>
                
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <h2 class="fw-bold font-poppins text-dark mb-1">Sub-Admin Details</h2>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($Row['admin_id'] . ' · ' . $Row['first_name'] . ' ' . $Row['last_name']); ?></p>
                    </div>
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <a href="subadmin-edit.php?id=<?php echo htmlspecialchars($Row['admin_id']); ?>" class="btn btn-outline-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm text-center text-decoration-none">
                            Edit Sub-Admin
                        </a>
                        <a href="#" class="btn btn-palette-danger px-4 py-2 rounded-3 fw-semibold small shadow-sm text-center text-white text-decoration-none" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $Row['admin_id']; ?>">
                            Delete Sub-Admin
                        </a>
                        <a href="subadmin-list.php" class="btn btn-dark-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm text-center text-white text-decoration-none">
                            <i class="bi bi-arrow-left me-2"></i> Back
                        </a>
                    </div>
                </div>

                    <div class="auth-card-admin p-4 p-md-5 bg-white">
                        <h2 class="auth-title h4 mb-4 fw-bold font-poppins">Sub-Admin Information</h2>
                        <div class="d-flex flex-column gap-3 small">
                            <div class="d-flex justify-content-between pb-2 border-bottom">
                                <span class="text-muted">Admin ID</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($Row['admin_id']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between pb-2 border-bottom">
                                <span class="text-muted">Full Name</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($fullname); ?></span>
                            </div>
                            <div class="d-flex justify-content-between pb-2 border-bottom">
                                <span class="text-muted">Email</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($Row['email']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between pb-2 border-bottom">
                                <span class="text-muted">Contact Number</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($Row['contact']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between pb-2 border-bottom">
                                <span class="text-muted">Username</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($Row['username']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between pb-2 border-bottom">
                                <span class="text-muted">Role</span>
                                <span class="badge p-2 <?php echo ($Row['role'] === 'admin') ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo ucfirst(str_replace('_', '-', $Row['role'])); ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between pb-2 border-bottom">
                                <span class="text-muted">Added On</span>
                                <span class="fw-semibold text-dark"><?php echo $date_added; ?></span>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </main>
        </div>
    </div>

    <?php if ($Row) { ?>
        <?php 
        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_data_seek($result, 0);
            while ($Row = mysqli_fetch_assoc($result)) { 
                $fullname = trim($Row['first_name'] . ' ' . $Row['last_name']);
                $role_label = ($Row['role'] === 'admin') ? 'administrator' : 'sub-administrator';
        ?>
            <div class="modal fade" id="deleteModal<?php echo $Row['admin_id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $Row['admin_id']; ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
                    <div class="modal-content border-0 shadow-lg rounded-4 p-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="modal-title fw-bold font-poppins text-dark mb-0" id="deleteModalLabel<?php echo $Row['admin_id']; ?>">Delete this <?php echo ($Row['role'] === 'admin') ? 'admin' : 'sub-admin'; ?>?</h5>
                            <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        
                        <div class="mb-4">
                            <p class="text-muted small mb-3">This permanently removes the <?php echo $role_label; ?> account. This cannot be undone.</p>
                            
                            <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 small">
                                <span class="text-muted"><?php echo ($Row['role'] === 'admin') ? 'Admin' : 'Sub-admin'; ?></span>
                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($fullname . ' (' . $Row['admin_id'] . ')'); ?></span>
                            </div>
                        </div>

                        <div class="delete-actions-wrapper">
                            <button type="button" class="btn btn-outline-custom px-4 rounded-3 fw-semibold small shadow-none btn-cancel-custom" data-bs-dismiss="modal">
                                Cancel
                            </button>
                            <a href="subadmin-list.php?del=<?php echo $Row['admin_id']; ?>" class="btn btn-palette-danger px-4 rounded-3 fw-semibold small text-white text-decoration-none shadow-sm btn-delete-custom">
                                Yes, Delete <?php echo ($Row['role'] === 'admin') ? 'Admin' : 'Sub-Admin'; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php 
            }
        } 
        ?>
    <?php } ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>