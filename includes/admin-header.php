<?php
$admin_role_session = $_SESSION['admin_role'] ?? 'admin';
$display_role_title = ($admin_role_session === 'admin') ? 'Administrator' : 'Sub-Administrator';
?>
<nav class="navbar navbar-expand-lg custom-admin-navbar border-bottom shadow-sm bg-white fixed-top">
    <div class="container-fluid px-3 px-md-4 py-1 flex-nowrap">
        <a class="navbar-brand d-flex align-items-center gap-2 gap-sm-3 text-decoration-none me-2 overflow-hidden" href="<?= file_exists('dashboard.php') ? 'dashboard.php' : '../dashboard.php'; ?>" style="min-width: 0;">
            <img src="<?= file_exists('../assets/images/logos/transparent-combo-mark-logo.png') ? '../assets/images/logos/transparent-combo-mark-logo.png' : '../../assets/images/logos/transparent-combo-mark-logo.png'; ?>" alt="Logo" class="admin-navbar-logo flex-shrink-0">
            <div class="vr opacity-25 flex-shrink-0 admin-navbar-divider"></div>
            <span class="fw-semibold font-poppins text-dark admin-panel-title text-truncate">Admin Panel</span>
        </a>

        <div class="d-none d-lg-flex align-items-center gap-3 flex-shrink-0">
            <div class="dropdown">
                <button class="btn btn-light border-0 d-flex align-items-center gap-2 py-1.5 px-3 rounded-pill dropdown-toggle shadow-sm" type="button" id="adminAccountMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="rounded-circle bg-secondary-subtle d-flex align-items-center justify-content-center text-muted" style="width: 28px; height: 28px;">
                        <i class="bi bi-person-fill small"></i>
                    </div>
                    <span class="fw-medium text-dark small"><?php echo htmlspecialchars($display_role_title); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border rounded-3 py-2 mt-2" aria-labelledby="adminAccountMenu" style="min-width: 200px;">
                    <li><h6 class="dropdown-header text-muted small fw-semibold">Manage Account</h6></li>
                    <li><a class="dropdown-item py-2 small" href="<?= file_exists('account-settings/account-settings.php') ? 'account-settings/account-settings.php' : '../account-settings/account-settings.php'; ?>"><i class="bi bi-gear me-2 text-muted"></i> Account Settings</a></li>
                    <li><a class="dropdown-item py-2 small" href="<?= file_exists('account-settings/personal-information.php') ? 'account-settings/personal-information.php' : '../account-settings/personal-information.php'; ?>"><i class="bi bi-person-badge me-2 text-muted"></i> Personal Information</a></li>
                    <li><a class="dropdown-item py-2 small" href="<?= file_exists('account-settings/edit-profile.php') ? 'account-settings/edit-profile.php' : '../account-settings/edit-profile.php'; ?>"><i class="bi bi-pencil-square me-2 text-muted"></i> Edit Profile</a></li>
                </ul>
            </div>

            <button type="button" class="btn btn-outline-custom px-3 py-1.5 rounded-3 fw-semibold small" data-bs-toggle="modal" data-bs-target="#logoutModal">
                Log Out
            </button>
        </div>

        <button class="navbar-toggler custom-toggler d-lg-none flex-shrink-0 ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#mobileAdminMenu" aria-controls="mobileAdminMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>

    <div class="collapse admin-collapse d-lg-none bg-white border-top shadow-sm px-4 py-3 position-absolute start-0 w-100" id="mobileAdminMenu" style="top: 100%; z-index: 1025;">
        <?php
        $mobile_links = [
            [
                'label' => 'Dashboard',
                'slug'  => 'dashboard',
                'icon'  => 'bi-speedometer2',
                'url'   => file_exists('dashboard.php') ? 'dashboard.php' : '../dashboard.php'
            ],
            [
                'label' => 'Customer Management',
                'slug'  => 'customer',
                'icon'  => 'bi-people',
                'url'   => file_exists('customer/customer-list.php') ? 'customer/customer-list.php' : '../customer/customer-list.php'
            ],
            [
                'label' => 'Product Management',
                'slug'  => 'product',
                'icon'  => 'bi-box-seam',
                'url'   => file_exists('product/product-list.php') ? 'product/product-list.php' : '../product/product-list.php'
            ],
            [
                'label' => 'Order Management',
                'slug'  => 'order',
                'icon'  => 'bi-receipt',
                'url'   => file_exists('order/order-list.php') ? 'order/order-list.php' : '../order/order-list.php'
            ],
            [
                'label' => 'Messages',
                'slug'  => 'messages',
                'icon'  => 'bi-chat-square-text',
                'url'   => file_exists('messages/inbox.php') ? 'messages/inbox.php' : '../messages/inbox.php'
            ],
            [
                'label' => 'Sub-Admin Management',
                'slug'  => 'sub-admin',
                'icon'  => 'bi-shield-lock',
                'url'   => file_exists('sub-admin/subadmin-list.php') ? 'sub-admin/subadmin-list.php' : '../sub-admin/subadmin-list.php',
                'admin_only' => true
            ],
            [
                'label' => 'Account Settings',
                'slug'  => 'settings',
                'icon'  => 'bi-sliders',
                'url'   => file_exists('account-settings/account-settings.php') ? 'account-settings/account-settings.php' : '../account-settings/account-settings.php'
            ],
        ];
        ?>
        <ul class="nav flex-column gap-1 mb-3">
            <?php foreach ($mobile_links as $link) {
                if (!empty($link['admin_only']) && $admin_role_session !== 'admin') {
                    continue;
                }
                $is_active = (($current_page ?? '') === $link['slug']);
                $active_classes = $is_active ? 'admin-nav-active bg-light text-dark fw-bold border-0 shadow-sm rounded-3' : 'text-secondary fw-medium rounded-3';
            ?>
                <li class="nav-item">
                    <a href="<?php echo htmlspecialchars($link['url']); ?>" class="nav-link d-flex align-items-center gap-3 py-2.5 px-3 transition-all <?php echo $active_classes; ?>">
                        <i class="bi <?php echo htmlspecialchars($link['icon']); ?> fs-5 <?php echo $is_active ? 'text-success' : 'text-muted'; ?>"></i>
                        <span class="small font-poppins"><?php echo htmlspecialchars($link['label']); ?></span>
                    </a>
                </li>
            <?php } ?>
        </ul>

        <hr class="my-3 text-muted opacity-25">

        <div class="d-flex align-items-center mb-3 px-2 gap-2">
            <div class="rounded-circle bg-secondary-subtle d-flex align-items-center justify-content-center text-muted" style="width: 32px; height: 32px;">
                <i class="bi bi-person-fill small"></i>
            </div>
            <span class="fw-medium text-dark small"><?php echo htmlspecialchars($display_role_title); ?></span>
        </div>
        <div class="px-2 pb-2">
            <button type="button" class="btn btn-outline-custom w-100 py-2 rounded-3 fw-semibold small" data-bs-toggle="modal" data-bs-target="#logoutModal">
                Log Out
            </button>
        </div>
    </div>
</nav>

<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg p-3">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold font-poppins text-dark" id="logoutModalLabel">Log out?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="text-muted small mb-0">You will be signed out of the administrator panel.</p>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex flex-column flex-sm-row gap-2 justify-content-sm-end">
                <button type="button" class="btn btn-outline-custom px-4 py-2 rounded-3 small fw-semibold order-2 order-sm-1" data-bs-dismiss="modal">Cancel</button>
                <a href="<?= file_exists('logout.php') ? 'logout.php' : '../logout.php'; ?>" class="btn btn-dark-custom px-4 py-2 rounded-3 small fw-semibold order-1 order-sm-2 text-center">Yes, Log Out</a>
            </div>
        </div>
    </div>
</div>