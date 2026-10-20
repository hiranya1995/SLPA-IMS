<?php
session_start();
require_once 'config.php';

$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = $_POST['password'];

   
    $query = "SELECT u.*, d.division_name FROM `uer` u 
              JOIN `divisions` d ON u.division_id = d.id 
              WHERE u.`user name` = '$user' AND u.`password` = '$pass'";
    $result = mysqli_query($conn, $query);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        
        $_SESSION['user_id']       = $row['id'];
        $_SESSION['username']      = $row['user name'];
        $_SESSION['role']          = $row['role'];
        $_SESSION['division_id']   = $row['division_id'];     
        $_SESSION['division_name'] = $row['division_name'];   
        
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid credentials. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SLPA Inventory - Login</title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --navy-900: #0a1628;
        --blue-400: #2563eb;
        --steel-50: #f9fafb;
        --steel-600: #4b5563;
        --white: #ffffff;
        --red-500: #ef4444;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'IBM Plex Sans', sans-serif;
        background-color: var(--navy-900);
        height: 100vh;
        display: flex;
        overflow: hidden;
    }

    /* Left Side - Visual Branding */
    .login-side-visual {
        flex: 1.2;
        background: linear-gradient(rgba(10, 22, 40, 0.6), rgba(10, 22, 40, 0.6)), 
                    url('slpa-bg.png');
        background-size: cover;
        background-position: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 4rem;
        color: white;
    }

    .visual-content h1 { font-size: 3rem; font-weight: 700; margin-bottom: 1rem; letter-spacing: -0.02em; }
    .visual-content p { font-size: 1.2rem; opacity: 0.9; max-width: 500px; line-height: 1.5; }

    /* Right Side - Form Panel */
    .login-side-form {
        flex: 0.8;
        background: var(--white);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .login-card { width: 100%; max-width: 400px; }
    .login-header { text-align: center; margin-bottom: 2rem; }
    .login-header h2 { color: var(--navy-900); font-size: 24px; font-weight: 700; }

    .error-box {
        background: #fee2e2;
        color: var(--red-500);
        padding: 12px;
        border-radius: 8px;
        font-size: 14px;
        margin-bottom: 1.5rem;
        border: 1px solid #fecaca;
        text-align: center;
    }

    .form-group { margin-bottom: 1.5rem; }
    .form-label { display: block; font-size: 12px; font-weight: 600; color: var(--steel-600); margin-bottom: 8px; text-transform: uppercase; }
    
    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 1.5px solid #e5e7eb;
        border-radius: 8px;
        font-size: 15px;
    }

    .btn-primary {
        width: 100%;
        padding: 12px;
        background: var(--blue-400);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 15px;
    }

    .divider { display: flex; align-items: center; margin: 2rem 0; color: #9ca3af; font-size: 12px; }
    .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #e5e7eb; }
    .divider span { padding: 0 10px; }

    .btn-outline {
        display: block;
        width: 100%;
        padding: 12px;
        text-align: center;
        text-decoration: none;
        color: var(--navy-900);
        border: 1.5px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
    }

    @media (max-width: 900px) {
        .login-side-visual { display: none; }
        .login-side-form { flex: 1; }
    }
</style>
</head>
<body>

    <div class="login-side-visual">
        <div class="visual-content">
            <h1>SLPA — IMS</h1>
            <p>Inventory Management System. Secure access for hardware tracking across Enterprise Multi-Divisions.</p>
        </div>
    </div>

    <div class="login-side-form">
        <div class="login-card">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Please enter your credentials</p>
            </div>
            
            <?php if($error): ?>
                <div class="error-box"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" autocomplete="off">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" autocomplete="off" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" autocomplete="new-password" required>
                </div>

                <button type="submit" class="btn-primary">Sign In</button>
            </form>

            <div class="divider"><span>OR</span></div>
            <a href="user_view.php" class="btn-outline">Access as Normal User</a>
        </div>
    </div>
    
</body>
</html>