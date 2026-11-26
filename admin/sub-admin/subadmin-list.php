<?php
session_start();
$current_page = 'sub-admin';

require_once __DIR__ . '/../../db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

$search = is_string($_GET['search'] ?? null) ? trim($_GET['search']) : '';

if (isset($_GET['del'])) {
    $del_id = intval($_GET['del']);
    $del_stmt = mysqli_prepare($Connection, "DELETE FROM admins WHERE admin_id = ?");
    if ($del_stmt) {
        mysqli_stmt_bind_param($del_stmt, 'i', $del_id);
        mysqli_stmt_execute($del_stmt);
        mysqli_stmt_close($del_stmt);
    }
    header('Location: subadmin-list.php?deleted=1');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sub-Admin Management - Admin Panel</title>
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
                        <h2 class="fw-bold font-poppins text-dark">Sub-Admin Management</h2>
                        <p class="text-secondary small mb-0">Manage sub-administrator accounts.</p>
                    </div>
                    <div>
                        <a href="subadmin-add.php" class="btn btn-dark-custom w-100 w-md-auto px-4 py-2.5 rounded-3 fw-semibold small shadow-sm d-inline-flex align-items-center justify-content-center gap-2 text-white text-decoration-none">
                            <i class="bi bi-plus-lg"></i> Add Sub-Admin
                        </a>
                    </div>
                </div>

                <form method="GET" action="subadmin-list.php" class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 mb-4">
                    <div class="input-group overflow-hidden flex-grow-1" style="max-width: 400px; width: 100%;">
                        <span class="input-group-text bg-white border-end-0 text-muted ps-3">
                            <i class="bi bi-search small"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0 ps-1 shadow-none small" placeholder="Search sub-admins..." value="<?php echo htmlspecialchars($search); ?>">
                        <?php if ($search !== '') { ?>
                            <a href="subadmin-list.php" class="input-group-text bg-white border-start-0 text-decoration-none text-muted px-2" title="Clear search">
                                <i class="bi bi-x-lg small"></i>
                            </a>
                        <?php } ?>
                        <button type="submit" class="btn btn-palette-primary px-4 py-2 mx-2 rounded-3 small fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-1 text-white text-decoration-none flex-shrink-0">
                            <span>Search</span>
                        </button>
                    </div>
                </form>

                <?php if (isset($_GET["success"])) { ?>
                    <div class="alert alert-success-custom alert-dismissible fade show my-3 py-3 px-3 shadow-sm d-flex align-items-center justify-content-between" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-success fs-5 me-2"></i>
                            <span class="mb-0">Sub-administrator account created successfully.</span>
                        </div>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php } ?>

                <?php if (isset($_GET["deleted"])) { ?>
                    <div class="alert alert-danger-custom alert-dismissible fade show my-3 py-3 px-3 shadow-sm d-flex align-items-center justify-content-between" role="alert" style="background-color: #E74C3C; color: #ffffff; border-color: #E74C3C;">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-trash3-fill fs-5 me-2"></i>
                            <span class="mb-0">Sub-administrator account deleted successfully.</span>
                        </div>
                        <button type="button" class="btn-close btn-close-white shadow-none m-0 p-0" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php } ?>

                <?php
                $escapedSearch = mysqli_real_escape_string($Connection, $search);
                $query = "SELECT * FROM admins WHERE (first_name LIKE '%{$escapedSearch}%' OR last_name LIKE '%{$escapedSearch}%') ORDER BY admin_id DESC";
                $result = mysqli_query($Connection, $query) or die(mysqli_error($Connection));

                if ($result && mysqli_num_rows($result) > 0) {
                ?>

                    <div class="card border-0 shadow-sm rounded-4 d-none d-lg-block mb-3 bg-white">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light text-uppercase fs-7 text-muted border-bottom">
                                        <tr>
                                            <th class="py-3 px-4 fw-semibold text-secondary small">ID</th>
                                            <th class="py-3 px-3 fw-semibold text-secondary small">Name</th>
                                            <th class="py-3 px-3 fw-semibold text-secondary small">Email</th>
                                            <th class="py-3 px-3 fw-semibold text-secondary small">Username</th>
                                            <th class="py-3 px-3 fw-semibold text-secondary small">Role</th>
                                            <th class="py-3 px-3 fw-semibold text-secondary small">Added</th>
                                            <th class="py-3 px-4 fw-semibold text-secondary small text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($Row = mysqli_fetch_assoc($result)) { 
                                            $fullname = trim($Row['first_name'] . ' ' . $Row['last_name']);
                                            $date_added = date('M j, Y • g:i A', strtotime($Row['created_at']));
                                            $role_badge = ($Row['role'] === 'admin') ? '<span class="badge bg-success p-2">Admin</span>' : '<span class="badge bg-secondary p-2">Sub-Admin</span>';
                                        ?>
                                            <tr>
                                                <td class="py-3 px-4 fw-medium text-secondary small"><?php echo htmlspecialchars($Row['admin_id']); ?></td>
                                                <td class="py-3 px-3 fw-semibold text-dark small"><?php echo htmlspecialchars($fullname); ?></td>
                                                <td class="py-3 px-3 text-muted small"><?php echo htmlspecialchars($Row['email']); ?></td>
                                                <td class="py-3 px-3 text-muted small"><?php echo htmlspecialchars($Row['username']); ?></td>
                                                <td class="py-3 px-3 text-muted small"><?php echo $role_badge; ?></td>
                                                <td class="py-3 px-3 text-muted small"><?php echo $date_added; ?></td>
                                                <td class="py-3 px-4 text-end">
                                                    <div class="d-flex justify-content-end gap-1">
                                                        <a href="subadmin-view.php?id=<?php echo $Row['admin_id']; ?>" class="btn btn-sm btn-palette-info px-2 py-1 rounded-2 small fw-semibold shadow-sm">View</a>
                                                        <a href="subadmin-edit.php?id=<?php echo $Row['admin_id']; ?>" class="btn btn-sm btn-palette-primary px-2 py-1 rounded-2 small fw-semibold shadow-sm">Edit</a>
                                                        <a href="#" class="btn btn-sm btn-palette-danger px-2 py-1 rounded-2 small fw-semibold shadow-sm d-inline-flex align-items-center gap-1 text-white text-decoration-none" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $Row['admin_id']; ?>">Delete</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="d-lg-none d-flex flex-column gap-3 mb-3">
                        <?php 
                        mysqli_data_seek($result, 0);
                        while ($Row = mysqli_fetch_assoc($result)) { 
                            $fullname = trim($Row['first_name'] . ' ' . $Row['last_name']);
                            $date_added = date('M j, Y • g:i A', strtotime($Row['created_at']));
                        ?>
                            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                                <div class="d-flex justify-content-between align-items-start mb-3 pb-2 border-bottom">
                                    <h5 class="fw-bold font-poppins text-dark fs-6 mb-0"><?php echo htmlspecialchars($fullname); ?></h5>
                                    <span class="badge <?php echo ($Row['role'] === 'admin') ? 'bg-success' : 'bg-secondary'; ?>"><?php echo ucfirst(str_replace('_', '-', $Row['role'])); ?></span>
                                </div>
                                <div class="d-flex flex-column gap-2 small text-muted mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>ID</span>
                                        <span class="fw-medium text-dark"><?php echo htmlspecialchars($Row['admin_id']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Email</span>
                                        <span class="fw-medium text-dark"><?php echo htmlspecialchars($Row['email']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Username</span>
                                        <span class="fw-medium text-dark"><?php echo htmlspecialchars($Row['username']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Added</span>
                                        <span class="fw-medium text-dark"><?php echo $date_added; ?></span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                                    <a href="subadmin-view.php?id=<?php echo $Row['admin_id']; ?>" class="btn btn-sm btn-palette-info px-2.5 py-1 rounded-2 small fw-semibold shadow-sm d-flex align-items-center gap-1"><i class="bi bi-eye"></i> View</a>
                                    <a href="subadmin-edit.php?id=<?php echo $Row['admin_id']; ?>" class="btn btn-sm btn-palette-primary px-2.5 py-1 rounded-2 small fw-semibold shadow-sm d-flex align-items-center gap-1"><i class="bi bi-pencil"></i> Edit</a>
                                    <a href="#" class="btn btn-sm btn-palette-danger px-2.5 py-1 rounded-2 small fw-semibold shadow-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $Row['admin_id']; ?>"><i class="bi bi-trash"></i> Delete</a>
                                </div>
                            </div>
                        <?php } ?>
                    </div>

                <?php } else { ?>
                    <div class="card border-0 shadow-sm rounded-4 my-3 bg-white">
                        <div class="card-body text-center py-5">
                            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center text-muted mb-3" style="width: 56px; height: 56px;">
                                <i class="bi bi-shield-lock fs-3"></i>
                            </div>
                            <h6 class="fw-semibold text-dark mb-1 font-poppins">No sub-administrators found</h6>
                            <p class="text-muted small mb-3">There are currently no sub-admin accounts registered in the system.</p>
                            <a href="subadmin-add.php" class="btn btn-dark-custom btn-sm px-4 py-2 rounded-3 fw-semibold text-decoration-none">
                                <i class="bi bi-plus-lg me-1"></i> Add First Sub-Admin
                            </a>
                        </div>
                    </div>
                <?php } ?>
            </main>
        </div>
    </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>