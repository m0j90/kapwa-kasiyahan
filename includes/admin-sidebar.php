<?php
$current_page = $current_page ?? '';
$admin_role = $_SESSION['admin_role'] ?? 'admin';

$sidebar_links = [
    [
        'label'         => 'Dashboard',
        'slug'          => 'dashboard',
        'icon'          => 'bi-speedometer2',
        'url'           => file_exists('dashboard.php') ? 'dashboard.php' : '../dashboard.php'
    ],
    [
        'label'         => 'Customer Management',
        'slug'          => 'customer',
        'icon'          => 'bi-people',
        'url'           => file_exists('customer/customer-list.php') ? 'customer/customer-list.php' : '../customer/customer-list.php'
    ],
    [
        'label'         => 'Product Management',
        'slug'          => 'product',
        'icon'          => 'bi-box-seam',
        'url'           => file_exists('product/product-list.php') ? 'product/product-list.php' : '../product/product-list.php'
    ],
    [
        'label'         => 'Order Management',
        'slug'          => 'order',
        'icon'          => 'bi-receipt',
        'url'           => file_exists('order/order-list.php') ? 'order/order-list.php' : '../order/order-list.php'
    ],
    [
        'label'         => 'Messages',
        'slug'          => 'messages',
        'icon'          => 'bi-chat-square-text',
        'url'           => file_exists('messages/inbox.php') ? 'messages/inbox.php' : '../messages/inbox.php'
    ],
    [
        'label'         => 'Sub-Admin Management',
        'slug'          => 'sub-admin',
        'icon'          => 'bi-shield-lock',
        'url'           => file_exists('sub-admin/subadmin-list.php') ? 'sub-admin/subadmin-list.php' : '../sub-admin/subadmin-list.php',
        'admin_only'    => true
    ],
    [
        'label'         => 'Account Settings',
        'slug'          => 'settings',
        'icon'          => 'bi-sliders',
        'url'           => file_exists('account-settings/account-settings.php') ? 'account-settings/account-settings.php' : '../account-settings/account-settings.php'
    ],
];
?>

<div class="admin-sidebar bg-white border-end h-100 p-3 shadow-sm d-none d-lg-block" style="position: fixed !important; top: 85px !important; left: 0; bottom: 0; width: 260px; z-index: 1020; overflow-y: auto;">
    <ul class="nav flex-column gap-1">
        <?php foreach ($sidebar_links as $link) {
            if (!empty($link['admin_only']) && $admin_role !== 'admin') {
                continue;
            }
            $is_active = ($current_page === $link['slug']);
            $active_classes = $is_active ? 'admin-nav-active bg-light text-dark fw-bold border-0 shadow-sm rounded-3' : 'text-secondary fw-medium rounded-3';
        ?>
            <li class="nav-item">
                <a href="<?php echo htmlspecialchars($link['url']); ?>"
                    class="nav-link d-flex align-items-center gap-3 py-2.5 px-3 transition-all <?php echo $active_classes; ?>">
                    <i class="bi <?php echo htmlspecialchars($link['icon']); ?> fs-5 <?php echo $is_active ? 'text-success' : 'text-muted'; ?>"></i>
                    <span class="small font-poppins"><?php echo htmlspecialchars($link['label']); ?></span>
                </a>
            </li>
        <?php } ?>
    </ul>
</div>