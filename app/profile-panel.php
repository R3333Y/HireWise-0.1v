<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ./login.php');
    exit;
}

require_once __DIR__ . '/../config/db/connection.php';

$userId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare('SELECT id, nombre, apellido, email, edad, premium, usuario FROM usuarios WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    session_destroy();
    header('Location: ./login.php');
    exit;
}

$premiumLabel = $user['premium'] == 1 ? 'Premium' : 'Standard';
$cssVersion = filemtime(__DIR__ . '/../styles/styles.css');

$countryRegions = [
    'Argentina' => ['Buenos Aires', 'Córdoba', 'Santa Fe', 'Mendoza', 'Neuquén'],
    'Chile' => ['Santiago', 'Valparaíso', 'Concepción', 'Biobío', 'Antofagasta'],
    'Colombia' => ['Bogotá D.C.', 'Antioquia', 'Atlántico', 'Bolívar', 'Boyacá', 'Caldas', 'Caquetá', 'Cauca', 'Cesar', 'Chocó', 'Córdoba', 'Cundinamarca', 'Guainía', 'Guaviare', 'Huila', 'La Guajira', 'Magdalena', 'Meta', 'Nariño', 'Norte de Santander', 'Putumayo', 'Quindío', 'Risaralda', 'San Andrés y Providencia', 'Santander', 'Sucre', 'Tolima', 'Valle del Cauca', 'Vaupés', 'Vichada', 'Arauca', 'Casanare'],
    'México' => ['Ciudad de México', 'Jalisco', 'Nuevo León', 'Guadalajara', 'Monterrey'],
    'Perú' => ['Lima', 'Arequipa', 'Cusco', 'Trujillo', 'Piura'],
    'España' => ['Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Bilbao'],
    'Uruguay' => ['Montevideo', 'Canelones', 'Maldonado', 'Salto', 'Rivera'],
];

$companyData = null;
$workerData = null;
$companyMessage = '';
$workerMessage = '';

$companyQuery = $conn->prepare('SELECT * FROM empresas WHERE id_propietario = ? LIMIT 1');
$companyQuery->bind_param('i', $userId);
$companyQuery->execute();
$companyData = $companyQuery->get_result()->fetch_assoc();

$workerQuery = $conn->prepare('SELECT * FROM trabajadores WHERE id_usuario = ? LIMIT 1');
$workerQuery->bind_param('i', $userId);
$workerQuery->execute();
$workerData = $workerQuery->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['company_name'])) {
    $companyName = trim($_POST['company_name'] ?? '');
    $companyCountry = trim($_POST['company_country'] ?? '');
    $companyProvince = trim($_POST['company_province'] ?? '');
    $companyCity = trim($_POST['company_city'] ?? '');
    $companyAddress = trim($_POST['company_address'] ?? '');
    $companyEmail = trim(strtolower($_POST['company_email'] ?? ''));
    $selectedPlan = $_POST['plan'] ?? 'free';

    if ($companyName === '' || $companyCountry === '' || $companyProvince === '' || $companyCity === '' || $companyAddress === '' || $companyEmail === '') {
        $companyMessage = 'Completa nombre, país, provincia, ciudad, dirección y email de la empresa.';
    } else {
        $companyPhoto = $companyData['foto'] ?? null;
        if (isset($_FILES['company_photo']) && $_FILES['company_photo']['error'] === UPLOAD_ERR_OK && $_FILES['company_photo']['size'] > 0) {
            $dir = __DIR__ . '/../uploads/empresas';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['company_photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed, true)) {
                $companyMessage = 'La foto de la empresa debe ser JPG, PNG o WEBP.';
            } else {
                $fileName = 'empresa_' . $userId . '_' . time() . '.' . $ext;
                $target = $dir . '/' . $fileName;
                if (move_uploaded_file($_FILES['company_photo']['tmp_name'], $target)) {
                    $companyPhoto = 'uploads/empresas/' . $fileName;
                    if (!empty($companyData['foto']) && $companyData['foto'] !== $companyPhoto && file_exists(__DIR__ . '/../' . $companyData['foto'])) {
                        unlink(__DIR__ . '/../' . $companyData['foto']);
                    }
                }
            }
        }

        if ($companyMessage === '') {
            $fullAddress = trim($companyAddress . ', ' . $companyCity . ', ' . $companyProvince . ', ' . $companyCountry);
            $planValue = ['free' => 0, 'starter' => 1, 'growth' => 2, 'scale' => 3][$selectedPlan] ?? 0;

            if ($companyData) {
                $update = $conn->prepare('UPDATE empresas SET nombre_empresa = ?, email = ?, pais = ?, provincia = ?, ciudad = ?, `dirección` = ?, premium = ?, foto = ? WHERE id_propietario = ?');
                $update->bind_param('ssssssisi', $companyName, $companyEmail, $companyCountry, $companyProvince, $companyCity, $fullAddress, $planValue, $companyPhoto, $userId);
                $update->execute();
            } else {
                $insert = $conn->prepare('INSERT INTO empresas (id_propietario, nombre_empresa, email, pais, provincia, ciudad, `dirección`, premium, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $insert->bind_param('issssssis', $userId, $companyName, $companyEmail, $companyCountry, $companyProvince, $companyCity, $fullAddress, $planValue, $companyPhoto);
                $insert->execute();
            }

            $companyQuery->execute();
            $companyData = $companyQuery->get_result()->fetch_assoc();
            $_SESSION['company'] = [
                'name' => $companyName,
                'country' => $companyCountry,
                'province' => $companyProvince,
                'city' => $companyCity,
                'address' => $fullAddress,
                'email' => $companyEmail,
                'plan' => $selectedPlan,
            ];
            $companyMessage = 'Empresa guardada correctamente en la base de datos.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['worker_email'])) {
    $workerEmail = trim(strtolower($_POST['worker_email'] ?? ''));
    $workerAge = (int)($_POST['worker_age'] ?? 0);
    $workerStudies = trim($_POST['worker_studies'] ?? '');
    $workerExperience = trim($_POST['worker_experience'] ?? '');
    $workerSelfPerception = trim($_POST['worker_self_perception'] ?? '');

    if ($workerEmail === '' || $workerAge <= 0 || $workerStudies === '' || $workerExperience === '' || $workerSelfPerception === '') {
        $workerMessage = 'Completa todos los datos del trabajador.';
    } else {
        $workerPhoto = $workerData['foto'] ?? null;
        if (isset($_FILES['worker_photo']) && $_FILES['worker_photo']['error'] === UPLOAD_ERR_OK && $_FILES['worker_photo']['size'] > 0) {
            $dir = __DIR__ . '/../uploads/trabajadores';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['worker_photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed, true)) {
                $workerMessage = 'La foto del trabajador debe ser JPG, PNG o WEBP.';
            } else {
                $fileName = 'trabajador_' . $userId . '_' . time() . '.' . $ext;
                $target = $dir . '/' . $fileName;
                if (move_uploaded_file($_FILES['worker_photo']['tmp_name'], $target)) {
                    $workerPhoto = 'uploads/trabajadores/' . $fileName;
                    if (!empty($workerData['foto']) && $workerData['foto'] !== $workerPhoto && file_exists(__DIR__ . '/../' . $workerData['foto'])) {
                        unlink(__DIR__ . '/../' . $workerData['foto']);
                    }
                }
            }
        }

        if ($workerMessage === '') {
            if ($workerData) {
                $update = $conn->prepare('UPDATE trabajadores SET foto = ?, email = ?, edad = ?, estudios = ?, experiencia = ?, autopercepcion = ? WHERE id_usuario = ?');
                $update->bind_param('ssisssi', $workerPhoto, $workerEmail, $workerAge, $workerStudies, $workerExperience, $workerSelfPerception, $userId);
                $update->execute();
            } else {
                $insert = $conn->prepare('INSERT INTO trabajadores (id_usuario, foto, email, edad, estudios, experiencia, autopercepcion) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $insert->bind_param('ississs', $userId, $workerPhoto, $workerEmail, $workerAge, $workerStudies, $workerExperience, $workerSelfPerception);
                $insert->execute();
            }

            $workerQuery->execute();
            $workerData = $workerQuery->get_result()->fetch_assoc();
            $workerMessage = 'Perfil de trabajador guardado correctamente en la base de datos.';
        }
    }
}

$joinData = $conn->prepare('SELECT u.id, u.nombre, u.apellido, u.email, u.usuario, e.id_empresa, e.nombre_empresa, e.email AS empresa_email, e.pais, e.provincia, e.ciudad, e.`dirección`, e.premium AS empresa_plan, e.foto AS empresa_foto, t.id_trabajador, t.foto AS trabajador_foto, t.email AS trabajador_email, t.edad AS trabajador_edad, t.estudios, t.experiencia, t.autopercepcion FROM usuarios u LEFT JOIN empresas e ON e.id_propietario = u.id LEFT JOIN trabajadores t ON t.id_usuario = u.id WHERE u.id = ? LIMIT 1');
$joinData->bind_param('i', $userId);
$joinData->execute();
$joinedUserData = $joinData->get_result()->fetch_assoc();

$defaultSetupRole = !empty($companyData) ? 'company' : (!empty($workerData) ? 'worker' : 'company');
$showCompanySetup = empty($companyData) && empty($workerData);
$hasCompany = !empty($companyData);
$dashboardStats = [
    'candidatos' => $hasCompany ? '1.248' : '0',
    'matches' => $hasCompany ? '84%' : '0%',
    'procesos' => $hasCompany ? '36' : '0',
    'tiempo' => $hasCompany ? '4.2d' : '0d',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HireWise | Profile Panel</title>
    <meta name="description" content="Panel principal de HireWise.">
    <link rel="stylesheet" href="../styles/styles.css?v=<?php echo $cssVersion; ?>">
</head>
<body class="dashboard-page">
    <header class="dashboard-header">
        <div class="dashboard-brand">
            <span class="brand-mark" aria-hidden="true">
                <span></span>
            </span>
            <span>HireWise</span>
        </div>

        <nav class="dashboard-nav" aria-label="Navegación principal del panel">
            <a href="./profile-panel.php" class="active">Dashboard</a>
            <a href="./perfil.php">Perfil</a>
            <a href="./empresa.php">Empresa</a>
            <a href="./public-directory.php">Público</a>
        </nav>

        <div class="dashboard-user">
            <span class="user-pill"><?php echo htmlspecialchars($premiumLabel, ENT_QUOTES, 'UTF-8'); ?></span>
            <a href="logout.php" class="btn btn-ghost">Salir</a>
        </div>
    </header>

    <?php if ($showCompanySetup) : ?>
        <main class="setup-shell">
            <section class="company-setup-card" aria-label="Registro de empresa">
                <div class="setup-header">
                    <p class="eyebrow">Configuración inicial</p>
                    <h1>Registra tu empresa</h1>
                    <p>Antes de ver tus métricas, completa la información de la compañía y selecciona tu plan.</p>
                </div>

                <?php if ($companyMessage !== '') : ?>
                    <div class="auth-alert <?php echo strpos($companyMessage, 'correctamente') !== false ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($companyMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <?php if ($workerMessage !== '') : ?>
                    <div class="auth-alert <?php echo strpos($workerMessage, 'correctamente') !== false ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($workerMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="role-picker" aria-label="Tipo de registro">
                    <button type="button" class="role-btn <?php echo $defaultSetupRole === 'company' ? 'active' : ''; ?>" data-role="company">Empresa</button>
                    <button type="button" class="role-btn <?php echo $defaultSetupRole === 'worker' ? 'active' : ''; ?>" data-role="worker">Trabajador</button>
                </div>

                <div class="panel-role-area">
                    <form class="company-form panel-form <?php echo $defaultSetupRole === 'company' ? 'active' : ''; ?>" method="post" action="profile-panel.php" enctype="multipart/form-data">
                        <div class="field-grid three-columns">
                            <div class="field">
                                <span>Nombre</span>
                                <input type="text" name="company_name" value="<?php echo htmlspecialchars($_POST['company_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nombre de la empresa" required>
                            </div>

                            <div class="field">
                                <span>País</span>
                                <select id="company_country" name="company_country" required>
                                    <option value="">Selecciona</option>
                                    <?php foreach (array_keys($countryRegions) as $countryName) : ?>
                                        <option value="<?php echo htmlspecialchars($countryName, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($_POST['company_country'] ?? '') === $countryName) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($countryName, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="field">
                                <span>Provincia</span>
                                <select id="company_province" name="company_province" required disabled>
                                    <option value="">Selecciona</option>
                                </select>
                            </div>
                        </div>

                        <div class="field-grid two-columns">
                            <div class="field">
                                <span>Foto de empresa</span>
                                <div class="file-upload-box">
                                    <div class="company-thumb" aria-hidden="true">HW</div>
                                    <input type="file" name="company_photo" accept="image/*">
                                </div>
                            </div>

                            <div class="field">
                                <span>Email</span>
                                <input type="email" name="company_email" value="<?php echo htmlspecialchars($_POST['company_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="empresa@correo.com" required>
                            </div>
                        </div>

                        <div class="field-grid two-columns">
                            <div class="field">
                                <span>Ciudad</span>
                                <input type="text" name="company_city" value="<?php echo htmlspecialchars($_POST['company_city'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ciudad" required>
                            </div>

                            <div class="field">
                                <span>Dirección</span>
                                <input type="text" name="company_address" value="<?php echo htmlspecialchars($_POST['company_address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Calle 123, barrio, etc. (incluye la ciudad)" required>
                            </div>
                        </div>

                        <div class="plan-section">
                            <div class="plan-head">
                                <span>Selecciona tu plan</span>
                            </div>

                            <div class="plan-grid">
                                <label class="plan-option">
                                    <input type="radio" name="plan" value="free" checked>
                                    <span class="plan-name">Free</span>
                                    <strong>$0</strong>
                                    <small>Ideal para empezar.</small>
                                    <ul>
                                        <li>1 proyecto</li>
                                        <li>Acceso básico</li>
                                        <li>Soporte comunitario</li>
                                    </ul>
                                </label>

                                <label class="plan-option">
                                    <input type="radio" name="plan" value="starter">
                                    <span class="plan-name">Starter</span>
                                    <strong>$29</strong>
                                    <small>Para equipos pequeños.</small>
                                    <ul>
                                        <li>5 proyectos</li>
                                        <li>Analítica básica</li>
                                        <li>Soporte prioritario</li>
                                    </ul>
                                </label>

                                <label class="plan-option">
                                    <input type="radio" name="plan" value="growth">
                                    <span class="plan-name">Growth</span>
                                    <strong>$79</strong>
                                    <small>Para empresas en expansión.</small>
                                    <ul>
                                        <li>Proyectos ilimitados</li>
                                        <li>Integraciones</li>
                                        <li>IA aplicada</li>
                                    </ul>
                                </label>

                                <label class="plan-option">
                                    <input type="radio" name="plan" value="scale">
                                    <span class="plan-name">Scale</span>
                                    <strong>$149</strong>
                                    <small>Para operación completa.</small>
                                    <ul>
                                        <li>Todo en Growth</li>
                                        <li>Equipo completo</li>
                                        <li>Atención personalizada</li>
                                    </ul>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full">Guardar empresa</button>
                    </form>

                    <form class="worker-form panel-form <?php echo $defaultSetupRole === 'worker' ? 'active' : ''; ?>" method="post" action="profile-panel.php" enctype="multipart/form-data">
                        <div class="worker-preview" aria-label="Previsualización para trabajador">
                            <div class="worker-thumb" aria-hidden="true"><?php echo htmlspecialchars(substr($user['nombre'], 0, 1) ?: 'T', ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="worker-copy">
                                <strong>Perfil de trabajador</strong>
                                <span><?php echo !empty($workerData) ? 'Actualiza tu información profesional.' : 'Completa tu información profesional.'; ?></span>
                            </div>
                        </div>

                        <div class="field-grid two-columns">
                            <div class="field">
                                <span>Foto</span>
                                <div class="file-upload-box">
                                    <div class="worker-thumb" aria-hidden="true"><?php echo htmlspecialchars(substr($user['nombre'], 0, 1) ?: 'T', ENT_QUOTES, 'UTF-8'); ?></div>
                                    <input type="file" name="worker_photo" accept="image/*">
                                </div>
                            </div>

                            <div class="field">
                                <span>Email</span>
                                <input type="email" name="worker_email" value="<?php echo htmlspecialchars($workerData['email'] ?? $_POST['worker_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="trabajador@correo.com">
                            </div>
                        </div>

                        <div class="field-grid two-columns">
                            <div class="field">
                                <span>Edad</span>
                                <input type="number" name="worker_age" min="18" max="80" value="<?php echo htmlspecialchars((string)($workerData['edad'] ?? $_POST['worker_age'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="25">
                            </div>

                            <div class="field">
                                <span>Estudios</span>
                                <input type="text" name="worker_studies" value="<?php echo htmlspecialchars($workerData['estudios'] ?? $_POST['worker_studies'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ingeniería, Licenciatura, etc.">
                            </div>
                        </div>

                        <div class="field">
                            <span>Experiencia</span>
                            <textarea name="worker_experience" rows="4" placeholder="Describe tu experiencia laboral más relevante..."><?php echo htmlspecialchars($workerData['experiencia'] ?? $_POST['worker_experience'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <div class="field">
                            <span>Autopercepción</span>
                            <textarea name="worker_self_perception" rows="5" placeholder="Cuéntanos cómo te describirías profesionalmente..."><?php echo htmlspecialchars($workerData['autopercepcion'] ?? $_POST['worker_self_perception'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full"><?php echo !empty($workerData) ? 'Actualizar perfil de trabajador' : 'Guardar perfil de trabajador'; ?></button>
                    </form>
                </div>
            </section>
        </main>
    <?php else : ?>
        <main class="dashboard-shell">
            <aside class="dashboard-sidebar" aria-label="Menú lateral">
                <div class="profile-card">
                    <div class="profile-avatar"><?php echo htmlspecialchars(substr($user['nombre'], 0, 1), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div>
                        <h2><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p><?php echo htmlspecialchars($user['usuario'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>

                <?php if ($hasCompany) : ?>
                    <div class="company-summary">
                        <span>Empresa</span>
                        <strong><?php echo htmlspecialchars($companyData['nombre_empresa'] ?? 'Sin empresa', ENT_QUOTES, 'UTF-8'); ?></strong>
                        <small><?php echo htmlspecialchars((($companyData['premium'] ?? 0) >= 1) ? 'Premium' : 'Standard', ENT_QUOTES, 'UTF-8'); ?> plan</small>
                    </div>
                <?php else : ?>
                    <a href="empresa.php" class="company-summary company-summary-empty" aria-label="Crear empresa">
                        <span>Empresa</span>
                        <strong>Sin empresa</strong>
                        <small>Crear empresa</small>
                    </a>
                <?php endif; ?>

                <ul class="menu-list">
                    <li><a href="profile-panel.php" class="active"><span>Overview</span><span>01</span></a></li>
                    <li><a href="perfil.php"><span>Perfil</span><span>02</span></a></li>
                    <li><a href="empresa.php"><span>Empresa</span><span>03</span></a></li>
                    <li><a href="#"><span>Talento</span><span>12</span></a></li>
                    <li><a href="#"><span>Procesos</span><span>04</span></a></li>
                </ul>
            </aside>

            <section class="dashboard-main" aria-label="Información principal del software">
                <div class="dashboard-topbar">
                    <div>
                        <p class="eyebrow">Software principal</p>
                        <h1>Dashboard</h1>
                    </div>
                    <button type="button" class="btn btn-primary">Nueva evaluación</button>
                </div>

                <div class="stat-grid">
                    <article class="stat-card">
                        <span>Candidatos</span>
                        <strong><?php echo htmlspecialchars($dashboardStats['candidatos'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    </article>

                    <article class="stat-card">
                        <span>Matches</span>
                        <strong><?php echo htmlspecialchars($dashboardStats['matches'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    </article>

                    <article class="stat-card">
                        <span>Procesos</span>
                        <strong><?php echo htmlspecialchars($dashboardStats['procesos'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    </article>

                    <article class="stat-card">
                        <span>Tiempo medio</span>
                        <strong><?php echo htmlspecialchars($dashboardStats['tiempo'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    </article>
                </div>

                <div class="content-grid">
                    <article class="data-panel">
                        <h3>Progreso del pipeline</h3>
                        <ul class="progress-list">
                            <li class="progress-item">
                                <span>Screening</span>
                                <div class="progress-bar"><span style="width: <?php echo $hasCompany ? '82%' : '0%'; ?>;"></span></div>
                                <span class="meta"><?php echo $hasCompany ? '82%' : '0%'; ?></span>
                            </li>
                            <li class="progress-item">
                                <span>Entrevista</span>
                                <div class="progress-bar"><span style="width: <?php echo $hasCompany ? '64%' : '0%'; ?>;"></span></div>
                                <span class="meta"><?php echo $hasCompany ? '64%' : '0%'; ?></span>
                            </li>
                            <li class="progress-item">
                                <span>Oferta</span>
                                <div class="progress-bar"><span style="width: <?php echo $hasCompany ? '48%' : '0%'; ?>;"></span></div>
                                <span class="meta"><?php echo $hasCompany ? '48%' : '0%'; ?></span>
                            </li>
                        </ul>
                    </article>

                    <article class="data-panel">
                        <h3>Próximas tareas</h3>
                        <ul class="task-list">
                            <li class="task-item">
                                <span>Revisión de perfil</span>
                                <span class="task-badge">Hoy</span>
                            </li>
                            <li class="task-item">
                                <span>Entrevista final</span>
                                <span class="task-badge">Mañana</span>
                            </li>
                            <li class="task-item">
                                <span>Feedback de cliente</span>
                                <span class="task-badge">Viernes</span>
                            </li>
                        </ul>
                    </article>

                    <article class="data-panel wide">
                        <h3>Actividad reciente</h3>
                        <div class="table-list">
                            <div class="table-row header">
                                <span>Usuario</span>
                                <span>Etapa</span>
                                <span>Estado</span>
                                <span>Score</span>
                            </div>

                            <div class="table-row">
                                <span>María López</span>
                                <span>Entrevista</span>
                                <span>Activo</span>
                                <span>92</span>
                            </div>

                            <div class="table-row">
                                <span>Diego Ruiz</span>
                                <span>Screening</span>
                                <span>En revisión</span>
                                <span>87</span>
                            </div>

                            <div class="table-row">
                                <span>Andrea Sol</span>
                                <span>Oferta</span>
                                <span>Listo</span>
                                <span>95</span>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </main>
    <?php endif; ?>

    <script>
        const countryProvinces = <?php echo json_encode($countryRegions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const countrySelect = document.getElementById('company_country');
        const provinceSelect = document.getElementById('company_province');
        const roleButtons = document.querySelectorAll('.role-btn');
        const panelForms = {
            company: document.querySelector('.company-form'),
            worker: document.querySelector('.worker-form')
        };

        function updateProvinceOptions() {
            const country = countrySelect.value;
            const selectedProvince = '<?php echo htmlspecialchars($_POST['company_province'] ?? '', ENT_QUOTES, 'UTF-8'); ?>';

            provinceSelect.innerHTML = '<option value="">Selecciona</option>';
            provinceSelect.disabled = !country;

            if (!country) {
                return;
            }

            const provinces = countryProvinces[country] || [];

            provinces.forEach((province) => {
                const option = document.createElement('option');
                option.value = province;
                option.textContent = province;
                if (province === selectedProvince) {
                    option.selected = true;
                }
                provinceSelect.appendChild(option);
            });
        }

        function switchRole(role) {
            roleButtons.forEach((button) => {
                const isActive = button.dataset.role === role;
                button.classList.toggle('active', isActive);
            });

            Object.entries(panelForms).forEach(([key, form]) => {
                if (!form) return;
                form.classList.toggle('active', key === role);
            });
        }

        const initialRole = '<?php echo $defaultSetupRole; ?>';
        if (initialRole && panelForms[initialRole]) {
            switchRole(initialRole);
        }

        function bindImagePreview(inputSelector, previewSelector, fallbackText) {
            const input = document.querySelector(inputSelector);
            const preview = document.querySelector(previewSelector);

            if (!input || !preview) {
                return;
            }

            input.addEventListener('change', function () {
                const file = this.files && this.files[0];
                if (!file) {
                    preview.style.background = 'radial-gradient(circle at 30% 30%, rgba(196, 181, 253, 0.28), rgba(139, 92, 246, 0.2) 20%, rgba(10,7,16,0.9) 72%)';
                    preview.textContent = fallbackText;
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (event) {
                    preview.style.backgroundImage = 'url("' + event.target.result + '")';
                    preview.style.backgroundSize = 'cover';
                    preview.style.backgroundPosition = 'center';
                    preview.style.backgroundRepeat = 'no-repeat';
                    preview.textContent = '';
                };
                reader.readAsDataURL(file);
            });
        }

        if (countrySelect) {
            countrySelect.addEventListener('change', updateProvinceOptions);
            updateProvinceOptions();
        }

        roleButtons.forEach((button) => {
            button.addEventListener('click', () => switchRole(button.dataset.role));
        });

        bindImagePreview('input[name="company_photo"]', '.company-thumb', 'HW');
        bindImagePreview('input[name="worker_photo"]', '.worker-thumb', 'JD');
    </script>
</body>
</html>
