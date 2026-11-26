<?php 
session_start();

date_default_timezone_set('Asia/Manila');

include 'db_connect.php';

$error_message = '';
$success_message = '';
$form_data = [
    'fullname'  => '',
    'email'     => '', 
    'subject'   => '',
    'message'   => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['fullname'] = trim($_POST['fullname'] ?? '');
    $form_data['email'] = trim($_POST['email'] ?? '');
    $form_data['subject'] = trim($_POST['subject'] ?? '');
    $form_data['message'] = trim($_POST['message'] ?? '');

    $has_error = false;

    if (empty($form_data['fullname'])) {
        $has_error = true;
    }
    if (empty($form_data['email']) || !filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $has_error = true;
    }
    if (empty($form_data['subject'])) {
        $has_error = true;
    }
    if (empty($form_data['message'])) {
        $has_error = true;
    }

    if ($has_error) {
        $error_message = "Your message was not sent. Please fix the highlighted fields.";
    } else {
        mysqli_query($Connection, "SET time_zone = '+08:00';");

        $customer_id = isset($_SESSION['customer_id']) ? intval($_SESSION['customer_id']) : NULL;

        $conv_stmt = mysqli_prepare(
            $Connection,
            "INSERT INTO conversations (customer_id, fullname, email, subject, status, last_message_at, created_at, updated_at) VALUES (?, ?, ?, ?, 'open', NOW(), NOW(), NOW())"
        );
        mysqli_stmt_bind_param(
            $conv_stmt,
            'isss',
            $customer_id,
            $form_data['fullname'],
            $form_data['email'],
            $form_data['subject']
        );
        mysqli_stmt_execute($conv_stmt);
        $conversation_id = mysqli_insert_id($Connection);
        mysqli_stmt_close($conv_stmt);

        $msg_stmt = mysqli_prepare(
            $Connection,
            "INSERT INTO conversation_messages (conversation_id, sender_type, customer_id, message, sent_at) VALUES (?, 'customer', ?, ?, NOW())"
        );
        mysqli_stmt_bind_param(
            $msg_stmt,
            'iis',
            $conversation_id,
            $customer_id,
            $form_data['message']
        );
        mysqli_stmt_execute($msg_stmt);
        mysqli_stmt_close($msg_stmt);

        $success_message = "Message sent! You can follow the conversation in Messages.";
        $form_data = ['fullname' => '', 'email' => '', 'subject' => '', 'message' => ''];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="assets/images/logos/transparent-symbol-logo.png">
</head>
<body class="bg-light text-dark">

    <?php 
        $activePage = 'contact';
        include 'includes/navbar.php';
    ?>

    <header class="py-5 bg-white border-bottom">
        <div class="container px-4 px-lg-5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-success">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Contact Us</li>
                </ol>
            </nav>
            <h1 class="fw-bold display-5 font-poppins text-dark">Contact Us</h1>
            <p class="text-muted mb-0">Send us a question or concern and we will reply in your inbox.</p>
        </div>
    </header>

    <main class="py-5">
        <div class="container px-4 px-lg-5">
            <div class="row g-5">
                <div class="col-lg-5">
                    <h2 class="fw-bold font-poppins h3 mb-4">Get in touch</h2>

                    <div class="d-flex align-items-start mb-4">
                        <div class="p-3 bg-white border rounded-3 me-3 shadow-sm text-success">
                            <i class="bi bi-geo-alt fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-semibold font-poppins mb-1 fs-6">Address</h5>
                            <p class="text-muted small mb-0">Redemptorist Road, Baclaran, Parañaque City 1700, Metro Manila, Philippines</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-4">
                        <div class="p-3 bg-white border rounded-3 me-3 shadow-sm text-success">
                            <i class="bi bi-envelope fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-semibold font-poppins mb-1 fs-6">Email</h5>
                            <p class="text-muted small mb-0">baclaranrector@yahoo.com</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-4">
                        <div class="p-3 bg-white border rounded-3 me-3 shadow-sm text-success">
                            <i class="bi bi-telephone fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-semibold font-poppins mb-1 fs-6">Telephone</h5>
                            <p class="text-muted small mb-0">(02) 8832-1150 / 8832-1151 / 8833-5016</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-4">
                        <div class="p-3 bg-white border rounded-3 me-3 shadow-sm text-success">
                            <i class="bi bi-clock fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-semibold font-poppins mb-1 fs-6">Office hours</h5>
                            <p class="text-muted small mb-0">Daily · 24 Hours</p>
                        </div>
                    </div>

                    <div class="ratio ratio-4x3 bg-secondary-subtle rounded-4 overflow-hidden shadow-sm">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3862.1827181106787!2d120.9922954751048!3d14.531538785946713!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397c9515d3b485b%3A0xe28d786a0ffecaf8!2sBaclaran%20Church!5e0!3m2!1sen!2sph!4v1793237202988!5m2!1sen!2sph" class="w-100 h-100" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                        <h2 class="fw-bold font-poppins h3 mb-4">Send us a message</h2>

                        <?php if (!empty($error_message)) { ?>
                            <div class="alert-danger-custom d-flex align-items-center mb-4 rounded-3 py-3" role="alert">
                                <i class="bi bi-x-circle-fill text-danger me-2 fs-5"></i>
                                <div><?php echo htmlspecialchars($error_message); ?></div>
                            </div>
                        <?php } ?>

                        <?php if (!empty($success_message)) { ?>
                            <div class="alert alert-success-custom d-flex align-items-center mb-4 rounded-3 py-3" role="alert">
                                <i class="bi bi-check-circle-fill text-success me-2 fs-5"></i>
                                <div><?php echo htmlspecialchars($success_message); ?></div>
                            </div>
                        <?php } ?>

                        <form action="contact.php" method="POST" novalidate>
                            <div class="mb-3">
                                <label for="fullname" class="form-label fw-medium small">Full Name</label>
                                <input type="text"
                                    class="form-control py-2 <?php echo ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($form_data['fullname']) && !empty($error_message)) ? 'border-danger' : ''; ?>"
                                    id="fullname"
                                    name="fullname"
                                    placeholder="Your full name"
                                    value="<?php echo htmlspecialchars($form_data['fullname']); ?>">
                                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($form_data['fullname']) && !empty($error_message)) { ?>
                                    <div class="input-error-text">(!) Full name is required</div>
                                <?php } ?>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-medium small">Email Address</label>
                                <input type="email"
                                    class="form-control py-2 <?php echo ($_SERVER['REQUEST_METHOD'] === 'POST' && (empty($form_data['email']) || !filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) && !empty($error_message)) ? 'border-danger' : ''; ?>"
                                    id="email"
                                    name="email"
                                    placeholder="name@example.com"
                                    value="<?php echo htmlspecialchars($form_data['email']); ?>">
                                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && (empty($form_data['email']) || !filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) && !empty($error_message)) { ?>
                                    <div class="input-error-text">(!) Enter a valid email address</div>
                                <?php } ?>
                            </div>

                            <div class="mb-3">
                                <label for="subject" class="form-label fw-medium small">Subject</label>
                                <select class="form-select py-2 <?php echo ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($form_data['subject']) && !empty($error_message)) ? 'border-danger' : ''; ?>"
                                    id="subject"
                                    name="subject">
                                <option value="" disabled <?php echo empty($form_data['subject']) ? 'selected' : ''; ?>>Select a topic</option>

                                <?php
                                $subject_options = [
                                    "Order inquiry",
                                    "Donations & Outreach",
                                    "General Question"
                                ];

                                foreach ($subject_options as $option) { 
                                    $is_selected = ($form_data['subject'] === $option) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $is_selected; ?>>
                                        <?php echo htmlspecialchars($option); ?>
                                    </option>
                                <?php } ?>
                                </select>
                                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($form_data['subject']) && !empty($error_message)) { ?>
                                    <div class="input-error-text">(!) Please choose a subject</div>
                                <?php } ?>
                            </div>

                            <div class="mb-4">
                                <label for="message" class="form-label fw-medium small">Message</label>
                                <textarea class="form-control <?php echo ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($form_data['message']) && !empty($error_message)) ? 'border-danger' : ''; ?>" 
                                          id="message" 
                                          name="message" 
                                          rows="5" 
                                          placeholder="Write your message here..."><?php echo htmlspecialchars($form_data['message']); ?></textarea>
                                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($form_data['message']) && !empty($error_message)) { ?>
                                    <div class="input-error-text">(!) Message cannot be empty</div>
                                <?php } ?>
                            </div>

                            <button type="submit" class="btn btn-primary-custom fw-semibold rounded-3 mb-3">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php' ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>