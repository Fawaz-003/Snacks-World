
<?php
// Include DB connection
require_once 'db.php';

// Fetch items from 'item' table
$sql = "SELECT name, image, price FROM item";
$result = $conn->query($sql);
$items = [];
if ($result && $result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    $items[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SnackWorld | Delicious Food</title>
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
  <!-- AOS CSS for scroll animations -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css"/>
  <style>
    body { 
      background: #fff; 
      color: #1a1a1a; 
      font-family: 'Roboto', Arial, sans-serif;
    }
    .navbar { 
      background: #ffffff !important; 
      box-shadow: 0 2px 15px rgba(0,0,0,0.05);
    }
    .navbar-brand { 
      font-family: 'Montserrat', sans-serif; 
      font-weight: 700; 
      font-size: 1.8rem; 
      color: #1a1a1a !important; 
    }
    .navbar-brand span {
      color: #e85d04;
    }
    .nav-link { 
      color: #1a1a1a !important; 
      font-family: 'Montserrat', sans-serif; 
      font-weight: 600; 
      margin: 0 15px;
    }
    .nav-link.active, 
    .nav-link:hover { 
      color: #e85d04 !important; 
    }
    .cart-icon {
      font-size: 1.5rem;
      position: relative;
    }
    .cart-badge {
      position: absolute;
      top: -8px;
      right: -8px;
      background-color: #e85d04;
      color: white;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      font-size: 0.75rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .btn-primary {
      background: #e85d04;
      color: white;
      border: none;
      border-radius: 6px;
      font-family: 'Montserrat', sans-serif;
      font-weight: 600;
      padding: 12px 25px;
      transition: all 0.3s ease;
    }
    .btn-secondary {
      background: transparent;
      color: #1a1a1a;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-family: 'Montserrat', sans-serif;
      font-weight: 600;
      padding: 12px 25px;
      transition: all 0.3s ease;
    }
    .btn-primary:hover {
      background: #d04e00;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(232, 93, 4, 0.2);
    }
    .btn-secondary:hover {
      border-color: orange;
      background: white;
      color: #d04e00;
      transform: translateY(-2px);
    }
    .hero-section { 
      min-height: 100vh; 
      display: flex; 
      align-items: center; 
      background-color: #fff1e6;
      padding-top: 80px;
      overflow: hidden;
    }
    .hero-content h1 {
      font-family: 'Montserrat', sans-serif;
      font-weight: 800;
      font-size: 3.5rem;
      line-height: 1.2;
      margin-bottom: 1rem;
      color: #1a1a1a;
    }
    .hero-content h1 span {
      color: #e85d04;
      display: block;
    }
    .hero-content p {
      font-size: 1.2rem;
      line-height: 1.6;
      width: 95%;
      color: #555;
      margin-bottom: 2rem;
    }
    .hero-img img {
      border-radius: 50%;
      display: block;
      animation: rotate360 20s linear infinite;
    }
    @keyframes rotate360 {
      from {
        transform: rotate(0deg);
      }
      to {
        transform: rotate(360deg);
      }
    }
    .status-badge {
      display: inline-flex;
      align-items: center;
      background: #fff;
      padding: 10px 20px;
      border-radius: 30px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      margin-top: 20px;
      position: relative;
    }
    .status-badge .dot {
      width: 12px;
      height: 12px;
      background: #4caf50;
      border-radius: 50%;
      margin-right: 8px;
      position: relative;
    }
    
    /* Dot pulse animation */
    .status-badge .dot:before,
    .status-badge .dot:after {
      content: "";
      position: absolute;
      top: 50%;
      left: 50%;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: rgba(76, 175, 80, 0.6);
      transform: translate(-50%, -50%);
      animation: pulse 2s infinite;
    }
    
    .status-badge .dot:after {
      animation-delay: 0.5s;
    }
    
    @keyframes pulse {
      0% {
        width: 12px;
        height: 12px;
        opacity: 1;
      }
      100% {
        width: 36px;
        height: 36px;
        opacity: 0;
      }
    }
    
    .status-badge span {
      font-weight: 600;
      color: #1a1a1a;
    }
    .menu-title { 
      font-family: 'Montserrat', sans-serif; 
      font-size: 2.2em; 
      color: #1a1a1a; 
      font-weight: 700; 
      text-align: center; 
      margin-bottom: 32px; 
    }
    .card.snack-card {
      background: #ffffff;
      border: none;
      border-radius: 12px;
      color: #1a1a1a;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      height: 100%;
      display: flex;
      flex-direction: column;
      align-items: stretch;
      justify-content: flex-start;
      min-width: 220px;
      max-width: 350px;
      margin: 0 auto;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card.snack-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .snack-img-wrap {
      width: 100%;
      aspect-ratio: 4/3;
      overflow: hidden;
      border-radius: 12px 12px 0 0;
      background: #f8f8f8;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .snack-img-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 12px 12px 0 0;
      display: block;
      transition: transform 0.5s ease;
    }
    .snack-card:hover .snack-img-wrap img {
      transform: scale(1.05);
    }
    .snack-card-body {
      flex: 1 1 auto;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      align-items: stretch;
      padding: 1.5rem;
      text-align: left;
      min-height: 180px;
    }
    .snack-title {
      font-family: 'Montserrat', sans-serif;
      color: #1a1a1a;
      font-weight: 700;
      font-size: 1.2rem;
      margin-bottom: 0.5rem;
      line-height: 1.3;
      word-break: break-word;
      display: block;
      overflow: visible;
    }
    .price {
      color: #e85d04;
      font-weight: 700;
      font-size: 1.2em;
      margin-bottom: 1em;
    }
    .snack-card .btn-primary {
      width: 100%;
      margin-top: auto;
      font-size: 1em;
      padding: 0.7em 0;
    }
    
    /* About Us Section Styles */
    .about-section {
      padding: 100px 0;
      background-color: #f9f9f9;
    }
    
    .about-content {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .about-text {
      flex: 1;
      padding-right: 50px;
    }
    
    .about-text h2 {
      font-family: 'Montserrat', sans-serif;
      font-weight: 700;
      font-size: 2.5rem;
      margin-bottom: 20px;
      color: #1a1a1a;
    }
    
    .about-text h2 span {
      color: #e85d04;
    }
    
    .about-text p {
      font-size: 1.1rem;
      line-height: 1.8;
      color: #555;
      margin-bottom: 25px;
    }
    
    .about-features {
      margin-top: 30px;
    }
    
    .feature-item {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .feature-icon {
      width: 50px;
      height: 50px;
      background: #fff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      margin-right: 15px;
      color: #e85d04;
      font-size: 1.2rem;
    }
    
    .feature-text {
      font-weight: 500;
      color: #333;
    }
    
    .about-image {
      flex: 1;
      text-align: center;
      position: relative;
    }
    
    .about-image img {
      max-width: 90%;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .about-badge {
      position: absolute;
      bottom: 30px;
      right: 30px;
      background: #fff;
      padding: 15px 25px;
      border-radius: 50px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      display: flex;
      align-items: center;
    }
    
    .about-badge .dot {
      width: 12px;
      height: 12px;
      background: #4caf50;
      border-radius: 50%;
      margin-right: 10px;
      position: relative;
    }
    
    .about-badge .dot:before,
    .about-badge .dot:after {
      content: "";
      position: absolute;
      top: 50%;
      left: 50%;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: rgba(76, 175, 80, 0.6);
      transform: translate(-50%, -50%);
      animation: pulse 2s infinite;
    }
    
    .about-badge .dot:after {
      animation-delay: 0.5s;
    }
    
    .about-badge span {
      font-weight: 600;
      color: #1a1a1a;
    }
    
    /* Footer Styles */
    .footer {
      background-color: #10131f;
      color: #fff;
      padding: 70px 0 30px;
    }
    
    .footer-brand {
      font-family: 'Montserrat', sans-serif;
      font-weight: 700;
      font-size: 2rem;
      margin-bottom: 20px;
      color: #fff;
    }
    
    .footer-brand span {
      color: #e85d04;
    }
    
    .footer-desc {
      color: #ccc;
      margin-bottom: 25px;
      line-height: 1.7;
    }
    
    .footer-social {
      display: flex;
      margin-bottom: 30px;
    }
    
    .social-link {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(255,255,255,0.1);
      color: #fff;
      margin-right: 12px;
      transition: all 0.3s ease;
    }
    
    .social-link:hover {
      background: #e85d04;
      transform: translateY(-3px);
      color: #fff;
    }
    
    .footer-title {
      font-family: 'Montserrat', sans-serif;
      font-weight: 600;
      font-size: 1.2rem;
      margin-bottom: 25px;
      color: #fff;
    }
    
    .footer-links {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .footer-links li {
      margin-bottom: 12px;
    }
    
    .footer-links a {
      color: #ccc;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    
    .footer-links a:hover {
      color: #e85d04;
      text-decoration: none;
      padding-left: 5px;
    }
    
    .footer-schedule {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .schedule-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(255,255,255,0.1);
      margin-right: 15px;
      color: #e85d04;
    }
    
    .schedule-text {
      color: #ccc;
    }
    
    .footer-contact {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .contact-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(255,255,255,0.1);
      margin-right: 15px;
      color: #e85d04;
    }
    
    .contact-text {
      color: #ccc;
    }
    
    .footer-bottom {
      border-top: 1px solid rgba(255,255,255,0.1);
      padding-top: 30px;
      margin-top: 30px;
      text-align: center;
      color: #999;
    }
    
    @media (max-width: 1200px) {
      .hero-content h1 {
        font-size: 3rem;
      }
      .hero-content p {
        font-size: 1.1rem;
      }
      .about-text {
        padding-right: 30px;
      }
    }
    
    @media (max-width: 991px) {
      .hero-content {
        text-align: center;
        margin-bottom: 3rem;
      }
      .hero-content .btn-container {
        justify-content: center;
      }
      .hero-img img {
        margin: 0 auto;
      }
      .status-badge {
        margin: 20px auto;
      }
      .about-content {
        flex-direction: column;
      }
      .about-text {
        padding-right: 0;
        margin-bottom: 50px;
      }
      .about-badge {
        right: 50%;
        transform: translateX(50%);
      }
    }
    
    @media (max-width: 768px) {
      .hero-content h1 {
        font-size: 2.5rem;
      }
      .hero-img img {
        max-width: 80%;
      }
      .menu-title {
        font-size: 1.8em;
      }
      .about-text h2 {
        font-size: 2rem;
      }
    }
    
    @media (max-width: 576px) {
      .hero-content h1 {
        font-size: 2rem;
      }
      .hero-content p {
        font-size: 1rem;
      }
      .hero-img img {
        max-width: 90%;
      }
      .menu-title {
        font-size: 1.5em;
      }
      .snack-card {
        min-width: 100%;
      }
      .about-text h2 {
        font-size: 1.8rem;
      }
    }
    
    /* Footer icon styling */
    .footer-admin-icon {
      position: fixed;
      right: 24px;
      bottom: 24px;
      background: #e85d04;
      color: white;
      border-radius: 50%;
      width: 52px;
      height: 52px;
      display: flex;
      justify-content: center;
      align-items: center;
      box-shadow: 0 4px 16px rgba(232, 93, 4, 0.25);
      z-index: 1050;
      transition: all 0.3s ease;
      font-size: 1.4rem;
      border: none;
      cursor: pointer;
      text-decoration: none;
    }
    .footer-admin-icon:hover {
      background: #d04e00;
      color: #fff;
      box-shadow: 0 8px 24px rgba(232, 93, 4, 0.35);
      transform: translateY(-3px);
      text-decoration: none;
    }
  </style>
</head>
<body>
<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light fixed-top">
  <div class="container">
    <a class="navbar-brand" href="#">Snack<span>World</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#menu">Menu</a></li>
        <li class="nav-item"><a class="nav-link" href="#about">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- HERO SECTION -->
<section class="hero-section" id="hero">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-7 hero-content">
        <h1>Delicious Food,<br><span>Delivered Fresh</span></h1>
        <p>At Snack World, we serve joy in every bite. From crispy treats to delicious munchies, we offer a wide variety of fresh and tasty snacks made with love and quality ingredients.</p>
        <div class="d-flex gap-3 btn-container">
          <a href="#menu" class="btn btn-primary">Order Now</a>
          <a href="#menu" class="btn btn-secondary">View Menu</a>
        </div>
        <div class="status-badge">
          <div class="dot"></div>
          <span>Open Now</span>
        </div>
      </div>
      <div class="col-lg-5 text-center hero-img">
        <img src="plate.png" alt="Delicious Food Bowl" class="img-fluid">
      </div>
    </div>
  </div>
</section>

<!-- MENU SECTION (DYNAMIC) -->
<section id="menu" class="py-5" style="margin-top: 70px;">
  <div class="container">
    <h2 class="menu-title mb-4" data-aos="fade-up">Our Menu</h2>
    <div class="row g-4 justify-content-center">
      <?php foreach($items as $index => $item): ?>
        <div class="col-md-4 col-sm-5 d-flex" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
          <div class="card snack-card flex-fill h-100">
            <div class="snack-img-wrap">
              <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
            </div>
            <div class="snack-card-body">
              <div class="snack-title"><?php echo htmlspecialchars($item['name']); ?></div>
              <div class="price">₹<?php echo number_format($item['price']); ?></div>
              <a href="order.php?item=<?php echo urlencode($item['name']); ?>" class="btn btn-primary">Order Now</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($items)): ?>
        <div class="col-12 text-center text-muted" data-aos="fade-up">No items available.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ABOUT US SECTION -->
<section id="about" class="about-section">
  <div class="container">
    <div class="about-content">
      <div class="about-text" data-aos="fade-right">
        <h2>About <span>Snack World</span></h2>
        <p>Established in 2020, Snack World is more than just a food delivery service - we're a culinary experience bringing joy to your doorstep. Our mission is to provide high-quality, delicious snacks made with the freshest ingredients sourced locally.</p>
        <p>Every dish at Snack World is prepared with passion and care by our team of experienced chefs who pride themselves on creating flavors that delight and satisfy. We believe that good food brings people together and creates memorable moments.</p>
        
        <div class="about-features">
          <div class="feature-item" data-aos="fade-up" data-aos-delay="100">
            <div class="feature-icon">
              <i class="fas fa-leaf"></i>
            </div>
            <div class="feature-text">Fresh, locally sourced ingredients</div>
          </div>
          <div class="feature-item" data-aos="fade-up" data-aos-delay="200">
            <div class="feature-icon">
              <i class="fas fa-utensils"></i>
            </div>
            <div class="feature-text">Prepared by experienced chefs</div>
          </div>
          <div class="feature-item" data-aos="fade-up" data-aos-delay="300">
            <div class="feature-icon">
              <i class="fas fa-truck"></i>
            </div>
            <div class="feature-text">Fast delivery to your doorstep</div>
          </div>
        </div>
      </div>
      
      <div class="about-image" data-aos="fade-left">
        <img src="about-image.jpg" alt="Snack World Kitchen" class="img-fluid">
        <div class="about-badge">
          <div class="dot"></div>
          <span>Quality Guaranteed</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CONTACT SECTION -->
<section id="contact" class="py-5">
  <div class="container text-center">
    <h2 class="menu-title mb-3">Contact Us</h2>
    <p class="mb-4">Have a question or want to place a big order?</p>
    <a href="https://wa.me/919585790805" target="_blank" class="btn btn-primary"><i class="fa-brands fa-whatsapp me-2"></i>WhatsApp: +91 95857 90805</a>
  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="container">
    <div class="row">
      <!-- Footer Brand & Info -->
      <div class="col-lg-4 col-md-6 mb-5">
        <div class="footer-brand">Snack<span>World</span></div>
        <p class="footer-desc">Enjoy delicious meals prepared with fresh ingredients and delivered to your doorstep with care and love.</p>
        <div class="footer-social">
          <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
          <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
        </div>
      </div>
      
      <!-- Quick Links -->
      <div class="col-lg-2 col-md-6 mb-5">
        <h5 class="footer-title">Quick Links</h5>
        <ul class="footer-links">
          <li><a href="#">Home</a></li>
          <li><a href="#menu">Menu</a></li>
          <li><a href="#about">About Us</a></li>
          <li><a href="#contact">Contact</a></li>
          <li><a href="#">Privacy Policy</a></li>
        </ul>
      </div>
      
      <!-- Opening Hours -->
      <div class="col-lg-3 col-md-6 mb-5">
        <h5 class="footer-title">Opening Hours</h5>
        <div class="footer-schedule">
          <div class="schedule-icon">
            <i class="far fa-clock"></i>
          </div>
          <div class="schedule-text">
            Monday - Friday<br>10:00 AM - 10:00 PM
          </div>
        </div>
        <div class="footer-schedule">
          <div class="schedule-icon">
            <i class="far fa-clock"></i>
          </div>
          <div class="schedule-text">
            Saturday - Sunday<br>11:00 AM - 11:00 PM
          </div>
        </div>
      </div>
      
      <!-- Contact Info -->
      <div class="col-lg-3 col-md-6 mb-5">
        <h5 class="footer-title">Contact Us</h5>
        <div class="footer-contact">
          <div class="contact-icon">
            <i class="fas fa-map-marker-alt"></i>
          </div>
          <div class="contact-text">
            123 Food Street, Culinary Avenue, City 10001
          </div>
        </div>
        <div class="footer-contact">
          <div class="contact-icon">
            <i class="fas fa-phone-alt"></i>
          </div>
          <div class="contact-text">
            +91 95857 90805
          </div>
        </div>
        <div class="footer-contact">
          <div class="contact-icon">
            <i class="fas fa-envelope"></i>
          </div>
          <div class="contact-text">
            info@snackworld.com
          </div>
        </div>
      </div>
    </div>
    
    <!-- Footer Bottom -->
    <div class="footer-bottom">
      <p>© 2025 SnackWorld. All rights reserved.</p>
    </div>
  </div>
</footer>

<!-- Footer Admin Icon -->
<a href="admin.php" class="footer-admin-icon" title="Admin Dashboard">
  <i class="fa-solid fa-user-shield"></i>
</a>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AOS JS for scroll animations -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
  // Initialize AOS
  AOS.init({
    duration: 800,
    easing: 'ease-in-out',
    once: false,
    mirror: false
  });

  // Highlight active nav-link on click
  document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function() {
      document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
      this.classList.add('active');
    });
  });
</script>
</body>
</html>