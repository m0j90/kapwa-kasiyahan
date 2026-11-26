<?php
session_start();

if (!isset($_SESSION['customer_id']) && !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

include 'db_connect.php';

$customer_id = $_SESSION['customer_id'] ?? 0;
$username = $_SESSION['username'] ?? '';

$customer_fullname = $username;
$customer_email = '';
$cust_stmt = mysqli_prepare($Connection, "SELECT first_name, last_name, email FROM customers WHERE customer_id = ?");
if ($cust_stmt) {
    mysqli_stmt_bind_param($cust_stmt, 'i', $customer_id);
    mysqli_stmt_execute($cust_stmt);
    $cust_res = mysqli_stmt_get_result($cust_stmt);
    if ($Row = mysqli_fetch_assoc($cust_res)) {
        $customer_fullname = !empty($Row['first_name' . 'last_name']) ? $Row['first_name' . 'last_name'] : $username;
        $customer_email = $Row['email'] ?? '';
    }
    mysqli_stmt_close($cust_stmt);
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_conversation'])) {
    $subject = trim($_POST['subject'] ?? '');
    $message_body = trim($_POST['message'] ?? '');

    if (empty($subject) || empty($message_body)) {
        $error_message = "Subject and message content cannot be empty.";
    } else {
        mysqli_query($Connection, "SET time_zone = '+08:00';");

        $conv_sql = "INSERT INTO conversations (customer_id, fullname, email, subject, status, last_message_at, created_at, updated_at) VALUES (?, ?, ?, ?, 'open', NOW(), NOW(), NOW())";
        $conv_stmt = mysqli_prepare($Connection, $conv_sql);

        if ($conv_stmt) {
            mysqli_stmt_bind_param($conv_stmt, 'isss', $customer_id, $customer_fullname, $customer_email, $subject);
            mysqli_stmt_execute($conv_stmt);
            $conversation_id = mysqli_insert_id($Connection);
            mysqli_stmt_close($conv_stmt);

            $msg_sql = "INSERT INTO conversation_messages (conversation_id, sender_type, customer_id, message, customer_read_at, sent_at) VALUES (?, 'customer', ?, ?, NOW(), NOW())";
            $msg_stmt = mysqli_prepare($Connection, $msg_sql);
            if ($msg_stmt) {
                mysqli_stmt_bind_param($msg_stmt, 'iis', $conversation_id, $customer_id, $message_body);
                mysqli_stmt_execute($msg_stmt);
                mysqli_stmt_close($msg_stmt);
            }

            header("Location: messages.php?view=conversation&id={$conversation_id}&sent=1");
            exit();
        } else {
            $error_message = "Database error: " . mysqli_error($Connection);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    $reply_body = trim($_POST['reply_message'] ?? '');

    if ($conversation_id > 0 && !empty($reply_body)) {
        $check_stmt = mysqli_prepare($Connection, "SELECT conversation_id FROM conversations WHERE conversation_id = ? AND customer_id = ?");
        mysqli_stmt_bind_param($check_stmt, 'ii', $conversation_id, $customer_id);
        mysqli_stmt_execute($check_stmt);
        $check_res = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_res) > 0) {
            mysqli_stmt_close($check_stmt);

            mysqli_query($Connection, "SET time_zone = '+08:00';");

            $reply_sql = "INSERT INTO conversation_messages (conversation_id, sender_type, customer_id, message, customer_read_at, sent_at) VALUES (?, 'customer', ?, ?, NOW(), NOW())";
            $reply_stmt = mysqli_prepare($Connection, $reply_sql);
            mysqli_stmt_bind_param($reply_stmt, 'iis', $conversation_id, $customer_id, $reply_body);
            mysqli_stmt_execute($reply_stmt);
            mysqli_stmt_close($reply_stmt);

            $upd_stmt = mysqli_prepare($Connection, "UPDATE conversations SET last_message_at = NOW(), updated_at = NOW(), status = 'open' WHERE conversation_id = ?");
            mysqli_stmt_bind_param($upd_stmt, 'i', $conversation_id);
            mysqli_stmt_execute($upd_stmt);
            mysqli_stmt_close($upd_stmt);

            header("Location: messages.php?view=conversation&id={$conversation_id}&sent=1");
            exit();
        }
    }
    $error_message = "Failed to send reply. Please try again.";
}

$conversations_list = [];
$list_stmt = mysqli_prepare($Connection, "
    SELECT c.*, 
           (SELECT m.message FROM conversation_messages m WHERE m.conversation_id = c.conversation_id ORDER BY m.sent_at DESC LIMIT 1) AS last_message,
           (SELECT COUNT(*) FROM conversation_messages m WHERE m.conversation_id = c.conversation_id AND m.sender_type = 'admin' AND m.customer_read_at IS NULL) AS unread_count
    FROM conversations c
    WHERE c.customer_id = ?
    ORDER BY c.last_message_at DESC, c.updated_at DESC
");
if ($list_stmt) {
    mysqli_stmt_bind_param($list_stmt, 'i', $customer_id);
    mysqli_stmt_execute($list_stmt);
    $res = mysqli_stmt_get_result($list_stmt);
    while ($Row = mysqli_fetch_assoc($res)) {
        $conversations_list[] = $Row;
    }
    mysqli_stmt_close($list_stmt);
}

$view = $_GET['view'] ?? 'inbox';
$conversation_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$active_conversation = null;
$conversation_messages = [];

if ($conversation_id) {
    $conv_fetch = mysqli_prepare($Connection, "SELECT * FROM conversations WHERE conversation_id = ? AND customer_id = ?");
    mysqli_stmt_bind_param($conv_fetch, 'ii', $conversation_id, $customer_id);
    mysqli_stmt_execute($conv_fetch);
    $conv_res = mysqli_stmt_get_result($conv_fetch);
    $active_conversation = mysqli_fetch_assoc($conv_res);
    mysqli_stmt_close($conv_fetch);

    if ($active_conversation) {
        $read_stmt = mysqli_prepare($Connection, "UPDATE conversation_messages SET customer_read_at = NOW() WHERE conversation_id = ? AND sender_type = 'admin' AND customer_read_at IS NULL");
        mysqli_stmt_bind_param($read_stmt, 'i', $conversation_id);
        mysqli_stmt_execute($read_stmt);
        mysqli_stmt_close($read_stmt);

        $msg_fetch = mysqli_prepare($Connection, "SELECT * FROM conversation_messages WHERE conversation_id = ? ORDER BY sent_at ASC");
        mysqli_stmt_bind_param($msg_fetch, 'i', $conversation_id);
        mysqli_stmt_execute($msg_fetch);
        $msg_res = mysqli_stmt_get_result($msg_fetch);
        while ($m_row = mysqli_fetch_assoc($msg_res)) {
            $conversation_messages[] = $m_row;
        }
        mysqli_stmt_close($msg_fetch);
    }
} elseif (!empty($conversations_list) && $view === 'conversation') {
    header("Location: messages.php?view=conversation&id=" . $conversations_list[0]['conversation_id']);
    exit();
}

$sent_success = isset($_GET['sent']) && $_GET['sent'] === '1';
$tab = $_GET['tab'] ?? 'all';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="assets/images/logos/transparent-symbol-logo.png">
</head>
<body class="bg-light text-dark d-flex flex-column min-vh-100">

    <?php
        $activePage = 'messages'; 
        include 'includes/navbar.php';
    ?>

    <header class="py-5 bg-white border-bottom">
        <div class="container px-4 px-lg-5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-success">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Messages</li>
                </ol>
            </nav>
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <div>
                    <h1 class="fw-bold display-6 font-poppins text-dark">Messages</h1>
                    <p class="text-secondary small mb-0">Two-way conversation inbox with store administrators.</p>
                </div>
                <div>
                    <button type="button" class="btn btn-dark-custom px-3 py-2 text-nowrap d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                        <i class="bi bi-pencil-square"></i>
                        <span>New Message</span>
                    </button>
                </div>
            </div>
        </div>    
    </header>

    <main class="py-5 flex-grow-1">
        <div class="container px-4 px-lg-5">
            <?php if ($sent_success) { ?>
                <div class="alert alert-success-custom alert-dismissible fade show mb-4 d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0"></i>
                    <div>
                        <strong>Success!</strong> Your message has been sent successfully.
                    </div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php } ?>

            <?php if (!empty($error_message)) { ?>
                <div class="alert-danger-custom d-flex align-items-center mb-4 rounded-3 py-3" role="alert">
                    <i class="bi bi-x-circle-fill text-danger me-2 fs-5"></i>
                    <div><?php echo htmlspecialchars($error_message); ?></div>
                </div>
            <?php } ?>

            <div class="row g-4">
                <div class="col-lg-4 <?php echo ($view === 'conversation' && $active_conversation) ? 'd-none d-lg-block' : 'd-block'; ?>">
                    <div class="card border rounded-3 shadow-sm bg-white overflow-hidden">
                        <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold font-poppins text-dark mb-0 fs-6">Inbox</h5>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="messages.php?view=inbox&tab=all" class="btn btn-outline-secondary <?php echo ($tab === 'all') ? 'active bg-dark text-white' : ''; ?>">All</a>
                                <a href="messages.php?view=inbox&tab=unread" class="btn btn-outline-secondary <?php echo ($tab === 'unread') ? 'active bg-dark text-white' : ''; ?>">Unread</a>
                            </div>
                        </div>

                        <div class="list-group list-group-flush" style="max-height: 520px; overflow-y: auto;">
                            <?php if (empty($conversations_list)) { ?>
                                <div class="p-5 text-center text-muted">
                                    <i class="bi bi-inbox fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                                    <h6 class="fw-semibold text-dark mb-1">No Conversations Yet</h6>
                                    <p class="small mb-3">You haven't started any message threads.</p>
                                    <button type="button" class="btn btn-sm btn-dark-custom" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                                        Send Message
                                    </button>
                                </div>
                            <?php } else { 
                                foreach ($conversations_list as $conv) { 
                                    $hasUnread = ($conv['unread_count'] > 0);
                                    if ($tab === 'unread' && !$hasUnread) continue;
                                    $isActive = ($view === 'conversation' && $conversation_id == $conv['conversation_id']);
                            ?>
                                <a href="messages.php?view=conversation&id=<?php echo $conv['conversation_id']; ?>" class="list-group-item list-group-item-action p-3 transition-all border-bottom <?php echo $isActive ? 'bg-light border-start border-4 border-success' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="text-dark mb-0 text-truncate <?php echo $hasUnread ? 'fw-bold' : 'fw-semibold'; ?>" style="max-width: 70%;">
                                            <?php echo htmlspecialchars($conv['subject']); ?>
                                        </h6>
                                        <small class="text-muted fs-7"><?php echo date('M d, g:i A', strtotime($conv['last_message_at'] ?? $conv['created_at'])); ?></small>
                                    </div>
                                    
                                    <p class="small text-secondary mb-2 text-truncate" style="font-size: 0.825rem;">
                                        <?php echo htmlspecialchars($conv['last_message'] ?? 'No messages yet'); ?>
                                    </p>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <?php if ($hasUnread) { ?>
                                            <span class="badge bg-success rounded-pill fw-medium" style="font-size: 0.7rem;"><i class="bi bi-dot"></i> <?php echo $conv['unread_count']; ?> New Reply</span>
                                        <?php } else { ?>
                                            <span class="badge bg-light text-muted border rounded-pill fw-normal" style="font-size: 0.7rem;"><?php echo ucfirst($conv['status']); ?></span>
                                        <?php } ?>

                                        <?php if ($hasUnread) { ?>
                                            <span class="bg-success rounded-circle" style="width: 8px; height: 8px;" title="Unread"></span>
                                        <?php } ?>
                                    </div>
                                </a>
                            <?php
                                }
                            } 
                            ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8 <?php echo ($view !== 'conversation' || !$active_conversation) ? 'd-none d-lg-block' : 'd-block'; ?>">
                    <div class="card border rounded-3 shadow-sm bg-white overflow-hidden d-flex flex-column" style="min-height: 520px;">
                        <?php if ($active_conversation) { ?>
                            <div class="card-header bg-white p-3 border-bottom d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <a href="messages.php?view=inbox" class="btn btn-sm btn-outline-secondary d-lg-none py-1 px-2 me-1" aria-label="Back to Inbox">
                                        <i class="bi bi-arrow-left"></i>
                                    </a>
                                    <div>
                                        <h5 class="fw-bold font-poppins text-dark mb-0 fs-6"><?php echo htmlspecialchars($active_conversation['subject']); ?></h5>
                                        <span class="small text-muted" style="font-size: 0.75rem;">Thread ID: #<?php echo $active_conversation['conversation_id']; ?> &middot; Status: <?php echo ucfirst($active_conversation['status']); ?></span>
                                    </div>
                                </div>
                                <div>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><?php echo ucfirst($active_conversation['status']); ?> Thread</span>
                                </div>
                            </div>

                            <div class="card-body p-4 flex-grow-1 overflow-y-auto bg-light" style="max-height: 400px;">
                                <div class="d-flex flex-column gap-3">
                                    <?php foreach ($conversation_messages as $msg) { 
                                        $isCustomer = ($msg['sender_type'] === 'customer');
                                    ?>
                                        <div class="d-flex flex-column <?php echo $isCustomer ? 'align-items-end' : 'align-items-start'; ?>">
                                            <div class="small text-muted mb-1 px-1" style="font-size: 0.75rem;">
                                                <strong><?php echo $isCustomer ? 'You (' . htmlspecialchars($customer_fullname) . ')' : 'Admin Support'; ?></strong> &middot; <?php echo date('M d, g:i A', strtotime($msg['sent_at'])); ?>
                                            </div>
                                            
                                            <div class="p-3 rounded-3 shadow-sm <?php echo $isCustomer ? 'bg-success text-white' : 'bg-white text-dark border'; ?>" style="max-width: 80%; line-height: 1.5; font-size: 0.925rem;">
                                                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                            </div>

                                            <div class="small text-muted mt-1 px-1" style="font-size: 0.7rem;">
                                                <?php if ($isCustomer) { ?>
                                                    <i class="bi bi-check2-all text-success"></i> Sent
                                                <?php } else { ?>
                                                    <span class="text-secondary"><?php echo $msg['customer_read_at'] ? 'Read' : 'Delivered'; ?></span>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>

                            <div class="card-footer bg-white p-3 border-top">
                                <form action="messages.php?view=conversation&id=<?php echo $active_conversation['conversation_id']; ?>" method="POST">
                                    <input type="hidden" name="conversation_id" value="<?php echo $active_conversation['conversation_id']; ?>">
                                    <div class="mb-2">
                                        <textarea name="reply_message" class="form-control form-control-custom" rows="3" placeholder="Type your reply here..." required></textarea>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small text-muted" style="font-size: 0.75rem;">
                                            <i class="bi bi-info-circle me-1"></i>Two-way conversation active.
                                        </span>
                                        <button type="submit" name="send_reply" class="btn btn-dark-custom px-4 py-2 small fw-semibold">
                                            <i class="bi bi-send me-1"></i> Send Reply
                                        </button>
                                    </div>
                                </form>
                            </div>

                        <?php } else { ?>
                            <div class="card-body p-5 d-flex flex-column align-items-center justify-content-center text-center text-muted flex-grow-1">
                                <i class="bi bi-chat-square-text fs-1 text-secondary opacity-50 mb-3"></i>
                                <h6 class="fw-semibold text-dark mb-1">Select a Conversation</h6>
                                <p class="small mb-3" style="max-width: 320px;">Click on any message thread from your inbox to view chat history or send a reply.</p>
                                <button type="button" class="btn btn-outline-custom btn-sm" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                                    Start New Thread
                                </button>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php' ?>

    <div class="modal fade" id="newMessageModal" tabindex="-1" aria-labelledby="newMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
            <div class="modal-content border-0 rounded-4 shadow p-3">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold font-poppins text-dark" id="newMessageModalLabel">New Message to Support</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="messages.php" method="POST">
                    <div class="modal-body py-3">
                        <div class="mb-3">
                            <label for="subject" class="form-label-custom">Subject / Topic</label>
                            <select class="form-select form-control-custom" id="subject" name="subject" required>
                                <option value="" disabled <?php echo empty($subject) ? 'selected' : ''; ?>>Select a topic</option>
                                <?php
                                $subject_options = [
                                    "Order inquiry",
                                    "Donations & Outreach",
                                    "General Question"
                                ];

                                foreach ($subject_options as $option) { 
                                    $is_selected = (isset($subject) && $subject === $option) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $is_selected; ?>>
                                        <?php echo htmlspecialchars($option); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="message" class="form-label-custom">Initial Message</label>
                            <textarea class="form-control form-control-custom" id="message" name="message" rows="4" placeholder="Describe your inquiry or concern here..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 d-flex flex-column flex-sm-row gap-2 justify-content-sm-end">
                        <button type="button" class="btn btn-outline-custom w-sm-100 px-4 py-2 rounded-3 small fw-semibold order-2 order-sm-1" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="start_conversation" class="btn btn-dark-custom w-sm-100 px-4 py-2 rounded-3 small fw-semibold order-1 order-sm-2">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>