<aside>
    <nav class="sidenav">
        <div class="nav-tabs">
            <button id="sidebar-toggle-btn" title="Toggle Sidebar"><i class="fa-solid fa-chevron-left"></i></button>
            <div class="sidebar-logo">
                <a href="dashboard-executive">
                    <img src="assets/images/wnet-image.png" class="full-logo" alt="WildNet logo">
                    <img src="assets/images/wnet-logo.png" class="collapsed-logo" alt="WildNet logo">
                </a>
            </div>
            <div class="profile-img">
                <div class="img-overlay"></div>
                <img src="<?= $employee['profile_photo'] ?>" id="profile-image" alt="profile-img"
                    onerror="this.onerror=null; this.src='assets/images/profile-picture.png';">
                <i class="fa-solid fa-camera"></i>
            </div>
            <div class="profile-head">
                <h4><?= $employee['name'] ?></h4>
                <h5>Emp Id: <?= $employee['emp_id'] ?></h5>
            </div>
            <a href="dashboard-executive" class="tab">
                <i class="fa-solid fa-house"></i> <span>Dashboard</span>
            </a>
            <a href="profile" class="tab">
                <i class="fa-solid fa-user"></i> <span>My Profile</span>
            </a>
            <a class="tab" id="logout-link">
                <i class="fa-solid fa-right-from-bracket"></i> <span>Log Out</span>
            </a>
        </div>
    </nav>
</aside>