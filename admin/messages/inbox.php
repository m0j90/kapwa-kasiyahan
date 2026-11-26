<?php
session_start();
$current_page = 'messages';

require_once __DIR__ . '/../../db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

$conversations_query = "
    SELECT c.*, 
           (SELECT m.message FROM conversation_messages m WHERE m.conversation_id = c.conversation_id ORDER BY m.sent_at DESC LIMIT 1) AS last_message,
           (SELECT m.sender_type FROM conversation_messages m WHERE m.conversation_id = c.conversation_id ORDER BY m.sent_at DESC LIMIT 1) AS last_sender,
           (SELECT COUNT(*) FROM conversation_messages m WHERE m.conversation_id = c.conversation_id AND m.sender_type = 'customer' AND m.admin_read_at IS NULL) AS unread_customer_msgs
    FROM conversations c
    ORDER BY c.last_message_at DESC, c.updated_at DESC
";

$result = mysqli_query($Connection, $conversations_query);
$conversations = [];
if ($result) {
    while ($Row = mysqli_fetch_assoc($result)) {
        $conversations[] = $Row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox</title>
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
                    <h1 class="fw-bold font-poppins display-6 text-dark mb-1">Inbox</h1>
                    <p class="text-secondary small mb-0">Messages sent by customers from the website.</p>
                </div>

                <?php if (empty($conversations)) { ?>
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                        <div class="card-body">
                            <i class="bi bi-inbox fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                            <h5 class="fw-bold font-poppins text-dark">No Customer Messages</h5>
                            <p class="text-secondary small mb-0">When customers submit inquiries via the contact form or messages page, they will appear here.</p>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="card border-0 shadow-sm rounded-4 bg-white d-none d-md-block overflow-hidden">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light border-bottom text-secondary" style="font-size: 0.85rem;">
                                    <tr>
                                        <th class="py-3 px-4 fw-semibold">Customer</th>
                                        <th class="py-3 px-3 fw-semibold">Subject</th>
                                        <th class="py-3 px-3 fw-semibold">Last Message</th>
                                        <th class="py-3 px-3 fw-semibold">Date</th>
                                        <th class="py-3 px-3 fw-semibold">Status</th>
                                        <th class="py-3 px-4 fw-semibold text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($conversations as $conv) {
                                        $last_sender = $conv['last_sender'];
                                        $unread_count = $conv['unread_customer_msgs'];

                                        if ($last_sender === 'customer' && $unread_count > 0) {
                                            $status_label = 'Awaiting Reply';
                                            $badge_class = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                        } elseif ($last_sender === 'customer') {
                                            $status_label = 'New Reply';
                                            $badge_class = 'bg-dark text-white'; 
                                        } else {
                                            $status_label = 'Read';
                                            $badge_class = 'bg-light text-secondary border';
                                        }
                                    ?>
                                        <tr class="border-bottom transition-all hover-bg-light">
                                            <td class="py-3 px-4 fw-medium text-dark">
                                                <?php echo htmlspecialchars($conv['fullname']); ?>
                                            </td>
                                            <td class="py-3 px-3 fw-bold text-dark">
                                                <?php echo htmlspecialchars($conv['subject']); ?>
                                            </td>
                                            <td class="py-3 px-3 text-secondary small text-truncate" style="max-width: 260px;">
                                                <?php 
                                                    $prefix = ($last_sender === 'customer') ? 'Customer: ' : 'You: ';
                                                    echo htmlspecialchars($prefix . $conv['last_message']); 
                                                ?>
                                            </td>
                                            <td class="py-3 px-3 text-secondary small text-nowrap">
                                                <?php echo date('M d, Y', strtotime($conv['last_message_at'] ?? $conv['created_at'])); ?>
                                            </td>
                                            <td class="py-3 px-3">
                                                <span class="badge rounded-pill px-3 py-1 fw-medium <?php echo $badge_class; ?>" style="font-size: 0.75rem;">
                                                    <?php echo $status_label; ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-end">
                                                <a href="conversation.php?id=<?php echo $conv['conversation_id']; ?>" class="btn btn-sm btn-outline-custom px-3 py-1">
                                                    Open
                                                </a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-md-none d-flex flex-column gap-3">
                        <?php foreach ($conversations as $conv) { 
                            $last_sender = $conv['last_sender'];
                            $unread_count = $conv['unread_customer_msgs'];

                            if ($last_sender === 'customer' && $unread_count > 0) {
                                $status_label = 'Awaiting Reply';
                                $badge_class = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                            } elseif ($last_sender === 'customer') {
                                $status_label = 'New Reply';
                                $badge_class = 'bg-dark text-white';
                            } else {
                                $status_label = 'Read';
                                $badge_class = 'bg-light text-secondary border';
                            }
                        ?>
                            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="fw-bold font-poppins text-dark fs-6 mb-0 pe-2">
                                        <?php echo htmlspecialchars($conv['subject']); ?>
                                    </h5>
                                    <span class="badge rounded-pill px-3 py-1 fw-medium <?php echo $badge_class; ?>" style="font-size: 0.75rem;">
                                        <?php echo $status_label; ?>
                                    </span>
                                </div>

                                <div class="mb-2 small">
                                    <div class="text-secondary mb-1 d-flex justify-content-between">
                                        <span>Customer</span>
                                        <span class="fw-medium text-dark"><?php echo htmlspecialchars($conv['fullname']); ?></span>
                                    </div>
                                    <div class="text-secondary mb-1 d-flex justify-content-between">
                                        <span>Date</span>
                                        <span class="fw-medium text-dark"><?php echo date('M d, Y', strtotime($conv['last_message_at'] ?? $conv['created_at'])); ?></span>
                                    </div>
                                </div>

                                <div class="border-top pt-2 mb-3">
                                    <span class="text-muted d-block small mb-1" style="font-size: 0.75rem;">Last Message</span>
                                    <p class="small text-secondary mb-0 text-truncate">
                                        <?php 
                                            $prefix = ($last_sender === 'customer') ? 'Customer: ' : 'You: ';
                                            echo htmlspecialchars($prefix . $conv['last_message']); 
                                        ?>
                                    </p>
                                </div>

                                <div class="text-end">
                                    <a href="conversation.php?id=<?php echo $conv['conversation_id']; ?>" class="btn btn-sm btn-outline-custom w-100 py-2">
                                        Open
                                    </a>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>