<?php
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$email_sent = false;
$reset_success = false;
$error = '';

// التحقق من وجود الأعمدة المطلوبة في الجدول
try {
    $check_columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
    if ($check_columns->rowCount() == 0) {
        // إضافة الأعمدة إذا لم تكن موجودة
        $pdo->exec("ALTER TABLE users 
                   ADD COLUMN reset_token VARCHAR(64) NULL,
                   ADD COLUMN reset_expiry DATETIME NULL");
    }
} catch (PDOException $e) {
    $error = "Database configuration error. Please contact administrator.";
}

// Handle password reset request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && empty($error)) {
    $email = trim($_POST['email']);
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Generate reset token
        $reset_token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));
        
        // Store token in database
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?");
        if ($stmt->execute([$reset_token, $expiry, $email])) {
            // Send reset email
            if (sendResetEmail($email, $user['first_name'], $reset_token)) {
                $email_sent = true;
            } else {
                $error = "Failed to send reset email. Please try again.";
            }
        } else {
            $error = "Error generating reset token. Please try again.";
        }
    } else {
        $error = "No account found with that email address.";
    }
}

// Handle password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password']) && empty($error)) {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!preg_match('/^(?=.*[A-Za-z]{2,})(?=.*\d{6,}).+$/', $password)) {
        $error = "Password must contain at least 6 numbers and 2 letters.";
    } else {
        // Check if token is valid and not expired
        $stmt = $pdo->prepare("SELECT id, reset_expiry FROM users WHERE reset_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && strtotime($user['reset_expiry']) > time()) {
            // Update password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
            if ($stmt->execute([$hashed_password, $user['id']])) {
                $reset_success = true;
            } else {
                $error = "Error resetting password. Please try again.";
            }
        } else {
            $error = "Invalid or expired reset token. Please request a new reset link.";
        }
    }
}

// Handle token verification (when user clicks reset link)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['token']) && empty($error)) {
    $token = $_GET['token'];
    
    // Check if token is valid and not expired
    $stmt = $pdo->prepare("SELECT id, reset_expiry FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || strtotime($user['reset_expiry']) <= time()) {
        $error = "Invalid or expired reset token. Please request a new reset link.";
    }
}

function sendResetEmail($email, $name, $token) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'abdrhmanesamse06@gmail.com'; 
        $mail->Password = 'ajalxrihlehuvdwp';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . "?token=$token";

        $mail->setFrom('abdrhmanesamse06@gmail.com', 'SnapTask');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request - SnapTask';
        $mail->Body = '
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body { font-family: Arial, sans-serif; background-color: #f7f7f7; margin: 0; padding: 0; }
    .container { max-width: 500px; margin: 30px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .header { background: #4361ee; padding: 20px; text-align: center; color: #fff; }
    .content { padding: 20px; text-align: center; }
    .footer { background: #f1f1f1; padding: 10px; text-align: center; font-size: 12px; color: #777; }
    .btn { display: inline-block; padding: 12px 24px; background: #4361ee; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    @media only screen and (max-width: 600px) {
        .container { width: 90%; }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h2>Password Reset</h2>
    </div>
    <div class="content">
      <p>Hello ' . htmlspecialchars($name) . ',</p>
      <p>You requested to reset your password for your SnapTask account.</p>
      <p>Click the button below to reset your password:</p>
      <a href="' . $reset_link . '" class="btn">Reset Password</a>
      <p>This link will expire in 1 hour for security reasons.</p>
      <p>If you didn\'t request this reset, please ignore this email.</p>
    </div>
    <div class="footer">
      &copy; ' . date('Y') . ' SnapTask. All rights reserved.
    </div>
  </div>
</body>
</html>';

        return $mail->send();
    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SnapTask</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #e0e7ff;
            --primary-dark: #3a0ca3;
            --secondary: #f72585;
            --dark: #1A1D26;
            --light: #FFFFFF;
            --gray: #8A92A6;
            --light-gray: #F5F7FA;
            --lighter-gray: #f8fafc;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --border-radius-xl: 24px;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-md: 0 10px 15px rgba(0,0,0,0.1);
            --shadow-lg: 0 20px 25px rgba(0,0,0,0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--lighter-gray);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            line-height: 1.6;
        }

        .password-container {
            width: 100%;
            max-width: 500px;
            background: var(--light);
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            padding: 40px;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 30px;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .logo-text {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
        }

        .step {
            display: none;
            animation: fadeIn 0.5s ease-out;
        }

        .step.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .step-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .step-header h2 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .step-header p {
            color: var(--gray);
            font-size: 15px;
        }

        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 15px;
            transition: var(--transition);
            background: var(--lighter-gray);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
            background: var(--light);
        }

        .password-strength {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
            position: relative;
        }

        .password-strength-fill {
            height: 100%;
            width: 0%;
            background: var(--danger);
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        .password-hint {
            font-size: 12px;
            color: var(--gray);
            margin-top: 4px;
            display: none;
        }

        .btn {
            padding: 15px 25px;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-ghost {
            background: transparent;
            color: var(--primary);
        }

        .btn-ghost:hover {
            background: var(--primary-light);
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        .error-message {
            color: var(--danger);
            font-size: 14px;
            margin-top: 5px;
            display: block;
            text-align: center;
            padding: 10px;
            background: rgba(239, 68, 68, 0.1);
            border-radius: var(--border-radius);
            border-left: 3px solid var(--danger);
        }

        .success-message {
            color: var(--success);
            font-size: 14px;
            margin-top: 5px;
            display: block;
            text-align: center;
            padding: 10px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: var(--border-radius);
            border-left: 3px solid var(--success);
        }

        .confirmation {
            text-align: center;
            padding: 20px 0;
        }

        .confirmation-icon {
            font-size: 80px;
            color: var(--success);
            margin-bottom: 20px;
        }

        .confirmation h3 {
            font-size: 28px;
            margin-bottom: 15px;
            color: var(--dark);
        }

        .confirmation p {
            color: var(--gray);
            margin-bottom: 5px;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 42px;
            color: var(--gray);
            cursor: pointer;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 576px) {
            .password-container {
                padding: 30px 20px;
                border-radius: var(--border-radius-lg);
            }
            
            .step-header h2 {
                font-size: 24px;
            }
            
            .btn {
                padding: 12px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="password-container">
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="logo-text">blackhorse</div>
        </div>

        <?php if (isset($error) && $error): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($reset_success): ?>
            <!-- Step 3: Password reset successful -->
            <div class="step active" id="step3">
                <div class="confirmation">
                    <div class="confirmation-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Password Reset Successfully!</h3>
                    <p>Your password has been updated successfully</p>
                    <p>You can now log in with your new password</p>
                    
                    <a href="login.php" class="btn btn-primary btn-block" style="margin-top: 30px;">
                        Continue to Login <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

        <?php elseif (isset($_GET['token'])): ?>
            <!-- Step 2: Reset password form -->
            <div class="step active" id="step2">
                <div class="step-header">
                    <h2>Create New Password</h2>
                    <p>Enter your new password below</p>
                </div>
                
                <form method="POST" action="" id="reset-form">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                    
                    <div class="input-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" id="password" class="form-control" 
                               placeholder="At least 6 numbers + 2 letters" required>
                        <i class="fas fa-eye password-toggle" id="toggle-password"></i>
                        <div class="password-strength">
                            <div class="password-strength-fill" id="password-strength-fill"></div>
                        </div>
                        <div class="password-hint" id="password-hint">
                            Password must contain at least 6 numbers and 2 letters
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" 
                               placeholder="Confirm your password" required>
                    </div>
                    
                    <button type="submit" name="reset_password" class="btn btn-primary btn-block">
                        Reset Password <i class="fas fa-lock"></i>
                    </button>
                </form>
                
                <div class="back-link">
                    <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
                </div>
            </div>

        <?php elseif ($email_sent): ?>
            <!-- Step 1b: Email sent confirmation -->
            <div class="step active" id="step1b">
                <div class="confirmation">
                    <div class="confirmation-icon" style="color: var(--primary);">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Check Your Email</h3>
                    <p>We've sent a password reset link to your email address</p>
                    <p>The link will expire in 1 hour for security reasons</p>
                    
                    <div class="back-link" style="margin-top: 30px;">
                        <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Step 1: Request reset email -->
            <div class="step active" id="step1">
                <div class="step-header">
                    <h2>Reset Your Password</h2>
                    <p>Enter your email address to receive a reset link</p>
                </div>
                
                <form method="POST" action="" id="request-form">
                    <div class="input-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" 
                               placeholder="your@email.com" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        Send Reset Link <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
                
                <div class="back-link">
                    <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Password strength functionality
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            const strengthFill = document.getElementById('password-strength-fill');
            const passwordHint = document.getElementById('password-hint');
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                // Check for numbers (at least 6)
                if (password.match(/(.*[0-9].*[0-9].*[0-9].*[0-9].*[0-9].*[0-9])/)) {
                    strength += 40;
                }
                
                // Check for letters (at least 2)
                if (password.match(/(.*[A-Za-z].*[A-Za-z])/)) {
                    strength += 30;
                }
                
                // Check for special characters
                if (password.match(/[^A-Za-z0-9]/)) {
                    strength += 20;
                }
                
                // Check for length
                if (password.length >= 8) {
                    strength += 10;
                }
                
                if (strengthFill) {
                    strengthFill.style.width = strength + '%';
                    
                    // Change color based on strength
                    if (strength < 40) {
                        strengthFill.style.backgroundColor = 'var(--danger)';
                    } else if (strength < 70) {
                        strengthFill.style.backgroundColor = 'var(--warning)';
                    } else {
                        strengthFill.style.backgroundColor = 'var(--success)';
                    }
                }
                
                // Show hint if password is not empty
                if (passwordHint) {
                    passwordHint.style.display = password.length > 0 ? 'block' : 'none';
                }
            });
            
            // Toggle password visibility
            const togglePassword = document.getElementById('toggle-password');
            if (togglePassword) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            }
        }
    </script>
</body>
</html>