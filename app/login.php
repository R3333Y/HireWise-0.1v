<?php
session_start();
require_once __DIR__ . '/../config/db/connection.php';

$siteTitle = 'HireWise | Iniciar sesión';
$mode = $_GET['mode'] ?? 'login';
$cssVersion = filemtime(__DIR__ . '/../styles/styles.css');

if (isset($_SESSION['user_id'])) {
    header('Location: ./profile-panel.php');
    exit;
}

$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'register') {
        $nombre = trim($_POST['name'] ?? '');
        $apellido = trim($_POST['lastname'] ?? '');
        $usuario = trim($_POST['username'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $edad = (int)($_POST['age'] ?? 0);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($nombre === '' || $apellido === '' || $usuario === '' || $email === '' || $edad <= 0 || $password === '' || $confirmPassword === '') {
            $message = 'Completa todos los campos para continuar.';
        } elseif (mb_strlen($usuario) < 3 || mb_strlen($usuario) > 15) {
            $message = 'El usuario debe tener entre 3 y 15 caracteres.';
        } elseif ($password !== $confirmPassword) {
            $message = 'Las contraseñas no coinciden.';
        } else {
            $check = $conn->prepare('SELECT id FROM usuarios WHERE email = ? OR usuario = ? LIMIT 1');
            $check->bind_param('ss', $email, $usuario);
            $check->execute();
            $existing = $check->get_result();

            if ($existing->num_rows > 0) {
                $message = 'El email o usuario ya están registrados.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('INSERT INTO usuarios (nombre, apellido, email, edad, premium, contraseña, usuario) VALUES (?, ?, ?, ?, 0, ?, ?)');
                $stmt->bind_param('sssiis', $nombre, $apellido, $email, $edad, $hashedPassword, $usuario);

                if ($stmt->execute()) {
                    $_SESSION['user_id'] = $conn->insert_id;
                    $_SESSION['user_name'] = $nombre;
                    $_SESSION['user_lastname'] = $apellido;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_usuario'] = $usuario;

                    header('Location: ./profile-panel.php');
                    exit;
                }

                $message = 'No se pudo crear la cuenta en este momento.';
            }
        }
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $message = 'Ingresa tu usuario y contraseña.';
        } else {
            $stmt = $conn->prepare('SELECT id, nombre, apellido, email, edad, premium, contraseña, usuario FROM usuarios WHERE usuario = ? OR email = ? LIMIT 1');
            $stmt->bind_param('ss', $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['contraseña'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nombre'];
                    $_SESSION['user_lastname'] = $user['apellido'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_usuario'] = $user['usuario'];
                    $_SESSION['user_premium'] = (int)$user['premium'];
                    $_SESSION['user_edad'] = (int)$user['edad'];

                    header('Location: ./profile-panel.php');
                    exit;
                }
            }

            $message = 'Credenciales incorrectas. Intenta nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode === 'register' ? 'HireWise | Crear cuenta' : $siteTitle; ?></title>
    <meta name="description" content="Inicia sesión en HireWise para gestionar evaluaciones y procesos de reclutamiento.">
    <link rel="stylesheet" href="../styles/styles.css?v=<?php echo $cssVersion; ?>">
</head>
<body class="auth-page">
    <header class="site-header">
        <a href="../index.php" class="brand" aria-label="HireWise inicio">
            <span class="brand-mark" aria-hidden="true">
                <span></span>
            </span>
            <span>HireWise</span>
        </a>

        <nav class="main-nav" aria-label="Navegacion principal">
            <a href="../index.php#inicio">Inicio</a>
            <a href="../index.php#plataforma">Plataforma</a>
            <a href="../index.php#analisis">Analisis IA</a>
            <a href="../index.php#contacto">Contacto</a>
        </nav>

        <div class="header-actions">
            <a href="login.php" class="btn btn-primary">Ingresar</a>
        </div>
    </header>

    <main class="auth-shell">
        <section class="auth-card auth-card-compact" aria-label="Formulario de acceso a HireWise">
            <div class="auth-brand-top">
                <span class="brand-mark" aria-hidden="true"><span></span></span>
                <span>HireWise</span>
            </div>

            <div class="auth-switch" aria-label="Selecciona tu tipo de acceso">
                <a href="login.php" class="switch-btn <?php echo $mode === 'login' ? 'active' : ''; ?>">Iniciar sesión</a>
                <a href="login.php?mode=register" class="switch-btn <?php echo $mode === 'register' ? 'active' : ''; ?>">Crear cuenta</a>
            </div>

            <?php if ($message !== '') : ?>
                <div class="auth-alert <?php echo $messageType; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($mode === 'register') : ?>
                <div class="auth-heading">
                    <p>Bienvenido</p>
                    <h1>Crear cuenta</h1>
                </div>

                <form class="auth-form" action="login.php?mode=register" method="post">
                    <div class="field-grid two-columns">
                        <div class="field">
                            <span>Nombre</span>
                            <input type="text" name="name" placeholder="Tu nombre" value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>

                        <div class="field">
                            <span>Apellido</span>
                            <input type="text" name="lastname" placeholder="Tu apellido" value="<?php echo htmlspecialchars($_POST['lastname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>

                    <div class="field">
                        <span>Usuario</span>
                        <input type="text" name="username" placeholder="usuario" maxlength="15" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>

                    <div class="field">
                        <span>Email</span>
                        <input type="email" name="email" placeholder="nombre@empresa.com" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>

                    <div class="field">
                        <span>Edad</span>
                        <input type="number" name="age" placeholder="18" min="18" value="<?php echo htmlspecialchars($_POST['age'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>

                    <div class="field-grid two-columns">
                        <div class="field">
                            <span>Contraseña</span>
                            <input type="password" name="password" placeholder="••••••••" required>
                        </div>

                        <div class="field">
                            <span>Confirmación</span>
                            <input type="password" name="confirm_password" placeholder="Repite tu contraseña" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Crear cuenta</button>
                </form>

                <p class="auth-footer">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
            <?php else : ?>
                <div class="auth-heading">
                    <p>Bienvenido</p>
                    <h1>Iniciar sesión</h1>
                </div>

                <form class="auth-form" action="login.php" method="post">
                    <div class="field">
                        <span>Usuario</span>
                        <input type="text" name="username" placeholder="Tu nombre de usuario" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>

                    <div class="field">
                        <span>Contraseña</span>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>

                    <div class="form-inline">
                        <label class="checkbox-row" for="remember">
                            <input type="checkbox" id="remember" name="remember">
                            <span>Recordarme</span>
                        </label>

                        <a href="#">Olvidé mi contraseña</a>
                    </div>

                    <button type="submit" class="btn btn-primary">Entrar</button>
                </form>

                <p class="auth-footer">¿No tienes cuenta? <a href="login.php?mode=register">Crear cuenta</a></p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
