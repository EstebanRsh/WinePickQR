<?php
// admin/panel.php
// Dashboard principal del panel de administración alineado al nuevo MER.

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../api/config.php';

$activeAdminNav = 'panel';

// Valores por defecto
$totalProducts     = 0;
$productsWithPromo = 0;
$totalQr           = 0;
$totalSearch       = 0;
$totalEvents       = 0;
$latestProducts    = [];
$latestViewEvents  = [];
$dbError           = '';

/**
 * Escapar texto para HTML.
 */
function h(?string $text): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

try {
    $pdo = getPDO();

    // ============================
    // MÉTRICAS SOBRE PRODUCTOS
    // ============================

    // 1) Total de productos
    $stmt = $pdo->query('SELECT COUNT(*) AS total FROM products');
    $row  = $stmt->fetch();
    $totalProducts = $row ? (int)$row['total'] : 0;

    // 2) Productos con promoción activa
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT product_id) AS total
        FROM product_promotions
        WHERE active = 1
          AND CURDATE() BETWEEN start_date AND end_date
    ");
    $row = $stmt->fetch();
    $productsWithPromo = $row ? (int)$row['total'] : 0;

    // 3) Últimos productos cargados
    $stmt = $pdo->query("
        SELECT
          id,
          pid,
          name,
          producer,
          varietal,
          origin,
          list_price,
          stock_status,
          created_at
        FROM products
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $latestProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Enriquecer últimos productos con etiqueta de promo (si tienen)
    if (!empty($latestProducts)) {
        $ids = array_column($latestProducts, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sqlPromo = "
            SELECT
              product_id,
              percent,
              pack_size,
              pack_price,
              note
            FROM product_promotions
            WHERE active = 1
              AND CURDATE() BETWEEN start_date AND end_date
              AND product_id IN ($placeholders)
        ";

        $stmtPromo = $pdo->prepare($sqlPromo);
        $stmtPromo->execute($ids);

        $promoByProductId = [];

        while ($promo = $stmtPromo->fetch(PDO::FETCH_ASSOC)) {
            $label = '';

            if ($promo['percent'] !== null) {
                $label = (float)$promo['percent'] . '% OFF';
            } elseif ($promo['pack_size'] !== null && $promo['pack_price'] !== null) {
                $label = 'Pack x' . (int)$promo['pack_size'] . ' $' .
                    number_format((float)$promo['pack_price'], 2, ',', '.');
            }

            if (!empty($promo['note'])) {
                if ($label !== '') {
                    $label .= ' · ';
                }
                $label .= $promo['note'];
            }

            $promoByProductId[$promo['product_id']] = $label;
        }

        foreach ($latestProducts as &$p) {
            $p['promo_label'] = $promoByProductId[$p['id']] ?? null;
        }
        unset($p);
    }

    // ============================
    // MÉTRICAS SOBRE VIEW_EVENTS
    // ============================

    // 4) Resumen de vistas QR / SEARCH
    $stmt = $pdo->query("
        SELECT
          SUM(CASE WHEN channel = 'QR'     THEN 1 ELSE 0 END) AS total_qr,
          SUM(CASE WHEN channel = 'SEARCH' THEN 1 ELSE 0 END) AS total_search,
          COUNT(*) AS total_events
        FROM view_events
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $totalQr      = $row['total_qr']     !== null ? (int)$row['total_qr']     : 0;
        $totalSearch  = $row['total_search'] !== null ? (int)$row['total_search'] : 0;
        $totalEvents  = $row['total_events'] !== null ? (int)$row['total_events'] : 0;
    }

    // Porcentajes para la barrita
    if ($totalEvents > 0) {
        $qrPercent     = round($totalQr / $totalEvents * 100);
        $searchPercent = round($totalSearch / $totalEvents * 100);
    } else {
        $qrPercent = 0;
        $searchPercent = 0;
    }

    // 5) Últimas vistas
    $stmt = $pdo->query("
        SELECT
          ve.viewed_at,
          ve.channel,
          ve.qr_code,
          p.id   AS product_id,
          p.pid,
          p.name,
          p.producer
        FROM view_events ve
        JOIN products p ON p.id = ve.product_id
        ORDER BY ve.viewed_at DESC
        LIMIT 5
    ");
    $latestViewEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $dbError = 'Error al obtener datos de la base: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8" />
    <title>Panel - WinePick QR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Fuente + Bootstrap + iconos -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      crossorigin="anonymous"
    />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    />

    <link rel="stylesheet" href="../public/css/winepick.css" />
    <link rel="stylesheet" href="../public/css/winepick-panel.css" />
  </head>
  <body class="admin-body">
    <main class="admin-main container">
      <!-- Header -->
      <header class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <div class="admin-section-label mb-1">Resumen de hoy</div>
          <h1 class="admin-header-title mb-1">Panel de administración</h1>
          <p class="admin-header-subtitle mb-0">
            Productos cargados, promociones vigentes y actividad de vistas en tiempo real.
          </p>
        </div>
        <div class="text-end">
          <span class="admin-header-pill">
            <span class="admin-header-pill-dot"></span>
            Admin
          </span>
        </div>
      </header>

      <?php if ($dbError !== ''): ?>
        <div class="alert alert-warning small">
          <?php echo h($dbError); ?>
        </div>
      <?php endif; ?>

      <!-- Tarjetas principales -->
      <section class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="admin-section-label">Resumen general</span>
        </div>

        <div class="row g-3">
          <!-- Productos cargados -->
          <div class="col-6 col-md-4">
            <div class="card metric-card h-100">
              <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="metric-card-label">Productos</span>
                  <div class="metric-card-icon-wrap">
                    <i class="bi bi-collection metric-card-icon"></i>
                  </div>
                </div>
                <div class="metric-card-value mb-1"><?php echo $totalProducts; ?></div>
                <p class="metric-card-foot mb-0">
                  Total cargados en la demo.
                </p>
              </div>
            </div>
          </div>

          <!-- Con promo activa -->
          <div class="col-6 col-md-4">
            <div class="card metric-card h-100">
              <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="metric-card-label">Con promo</span>
                  <div class="metric-card-icon-wrap">
                    <i class="bi bi-percent metric-card-icon"></i>
                  </div>
                </div>
                <div class="metric-card-value mb-1"><?php echo $productsWithPromo; ?></div>
                <p class="metric-card-foot mb-0">
                  Productos con alguna promoción vigente.
                </p>
              </div>
            </div>
          </div>

          <!-- Interacciones totales -->
          <div class="col-12 col-md-4">
            <div class="card metric-card h-100">
              <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="metric-card-label">Interacciones</span>
                  <div class="metric-card-icon-wrap">
                    <i class="bi bi-activity metric-card-icon"></i>
                  </div>
                </div>
                <div class="metric-card-value mb-1"><?php echo $totalEvents; ?></div>
                <p class="metric-card-foot mb-0">
                  Suma de vistas por QR y por búsqueda.
                </p>
              </div>
            </div>
          </div>

          <!-- Distribución QR / SEARCH -->
          <div class="col-12 col-md-6">
            <div class="card metric-card h-100">
              <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="metric-card-label">Origen de las vistas</span>
                  <div class="metric-card-icon-wrap">
                    <i class="bi bi-qr-code-scan metric-card-icon"></i>
                  </div>
                </div>

                <p class="admin-distribution-label mb-2">
                  Cómo llegan las personas a tus productos.
                </p>

                <div class="admin-distribution-row d-flex justify-content-between align-items-center mb-1">
                  <span>QR</span>
                  <span>
                    <strong><?php echo $totalQr; ?></strong>
                    · <?php echo $qrPercent; ?>%
                  </span>
                </div>
                <div class="progress admin-progress mb-3" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                  <div class="progress-bar" style="width: <?php echo $qrPercent; ?>%;"></div>
                </div>

                <div class="admin-distribution-row d-flex justify-content-between align-items-center mb-1">
                  <span>Búsqueda</span>
                  <span>
                    <strong><?php echo $totalSearch; ?></strong>
                    · <?php echo $searchPercent; ?>%
                  </span>
                </div>
                <div class="progress admin-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                  <div class="progress-bar" style="width: <?php echo $searchPercent; ?>%;"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Actividad reciente -->
      <section class="mb-4">
        <div class="card activity-card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div>
                <div class="admin-section-label mb-1">Actividad reciente</div>
                <h2 class="activity-card-title mb-0">Lo último en tu catálogo</h2>
                <p class="activity-card-subtitle mb-0">
                  Últimos productos cargados y vistas recientes de clientes.
                </p>
              </div>
              <a href="metricas.php" class="btn btn-outline-soft btn-sm">
                Ver métricas
              </a>
            </div>

            <div class="row g-4">
              <!-- Últimos productos -->
              <div class="col-12 col-md-6">
                <h3 class="admin-list-title">
                  Últimos productos
                </h3>

                <?php if (empty($latestProducts)): ?>
                  <p class="small text-muted mb-0">
                    Todavía no se cargaron productos.
                  </p>
                <?php else: ?>
                  <div>
                    <?php foreach ($latestProducts as $product): ?>
                      <div class="admin-product-item">
                        <div class="d-flex justify-content-between">
                          <div class="me-3">
                            <div class="admin-product-name">
                              <?php echo h($product['name']); ?>
                            </div>
                            <div class="admin-product-meta">
                              <?php
                                $metaParts = [];
                                if (!empty($product['producer'])) {
                                  $metaParts[] = $product['producer'];
                                }
                                if (!empty($product['varietal'])) {
                                  $metaParts[] = $product['varietal'];
                                }
                                if (!empty($product['origin'])) {
                                  $metaParts[] = $product['origin'];
                                }
                                echo h(implode(' · ', $metaParts));
                              ?>
                            </div>
                          </div>
                          <div class="text-end small">
                            <?php if ($product['list_price'] !== null): ?>
                              <div class="admin-product-price mb-1">
                                $ <?php echo number_format((float)$product['list_price'], 2, ',', '.'); ?>
                              </div>
                            <?php endif; ?>

                            <?php if (!empty($product['promo_label'])): ?>
                              <div class="admin-product-promo mb-1">
                                <?php echo h($product['promo_label']); ?>
                              </div>
                            <?php endif; ?>

                            <div class="admin-item-timestamp">
                              <i class="bi bi-clock"></i>
                              <?php echo h($product['created_at']); ?>
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Últimas vistas -->
              <div class="col-12 col-md-6">
                <h3 class="admin-list-title">
                  Últimas vistas
                </h3>

                <?php if (empty($latestViewEvents)): ?>
                  <p class="small text-muted mb-0">
                    Todavía no se registraron vistas de productos.
                  </p>
                <?php else: ?>
                  <div class="admin-timeline">
                    <?php foreach ($latestViewEvents as $event): ?>
                      <div class="admin-timeline-item">
                        <div class="admin-timeline-dot"></div>
                        <div class="d-flex justify-content-between">
                          <div class="me-3">
                            <div class="admin-view-product-name">
                              <?php echo h($event['name']); ?>
                            </div>
                            <div class="admin-view-meta">
                              <span class="admin-view-channel-badge">
                                <?php echo $event['channel'] === 'QR' ? 'QR' : 'Búsqueda'; ?>
                              </span>
                              <span class="ms-1 text-muted">
                                · Código: <?php echo h($event['pid']); ?>
                              </span>
                            </div>
                            <?php if (!empty($event['qr_code'])): ?>
                              <div class="admin-view-meta">
                                QR: <?php echo h($event['qr_code']); ?>
                              </div>
                            <?php endif; ?>
                          </div>
                          <div class="text-end admin-item-timestamp">
                            <i class="bi bi-clock"></i>
                            <?php echo h($event['viewed_at']); ?>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <?php include __DIR__ . '/bottom-nav-admin.php'; ?>

    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      crossorigin="anonymous"
    ></script>
  </body>
</html>
