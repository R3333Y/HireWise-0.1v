<?php
session_start();
require_once __DIR__ . '/../config/db/connection.php';

$conn->query("CREATE TABLE IF NOT EXISTS proyectos (
    id_proyecto INT NOT NULL AUTO_INCREMENT,
    id_empresa INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT NOT NULL,
    ubicacion VARCHAR(150) NOT NULL,
    tipo VARCHAR(80) NOT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'abierto',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_proyecto),
    KEY idx_empresa (id_empresa),
    CONSTRAINT fk_proyectos_empresa FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$conn->query("CREATE TABLE IF NOT EXISTS postulaciones (
    id_postulacion INT NOT NULL AUTO_INCREMENT,
    id_trabajador INT NOT NULL,
    id_empresa INT NOT NULL,
    mensaje TEXT DEFAULT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'pendiente',
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_postulacion),
    KEY idx_trabajador (id_trabajador),
    KEY idx_empresa (id_empresa),
    UNIQUE KEY uq_postulacion (id_trabajador, id_empresa),
    CONSTRAINT fk_postulaciones_trabajador FOREIGN KEY (id_trabajador) REFERENCES trabajadores(id_trabajador) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_postulaciones_empresa FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$cssVersion = filemtime(__DIR__ . '/../styles/styles.css');
$message = '';
$messageType = 'success';

$userId = $_SESSION['user_id'] ?? null;
$userRole = null;
$companyProfile = null;
$workerProfile = null;

if ($userId) {
    $companyProfile = $conn->query('SELECT * FROM empresas WHERE id_propietario = ' . (int)$userId . ' LIMIT 1')->fetch_assoc();
    $workerProfile = $conn->query('SELECT * FROM trabajadores WHERE id_usuario = ' . (int)$userId . ' LIMIT 1')->fetch_assoc();

    if ($companyProfile) {
        $userRole = 'empresa';
    } elseif ($workerProfile) {
        $userRole = 'trabajador';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'apply' && $userId && $workerProfile) {
        $companyId = (int)($_POST['company_id'] ?? 0);
        $messageText = trim($_POST['message'] ?? '');

        if ($companyId <= 0) {
            $message = 'Debes seleccionar una empresa válida.';
            $messageType = 'error';
        } else {
            $existing = $conn->prepare('SELECT id_postulacion FROM postulaciones WHERE id_trabajador = ? AND id_empresa = ? LIMIT 1');
            $existing->bind_param('ii', $workerProfile['id_trabajador'], $companyId);
            $existing->execute();
            $existingResult = $existing->get_result();

            if ($existingResult->num_rows > 0) {
                $message = 'Ya enviaste una postulación a esta empresa.';
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare('INSERT INTO postulaciones (id_trabajador, id_empresa, mensaje, estado) VALUES (?, ?, ?, "pendiente")');
                $stmt->bind_param('iis', $workerProfile['id_trabajador'], $companyId, $messageText);
                if ($stmt->execute()) {
                    $message = 'Tu postulación fue enviada correctamente.';
                    $messageType = 'success';
                } else {
                    $message = 'No se pudo enviar la postulación en este momento.';
                    $messageType = 'error';
                }
            }
        }
    }

    if ($action === 'create_project' && $userId && $companyProfile) {
        $title = trim($_POST['project_title'] ?? '');
        $description = trim($_POST['project_description'] ?? '');
        $location = trim($_POST['project_location'] ?? '');
        $type = trim($_POST['project_type'] ?? '');

        if ($title === '' || $description === '' || $location === '' || $type === '') {
            $message = 'Completa todos los campos del proyecto.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare('INSERT INTO proyectos (id_empresa, titulo, descripcion, ubicacion, tipo, estado) VALUES (?, ?, ?, ?, ?, "abierto")');
            $stmt->bind_param('issss', $companyProfile['id_empresa'], $title, $description, $location, $type);
            if ($stmt->execute()) {
                $message = 'Proyecto abierto correctamente.';
                $messageType = 'success';
            } else {
                $message = 'No se pudo abrir el proyecto.';
                $messageType = 'error';
            }
        }
    }
}

$companies = $conn->query('SELECT e.*, u.nombre, u.apellido, u.usuario FROM empresas e INNER JOIN usuarios u ON u.id = e.id_propietario ORDER BY e.id_empresa DESC');
$workers = $conn->query('SELECT t.*, u.nombre, u.apellido, u.usuario, u.email AS usuario_email FROM trabajadores t INNER JOIN usuarios u ON u.id = t.id_usuario ORDER BY t.id_trabajador DESC');
$projects = $conn->query('SELECT p.*, e.nombre_empresa FROM proyectos p INNER JOIN empresas e ON e.id_empresa = p.id_empresa ORDER BY p.id_proyecto DESC LIMIT 12');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HireWise | Directorio público</title>
    <link rel="stylesheet" href="../styles/styles.css?v=<?php echo $cssVersion; ?>">
</head>
<body class="dashboard-page">
    <header class="dashboard-header">
        <div class="dashboard-brand">
            <span class="brand-mark" aria-hidden="true"><span></span></span>
            <span>HireWise</span>
        </div>

        <nav class="dashboard-nav" aria-label="Navegación principal">
            <a href="./profile-panel.php">Dashboard</a>
            <a href="./perfil.php">Perfil</a>
            <a href="./empresa.php">Empresa</a>
            <a href="./public-directory.php" class="active">Público</a>
        </nav>

        <div class="dashboard-user">
            <a href="logout.php" class="btn btn-ghost">Salir</a>
        </div>
    </header>

    <main class="public-directory-shell">
        <section class="directory-hero">
            <div>
                <p class="eyebrow">Directorio público</p>
                <h1>Empresa y talento en un mismo ecosistema</h1>
            </div>

            <?php if ($message !== '') : ?>
                <div class="auth-alert <?php echo $messageType === 'success' ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </section>

        <?php if ($userRole === 'empresa') : ?>
            <section class="public-role-card">
                <div class="role-card-header">
                    <div>
                        <p class="eyebrow">Acción disponible</p>
                        <h2>Crear un proyecto para tu empresa</h2>
                    </div>
                    <span class="user-pill">Empresa</span>
                </div>

                <form class="project-form" method="post" action="public-directory.php">
                    <input type="hidden" name="action" value="create_project">
                    <div class="field-grid three-columns">
                        <div class="field">
                            <span>Título</span>
                            <input type="text" name="project_title" placeholder="Ej. Desarrollador Full Stack" required>
                        </div>

                        <div class="field">
                            <span>Tipo de proyecto</span>
                            <input type="text" name="project_type" placeholder="Ej. Producto, marketing, backend" required>
                        </div>

                        <div class="field">
                            <span>Ubicación</span>
                            <input type="text" name="project_location" placeholder="Ciudad o remoto" required>
                        </div>
                    </div>

                    <div class="field">
                        <span>Descripción</span>
                        <textarea name="project_description" rows="4" placeholder="Describe el proyecto, requisitos y perfil buscado..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Abrir proyecto</button>
                </form>
            </section>
        <?php elseif ($userRole === 'trabajador') : ?>
            <section class="public-role-card">
                <div class="role-card-header">
                    <div>
                        <p class="eyebrow">Acción disponible</p>
                        <h2>Postúlate a una empresa</h2>
                    </div>
                    <span class="user-pill">Trabajador</span>
                </div>
                <p class="muted-copy">Selecciona una empresa y envía tu intención de participación con un breve mensaje.</p>
            </section>
        <?php elseif ($userId) : ?>
            <section class="public-role-card">
                <div class="role-card-header">
                    <div>
                        <p class="eyebrow">Perfil pendiente</p>
                        <h2>Completa tu perfil para interactuar</h2>
                    </div>
                </div>
                <p class="muted-copy">Necesitas tener un perfil de empresa o trabajador para abrir proyectos o postularte.</p>
                <div class="quick-actions">
                    <a href="./empresa.php" class="btn btn-primary">Configurar empresa</a>
                    <a href="./profile-panel.php" class="btn btn-ghost">Volver al panel</a>
                </div>
            </section>
        <?php else : ?>
            <section class="public-role-card">
                <div class="role-card-header">
                    <div>
                        <p class="eyebrow">Acceso</p>
                        <h2>Inicia sesión para interactuar</h2>
                    </div>
                </div>
                <p class="muted-copy">Puedes ver el directorio público, pero para postularte o abrir proyectos debes entrar a tu cuenta.</p>
                <div class="quick-actions">
                    <a href="login.php" class="btn btn-primary">Iniciar sesión</a>
                </div>
            </section>
        <?php endif; ?>

        <section class="directory-tabs">
            <button type="button" class="tab-btn active" data-tab="companies">Empresas</button>
            <button type="button" class="tab-btn" data-tab="workers">Trabajadores</button>
            <button type="button" class="tab-btn" data-tab="projects">Proyectos</button>
        </section>

        <section id="companies" class="directory-panel active">
            <div class="card-grid">
                <?php while ($company = $companies->fetch_assoc()) : ?>
                    <article class="profile-card public-card">
                        <div class="card-header">
                            <div class="mini-avatar">
                                <img src="<?php echo !empty($company['foto']) ? '../' . htmlspecialchars($company['foto'], ENT_QUOTES, 'UTF-8') : '../assets/default-company.svg'; ?>" alt="Logo de <?php echo htmlspecialchars($company['nombre_empresa'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div>
                                <h3><?php echo htmlspecialchars($company['nombre_empresa'] ?: 'Empresa', ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><?php echo htmlspecialchars($company['pais'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($company['provincia'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>

                        <ul class="mini-list">
                            <li><strong>Email:</strong> <?php echo htmlspecialchars($company['email'], ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Ciudad:</strong> <?php echo htmlspecialchars($company['ciudad'], ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Dirección:</strong> <?php echo htmlspecialchars($company['dirección'], ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Plan:</strong> <?php echo htmlspecialchars(['free' => 'Free', 'starter' => 'Starter', 'growth' => 'Growth', 'scale' => 'Scale'][$company['premium'] ?? 0] ?? 'Free', ENT_QUOTES, 'UTF-8'); ?></li>
                        </ul>

                        <?php if ($userRole === 'trabajador') : ?>
                            <form method="post" action="public-directory.php">
                                <input type="hidden" name="action" value="apply">
                                <input type="hidden" name="company_id" value="<?php echo (int)$company['id_empresa']; ?>">
                                <textarea name="message" rows="3" placeholder="Cuéntale a la empresa por qué te interesa..."></textarea>
                                <button type="submit" class="btn btn-primary btn-full">Postularme</button>
                            </form>
                        <?php else : ?>
                            <button type="button" class="btn btn-ghost btn-full" disabled>Perfil público</button>
                        <?php endif; ?>
                    </article>
                <?php endwhile; ?>
            </div>
        </section>

        <section id="workers" class="directory-panel">
            <div class="card-grid">
                <?php while ($worker = $workers->fetch_assoc()) : ?>
                    <article class="profile-card public-card">
                        <div class="card-header">
                            <div class="mini-avatar worker-avatar">
                                <img src="<?php echo !empty($worker['foto']) ? '../' . htmlspecialchars($worker['foto'], ENT_QUOTES, 'UTF-8') : '../assets/default-user.svg'; ?>" alt="Foto de <?php echo htmlspecialchars($worker['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div>
                                <h3><?php echo htmlspecialchars($worker['nombre'] . ' ' . $worker['apellido'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><?php echo htmlspecialchars($worker['usuario'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>

                        <ul class="mini-list">
                            <li><strong>Email:</strong> <?php echo htmlspecialchars($worker['usuario_email'], ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Edad:</strong> <?php echo (int)$worker['edad']; ?></li>
                            <li><strong>Estudios:</strong> <?php echo htmlspecialchars($worker['estudios'], ENT_QUOTES, 'UTF-8'); ?></li>
                        </ul>

                        <p class="worker-summary"><?php echo htmlspecialchars($worker['experiencia'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if ($userRole === 'empresa') : ?>
                            <button type="button" class="btn btn-primary btn-full">Abrir proyecto</button>
                        <?php else : ?>
                            <button type="button" class="btn btn-ghost btn-full" disabled>Ver perfil</button>
                        <?php endif; ?>
                    </article>
                <?php endwhile; ?>
            </div>
        </section>

        <section id="projects" class="directory-panel">
            <div class="card-grid">
                <?php while ($project = $projects->fetch_assoc()) : ?>
                    <article class="profile-card public-card project-card">
                        <div class="project-head">
                            <span class="project-badge"><?php echo htmlspecialchars($project['estado'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="project-type"><?php echo htmlspecialchars($project['tipo'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <h3><?php echo htmlspecialchars($project['titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><?php echo htmlspecialchars($project['descripcion'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <ul class="mini-list">
                            <li><strong>Empresa:</strong> <?php echo htmlspecialchars($project['nombre_empresa'], ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Ubicación:</strong> <?php echo htmlspecialchars($project['ubicacion'], ENT_QUOTES, 'UTF-8'); ?></li>
                        </ul>
                        <?php if ($userRole === 'trabajador') : ?>
                            <button type="button" class="btn btn-primary btn-full">Postularme</button>
                        <?php else : ?>
                            <button type="button" class="btn btn-ghost btn-full" disabled>Proyecto público</button>
                        <?php endif; ?>
                    </article>
                <?php endwhile; ?>
            </div>
        </section>
    </main>

    <script>
        const tabs = document.querySelectorAll('.tab-btn');
        const panels = document.querySelectorAll('.directory-panel');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(item => item.classList.toggle('active', item === tab));
                panels.forEach(panel => {
                    panel.classList.toggle('active', panel.id === tab.dataset.tab);
                });
            });
        });
    </script>
</body>
</html>
