<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<nav style="background-color: #343a40; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; color: #fff;">
    <div style="font-size: 24px; font-weight: bold;">
        <a href="welcome.php" style="color: #fff; text-decoration: none;">Dashboard</a>
    </div>
    <div>
        <span style="margin-right: 20px; font-size: 16px;">Admin: <?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></span>
        <a href="logout.php" onclick="return confirm('Do you want to logout?');" style="color: #fff; text-decoration: none;">Logout</a>
    </div>
</nav>
