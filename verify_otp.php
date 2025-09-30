<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $enteredOtp = trim($_POST['otp']);

    
    $stmt = $pdo->prepare("SELECT otp FROM users WHERE email = ? AND is_verified = 0");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['otp'] == $enteredOtp) {
        
        $updateStmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE email = ?");
        $updateStmt->execute([$email]);

        
        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Account Activated</title>
            <!-- Font Awesome -->
            <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css' rel='stylesheet'>
            <!-- Poppins Font -->
            <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap' rel='stylesheet'>
            <!-- Custom CSS -->
            <style>
                /* الألوان الأساسية */
                :root {
                    --primary-color: #FF6F61; /* برتقالي */
                    --secondary-color: #FFFFFF; /* أبيض */
                    --accent-color: #F4F4F4; /* رمادي فاتح */
                    --error-color: #FF4D4D; /* أحمر */
                    --success-color: #4CAF50; /* أخضر */
                }

                body {
                    font-family: 'Poppins', sans-serif;
                    background: var(--accent-color);
                    color: #333;
                    min-height: 100vh;
                    margin: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    overflow: hidden;
                }

                .container {
                    width: 90%;
                    max-width: 400px;
                    background: var(--secondary-color);
                    border-radius: 20px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                    padding: 40px;
                    text-align: center;
                }

                .logo {
                    width: 80px;
                    margin-bottom: 20px;
                }

                h2 {
                    color: var(--success-color);
                    margin-bottom: 20px;
                }

                p {
                    font-size: 14px;
                    color: #666;
                }

                img {
                    width: 200px;
                    margin-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <img src='images/orange-logo.png' alt='Website Logo' class='logo'>
                <h2>✅ Your account has been activated successfully!</h2>
                <img src='images/sucess.png' alt='Success'>
                <p>You will be redirected to the login page...</p>
            </div>
            <script>setTimeout(function(){ window.location.href = 'login.php'; }, 3000);</script>
        </body>
        </html>";
        exit;
    } else {
        $msg = "❌ Invalid verification code, please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        
        :root {
            --primary-color: #FF6F61; 
            --secondary-color: #FFFFFF; 
            --accent-color: #F4F4F4; 
            --error-color: #FF4D4D; 
            --success-color: #4CAF50; 
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--accent-color);
            color: #333;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .container {
            width: 90%;
            max-width: 400px;
            background: var(--secondary-color);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
        }

        .logo {
            width: 80px;
            margin-bottom: 20px;
        }

        h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .email-info {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }

        .email-info strong {
            color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-control {
            width: 92%;
            padding: 12px 15px;
            border: 2px solid var(--accent-color);
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            display: block;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .form-control::placeholder {
            color: #999;
        }

        .btn-primary {
            background: var(--primary-color);
            color: var(--secondary-color);
            border: none;
            border-radius: 10px;
            padding: 12px;
            width: 100%;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn-primary:hover {
            background: #FF4A3D; 
        }

        .message {
            color: var(--error-color);
            font-size: 14px;
            margin-top: 10px;
        }

        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <img src="images/orange-logo.png" alt="Website Logo" class="logo">
        <h2>OTP Verification</h2>
        <?php if (isset($msg)) echo "<p class='message'>$msg</p>"; ?>
        <div class="email-info">
            A verification code has been sent to: <strong><?php echo htmlspecialchars($_GET['email'] ?? ''); ?></strong>
        </div>
        <form method="POST" action="">
            <div class="form-group">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
                <input type="text" name="otp" class="form-control" placeholder="Enter OTP" required>
            </div>
            <button type="submit" class="btn btn-primary">Verify</button>
        </form>
    </div>
</body>
</html>