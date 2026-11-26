<?php
$isLoggedIn = isset($_SESSION['customer_id']) || isset($_SESSION['customer_logged_in']);
?>

<footer class="custom-footer">
    <div class="container-fluid px-4 px-lg-5">
        <div class="row g-4 justify-content-between">
            <div class="col-12 col-md-5 col-lg-4">
                <a href="index.php" class="d-flex align-items-center gap-2 mb-3">
                    <img src="<?= file_exists('assets/images/logos/transparent-combo-mark-logo.svg') ? 'assets/images/logos/transparent-combo-mark-logo.svg' : '../assets/images/logos/transparent-combo-mark-logo.svg'; ?>" alt="Kapwa-Kabataan Kasiyahan [Combo Mark Logo Transparent]" width="160">
                </a>
                <p class="footer-description mb-0">
                    An NSTP student-led initiative supporting children's education through community service, outreach, and fundraising.
                </p>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
                <h3 class="footer-heading">Explore</h3>
                <ul class="footer-links">
                    <li><a href="<?= file_exists('index.php') ? 'index.php' : '../index.php'; ?>">Home</a></li>
                    <li><a href="<?= file_exists('about.php') ? 'about.php' : '../about.php'; ?>">About Us</a></li>
                    <li><a href="<?= file_exists('products.php') ? 'products.php' : '../products.php'; ?>">Products</a></li>
                    <li><a href="<?= file_exists('contact.php') ? 'contact.php' : '../contact.php'; ?>">Contact Us</a></li>
                </ul>
            </div>

            <?php if ($isLoggedIn) { ?>
                <div class="col-6 col-md-3 col-lg-2">
                    <h3 class="footer-heading">My Account</h3>
                    <ul class="footer-links">
                        <li><a href="<?= file_exists('customer-account/account-settings.php') ? 'customer-account/account-settings.php' : '../customer-account/account-settings.php'; ?>">Account Settings</a></li>
                        <li><a href="<?= file_exists('customer-account/account-info.php') ? 'customer-account/account-info.php' : '../customer-account/account-info.php'; ?>">Personal Information</a></li>
                        <li><a href="<?= file_exists('customer-account/order-history.php') ? 'customer-account/order-history.php' : '../customer-account/order-history.php'; ?>">Order History</a></li>
                        <li><a href="<?= file_exists('customer-account/track-order.php') ? 'customer-account/track-order.php' : '../customer-account/track-order.php'; ?>">Track Order</a></li>
                        <li class="d-md-none"><a href="<?= file_exists('messages.php') ? 'messages.php' : '../messages.php'; ?>">Messages</a></li>
                    </ul>
                </div>

                <div class="col-6 col-md-3 col-lg-2 d-none d-md-block">
                    <h3 class="footer-heading">Customer Support</h3>
                    <ul class="footer-links">
                        <li><a href="<?= file_exists('contact.php') ? 'contact.php' : '../contact.php'; ?>">Contact Us</a></li>
                        <li><a href="<?= file_exists('messages.php') ? 'messages.php' : '../messages.php'; ?>">Messages</a></li>
                    </ul>
                </div>
            <?php } else { ?>
                <div class="col-6 col-md-3 col-lg-2">
                    <h3 class="footer-heading">Get Started</h3>
                    <ul class="footer-links">
                        <li><a href="login.php">Login</a></li>
                        <li><a href="signup.php">Sign Up</a></li>
                    </ul>
                </div>
            <?php } ?>
        </div>

        <hr class="footer-divider">

        <div class="row">
            <div class="col-12">
                <p class="footer-copyright mb-0 text-center text-md-start">
                    &copy; <?php echo date('Y'); ?> Kapwa-Kabataan Kasiyahan &middot; National Service Training Program
                </p>
            </div>
        </div>
    </div>
</footer>