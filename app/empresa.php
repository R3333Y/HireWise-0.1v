<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ./login.php');
    exit;
}

require_once __DIR__ . '/../config/db/connection.php';

$userId = (int)$_SESSION['user_id'];
$cssVersion = filemtime(__DIR__ . '/../styles/styles.css');
$message = '';
$messageType = 'success';

$stmt = $conn->prepare('SELECT * FROM empresas WHERE id_propietario = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName = trim($_POST['company_name'] ?? '');
    $companyEmail = trim(strtolower($_POST['company_email'] ?? ''));
    $companyCountry = trim($_POST['company_country'] ?? '');
    $companyProvince = trim($_POST['company_province'] ?? '');
    $companyCity = trim($_POST['company_city'] ?? '');
    $companyAddress = trim($_POST['company_address'] ?? '');
    $companyPremium = (int)($_POST['premium'] ?? 0);

    if ($companyName === '' || $companyEmail === '' || $companyCountry === '' || $companyProvince === '' || $companyCity === '' || $companyAddress === '') {
        $message = 'Completa todos los datos de la empresa.';
        $messageType = 'error';
    } else {
        $companyPhoto = $company['foto'] ?? null;
        if (isset($_FILES['company_photo']) && $_FILES['company_photo']['error'] === UPLOAD_ERR_OK && $_FILES['company_photo']['size'] > 0) {
            $dir = __DIR__ . '/../uploads/empresas';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $ext = strtolower(pathinfo($_FILES['company_photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowed, true)) {
                $message = 'Formato de foto no permitido. Usa JPG, PNG o WEBP.';
                $messageType = 'error';
            } else {
                $fileName = 'company_' . $userId . '_' . time() . '.' . $ext;
                $target = $dir . '/' . $fileName;
                if (move_uploaded_file($_FILES['company_photo']['tmp_name'], $target)) {
                    $companyPhoto = 'uploads/empresas/' . $fileName;
                    if (!empty($company['foto']) && $company['foto'] !== $companyPhoto && file_exists(__DIR__ . '/../' . $company['foto'])) {
                        unlink(__DIR__ . '/../' . $company['foto']);
                    }
                }
            }
        }

        if ($messageType !== 'error') {
            if ($company) {
                $stmt = $conn->prepare('UPDATE empresas SET nombre_empresa = ?, email = ?, pais = ?, provincia = ?, ciudad = ?, `dirección` = ?, premium = ?, foto = ? WHERE id_propietario = ?');
                $stmt->bind_param('ssssssisi', $companyName, $companyEmail, $companyCountry, $companyProvince, $companyCity, $companyAddress, $companyPremium, $companyPhoto, $userId);
            } else {
                $stmt = $conn->prepare('INSERT INTO empresas (id_propietario, nombre_empresa, email, pais, provincia, ciudad, `dirección`, premium, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('issssssis', $userId, $companyName, $companyEmail, $companyCountry, $companyProvince, $companyCity, $companyAddress, $companyPremium, $companyPhoto);
            }

            if ($stmt->execute()) {
                $message = 'Datos de la empresa guardados correctamente.';
                $messageType = 'success';
                $company = [
                    'nombre_empresa' => $companyName,
                    'email' => $companyEmail,
                    'pais' => $companyCountry,
                    'provincia' => $companyProvince,
                    'ciudad' => $companyCity,
                    'dirección' => $companyAddress,
                    'premium' => $companyPremium,
                    'foto' => $companyPhoto,
                ];
            } else {
                $message = 'No se pudieron guardar los datos de la empresa.';
                $messageType = 'error';
            }
        }
    }
}

$companyPhoto = !empty($company['foto'] ?? null) ? '../' . $company['foto'] : '../assets/default-company.svg';
$countryRegions = [
    'Argentina' => ['Buenos Aires', 'Córdoba', 'Santa Fe', 'Mendoza', 'Neuquén'],
    'Chile' => ['Santiago', 'Valparaíso', 'Concepción', 'Biobío', 'Antofagasta'],
    'Colombia' => ['Bogotá D.C.', 'Antioquia', 'Atlántico', 'Bolívar', 'Boyacá', 'Caldas', 'Caquetá', 'Cauca', 'Cesar', 'Chocó', 'Córdoba', 'Cundinamarca', 'Guainía', 'Guaviare', 'Huila', 'La Guajira', 'Magdalena', 'Meta', 'Nariño', 'Norte de Santander', 'Putumayo', 'Quindío', 'Risaralda', 'San Andrés y Providencia', 'Santander', 'Sucre', 'Tolima', 'Valle del Cauca', 'Vaupés', 'Vichada', 'Arauca', 'Casanare'],
    'México' => ['Ciudad de México', 'Jalisco', 'Nuevo León', 'Guadalajara', 'Monterrey'],
    'Perú' => ['Lima', 'Arequipa', 'Cusco', 'Trujillo', 'Piura'],
    'España' => ['Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Bilbao'],
    'Uruguay' => ['Montevideo', 'Canelones', 'Maldonado', 'Salto', 'Rivera'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HireWise | Empresa</title>
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
            <a href="./perfil.php">Perfil</a>
            <a href="./empresa.php" class="active">Empresa</a>
            <a href="./public-directory.php">Público</a>
        </nav>

        <div class="dashboard-user">
            <span class="user-pill"><?php echo htmlspecialchars((($company['premium'] ?? 0) >= 1) ? 'Premium' : 'Standard', ENT_QUOTES, 'UTF-8'); ?></span>
            <a href="logout.php" class="btn btn-ghost">Salir</a>
        </div>
    </header>

    <main class="crud-shell">
        <section class="crud-card">
            <div class="crud-header">
                <div class="mini-avatar">
                    <img src="<?php echo htmlspecialchars($companyPhoto, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo de empresa">
                </div>
                <div>
                    <p class="eyebrow">Empresa</p>
                    <h1>Configuración de empresa</h1>
                </div>
            </div>

            <?php if ($message !== '') : ?>
                <div class="auth-alert <?php echo $messageType === 'success' ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form class="crud-form" method="post" enctype="multipart/form-data">
                <div class="field-grid three-columns">
                    <div class="field">
                        <span>Logo</span>
                        <div class="file-upload-box profile-upload">
                            <div class="company-thumb" aria-hidden="true">
                                <img src="<?php echo htmlspecialchars($companyPhoto, ENT_QUOTES, 'UTF-8'); ?>" alt="Preview de empresa">
                            </div>
                            <input type="file" name="company_photo" accept="image/*">
                        </div>
                    </div>

                    <div class="field">
                        <span>Nombre</span>
                        <input type="text" name="company_name" value="<?php echo htmlspecialchars($company['nombre_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>

                    <div class="field" style="grid-column: 1 / -1;">
                        <span>Plan</span>
                        <div class="plan-section">
                            <div class="plan-grid">
                                <label class="plan-option">
                                    <input type="radio" name="premium" value="0" <?php echo (($company['premium'] ?? 0) == 0) ? 'checked' : ''; ?>>
                                    <span class="plan-name">Free</span>
                                    <strong>$0</strong>
                                    <small>Ideal para empezar.</small>
                                    <ul>
                                        <li>1 proyecto</li>
                                        <li>Acceso básico</li>
                                        <li>Soporte básico</li>
                                    </ul>
                                </label>

                                <label class="plan-option">
                                    <input type="radio" name="premium" value="1" <?php echo (($company['premium'] ?? 0) == 1) ? 'checked' : ''; ?>>
                                    <span class="plan-name">Starter</span>
                                    <strong>$29</strong>
                                    <small>Para equipos pequeños.</small>
                                    <ul>
                                        <li>5 proyectos</li>
                                        <li>Reportes avanzados</li>
                                        <li>Soporte prioritario</li>
                                    </ul>
                                </label>

                                <label class="plan-option">
                                    <input type="radio" name="premium" value="2" <?php echo (($company['premium'] ?? 0) == 2) ? 'checked' : ''; ?>>
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
                                    <input type="radio" name="premium" value="3" <?php echo (($company['premium'] ?? 0) == 3) ? 'checked' : ''; ?>>
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
                    </div>
                </div>

                <div class="field-grid three-columns">
                    <div class="field">
                        <span>País</span>
                        <select id="company_country" name="company_country" required>
                            <option value="">Selecciona</option>
                            <?php foreach (array_keys($countryRegions) as $countryName) : ?>
                                <option value="<?php echo htmlspecialchars($countryName, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($company['pais'] ?? '') === $countryName ? 'selected' : ''); ?>>
                                    <?php echo htmlspecialchars($countryName, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <span>Provincia</span>
                        <select id="company_province" name="company_province" required>
                            <option value="">Selecciona</option>
                            <?php foreach (($countryRegions[$company['pais'] ?? ''] ?? []) as $province) : ?>
                                <option value="<?php echo htmlspecialchars($province, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($company['provincia'] ?? '') === $province ? 'selected' : ''); ?>>
                                    <?php echo htmlspecialchars($province, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <span>Ciudad</span>
                        <input type="text" name="company_city" value="<?php echo htmlspecialchars($company['ciudad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                </div>

                <div class="field-grid two-columns">
                    <div class="field">
                        <span>Email</span>
                        <input type="email" name="company_email" value="<?php echo htmlspecialchars($company['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>

                    <div class="field">
                        <span>Dirección</span>
                        <input type="text" name="company_address" value="<?php echo htmlspecialchars($company['dirección'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                </div>

                <div class="crud-actions">
                    <button type="submit" class="btn btn-primary">Guardar empresa</button>
                    <a href="profile-panel.php" class="btn btn-ghost">Volver</a>
                </div>
            </form>
        </section>
    </main>

    <script>
        const countryProvinces = <?php echo json_encode($countryRegions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const companyCountry = document.getElementById('company_country');
        const companyProvince = document.getElementById('company_province');

        function updateCompanyProvinceOptions() {
            const country = companyCountry.value;
            const selectedProvince = '<?php echo htmlspecialchars($company['provincia'] ?? '', ENT_QUOTES, 'UTF-8'); ?>';
            companyProvince.innerHTML = '<option value="">Selecciona</option>';

            if (!country) {
                return;
            }

            (countryProvinces[country] || []).forEach((province) => {
                const option = document.createElement('option');
                option.value = province;
                option.textContent = province;
                if (province === selectedProvince) {
                    option.selected = true;
                }
                companyProvince.appendChild(option);
            });
        }

        if (companyCountry) {
            companyCountry.addEventListener('change', updateCompanyProvinceOptions);
            updateCompanyProvinceOptions();
        }
    </script>
</body>
</html>
