

<?php include "../templates/nav.php" ?>

<!-- Hero Section -->
<section class="hero-section">
  <div class="container">
    <h1 class="hero-title">Organize Your Thoughts with NoteSync</h1>
    <p class="hero-subtitle">
      A simple, secure, and intuitive note management system designed to help you capture, organize, and access your ideas from anywhere.
    </p>
    <a href="register.php" class="btn btn-purple">Get Started</a>
  </div>
</section>

<!-- Features Section -->
<section class="features-section">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Why Choose Our Note Management System?</h2>
      <p class="text-muted w-75 mx-auto">
        Discover a better way to manage your notes with these powerful features
      </p>
    </div>
    
    <div class="row g-4">
      <!-- Feature 1 -->
      <div class="col-md-4">
        <div class="feature-card p-4 text-center">
          <div class="feature-icon">
            <i class="fas fa-lock"></i>
          </div>
          <h3 class="feature-title">Secure Storage</h3>
          <p class="feature-text">
            Your notes are encrypted and securely stored, ensuring your private information stays private.
          </p>
        </div>
      </div>
      
      <!-- Feature 2 -->
      <div class="col-md-4">
        <div class="feature-card p-4 text-center">
          <div class="feature-icon">
            <i class="fas fa-tags"></i>
          </div>
          <h3 class="feature-title">Smart Organization</h3>
          <p class="feature-text">
            Organize notes with labels, pins, and search functionality to quickly find what you need.
          </p>
        </div>
      </div>
      
      <!-- Feature 3 -->
      <div class="col-md-4">
        <div class="feature-card p-4 text-center">
          <div class="feature-icon">
            <i class="fas fa-share-alt"></i>
          </div>
          <h3 class="feature-title">Easy Sharing</h3>
          <p class="feature-text">
            Share notes with friends or colleagues while maintaining control over permissions.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Example UI Section -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Intuitive User Interface</h2>
      <p class="text-muted w-75 mx-auto">
        Our clean and intuitive design makes note management a breeze
      </p>
    </div>
    
    <!-- Example UI Preview -->
    <div class="row">
      <div class="col-lg-3">
        <!-- Sidebar -->
        <div class="card rounded-3 shadow-sm mb-4">
          <div class="card-body">
            <h5 class="mb-3 fw-bold">Categories</h5>
            <div class="sidebar-item active">
              <i class="fas fa-sticky-note sidebar-icon"></i>
              <span>All Notes</span>
            </div>
            <div class="sidebar-item">
              <i class="fas fa-thumbtack sidebar-icon"></i>
              <span>Pinned</span>
            </div>
            <div class="sidebar-item">
              <i class="fas fa-tag sidebar-icon"></i>
              <span>Work</span>
            </div>
            <div class="sidebar-item">
              <i class="fas fa-tag sidebar-icon"></i>
              <span>Personal</span>
            </div>
            <div class="sidebar-item">
              <i class="fas fa-tag sidebar-icon"></i>
              <span>Ideas</span>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-lg-9">
        <!-- Search and Add Note Bar -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div class="search-container flex-grow-1 me-3">
            <input type="text" class="form-control" placeholder="Search notes...">
          </div>
          <button class="btn btn-purple">
            <i class="fas fa-plus me-1"></i> Add Note
          </button>
        </div>
        
        <!-- Notes Grid -->
        <div class="row g-4">
          <!-- Note 1 -->
          <div class="col-md-6 col-lg-4">
            <div class="note-card p-3 position-relative">
              <div class="status-icons">
                <div class="status-icon" title="Pinned">
                  <i class="fas fa-thumbtack fa-sm"></i>
                </div>
              </div>
              <h4 class="note-title">Project Plan</h4>
              <div class="note-content">
                <p>Outline for Q3 deliverables. Need to prepare presentation for next week's meeting.</p>
              </div>
              <div class="note-date mt-2">May 3, 2025</div>
              <div class="note-labels">
                <span class="note-label">Work</span>
                <span class="note-label">Urgent</span>
              </div>
              <div class="note-actions">
                <button class="action-btn btn-edit">
                  <i class="fas fa-edit me-1"></i> Edit
                </button>
                <button class="action-btn btn-delete">
                  <i class="fas fa-trash me-1"></i> Delete
                </button>
              </div>
            </div>
          </div>
          
          <!-- Note 2 -->
          <div class="col-md-6 col-lg-4">
            <div class="note-card p-3 position-relative">
              <div class="status-icons">
                <div class="status-icon" title="Password Protected">
                  <i class="fas fa-lock fa-sm"></i>
                </div>
              </div>
              <h4 class="note-title">Personal Goals</h4>
              <div class="note-content">
                <p>Run 5k, learn Python, read 10 books. Need to track progress weekly.</p>
              </div>
              <div class="note-date mt-2">May 4, 2025</div>
              <div class="note-labels">
                <span class="note-label">Personal</span>
              </div>
              <div class="note-actions">
                <button class="action-btn btn-edit">
                  <i class="fas fa-edit me-1"></i> Edit
                </button>
                <button class="action-btn btn-delete">
                  <i class="fas fa-trash me-1"></i> Delete
                </button>
              </div>
            </div>
          </div>
          
          <!-- Note 3 -->
          <div class="col-md-6 col-lg-4">
            <div class="note-card p-3">
              <h4 class="note-title">Grocery List</h4>
              <div class="note-content">
                <p>Milk, eggs, bread, fruits, vegetables, chicken, rice, pasta, coffee.</p>
              </div>
              <div class="note-date mt-2">May 12, 2025</div>
              <div class="note-labels">
                <span class="note-label">Shopping</span>
              </div>
              <div class="note-actions">
                <button class="action-btn btn-edit">
                  <i class="fas fa-edit me-1"></i> Edit
                </button>
                <button class="action-btn btn-delete">
                  <i class="fas fa-trash me-1"></i> Delete
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Team Section -->
<section class="team-section">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Meet Our Team</h2>
      <p class="text-muted w-75 mx-auto">
        We're a team of passionate students who teamed up to create a safe and user-friendly note management system.
      </p>
    </div>
    
    <div class="row g-4">
      <!-- Team Member 1 -->
      <div class="col-md-4">
        <div class="team-card">
          <div class="card-img-wrapper">
            <img src="../public/images/image1.jpg" alt="Aung Kaung Htet" class="card-img-top">
          </div>
          <div class="card-body text-center">
            <h5 class="card-title">Aung Kaung Htet</h5>
            <p class="card-text mb-1">Student ID: 523K0069</p>
            <p class="card-text mb-3">523K0069@student.tdtu.edu.vn</p>
            <div class="social-links">
              <a href="#"><i class="fab fa-linkedin"></i></a>
              <a href="#"><i class="fab fa-facebook"></i></a>
              <a href="#"><i class="fab fa-github"></i></a>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Team Member 2 -->
      <div class="col-md-4">
        <div class="team-card">
          <div class="card-img-wrapper">
            <img src="../public/images/image2.jpg" alt="Wai Yai Moe Myint" class="card-img-top">
          </div>
          <div class="card-body text-center">
            <h5 class="card-title">Wai Yai Moe Myint</h5>
            <p class="card-text mb-1">Student ID: 523K0071</p>
            <p class="card-text mb-3">523K0071@student.tdtu.edu.vn</p>
            <div class="social-links">
              <a href="#"><i class="fab fa-linkedin"></i></a>
              <a href="#"><i class="fab fa-facebook"></i></a>
              <a href="#"><i class="fab fa-github"></i></a>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Team Member 3 -->
      <div class="col-md-4">
        <div class="team-card">
          <div class="card-img-wrapper">
            <img src="../public/images/image3.jpg" alt="Nan Hnin Yai Kyi" class="card-img-top">
          </div>
          <div class="card-body text-center">
            <h5 class="card-title">Nan Hnin Yai Kyi</h5>
            <p class="card-text mb-1">Student ID: 523K0051</p>
            <p class="card-text mb-3">523K0051@student.tdtu.edu.vn</p>
            <div class="social-links">
              <a href="#"><i class="fab fa-linkedin"></i></a>
              <a href="#"><i class="fab fa-facebook"></i></a>
              <a href="#"><i class="fab fa-github"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="text-center mt-5">
      <a href="register.php" class="btn btn-purple">Join Us Today</a>
    </div>
  </div>
</section>

<?php include "../templates/footer.php" ?>