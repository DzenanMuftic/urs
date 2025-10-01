<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in
if (isset($_SESSION['mcl_logged_in']) && $_SESSION['mcl_logged_in']) {
    echo '<script>window.location.href = "' . home_url('/user-dashboard/') . '";</script>';
    return '<div class="mcl-info">Već ste prijavljeni. Preusmjeravamo vas na dashboard...</div>';
}

// Generate mathematical question
$num1 = rand(1, 10);
$num2 = rand(1, 10);
$operations = ['+', '-', '*'];
$operation = $operations[array_rand($operations)];

switch($operation) {
    case '+':
        $answer = $num1 + $num2;
        $question = "$num1 + $num2";
        break;
    case '-':
        if ($num1 < $num2) {
            $temp = $num1;
            $num1 = $num2;
            $num2 = $temp;
        }
        $answer = $num1 - $num2;
        $question = "$num1 - $num2";
        break;
    case '*':
        $num1 = rand(2, 5);
        $num2 = rand(2, 5);
        $answer = $num1 * $num2;
        $question = "$num1 × $num2";
        break;
}

// Store answer in session for verification
$_SESSION['math_answer'] = $answer;
?>

<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URS BiH - Prijava</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc2626;
            --secondary-color: #0073aa;
            --accent-color: #f59e0b;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
            max-width: 450px;
            width: 100%;
            margin: 20px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-container img {
            max-width: 120px;
            height: auto;
        }

        .login-title {
            text-align: center;
            color: var(--primary-color);
            font-weight: bold;
            font-size: 1.8rem;
            margin-bottom: 30px;
        }

        .form-floating {
            margin-bottom: 20px;
        }

        .form-floating > .form-control {
            border: 2px solid #e3e6f0;
            border-radius: 10px;
            padding: 12px 20px;
            font-size: 16px;
        }

        .form-floating > .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.25);
        }

        .form-floating > label {
            color: #6c757d;
            padding-left: 20px;
        }

        .math-question {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border: 2px solid var(--accent-color);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .btn-login {
            background: linear-gradient(45deg, var(--primary-color), #e73c3c);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
            color: white;
            width: 100%;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: linear-gradient(45deg, #b91c1c, #dc2626);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
            color: white;
        }

        .alert-custom {
            border: none;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .loading-spinner {
            display: none;
        }

        .loading-spinner.show {
            display: inline-block;
        }

        .footer-text {
            text-align: center;
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 20px;
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 10;
        }

        .form-floating {
            position: relative;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <img src="LogoURSBIH.png" alt="URS BiH Logo" onerror="this.style.display='none'">
        </div>

        <h2 class="login-title">
            <i class="fas fa-user-circle me-2"></i>Prijava korisnika
        </h2>

        <div id="loginMessage" class="alert alert-custom d-none" role="alert"></div>

        <form id="userLoginForm">
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" placeholder="Korisničko ime" required>
                <label for="username"><i class="fas fa-user me-2"></i>Korisničko ime</label>
                <i class="fas fa-user input-icon"></i>
            </div>

            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Lozinka" required>
                <label for="password"><i class="fas fa-lock me-2"></i>Lozinka</label>
                <i class="fas fa-lock input-icon"></i>
            </div>

            <div class="math-question">
                <i class="fas fa-calculator me-2"></i>
                <strong>Sigurnosna provjera:</strong><br>
                Koliko je <?php echo $question; ?> ?
            </div>

            <div class="form-floating">
                <input type="number" class="form-control" id="mathAnswer" name="mathAnswer" placeholder="Vaš odgovor" required>
                <label for="mathAnswer"><i class="fas fa-hashtag me-2"></i>Vaš odgovor</label>
                <i class="fas fa-calculator input-icon"></i>
            </div>

            <button type="submit" class="btn btn-login">
                <span class="loading-spinner spinner-border spinner-border-sm me-2" role="status"></span>
                <i class="fas fa-sign-in-alt me-2"></i>Prijavite se
            </button>

            <input type="hidden" name="action" value="user_login">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('user_login_nonce'); ?>">
        </form>

        <div class="footer-text">
            <i class="fas fa-shield-alt me-1"></i>
            Sigurna prijava - URS BiH sistem
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('userLoginForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const button = this.querySelector('button[type="submit"]');
            const spinner = button.querySelector('.loading-spinner');
            const messageDiv = document.getElementById('loginMessage');

            // Show loading state
            button.disabled = true;
            spinner.classList.add('show');
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Prijavljivanje...';

            // Hide previous messages
            messageDiv.classList.add('d-none');

            const formData = new FormData(this);

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.className = 'alert alert-success alert-custom';
                    messageDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + data.data.message;
                    messageDiv.classList.remove('d-none');

                    // Redirect after successful login
                    setTimeout(() => {
                        if (data.data.redirect_url) {
                            window.location.href = data.data.redirect_url;
                        } else {
                            window.location.href = '<?php echo home_url('/user-dashboard/'); ?>';
                        }
                    }, 1500);
                } else {
                    messageDiv.className = 'alert alert-danger alert-custom';
                    messageDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>' + data.data;
                    messageDiv.classList.remove('d-none');

                    // Reset math question on error
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.className = 'alert alert-danger alert-custom';
                messageDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Greška pri povezivanju sa serverom.';
                messageDiv.classList.remove('d-none');
            })
            .finally(() => {
                // Reset button state
                button.disabled = false;
                spinner.classList.remove('show');
                button.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Prijavite se';
            });
        });

        // Auto-focus first input
        document.getElementById('username').focus();
    </script>
</body>
</html>