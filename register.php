<?php
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ---------------- OTP Verification ----------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['otp'])) {
    $email = trim($_POST['email']);
    $enteredOtp = trim($_POST['otp']);

    $stmt = $pdo->prepare("SELECT otp FROM users WHERE email = ? AND is_verified = 0");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['otp'] == $enteredOtp) {
        $updateStmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE email = ?");
        $updateStmt->execute([$email]);
        $success = true;
    } else {
        $otp_error = "❌ Invalid verification code, please try again.";
    }
}

// ---------------- Registration Handling ----------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = 'student';
    $otp = rand(100000, 999999);
    $is_verified = 0;

    // التحقق من قوة الباسورد (6 أرقام + 2 حروف على الأقل)
    if (!preg_match('/^(?=.*[A-Za-z]{2,})(?=.*\d{6,}).+$/', $password)) {
        $errors['password'] = "⚠️ Password must contain at least 6 numbers and 2 letters.";
    } else {
        $password = password_hash($password, PASSWORD_BCRYPT);

        // التأكد إن الاسم مش مستخدم
        $stmt = $pdo->prepare("SELECT name FROM users WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->rowCount() > 0) {
            $errors['name'] = "⚠️ This name is already used!";
        } else {
            // التأكد إن الإيميل مش مستخدم
            $stmt = $pdo->prepare("SELECT email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $errors['email'] = "⚠️ Email is already used!";
            } else {
                // إدخال البيانات
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, otp, is_verified) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$name, $email, $password, $role, $otp, $is_verified])) {
                    // إرسال الإيميل
                    if (sendOtpEmail($email, $otp)) {
                        $otp_sent = true;
                        $otp_email = $email;
                    } else {
                        $errors['email'] = "⚠️ Registration successful, but failed to send email.";
                    }
                } else {
                    $errors['general'] = "❌ Registration failed! Please try again.";
                }
            }
        }
    }
}

// ---------------- Function to Send OTP ----------------
function sendOtpEmail($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'abdrhmanesamse06@gmail.com'; 
        $mail->Password = 'ajalxrihlehuvdwp';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->setFrom('abdrhmanesamse06@gmail.com', 'blackhorse');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Black Horse - OTP Verification';
        $mail->Body = '
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; background-color: #f7f7f7; margin: 0; padding: 0; }
    .container { max-width: 500px; margin: 30px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .header { background: #4CAF50; padding: 20px; text-align: center; color: #fff; }
    .content { padding: 20px; text-align: center; }
    .otp { font-size: 24px; font-weight: bold; color: #4CAF50; margin: 20px 0; }
    .footer { background: #f1f1f1; padding: 10px; text-align: center; font-size: 12px; color: #777; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h2>BlackHorse Verification</h2>
    </div>
    <div class="content">
      <p>Hello,</p>
      <p>Thank you for signing up with <b>BlackHorse</b>. Please use the following verification code to complete your registration:</p>
      <div class="otp">'.$otp.'</div>
      <p>This code is valid for 10 minutes.</p>
    </div>
    <div class="footer">
      &copy; '.date('Y').' BlackHorse. All rights reserved.
    </div>
  </div>
</body>
</html>
';
        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join SnapTask</title>
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

        .signup-container {
            width: 100%;
            max-width: 1100px;
            background: var(--light);
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 700px;
        }

        .welcome-section {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .welcome-section::after {
            content: '';
            position: absolute;
            bottom: -150px;
            left: -150px;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 60px;
            z-index: 1;
        }

        .logo-icon img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-text {
            font-size: 22px;
            font-weight: 700;
        }

        .welcome-content {
            z-index: 1;
            margin-bottom: 40px;
        }

        .welcome-content h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.3;
        }

        .welcome-content p {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 10px;
            max-width: 90%;
        }

        .features {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 40px;
            z-index: 1;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
            backdrop-filter: blur(5px);
            transition: var(--transition);
        }

        .feature:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
        }

        .feature i {
            font-size: 18px;
            color: var(--light);
        }

        .interactive-section {
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--light);
            position: relative;
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

        .progress-container {
            margin-bottom: 30px;
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--gray);
        }

        .progress-bar {
            height: 6px;
            background: var(--light-gray);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress {
            height: 100%;
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            border-radius: 3px;
            transition: width 0.5s ease;
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

        .navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .error-message {
            color: var(--danger);
            font-size: 14px;
            margin-top: 5px;
            display: block;
        }

        .success-message {
            color: var(--success);
            font-size: 14px;
            margin-top: 5px;
            display: block;
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

        .otp-inputs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .otp-input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 24px;
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .otp-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
            outline: none;
        }

        .resend-link {
            text-align: center;
            margin-top: 20px;
            color: var(--gray);
            font-size: 14px;
        }

        .resend-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .resend-link a:hover {
            text-decoration: underline;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 42px;
            color: var(--gray);
            cursor: pointer;
        }

        /* Slideshow Styles */
        .slideshow-container {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
            border-radius: var(--border-radius-xl) 0 0 var(--border-radius-xl);
        }

        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 40px;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        .slide.active {
            opacity: 1;
        }

        .slide-content {
            position: relative;
            z-index: 2;
            max-width: 90%;
        }

        .slide h2 {
            font-size: 28px;
            margin-bottom: 15px;
        }

        .slide p {
            font-size: 16px;
            opacity: 0.9;
        }

        .slide-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 50%, rgba(0,0,0,0.1) 100%);
        }

        .slideshow-controls {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 3;
        }

        .slideshow-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: var(--transition);
        }

        .slideshow-dot.active {
            background: var(--light);
            transform: scale(1.2);
        }

        @media (max-width: 992px) {
            .signup-container {
                grid-template-columns: 1fr;
                min-height: auto;
            }
            
            .welcome-section {
                padding: 40px 30px;
                text-align: center;
                display: none; /* Hide on mobile for simplicity */
            }
            
            .logo {
                justify-content: center;
            }
            
            .welcome-content p {
                max-width: 100%;
                margin-left: auto;
                margin-right: auto;
            }
            
            .features {
                align-items: center;
            }
            
            .feature {
                width: 100%;
                max-width: 350px;
            }

            .slideshow-container {
                display: none; /* Hide slideshow on mobile */
            }
        }

        @media (max-width: 576px) {
            .signup-container {
                border-radius: var(--border-radius-lg);
            }
            
            .interactive-section {
                padding: 30px 20px;
            }
            
            .step-header h2 {
                font-size: 24px;
            }
            
            .otp-input {
                width: 40px;
                height: 50px;
                font-size: 20px;
            }
            
            .btn {
                padding: 12px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <!-- Slideshow Section -->
        <div class="slideshow-container">
            <div class="slide active" style="background-image: url('https://images.unsplash.com/photo-1542744173-8e7e53415bb0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');">
                <div class="slide-overlay"></div>
                <div class="slide-content">
                    <h2>Boost Your Productivity</h2>
                    <p>Manage your tasks efficiently with our powerful tools</p>
                </div>
            </div>
            <div class="slide" style="background-image: url('https://images.unsplash.com/photo-1522071820081-009f0129c71c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');">
                <div class="slide-overlay"></div>
                <div class="slide-content">
                    <h2>Collaborate with Teams</h2>
                    <p>Work together seamlessly on projects and tasks</p>
                </div>
            </div>
            <div class="slide" style="background-image: url('https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');">
                <div class="slide-overlay"></div>
                <div class="slide-content">
                    <h2>Track Your Progress</h2>
                    <p>Monitor your achievements and stay on target</p>
                </div>
            </div>
            <div class="slideshow-controls">
                <div class="slideshow-dot active" data-slide="0"></div>
                <div class="slideshow-dot" data-slide="1"></div>
                <div class="slideshow-dot" data-slide="2"></div>
            </div>
        </div>
        
        <div class="interactive-section">
            <?php if (isset($success)): ?>
                
                <div class="step active" id="step3">
                    <div class="confirmation">
                        <div class="confirmation-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3>Account Verified!</h3>
                        <p>Your account has been successfully activated</p>
                        <p>You can now log in and start using SnapTask</p>
                        
                        <a href="login.php" class="btn btn-primary btn-block" style="margin-top: 30px;">
                            Continue to Login <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            
            <?php elseif (isset($otp_sent)): ?>
                
                <div class="step active" id="step2">
                    <div class="progress-container">
                        <div class="progress-text">
                            <span>Step 2 of 2</span>
                            <span>Email Verification</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress" style="width: 100%"></div>
                        </div>
                    </div>
                    
                    <div class="step-header">
                        <h2>Verify Your Email</h2>
                        <p>We've sent a 6-digit code to <strong><?php echo htmlspecialchars($otp_email); ?></strong></p>
                    </div>
                    
                    <form method="POST" action="" id="otp-form">
                        <div class="input-group">
                            <label class="form-label">Enter Verification Code</label>
                            <div class="otp-inputs">
                                <input type="text" name="otp1" class="otp-input" maxlength="1" data-index="1" autofocus>
                                <input type="text" name="otp2" class="otp-input" maxlength="1" data-index="2">
                                <input type="text" name="otp3" class="otp-input" maxlength="1" data-index="3">
                                <input type="text" name="otp4" class="otp-input" maxlength="1" data-index="4">
                                <input type="text" name="otp5" class="otp-input" maxlength="1" data-index="5">
                                <input type="text" name="otp6" class="otp-input" maxlength="1" data-index="6">
                            </div>
                            <input type="hidden" name="otp" id="full-otp">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($otp_email); ?>">
                            <?php if (isset($otp_error)): ?>
                                <span class="error-message"><?php echo $otp_error; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="resend-link">
                            Didn't receive code? <a href="#" id="resend-otp">Resend</a> (30s)
                        </div>
                        
                        <div class="navigation">
                            <button type="button" class="btn btn-ghost" onclick="window.location.href='signup.php'">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button type="submit" class="btn btn-primary" id="verify-btn">
                                Verify Account <i class="fas fa-check"></i>
                            </button>
                        </div>
                    </form>
                </div>
            
            <?php else: ?>
                
                <div class="step active" id="step1">
                    <div class="progress-container">
                        <div class="progress-text">
                            <span>Step 1 of 2</span>
                            <span>Account Information</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress" style="width: 50%"></div>
                        </div>
                    </div>
                    
                    <div class="step-header">
                        <h2>Create Your Account</h2>
                        <p>Fill in your details to get started</p>
                    </div>
                    
                    <form method="POST" action="" id="signup-form">
                        <div class="input-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="John Doe" required
                                value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
                            <?php if (isset($errors['name'])): ?>
                                <span class="error-message"><?php echo $errors['name']; ?></span>
                            <?php endif; ?>
                        </div>

                        
                        <div class="input-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="your@email.com" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                            <?php if (isset($errors['email'])): ?>
                                <span class="error-message"><?php echo $errors['email']; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="input-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" 
                                   placeholder="At least 6 numbers + 2 letters" required>
                            <i class="fas fa-eye password-toggle" id="toggle-password"></i>
                            <div class="password-strength">
                                <div class="password-strength-fill" id="password-strength-fill"></div>
                            </div>
                            <div class="password-hint" id="password-hint">
                                Password must contain at least 6 numbers and 2 letters
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <span class="error-message"><?php echo $errors['password']; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            Continue to Verification <i class="fas fa-arrow-right"></i>
                        </button>
                        
                        <?php if (isset($errors['general'])): ?>
                            <span class="error-message" style="text-align: center; margin-top: 15px; display: block;">
                                <?php echo $errors['general']; ?>
                            </span>
                        <?php endif; ?>
                        
                        <div class="resend-link" style="margin-top: 20px; text-align: center;">
                            Already have an account? <a href="login.php">Log in</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Password strength functionality
        const passwordInput = document.getElementById('password');
        const strengthFill = document.getElementById('password-strength-fill');
        const passwordHint = document.getElementById('password-hint');
        
        if (passwordInput && strengthFill && passwordHint) {
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
                
                strengthFill.style.width = strength + '%';
                
                // Change color based on strength
                if (strength < 40) {
                    strengthFill.style.backgroundColor = 'var(--danger)';
                } else if (strength < 70) {
                    strengthFill.style.backgroundColor = 'var(--warning)';
                } else {
                    strengthFill.style.backgroundColor = 'var(--success)';
                }
                
                // Show hint if password is not empty
                passwordHint.style.display = password.length > 0 ? 'block' : 'none';
            });
            
            // Toggle password visibility
            const togglePassword = document.getElementById('toggle-password');
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
        
        // OTP input functionality
        const otpInputs = document.querySelectorAll('.otp-input');
        if (otpInputs.length > 0) {
            otpInputs.forEach((input, index) => {
                input.addEventListener('input', function() {
                    if (this.value.length === 1) {
                        if (index < otpInputs.length - 1) {
                            otpInputs[index + 1].focus();
                        }
                    }
                });
                
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value.length === 0) {
                        if (index > 0) {
                            otpInputs[index - 1].focus();
                        }
                    }
                });
            });
            
            // Combine OTP inputs into one field
            const otpForm = document.getElementById('otp-form');
            if (otpForm) {
                otpForm.addEventListener('submit', function(e) {
                    let fullOtp = '';
                    otpInputs.forEach(input => {
                        fullOtp += input.value;
                    });
                    document.getElementById('full-otp').value = fullOtp;
                });
            }
        }
        
        // Resend OTP functionality
        const resendLink = document.getElementById('resend-otp');
        if (resendLink) {
            resendLink.addEventListener('click', function(e) {
                e.preventDefault();
                
                alert('A new verification code has been sent to your email.');
                
                // Disable resend for 30 seconds
                this.style.pointerEvents = 'none';
                let seconds = 30;
                const timer = setInterval(() => {
                    this.parentElement.innerHTML = `Didn't receive code? <a href="#" id="resend-otp">Resend</a> (${seconds}s)`;
                    seconds--;
                    if (seconds < 0) {
                        clearInterval(timer);
                        this.parentElement.innerHTML = `Didn't receive code? <a href="#" id="resend-otp">Resend</a>`;
                    }
                }, 1000);
            });
        }
        
        // Focus on first OTP input
        if (document.querySelector('.otp-input')) {
            document.querySelector('.otp-input').focus();
        }

        // Slideshow functionality
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.slideshow-dot');
        let currentSlide = 0;
        
        function showSlide(n) {
            // Hide all slides
            slides.forEach(slide => {
                slide.classList.remove('active');
            });
            dots.forEach(dot => {
                dot.classList.remove('active');
            });
            
            // Show the selected slide
            slides[n].classList.add('active');
            dots[n].classList.add('active');
            currentSlide = n;
        }
        
        // Auto-advance slides every 3 seconds
        setInterval(() => {
            let nextSlide = (currentSlide + 1) % slides.length;
            showSlide(nextSlide);
        }, 3000);
        
        // Add click events to dots
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
            });
        });
    </script>
</body>
</html>