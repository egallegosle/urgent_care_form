        </div><!-- .admin-container -->
    </main><!-- .admin-main -->

    <!-- Admin Footer -->
    <footer class="admin-footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> Urgent Care Form System. All rights reserved.</p>
            <p class="footer-meta">
                Session expires in: <span id="sessionTimer"></span> |
                Last activity: <span id="lastActivity"><?php echo date('h:i A'); ?></span>
            </p>
        </div>
    </footer>

    <!-- Admin Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="/js/admin.js"></script>

    <!-- Session Timer Script -->
    <script>
    // Session timeout warning
    const SESSION_TIMEOUT = <?php echo SESSION_TIMEOUT; ?> * 60; // Convert to seconds
    let lastActivity = <?php echo $_SESSION['last_activity'] ?? time(); ?>;
    let sessionStartTime = Date.now() / 1000;

    function updateSessionTimer() {
        const now = Date.now() / 1000;
        const elapsed = now - sessionStartTime;
        const remaining = SESSION_TIMEOUT - elapsed;

        if (remaining <= 0) {
            alert('Your session has expired. You will be redirected to the login page.');
            window.location.href = '/admin/login.php';
            return;
        }

        const minutes = Math.floor(remaining / 60);
        const seconds = Math.floor(remaining % 60);
        document.getElementById('sessionTimer').textContent =
            `${minutes}:${seconds.toString().padStart(2, '0')}`;

        // Warning at 5 minutes
        if (remaining <= 300 && remaining > 299) {
            alert('Your session will expire in 5 minutes. Please save your work.');
        }
    }

    // Update timer every second
    setInterval(updateSessionTimer, 1000);
    updateSessionTimer();

    // Reset timer on user activity
    ['click', 'keypress', 'scroll', 'mousemove'].forEach(event => {
        document.addEventListener(event, function() {
            sessionStartTime = Date.now() / 1000;
        }, { passive: true });
    });

    // Mobile menu toggle
    document.getElementById('mobileMenuToggle')?.addEventListener('click', function() {
        document.querySelector('.admin-nav').classList.toggle('mobile-active');
    });
    </script>
</body>
</html>
