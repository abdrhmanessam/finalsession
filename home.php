<?php
session_start();

$host = '127.0.0.1';
$dbname = 'elearn';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $user = null;
    $groups = [];
    $courses = [];

    if (isset($_SESSION['user_id'])) {
        // بيانات المستخدم
        $stmt = $pdo->prepare("SELECT id, name, profile_pic FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // الجروبات اللي مسجل فيها
        $sql = "SELECT g.id as group_id, g.name as group_name, c.title as course_title
                FROM enrollments e
                JOIN course_groups g ON e.group_id = g.id
                JOIN courses c ON e.course_id = c.id
                WHERE e.user_id = ? AND e.status = 'active'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // كل الكورسات
    $sql = "SELECT id, title, description FROM courses ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eLearn - منصة التعلم الإلكتروني</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --accent: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --success: #4cc9f0;
            --border-radius: 8px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Tajawal', sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        header {
            background-color: white;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .logo span {
            color: var(--secondary);
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-profile {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 2px solid var(--primary);
            cursor: pointer;
            transition: var(--transition);
        }

        .user-profile:hover {
            transform: scale(1.05);
        }

        .user-profile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-name {
            font-weight: 500;
            color: var(--dark);
        }

        .btn {
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-align: center;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
        }

        /* Hero Section */
        .hero {
            padding: 80px 0;
            background: linear-gradient(135deg, #4361ee 0%, #7209b7 100%);
            color: white;
            text-align: center;
        }

        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 30px;
            opacity: 0.9;
        }

        /* Sections */
        .section {
            padding: 80px 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title h2 {
            font-size: 2.2rem;
            color: var(--dark);
            margin-bottom: 15px;
            font-weight: 700;
        }

        .section-title p {
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            background: rgba(67, 97, 238, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.3rem;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .card-description {
            color: var(--gray);
            margin-bottom: 20px;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        /* Groups Section */
        .groups-section {
            background-color: var(--light);
        }

        .groups-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .group-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow);
            border-right: 4px solid var(--primary);
        }

        .group-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .group-course {
            color: var(--gray);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Courses List */
        .courses-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .course-item {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .course-info {
            flex: 1;
        }

        .course-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .course-description {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }

        /* Footer */
        footer {
            background-color: var(--dark);
            color: white;
            padding: 60px 0 30px;
            text-align: center;
        }

        .copyright {
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #adb5bd;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .user-name {
                display: none;
            }

            .hero {
                padding: 60px 0;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .section {
                padding: 60px 0;
            }

            .cards-grid,
            .groups-list,
            .courses-list {
                grid-template-columns: 1fr;
            }

            .course-item {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .hero h1 {
                font-size: 1.6rem;
            }

            .section-title h2 {
                font-size: 1.8rem;
            }

            .card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">e<span>Learn</span></a>
            
            <?php if($user): ?>
            <div class="user-section">
                <div class="user-info">
                    <a href="profile.php" class="user-profile">
                        <img src="<?php echo $user['profile_pic'] ?: 'images/default-avatar.png'; ?>" alt="User Profile">
                    </a>
                    <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                </div>
                <a href="logout.php" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i>
                    تسجيل الخروج
                </a>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <?php if(!$user): ?>
    <!-- Hero Section للزائر -->
    <section class="hero">
        <div class="container">
            <h1>أهلاً بك في eLearn</h1>
            <p>منصة التعلم الإلكتروني الرائدة لتطوير مهاراتك وتحقيق أهدافك التعليمية</p>
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="signup.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i>
                    إنشاء حساب جديد
                </a>
                <a href="login.php" class="btn" style="background: white; color: var(--primary);">
                    <i class="fas fa-sign-in-alt"></i>
                    تسجيل الدخول
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section">
        <div class="container">
            <div class="section-title">
                <h2>لماذا تختار eLearn؟</h2>
                <p>نوفر لك أفضل تجربة تعلم مع منصتنا المبتكرة</p>
            </div>
            
            <div class="cards-grid">
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <div>
                            <h3 class="card-title">كورسات متنوعة</h3>
                            <p class="card-description">مجموعة واسعة من الكورسات في مختلف المجالات</p>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h3 class="card-title">مجموعات تعليمية</h3>
                            <p class="card-description">انضم لمجموعات تفاعلية مع زملاء الدراسة</p>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-laptop"></i>
                        </div>
                        <div>
                            <h3 class="card-title">تعلم في أي وقت</h3>
                            <p class="card-description">ادرس من أي مكان وفي الوقت المناسب لك</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php else: ?>
    <!-- Hero Section للمستخدم -->
    <section class="hero">
        <div class="container">
            <h1>مرحباً <?php echo htmlspecialchars($user['name']); ?>! 👋</h1>
            <p>استمر في رحلتك التعليمية واكتشف كورسات جديدة لتطوير مهاراتك</p>
        </div>
    </section>

    <!-- User Groups Section -->
    <section class="section groups-section">
        <div class="container">
            <div class="section-title">
                <h2>مجموعاتك التعليمية</h2>
                <p>المجموعات التي أنت مسجل فيها حالياً</p>
            </div>
            
            <?php if(empty($groups)): ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <h3>لا توجد مجموعات</h3>
                <p>أنت غير مسجل في أي مجموعة حالياً. يمكنك التسجيل في إحدى الكورسات المتاحة أدناه.</p>
            </div>
            <?php else: ?>
            <div class="groups-list">
                <?php foreach($groups as $group): ?>
                <div class="group-card">
                    <div class="group-name"><?php echo htmlspecialchars($group['group_name']); ?></div>
                    <div class="group-course">
                        <i class="fas fa-book"></i>
                        <?php echo htmlspecialchars($group['course_title']); ?>
                    </div>
                    <a href="group.php?id=<?php echo $group['group_id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-door-open"></i>
                        دخول للمجموعة
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Available Courses Section -->
    <section class="section">
        <div class="container">
            <div class="section-title">
                <h2>الكورسات المتاحة</h2>
                <p>اختر من بين مجموعة واسعة من الكورسات التعليمية</p>
            </div>
            
            <?php if(empty($courses)): ?>
            <div class="empty-state">
                <i class="fas fa-book"></i>
                <h3>لا توجد كورسات</h3>
                <p>لا توجد كورسات متاحة حالياً.</p>
            </div>
            <?php else: ?>
            <div class="courses-list">
                <?php foreach($courses as $course): ?>
                <div class="course-item">
                    <div class="course-info">
                        <div class="course-title"><?php echo htmlspecialchars($course['title']); ?></div>
                        <?php if($course['description']): ?>
                        <div class="course-description"><?php echo htmlspecialchars($course['description']); ?></div>
                        <?php endif; ?>
                    </div>
                    <a href="request-enrollment.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        التسجيل في مجموعة
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="copyright">
                &copy; 2025 eLearn. جميع الحقوق محفوظة.
            </div>
        </div>
    </footer>
</body>
</html>