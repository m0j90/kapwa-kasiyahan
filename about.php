<?php
session_start();

$team_members = [
    [
        'role'  => 'Representative',
        'desc'  => 'Coordinates the team and represents the group in project-related matters.',
        'image' => 'assets/images/team_members/representative.jpeg'
    ],
    [
        'role'  => 'Assistant Representative',
        'desc'  => 'Assists the representative with coordination and team responsibilities.',
        'image' => 'assets/images/team_members/assistant-rep.jpeg'
    ],
    [
        'role'  => 'Secretary',
        'desc'  => 'Helps maintain meeting notes, records, and project documentation.',
        'image' => 'assets/images/team_members/secretary.jpeg'
    ],
    [
        'role'  => 'Creative Head',
        'desc'  => 'Helps prepare creative materials and visual presentations for the project.',
        'image' => 'assets/images/team_members/creative.jpeg'
    ],
    [
        'role'  => 'Documentation Head',
        'desc'  => 'Oversees the documentation of project activities and records.',
        'image' => 'assets/images/team_members/docu-head.jpeg'
    ],
    [
        'role'  => 'Assistant Documentation',
        'desc'  => 'Assists with recording and organizing project documentation.',
        'image' => 'assets/images/team_members/docu-asst.jpeg'
    ],
    [
        'role'  => 'Finance Head',
        'desc'  => 'Helps oversee financial records and the tracking of fundraising resources.',
        'image' => 'assets/images/team_members/finance-head.jpeg'
    ],
    [
        'role'  => 'Assistant Finance',
        'desc'  => 'Assists with financial records and fundraising-related tasks.',
        'image' => 'assets/images/team_members/finance-asst.jpeg'
    ],
    [
        'role'  => 'Logistics Head',
        'desc'  => 'Helps coordinate the materials, supplies, and arrangements needed for project activities.',
        'image' => 'assets/images/team_members/logistics-head.jpeg'
    ],
    [
        'role'  => 'Assistant Logistics',
        'desc'  => 'Assists with preparing and organizing the materials and logistical requirements.',
        'image' => 'assets/images/team_members/logistics-asst.jpeg'
    ]
]
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
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
        $activePage = 'about';
        include 'includes/navbar.php';
    ?>

    <header class="py-5 bg-white border-bottom">
        <div class="container px-4 px-lg-5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-success">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">About Us</li>
                </ol>
            </nav>
            <h1 class="fw-bold display-5 font-poppins text-dark">About Us</h1>
            <p class="lead text-muted mb-0">Learn about the National Service Training Program and why this project exists.</p>
        </div>
    </header>

    <main class="py-5">
        <div class="container px-4 px-lg-5">
            <section class="row align-items-center g-5 mb-5 pb-5 border-bottom">
                <div class="col-lg-6">
                    <div class="ratio ratio-16x9 bg-secondary-subtle rounded-4 overflow-hidden shadow-sm">
                        <img src="assets/images/pictures/about-project.jpeg" alt="Kapwa-Kabataan Kasiyahan Outreach Activity" class="object-fit-cover">
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <h2 class="fw-bold font-poppins mb-3 display-6">Who We Are</h2>
                    <p class="text-secondary mb-3">
                        Kapwa-Kabataan Kasiyahan is a community service project organized by NSTP students to bring happiness and support to children through educational assistance, fundraising, donations, and community activities.
                    </p>
                    <p class="text-secondary mb-4">
                        The project aims to help children continue their learning by providing basic school needs and learning materials. It also create opportunities for students and community members to work together in promoting learning, creativity, and social interaction.
                    </p>
                    <a href="products.php" class="btn btn-primary px-4 py-2 fw-semibold">
                        Browse Products
                    </a>
                </div>
            </section>

            <section class="mb-5 pb-5 border-bottom">
                <div class="text-center mb-5">
                    <h2 class="fw-bold font-poppins display-6">Mission, Vision and Program</h2>
                    <p class="text-muted">The core values and goals driving our community initiative.</p>
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm p-4">
                            <div class="card-body px-0">
                                <div class="badge bg-success-subtle text-success p-3 rounded-3 mb-3">
                                    <i class="bi bi-bullseye fs-4"></i>
                                </div>
                                <h3 class="h4 fw-bold font-poppins mb-3">Our Mission</h3>
                                <p class="text-secondary mb-0">
                                    Kapwa-Kabataan Kasiyahan is committed to supporting children through donations, educational activities, and community service that promote opportunities for learning and happiness.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm p-4">
                            <div class="card-body px-0">
                                <div class="badge bg-warning-subtle text-warning p-3 rounded-3 mb-3">
                                    <i class="bi bi-eye fs-4"></i>
                                </div>
                                <h3 class="h4 fw-bold font-poppins mb-3">Our Vision</h3>
                                <p class="text-secondary mb-0">
                                    To help children learn, grow, and be happy by providing educational support and a caring community.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm p-4 rounded-4">
                            <div class="card-body px-0">
                                <div class="badge bg-success-subtle text-success p-3 rounded-3 mb-3">
                                    <i class="bi bi-people fs-4"></i>
                                </div>
                                <h3 class="h4 fw-bold font-poppins mb-3">Our Program</h3>
                                <p class="text-secondary mb-0">
                                    An NSTP initiative addressing the educational needs of 35 children aged 8-12 at the National Shrine of Our Mother of Perpetual Help in Baclaran, Parañaque City.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mb-5">
                <div class="text-center mb-5">
                    <h2 class="fw-bold font-poppins display-6">Meet the Team</h2>
                    <p class="text-muted">
                        Behind Kapwa-Kabataan Kasiyahan is a team of NSTP students working together to organize the project, coordinate activities, manage resources, and document the initiative.
                    </p>
                </div>

                <div class="row g-4 text-center">
                    <?php foreach ($team_members as $member) { ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="p-3 border rounded-4 h-100 bg-white shadow-sm d-flex flex-column align-items-center">
                                <div class="rounded-circle bg-secondary-subtle overflow-hidden mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                                    <img src="<?php echo htmlspecialchars($member['image']); ?>"
                                        alt="<?php echo htmlspecialchars($member['role']); ?>"
                                        class="w-100 h-100 object-fit-cover"
                                        onerror="this.src='assets/images/team_members/user-avatar-filled.png';">
                                </div>
                                <h4 class="h6 fw-bold font-poppins mb-1"><?php echo htmlspecialchars($member['role']); ?></h4>
                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($member['desc']); ?></p>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </section>
        </div>
    </main>

    <?php include 'includes/footer.php' ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>