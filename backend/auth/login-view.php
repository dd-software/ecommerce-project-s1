<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

iniciarSesionAdmin();

if (AuthMiddleware::estaAutenticado()) {
    header('Location: ' . getBackendPath() . '/views/dashboard.php');
    exit;
}

$error = $_SESSION['login_error'] ?? '';
$email = $_SESSION['login_email'] ?? '';

unset($_SESSION['login_error'], $_SESSION['login_email']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            font-weight: 700;
            color: #2d3748;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.5);
            border-color: #4299e1;
        }
        .btn-primary {
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            background: linear-gradient(to right, #4299e1, #3182ce);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(to right, #3182ce, #2b6cb0);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="bi bi-shield-lock-fill text-primary" style="font-size: 3rem;"></i>
            <h2>Panel Admin</h2>
            <p class="text-muted">Ingrese sus credenciales</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= e($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="<?= getBackendPath() ?>/auth/login.php" method="POST">
            <?= csrfField() ?>
            
            <div class="mb-3">
                <label for="email" class="form-label text-muted fw-semibold">Correo Electrónico</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required autofocus placeholder="admin@uct.cl">
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label text-muted fw-semibold">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent"><i class="bi bi-key"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                Iniciar Sesión <i class="bi bi-box-arrow-in-right ms-2"></i>
            </button>
            
            <div class="text-center mt-3">
                <a href="<?= getBasePath() ?>/" class="text-decoration-none text-muted small"><i class="bi bi-arrow-left"></i> Volver a la tienda</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
