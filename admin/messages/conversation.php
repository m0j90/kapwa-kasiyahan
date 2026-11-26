<?php
session_start();
$current_page = 'messages';

require_once __DIR__ . '/../../db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

$admin_id = $_SESSION['admin_id'] ?? 1;
$admin_role = $_SESSION['admin_role'] ?? 'admin';
$admin_role_label = ($admin_role === 'admin') ? 'Administrator' : 'Sub-Administrator';
$conversation_id = isset($_GET['id']) ? intval($_GET['id']) : 0; 

if ($conversation_id <= 0) {
    header('Location: inbox.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_admin_reply'])) {
    $reply_message = trim($_POST['reply_message'] ?? '');

    if (empty($reply_message)) {
        $error_message = "Reply message cannot be empty.";
    } else {
        mysqli_query($Connection, "SET time_zone = '+08:00';");

        $insert_reply = mysqli_prepare(
            $Connection,
            "INSERT INTO conversation_messages (conversation_id, sender_type, admin_id, message, admin_read_at, sent_at) VALUES (?, 'admin', ?, ?, NOW(), NOW())"
        );
        if ($insert_reply) {
            mysqli_stmt_bind_param($insert_reply, 'iis', $conversation_id, $admin_id, $reply_message);
            mysqli_stmt_execute($insert_reply);
            mysqli_stmt_close($insert_reply);

            $update_conv = mysqli_prepare(
                $Connection,
                "UPDATE conversations SET last_message_at = NOW(), updated_at = NOW(), status = 'open' WHERE conversation_id = ?"
            );
            mysqli_stmt_bind_param($update_conv, 'i', $conversation_id);
            mysqli_stmt_execute($update_conv);
            mysqli_stmt_close($update_conv);

            header("Location: conversation.php?id={$conversation_id}&sent=1");
            exit();
        } else {
            $error_message = "Database error: " . mysqli_error($Connection);
        }
    }
}

$conv_stmt = mysqli_prepare($Connection, "SELECT * FROM conversations WHERE conversation_id = ?");
mysqli_stmt_bind_param($conv_stmt, 'i', $conversation_id);
mysqli_stmt_execute($conv_stmt);
$conv_result = mysqli_stmt_get_result($conv_stmt);
$conversation = mysqli_fetch_assoc($conv_result);
mysqli_stmt_close($conv_stmt);

if (!$conversation) {
    header('Location: inbox.php');
    exit();
}

$mark_read = mysqli_prepare(
    $Connection,
    "UPDATE conversation_messages SET admin_read_at = NOW() WHERE conversation_id = ? AND sender_type = 'customer' AND admin_read_at IS NULL"
);
mysqli_stmt_bind_param($mark_read, 'i', $conversation_id);
mysqli_stmt_execute($mark_read);
mysqli_stmt_close($mark_read);

$msgs_stmt = mysqli_prepare($Connection, "SELECT * FROM conversation_messages WHERE conversation_id = ? ORDER BY sent_at ASC");
mysqli_stmt_bind_param($msgs_stmt, 'i', $conversation_id);
mysqli_stmt_execute($msgs_stmt);
$msgs_result = mysqli_stmt_get_result($msgs_stmt);
$messages = [];
while ($m = mysqli_fetch_assoc($msgs_result)) {
    $messages[] = $m;
}
mysqli_stmt_close($msgs_stmt);

$last_msg = end($messages);
reset($messages);
$is_awaiting = ($last_msg && $last_msg['sender_type'] === 'customer' && $last_msg['admin_read_at'] === null);
$status_label = $is_awaiting ? 'Awaiting Reply' : 'New Reply';

$reply_sent = isset($_GET['sent']) && $_GET['sent'] === '1';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Conversation</title>
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
                        <h2 class="fw-bold font-poppins text-dark mb-1">Customer Conversation</h2>
                        <p class="text-secondary small mb-0">
                            <?php echo htmlspecialchars($conversation['fullname']); ?> &middot; <?php echo htmlspecialchars($conversation['subject']); ?>
                        </p>
                    </div>
                    <div>
                        <a href="inbox.php" class="btn btn-outline-custom px-4 py-2 rounded-3 fw-semibold small shadow-sm">
                            <i class="bi bi-arrow-left me-1"></i> Back to Inbox
                        </a>
                    </div>
                </div>

                <?php if ($reply_sent) { ?>
                    <div class="alert alert-success-custom alert-dismissible fade show mb-4 d-flex align-items-center gap-2 py-3 px-4 shadow-sm" role="alert">
                        <i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0"></i>
                        <div>
                            <strong>Reply sent.</strong> The customer can read it in their Messages page.
                        </div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php } ?>

                <?php if (!empty($error_message)) { ?>
                    <div class="alert-danger-custom d-flex align-items-center mb-4 rounded-3 py-3 px-4" role="alert">
                        <i class="bi bi-x-circle-fill text-danger me-2 fs-5"></i>
                        <div><?php echo htmlspecialchars($error_message); ?></div>
                    </div>
                <?php } ?>

                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h3 class="fw-bold font-poppins text-dark h4 mb-1"><?php echo htmlspecialchars($conversation['subject']); ?></h3>
                            <p class="text-secondary small mb-0">Conversation with <?php echo htmlspecialchars($conversation['fullname']); ?></p>
                        </div>
                        <div>
                            <span class="badge bg-dark text-white px-3 py-2 rounded-pill fw-medium" style="font-size: 0.8rem;">
                                <?php echo $status_label; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($messages as $msg) { 
                            $is_customer = ($msg['sender_type'] === 'customer');
                        ?>
                            <?php if ($is_customer) { ?>
                                <div class="d-flex flex-column align-items-start align-self-start" style="max-width: 80%;">
                                    <div class="d-flex align-items-center gap-2 mb-1 px-1 small text-muted" style="font-size: 0.8rem;">
                                        <strong class="text-dark"><?php echo htmlspecialchars($conversation['fullname']); ?> (Customer)</strong>
                                        <span>&middot;</span>
                                        <span><?php echo date('M d, g:i A', strtotime($msg['sent_at'])); ?></span>
                                    </div>
                                    <div class="p-3 rounded-4 border bg-light text-dark shadow-xs w-100">
                                        <p class="mb-0 text-dark" style="white-space: pre-line; font-size: 0.95rem;"><?php echo htmlspecialchars($msg['message']); ?></p>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <div class="d-flex flex-column align-items-end align-self-end" style="max-width: 80%;">
                                    <div class="d-flex align-items-center gap-2 mb-1 px-1 small text-muted" style="font-size: 0.8rem;">
                                        <strong class="text-dark">You (<?php echo htmlspecialchars($admin_role_label); ?>)</strong>
                                        <span>&middot;</span>
                                        <span><?php echo date('M d, g:i A', strtotime($msg['sent_at'])); ?></span>
                                    </div>
                                    <div class="p-3 rounded-4 text-white shadow-sm w-100" style="background-color: #0a6e06;">
                                        <p class="mb-0 text-white" style="white-space: pre-line; font-size: 0.95rem;"><?php echo htmlspecialchars($msg['message']); ?></p>
                                    </div>
                                    <div class="mt-1 px-1 small text-success fw-medium" style="font-size: 0.75rem;">
                                        <i class="bi bi-check2-all me-1"></i>Sent
                                    </div>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold font-poppins text-dark h6 mb-3">Reply to <?php echo htmlspecialchars($conversation['fullname']); ?></h5>
                    
                    <form action="conversation.php?id=<?php echo $conversation_id; ?>" method="POST">
                        <div class="mb-3">
                            <textarea name="reply_message" class="form-control form-control-custom" rows="4" placeholder="Write your reply..." required></textarea>
                        </div>
                        
                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <button type="submit" name="send_admin_reply" class="btn btn-dark-custom px-4 py-2 rounded-3 fw-semibold">
                                Send Reply
                            </button>
                            <a href="inbox.php" class="btn btn-outline-secondary bg-white px-4 py-2 rounded-3 fw-semibold text-dark">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>