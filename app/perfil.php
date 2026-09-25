<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ./login.php');
    exit;
}

require_once __DIR__ . '/../config/db/connection.php';

$message = '';
$messageType = 'success';
$userId = (int)$_SESSION['user_id'];
$cssVersion = filemtime(__DIR__ . '/../styles/styles.css');

$stmt = $conn->prepare('SELECT id, nombre, apellido, email, edad, premium, usuario, contraseña, foto FROM usuarios WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header('Location: ./login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = trim($_POST['name'] ?? '');
    $newLastname = trim($_POST['lastname'] ?? '');
    $newEmail = trim(strtolower($_POST['email'] ?? ''));
    $newAge = (int)($_POST['age'] ?? 0);
    $newUsername = trim($_POST['username'] ?? '');
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newName === '' || $newLastname === '' || $newEmail === '' || $newAge <= 0 || $newUsername === '') {
        $message = 'Completa todos los campos del perfil.';
        $messageType = 'error';
    } else {
        $photoPath = $user['foto'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK && $_FILES['photo']['size'] > 0) {
            $dir = __DIR__ . '/../uploads/usuarios';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowed, true)) {
                $message = 'Formato de foto no permitido. Usa JPG, PNG o WEBP.';
                $messageType = 'error';
            } else {
                $fileName = 'user_' . $userId . '_' . time() . '.' . $ext;
                $target = $dir . '/' . $fileName;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                    $photoPath = 'uploads/usuarios/' . $fileName;
                    if (!empty($user['foto']) && $user['foto'] !== $photoPath && file_exists(__DIR__ . '/../' . $user['foto'])) {
                        unlink(__DIR__ . '/../' . $user['foto']);
                    }
                }
            }
        }

        if ($messageType !== 'error') {
            $hashedPassword = $user['contraseña'];
            if ($newPassword !== '') {
                if ($newPassword !== $confirmPassword) {
                    $message = 'La nueva contraseña y su confirmación no coinciden.';
                    $messageType = 'error';
                } else {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                }
            }

            if ($messageType !== 'error') {
                $check = $conn->prepare('SELECT id FROM usuarios WHERE (email = ? OR usuario = ?) AND id != ? LIMIT 1');
                $check->bind_param('ssi', $newEmail, $newUsername, $userId);
                $check->execute();
                $exists = $check->get_result()->fetch_assoc();

                if ($exists) {
                    $message = 'El correo o usuario ya existen para otro usuario.';
                    $messageType = 'error';
                } else {
                    $stmt = $conn->prepare('UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, edad = ?, usuario = ?, contraseña = ?, foto = ? WHERE id = ?');
                    $stmt->bind_param('sssisisi', $newName, $newLastname, $newEmail, $newAge, $newUsername, $hashedPassword, $photoPath, $userId);

                    if ($stmt->execute()) {
                        $_SESSION['user_name'] = $newName;
                        $_SESSION['user_lastname'] = $newLastname;
                        $_SESSION['user_email'] = $newEmail;
                        $_SESSION['user_usuario'] = $newUsername;
                        $user['foto'] = $photoPath;
                        $message = 'Perfil actualizado correctamente.';
                        $messageType = 'success';
                    } else {
                        $message = 'No se pudo actualizar el perfil.';
                        $messageType = 'error';
                    }
                }
            }
        }
    }
}

$profilePhoto = !empty($user['foto']) ? '../' . $user['foto'] : '../assets/default-user.svg';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HireWise | Perfil</title>
    <link rel="stylesheet" href="../styles/styles.css?v=<?php echo $cssVersion; ?>">
</head>
<body class="dashboard-page">
    <header class="dashboard-header">
        <div class="dashboard-brand">
            <span class="brand-mark"><span></span></span>
            <span>HireWise</span>
        </div>

        <nav class="dashboard-nav">
            <a href="./profile-panel.php">Dashboard</a>
            <a href="./perfil.php" class="active">Perfil</a>
            <a href="./empresa.php">Empresa</a>
            <a href="./public-directory.php">Público</a>
        </nav>

        <div class="dashboard-user">
            <span class="user-pill"><?php echo htmlspecialchars($user['premium'] == 1 ? 'Premium' : 'Standard', ENT_QUOTES, 'UTF-8'); ?></span>
            <a href="logout.php" class="btn btn-ghost">Salir</a>
        </div>
    </header>

    <main class="crud-shell">
        <section class="crud-card">
            <div class="crud-header">
                <div class="mini-avatar">
                    <img src="<?php echo htmlspecialchars($profilePhoto, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto de perfil">
                </div>
                <div>
                    <p class="eyebrow">Cuenta</p>
                    <h1>Mi perfil</h1>
                </div>
            </div>

            <?php if ($message !== '') : ?>
                <div class="auth-alert <?php echo $messageType === 'success' ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form class="crud-form" method="post" enctype="multipart/form-data">
                <div class="field-grid three-columns">
                    <div class="field">
                        <span>Foto</span>
                        <div class="file-upload-box profile-upload">
                            <div class="worker-thumb profile-thumb" aria-hidden="true">
                                <img src="<?php echo htmlspecialchars($profilePhoto, ENT_QUOTES, 'UTF-8'); ?>" alt="Preview">
                            </div>
                            <input type="file" name="photo" accept="image/*">
                        </div>
                    </div>

                    <div class="field">
                        <span>Nombre</span>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>

                    <div class="field">
                        <span>Apellido</span>
                        <input type="text" name="lastname" value="<?php echo htmlspecialchars($user['apellido'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                </div>

                <div class="field-grid two-columns">
                    <div class="field">
                        <span>Usuario</span>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($user['usuario'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>

                    <div class="field">
                        <span>Email</span>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                </div>

                <div class="field-grid two-columns">
                    <div class="field">
                        <span>Edad</span>
                        <input type="number" name="age" min="18" max="80" value="<?php echo htmlspecialchars((string)$user['edad'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>

                    <div class="field">
                        <span>Nueva contraseña</span>
                        <input type="password" name="password" placeholder="Deja vacío para conservarla">
                    </div>
                </div>

                <div class="field">
                    <span>Confirmar contraseña</span>
                    <input type="password" name="confirm_password" placeholder="Repite la nueva contraseña">
                </div>

                <div class="crud-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="profile-panel.php" class="btn btn-ghost">Volver</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
