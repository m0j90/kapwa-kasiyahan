<?php
$activePage = isset($activePage) ? $activePage : '';
$isLoggedIn = isset($_SESSION['customer_id']) || isset($_SESSION['customer_logged_in']);
$customer_id = $_SESSION['customer_id'] ?? 0;

$unread_messages_count = 0;
$unread_conversations = [];

if ($isLoggedIn && $customer_id > 0) {
    require_once __DIR__ . '/../db_connect.php';

    if (isset($Connection)) {
        $unread_query = "
            SELECT c.conversation_id, c.subject, 
                   (SELECT m.message FROM conversation_messages m WHERE m.conversation_id = c.conversation_id ORDER BY m.sent_at DESC LIMIT 1) AS last_message,
                   (SELECT m.sent_at FROM conversation_messages m WHERE m.conversation_id = c.conversation_id ORDER BY m.sent_at DESC LIMIT 1) AS last_message_at
            FROM conversations c
            WHERE c.customer_id = ? 
              AND EXISTS (
                  SELECT 1 FROM conversation_messages m 
                  WHERE m.conversation_id = c.conversation_id 
                    AND m.sender_type = 'admin' 
                    AND m.customer_read_at IS NULL
              )
            ORDER BY c.last_message_at DESC
        ";
        
        $stmt = mysqli_prepare($Connection, $unread_query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $customer_id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $unread_conversations[] = $row;
            }
            mysqli_stmt_close($stmt);
            $unread_messages_count = count($unread_conversations);
        }
    }
}
?>

<nav class="navbar navbar-expand-lg custom-navbar sticky-top">
    <div class="container-fluid px-4">

        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= file_exists('index.php') ? 'index.php' : '../index.php'; ?>">
            <img src="<?= file_exists('assets/images/logos/transparent-combo-mark-logo.png') ? 'assets/images/logos/transparent-combo-mark-logo.png' : '../assets/images/logos/transparent-combo-mark-logo.png'; ?> " alt="Kapwa-Kabataan Kasiyahan" width="160">
        </a>

        <div class="d-flex align-items-center gap-2 d-lg-none">
            <?php if ($isLoggedIn) { ?>
                <a href="<?= file_exists('shopping-checkout/shopping-cart.php') ? 'shopping-checkout/shopping-cart.php' : '../shopping-checkout/shopping-cart.php'; ?>" class="btn btn-outline-custom p-2 d-flex align-items-center justify-content-center <?php echo ($activePage === 'cart') ? 'active' : ''; ?>" style="width: 48px; height: 48px;" aria-label="Shopping Cart">
                    <i class="bi bi-cart fs-4"></i>
                </a>
            <?php } ?>

            <button class="navbar-toggler custom-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
            <ul class="navbar-nav mx-auto mb-lg-0 gap-2 gap-lg-3">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($activePage === 'home') ? 'active' : ''; ?>" href="<?= file_exists('index.php') ? 'index.php' : '../index.php'; ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($activePage === 'about') ? 'active' : ''; ?>" href="<?= file_exists('about.php') ? 'about.php' : '../about.php'; ?>">About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($activePage === 'products') ? 'active' : ''; ?>" href="<?= file_exists('products.php') ? 'products.php' : '../products.php'; ?>">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($activePage === 'contact') ? 'active' : ''; ?>" href="<?= file_exists('contact.php') ? 'contact.php' : '../contact.php'; ?>">Contact Us</a>
                </li>
                <?php if ($isLoggedIn) { ?>
                    <li class="nav-item d-lg-none">
                        <a class="nav-link <?php echo ($activePage === 'messages') ? 'active' : ''; ?> d-flex justify-content-between align-items-center" href="<?= file_exists('messages.php') ? 'messages.php' : '../messages.php'; ?>">
                            <span>Messages</span>
                            <?php if ($unread_messages_count > 0) { ?>
                                <span class="badge rounded-pill bg-dark text-white px-2 py-1" style="font-size: 0.7rem;">
                                    <?php echo $unread_messages_count; ?> new
                                </span>
                            <?php } ?>
                        </a>
                    </li>
                <?php } ?>
            </ul>

            <?php if ($isLoggedIn) { ?>
                <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-2 mt-3 mt-lg-0">
                    <div class="dropdown notification-dropdown position-relative d-none d-lg-inline-block">
                        <div class="btn-group">
                            <a href="<?= file_exists('messages.php') ? 'messages.php' : '../messages.php'; ?>" class="btn btn-outline-custom text-center position-relative px-3 py-2 <?php echo ($activePage === 'messages') ? 'active' : ''; ?>">
                                Messages
                                <?php if ($unread_messages_count > 0) { ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark text-white border border-white" style="font-size: 0.65rem; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; z-index: 2;">
                                        <?php echo $unread_messages_count; ?>
                                    </span>
                                <?php } ?>
                            </a>

                            <?php if ($unread_messages_count > 0) { ?>
                                <button type="button" class="btn btn-outline-custom dropdown-toggle dropdown-toggle-split px-2" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Toggle Messages Dropdown</span>
                                </button>
                                
                                <div class="dropdown-menu dropdown-menu-end shadow-lg border rounded-4 p-3 mt-2" style="width: 340px;">
                                    <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                        <h6 class="fw-bold font-poppins text-dark mb-0 fs-6">New Reply</h6>
                                        <small class="text-muted" style="font-size: 0.75rem;"><?php echo $unread_messages_count; ?> unread</small>
                                    </div>

                                    <div class="notification-preview-list mb-3" style="max-height: 240px; overflow-y: auto;">
                                        <?php $first_unread = $unread_conversations[0]; ?>
                                        <div class="p-2 rounded-3 bg-light border-0">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                                    <i class="bi bi-person-fill"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block" style="font-size: 0.7rem; line-height: 1;">Administrator replied</small>
                                                    <span class="fw-bold text-dark small"><?php echo htmlspecialchars($first_unread['subject']); ?></span>
                                                </div>
                                            </div>
                                            <p class="text-secondary small mb-1 text-truncate" style="font-size: 0.8rem;">
                                                <?php echo htmlspecialchars($first_unread['last_message']); ?>
                                            </p>
                                            <small class="text-muted" style="font-size: 0.68rem;"><?php echo date('M d, g:i A', strtotime($first_unread['last_message_at'])); ?></small>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <a href="<?= file_exists('messages.php') ? 'messages.php' : '../messages.php'; ?>?view=conversation&id=<?php echo $first_unread['conversation_id']; ?>" class="btn btn-dark-custom btn-sm py-2 rounded-3 fw-semibold text-center">
                                            Open Conversation
                                        </a>
                                        <a href="<?= file_exists('messages.php') ? 'messages.php' : '../messages.php'; ?>" class="text-center text-decoration-none small text-success fw-medium mt-1">
                                            View all messages
                                        </a>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <a href="<?= file_exists('shopping-checkout/shopping-cart.php') ? 'shopping-checkout/shopping-cart.php' : '../shopping-checkout/shopping-cart.php'; ?>" class="btn btn-outline-custom text-center d-none d-lg-inline-block <?php echo ($activePage === 'cart') ? 'active' : ''; ?>">Shopping Cart</a>
                    <a href="<?= file_exists('customer-account/account-settings.php') ? 'customer-account/account-settings.php' : '../customer-account/account-settings.php'; ?>" class="btn btn-outline-custom text-center <?php echo ($activePage === 'settings') ? 'active' : ''; ?>">Account Settings</a>

                    <button type="button" class="btn btn-outline-custom text-center" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal">
                        Log Out
                    </button>
                </div>
            <?php } else { ?>
                <div class="d-flex flex-column flex-lg-row gap-2 mt-3 mt-lg-0">
                    <a href="login.php" class="btn btn-outline-custom text-center">Login</a>
                    <a href="signup.php" class="btn btn-dark-custom text-center">Sign Up</a>
                </div>
            <?php } ?>
        </div>
    </div>
</nav>

<?php if ($isLoggedIn) { ?>
<div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg p-3">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold font-poppins text-dark" id="logoutModalLabel">Log out?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="text-muted small mb-0">Are you sure you want to sign out?</p>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex flex-column flex-sm-row gap-2 justify-content-sm-end">
                <button type="button" class="btn btn-outline-custom w-sm-100 px-4 py-2 rounded-3 small fw-semibold order-2 order-sm-1" data-bs-dismiss="modal">Cancel</button>
                <a href="<?= file_exists('logout.php') ? 'logout.php' : '../logout.php'; ?>" class="btn btn-dark-custom w-sm-100 px-4 py-2 rounded-3 small fw-semibold order-1 order-sm-2 text-center">Yes, Log Out</a>
            </div>
        </div>
    </div>
</div>
<?php } ?>