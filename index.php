<?php
$cssVersion = filemtime(__DIR__ . '/styles/styles.css');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HireWise | Seleccion inteligente de talento</title>
    <meta name="description" content="HireWise automatiza evaluaciones iniciales con IA, cuestionarios especializados y analisis de comportamiento para reclutamiento.">
    <link rel="stylesheet" href="styles/styles.css?v=<?php echo $cssVersion; ?>">
</head>
<body>
    <header class="site-header">
        <a href="#" class="brand" aria-label="HireWise inicio">
            <span class="brand-mark" aria-hidden="true">
                <span></span>
            </span>
            <span>HireWise</span>
        </a>

        <nav class="main-nav" aria-label="Navegacion principal">
            <a href="#inicio">Inicio</a>
            <a href="#plataforma">Plataforma</a>
            <a href="#analisis">Analisis IA</a>
            <a href="#contacto">Contacto</a>
        </nav>

        <div class="header-actions">
            <a href="app/login.php" class="btn btn-ghost">Ingresar</a>
            <a href="app/login.php" class="btn btn-primary">Registro</a>
        </div>
    </header>

    <main>
        <section class="hero-section" id="inicio">
            <div class="hero-content">
                <p class="eyebrow">Seleccion de personal con IA</p>
                <h1>Contrata mejor talento con evaluaciones mas rapidas, objetivas y profundas.</h1>
                <p class="hero-copy">
                    Automatiza la evaluacion inicial con IA, cuestionarios especializados y senales de comportamiento.
                </p>

                <div class="hero-actions">
                    <a href="#plataforma" class="btn btn-primary btn-large">Explorar plataforma</a>
                    <a href="#analisis" class="btn btn-outline btn-large">Ver analisis IA</a>
                </div>

                <div class="trust-strip" aria-label="Metricas principales">
                    <div>
                        <strong>IA</strong>
                        <span>Evaluacion automatizada</span>
                    </div>
                    <div>
                        <strong>360</strong>
                        <span>Lectura de competencias</span>
                    </div>
                    <div>
                        <strong>-Tiempo</strong>
                        <span>Procesos mas agiles</span>
                    </div>
                </div>
            </div>

            <aside class="hero-panel" aria-label="Resumen de entrevista inteligente">
                <div class="panel-header">
                    <span class="status-dot"></span>
                    <span>Entrevista en analisis</span>
                </div>

                <div class="candidate-score">
                    <span>Compatibilidad del candidato</span>
                    <strong>92%</strong>
                </div>

                <div class="signal-list">
                    <div class="signal-item">
                        <span class="signal-icon">01</span>
                        <div>
                            <strong>Habilidades tecnicas</strong>
                            <p>Alineadas al perfil.</p>
                        </div>
                    </div>
                    <div class="signal-item">
                        <span class="signal-icon">02</span>
                        <div>
                            <strong>Comunicacion</strong>
                            <p>Claridad y seguridad.</p>
                        </div>
                    </div>
                    <div class="signal-item">
                        <span class="signal-icon">03</span>
                        <div>
                            <strong>Comportamiento</strong>
                            <p>Expresiones y postura.</p>
                        </div>
                    </div>
                </div>
            </aside>
        </section>

        <section class="content-band" id="plataforma">
            <div class="section-heading">
                <p class="eyebrow">Plataforma</p>
                <h2>Una capa inteligente para filtrar, evaluar y comparar candidatos.</h2>
            </div>

            <div class="feature-grid">
                <article class="feature-card">
                    <span class="feature-number">01</span>
                    <h3>Cuestionarios especializados</h3>
                    <p>Pruebas adaptadas al cargo para detectar competencias clave.</p>
                </article>

                <article class="feature-card">
                    <span class="feature-number">02</span>
                    <h3>Evaluacion automatizada</h3>
                    <p>Resultados ordenados, patrones visibles y menos sesgo inicial.</p>
                </article>

                <article class="feature-card">
                    <span class="feature-number">03</span>
                    <h3>Reportes para reclutadores</h3>
                    <p>Comparacion clara para priorizar candidatos con rapidez.</p>
                </article>
            </div>
        </section>

        <section class="split-section" id="analisis">
            <div>
                <p class="eyebrow">Analisis IA</p>
                <h2>Mas contexto en cada entrevista, sin reemplazar el criterio humano.</h2>
                <p>
                    HireWise combina respuestas, expresiones y lenguaje corporal para apoyar decisiones mas precisas.
                </p>
            </div>

            <div class="insight-board">
                <div class="insight-row">
                    <span>Expresiones faciales</span>
                    <strong>Lectura contextual</strong>
                </div>
                <div class="insight-row">
                    <span>Lenguaje corporal</span>
                    <strong>Senales de comportamiento</strong>
                </div>
                <div class="insight-row">
                    <span>Respuestas del candidato</span>
                    <strong>Analisis semantico</strong>
                </div>
                <div class="insight-row">
                    <span>Perfil del cargo</span>
                    <strong>Comparacion objetiva</strong>
                </div>
            </div>
        </section>

        <section class="cta-section" id="contacto">
            <p class="eyebrow">HireWise para equipos modernos</p>
            <h2>Contrataciones mas rapidas, claras y confiables.</h2>
            <a href="mailto:contacto@hirewise.ai" class="btn btn-primary btn-large">Solicitar demo</a>
        </section>
    </main>
</body>
</html>
