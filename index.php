<?php
session_start();

date_default_timezone_set('Asia/Manila');
include 'db_connect.php';

$featuredQuery = "SELECT * FROM products ORDER BY created_at DESC LIMIT 4";
$featuredResult = mysqli_query($Connection, $featuredQuery);

$categories = [
    [
        'name'      => 'Button Pins',
        'image'     => 'assets/images/categories/button-pins.jpg'
    ],
    [
        'name'      => 'Bookmarks',
        'image'     => 'assets/images/categories/bookmarks.jpg'
    ],
    [
        'name'      => 'Elastic Band Pen Holders',
        'image'     => 'assets/images/categories/elastic-band-pen-holder.jpg'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="assets/images/logos/transparent-symbol-logo.png">
    
</head>
<body class="d-flex flex-column min-vh-100">

    <?php
        $activePage = 'home'; 
        include 'includes/navbar.php';
    ?>

    <main>
        <section class="py-5 bg-white border-bottom">
            <div class="container px-4 px-lg-5 py-my-4">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <h1 class="fw-bold font-poppins text-dark mb-3">Kapwa-Kabataan Kasiyahan</h1>
                        <h2 class="h4 fw-semibold text-success font-poppins mb-3">Supporting Children's Education Through Community Service</h2>
                        <p class="text-secondary mb-4" style="line-height: 1.6;">
                            Kapwa-Kabataan Kasiyahan is a community service and fundraising project led by NSTP students. It aims to bring happiness and support to children by helping provide school supplies, learning materials, and educational activities that encourage learning, creativity, confidence, and social interaction.
                        </p>
                        <div class="d-flex flex-column flex-sm-row gap-3">
                            <a href="products.php" class="btn btn-dark-custom px-3 py-2 fw-semibold text-center text-decoration-none"> 
                                Shop Products
                            </a>
                             <a href="about.php" class="btn btn-outline-custom px-3 py-2 fw-semibold text-center text-decoration-none"> 
                                Learn About Our Project
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="bg-light rounded-4 overflow-hidden border shadow-sm d-flex align-items-center justify-content-center mx-auto" style="width: 340px;">
                            <img src="assets/images/pictures/home-project.png" alt="Kapwa-Kabataan Kasiyahan Project" class="w-100 h-100 object-fit-cover" onerror="this.parentElement.innerHTML='<span class=\'text-muted small\'>No Image Available</span>';">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-5 bg-light border-bottom">
            <div class="container px-4 px-lg-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold font-poppins text-dark mb-0 fs-3">Shop by Category</h2>
                    <a href="products.php" class="text-dark small fw-semibold text-decoration-none">View all product &rarr;</a>
                </div>

                <div class="row g-4">
                    <?php foreach ($categories as $cat) { ?>
                        <div class="col-md-4">
                            <a href="products.php?category=<?php echo urlencode($cat['name']); ?>" class="card border rounded-3 shadow-sm text-decoration-none bg-white overflow-hidden h-100 transition-all">
                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 280px;">
                                    <img src="<?php echo htmlspecialchars($cat['image']); ?>" alt="<?php echo htmlspecialchars($cat['name']); ?>" class="w-100 h-100 object-fit-cover" onerror="this.replaceWith(Object.assign(document.createElement('span'), {className: 'text-muted small', textContent: 'No Category Image Available'}));">
                                </div>
                                <div class="card-body text-center p-3">
                                    <h5 class="fw-bold font-poppins text-dark mb-0 fs-6"><?php echo htmlspecialchars($cat['name']); ?></h5>
                                </div>
                            </a>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </section>

        <section class="py-5 bg-white border-bottom">
            <div class="container px-4 px-lg-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold font-poppins text-dark mb-0 fs-3">Featured Products</h2>
                    <a href="products.php" class="text-dark small fw-semibold text-decoration-none">View all &rarr;</a>
                </div>

                <div class="row g-3 g-lg-4">
                    <?php if ($featuredResult && mysqli_num_rows($featuredResult) > 0) { 
                        while ($Row = mysqli_fetch_assoc($featuredResult)) {
                            $stock = (int)$Row['stock'];
                            if ($stock <= 0) {
                                $stockText = 'Out of stock';
                                $badgeClass = 'text-muted';
                            } elseif ($stock <= 3) {
                                $stockText = 'Only ' . $stock . ' left';
                                $badgeClass = 'text-danger fw-semibold';
                            } else {
                                $stockText = 'In stock · ' . $stock . ' available';
                                $badgeClass = 'text-muted';
                            }
                    ?>
                        <div class="col-6 col-lg-3">
                            <div class="card h-100 border rounded-3 shadow-sm bg-white overflow-hidden transition-all">
                                <a href="product-details.php?id=<?php echo urlencode($Row['product_id']); ?>" class="text-decoration-none bg-light d-block position-relative" style="height: 200px;">
                                    <?php if (!empty($Row['product_image'])) { ?>
                                        <img src="uploads/<?php echo htmlspecialchars($Row['product_image']); ?>" alt="<?php echo htmlspecialchars($Row['product_name']); ?>" class="w-100 h-100 object-fit-cover">
                                    <?php } else { ?>
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted small">
                                            No Product Image Available
                                        </div>
                                    <?php } ?>
                                </a>

                                <div class="card-body d-flex flex-column p-3">
                                    <a href="product-details.php?id=<?php echo urlencode($Row['product_id']); ?>" class="text-decoration-none text-dark fw-semibold mb-1 text-truncate small">
                                        <?php echo htmlspecialchars($Row['product_name']); ?>
                                    </a>
                                    <div class="small mb-2 <?php echo $badgeClass; ?>" style="font-size: 0.75rem;"><?php echo $stockText; ?></div>
                                    <div class="fw-bold text-dark mb-3">&#8369;<?php echo number_format((float)$Row['price'], 2); ?></div>
                                    
                                    <div class="mt-auto">
                                        <form action="product-details.php?id=<?php echo urlencode($Row['product_id']); ?>" method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($Row['product_id']); ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="btn btn-dark-custom w-100 py-1.5 small fw-semibold <?php echo $stock <= 0 ? 'disabled' : ''; ?>">
                                                <?php echo $stock <= 0 ? 'Unavailable' : 'Add to Cart'; ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php }
                    } else { ?>
                        <div class="col-12 text-center py-4">
                            <p class="text-muted small">No featured products available at the moment.</p>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </section>

        <section class="py-5 bg-light">
            <div class="container px-4 px-lg-5">
                <div class="row g-4 text-sm-start">
                    <div class="col-sm-6 col-lg-3 d-flex align-items-center gap-3">
                        <div class="bg-white p-3 border rounded-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; min-width: 50px;">
                            <i class="bi bi-shield-check text-success fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold font-poppins text-dark mb-1 small">Secure Payment</h6>
                            <p class="text-muted small mb-0" style="font-size: 0.75rem;">Pay securely online or choose cash on delivery upon receipt.</p>
                        </div>
                    </div>

                    <div class="col-sm-6 col-lg-3 d-flex align-items-center gap-3">
                        <div class="bg-white p-3 border rounded-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; min-width: 50px;">
                            <i class="bi bi-box-seam text-success fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold font-poppins text-dark mb-1 small">Order Tracking</h6>
                            <p class="text-muted small mb-0" style="font-size: 0.75rem;">Monitor your order live from pending status to completed delivery.</p>
                        </div>
                    </div>

                    <div class="col-sm-6 col-lg-3 d-flex align-items-center gap-3">
                        <div class="bg-white p-3 border rounded-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; min-width: 50px;">
                            <i class="bi bi-truck text-success fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold font-poppins text-dark mb-1 small">Shipping & Delivery</h6>
                            <p class="text-muted small mb-0" style="font-size: 0.75rem;">Reliable door-to-door delivery supporting our outreach schedule.</p>
                        </div>
                    </div>

                    <div class="col-sm-6 col-lg-3 d-flex align-items-center gap-3">
                        <div class="bg-white p-3 border rounded-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; min-width: 50px;">
                            <i class="bi bi-arrow-counterclockwise text-success fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold font-poppins text-dark mb-1 small">Returns & Refunds</h6>
                            <p class="text-muted small mb-0" style="font-size: 0.75rem;">Hassle-free exchange policy for damaged or defective items.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php' ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>