<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['mcl_logged_in']) || !$_SESSION['mcl_logged_in']) {
    echo '<script>window.location.href = "' . home_url('/login/') . '";</script>';
    return '<div class="mcl-error">Morate se prijaviti da pristupite ovoj stranici.</div>';
}

// Get user data from session
$user_ime = $_SESSION['mcl_ime'] ?? 'Korisnik';
$user_prezime = $_SESSION['mcl_prezime'] ?? '';
$user_email = $_SESSION['mcl_email'] ?? 'Nije dostupno';
$user_id = $_SESSION['mcl_user_id'] ?? 0;
$user_full_name = trim($user_ime . ' ' . $user_prezime);
$login_time = $_SESSION['mcl_login_time'] ?? time();
?>

<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URS BiH - Dashboard korisnika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc2626;
            --secondary-color: #0073aa;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --dark-bg: #1f2937;
            --light-bg: #f8fafc;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-container {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            margin: 20px;
            padding: 0;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            backdrop-filter: blur(10px);
            overflow: hidden;
            min-height: calc(100vh - 40px);
        }

        .dashboard-header {
            background: linear-gradient(45deg, var(--primary-color), #e73c3c);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><pattern id="grain" width="100" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="20" fill="url(%23grain)"/></svg>') repeat;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .welcome-text {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .user-info {
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .dashboard-content {
            padding: 40px;
        }

        .info-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid rgba(220, 38, 38, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .info-card h5 {
            color: var(--primary-color);
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-value {
            font-weight: 500;
            color: var(--primary-color);
        }

        .btn-logout {
            background: linear-gradient(45deg, #dc2626, #ef4444);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            color: white;
            font-weight: bold;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-logout:hover {
            background: linear-gradient(45deg, #b91c1c, #dc2626);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
            color: white;
            text-decoration: none;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-online {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .quick-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .quick-action-btn {
            background: linear-gradient(45deg, var(--secondary-color), #0086c3);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .quick-action-btn:hover {
            background: linear-gradient(45deg, #005a87, var(--secondary-color));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 115, 170, 0.3);
            color: white;
            text-decoration: none;
        }

        .session-info {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .session-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .session-item:last-child {
            margin-bottom: 0;
        }

        .logo-container {
            position: absolute;
            top: 20px;
            left: 30px;
            opacity: 0.7;
        }

        .logo-container img {
            max-width: 50px;
            height: auto;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                margin: 10px;
                min-height: calc(100vh - 20px);
            }

            .dashboard-content {
                padding: 20px;
            }

            .quick-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="logo-container">
                <img src="LogoURSBIH.png" alt="URS BiH Logo" onerror="this.style.display='none'">
            </div>

            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>

            <div class="welcome-text">
                Dobro došli, <?php echo htmlspecialchars($user_full_name); ?>!
            </div>

            <div class="user-info">
                <span class="status-badge status-online">
                    <i class="fas fa-circle"></i>
                    Online
                </span>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-card">
                        <h5>
                            <i class="fas fa-user-circle"></i>
                            Informacije o korisniku
                        </h5>

                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-id-badge"></i>
                                ID korisnika:
                            </div>
                            <div class="info-value">#<?php echo htmlspecialchars($user_id); ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-user"></i>
                                Ime:
                            </div>
                            <div class="info-value"><?php echo htmlspecialchars($user_ime); ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-user"></i>
                                Prezime:
                            </div>
                            <div class="info-value"><?php echo htmlspecialchars($user_prezime); ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-envelope"></i>
                                Email:
                            </div>
                            <div class="info-value"><?php echo htmlspecialchars($user_email); ?></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-card">
                        <h5>
                            <i class="fas fa-clock"></i>
                            Informacije o sesiji
                        </h5>

                        <div class="session-info">
                            <div class="session-item">
                                <i class="fas fa-sign-in-alt text-success"></i>
                                <strong>Prijavljeni:</strong>
                                <span><?php echo date('d.m.Y H:i:s', $login_time); ?></span>
                            </div>

                            <div class="session-item">
                                <i class="fas fa-globe text-primary"></i>
                                <strong>IP adresa:</strong>
                                <span><?php echo $_SERVER['REMOTE_ADDR'] ?? 'N/A'; ?></span>
                            </div>

                            <div class="session-item">
                                <i class="fas fa-desktop text-info"></i>
                                <strong>Browser:</strong>
                                <span><?php echo substr($_SERVER['HTTP_USER_AGENT'] ?? 'N/A', 0, 50) . '...'; ?></span>
                            </div>

                            <div class="session-item">
                                <i class="fas fa-shield-alt text-warning"></i>
                                <strong>Status:</strong>
                                <span class="status-badge status-online">
                                    <i class="fas fa-check-circle"></i>
                                    Aktivan
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <h5>
                    <i class="fas fa-cog"></i>
                    Brze akcije
                </h5>

                <div class="quick-actions">
                    <a href="#" class="quick-action-btn" onclick="updateProfile()">
                        <i class="fas fa-user-edit"></i>
                        Ažuriraj profil
                    </a>

                    <a href="#" class="quick-action-btn" onclick="changePassword()">
                        <i class="fas fa-key"></i>
                        Promijeni lozinku
                    </a>

                    <a href="#" class="quick-action-btn" onclick="viewActivity()">
                        <i class="fas fa-history"></i>
                        Aktivnost
                    </a>

                    <a href="#" class="quick-action-btn" onclick="contactSupport()">
                        <i class="fas fa-headset"></i>
                        Podrška
                    </a>
                </div>

                <div style="margin-top: 30px; text-align: center;">
                    <a href="#" class="btn-logout" onclick="logout()">
                        <i class="fas fa-sign-out-alt"></i>
                        Odjavite se
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function logout() {
            if (confirm('Da li ste sigurni da se želite odjaviti?')) {
                // Clear session via AJAX
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=user_logout&nonce=<?php echo wp_create_nonce('user_logout_nonce'); ?>'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '<?php echo home_url('/login/'); ?>';
                    } else {
                        alert('Greška pri odjavi: ' + data.data);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Fallback - redirect anyway
                    window.location.href = '<?php echo home_url('/login/'); ?>';
                });
            }
        }

        function updateProfile() {
            alert('Funkcija ažuriranja profila će biti implementirana uskoro.');
        }

        function changePassword() {
            alert('Funkcija mijenjanja lozinke će biti implementirana uskoro.');
        }

        function viewActivity() {
            alert('Funkcija pregleda aktivnosti će biti implementirana uskoro.');
        }

        function contactSupport() {
            alert('Za podršku kontaktirajte: info@ursbih.ba');
        }

        // Update time every second
        setInterval(() => {
            const now = new Date();
            const timeString = now.toLocaleString('sr-RS', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            // Update browser title with current time
            document.title = 'URS BiH - Dashboard (' + timeString + ')';
        }, 1000);
    </script>
</body>
</html>