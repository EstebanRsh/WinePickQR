<?php
// admin/metricas.php
// Vista de métricas: buscador arriba (estilo app pública) + resumen + ranking + timeline.

session_start();

if (!isset($_SESSION['admin_id'])) {
  header('Location: login.php');
  exit;
}

$activeAdminNav = 'metrics';
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8" />
  <title>Métricas - WinePick QR</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Fuente + Bootstrap + iconos -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
    crossorigin="anonymous" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />

  <!-- Estilos base -->
  <link rel="stylesheet" href="../public/css/winepick.css" />
  <link rel="stylesheet" href="../public/css/winepick-search.css" />
  <link rel="stylesheet" href="../public/css/winepick-metrics.css" />
</head>

<body class="admin-metrics-body">
  <main class="admin-metrics-main container">

    <!-- 1) BUSCADOR + FILTROS ARRIBA -->
    <section class="metrics-search-section">
      <form id="productMetricsSearchForm" class="search-header-form mb-0">
        <div class="input-group search-header-input">
          <span class="input-group-text">
            <i class="bi bi-search"></i>
          </span>

          <input type="search" id="productMetricsSearchInput" class="form-control"
            placeholder="Buscar vinos por nombre, bodega o varietal…" autocomplete="off" />

          <button type="button" class="btn btn-filters" data-bs-toggle="modal" data-bs-target="#metricsFiltersModal">
            <i class="bi bi-sliders"></i>
          </button>

          <button type="submit" class="btn btn-winepick">
            Buscar
          </button>
        </div>
      </form>
    </section>

    <!-- Alertas (debajo del search) -->
    <div id="metricsAlert" class="alert d-none metrics-alert" role="alert"></div>

    <!-- 2) RESUMEN + RANGO DE FECHAS (con acordeón) -->
    <section class="mb-4">
      <!-- Botón/encabezado del acordeón -->
      <button class="metrics-accordion-toggle" type="button" data-bs-toggle="collapse"
        data-bs-target="#metricsSummaryCollapse" aria-expanded="true" aria-controls="metricsSummaryCollapse">
        <div>
          <span class="metrics-accordion-eyebrow">Resumen</span>
          <span class="metrics-accordion-title">
            Rango y vistas generales
          </span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="metrics-accordion-range small text-muted" id="metricsSummaryToggleRange">
            Todo el histórico
          </span>
          <span class="metrics-accordion-icon">
            <i class="bi bi-chevron-down"></i>
          </span>
        </div>
      </button>

      <!-- Cuerpo colapsable del acordeón -->
      <div id="metricsSummaryCollapse" class="collapse show">
        <div class="metrics-summary-shell">
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
              <div class="metrics-header-badge">Métricas</div>
              <h1 class="metrics-header-title mb-1">Resumen de interacción</h1>
              <p class="metrics-header-subtitle mb-0">
                Cómo se está usando la demo: QR vs búsquedas en el rango seleccionado.
              </p>
            </div>

            <div class="metrics-range-controls text-end">
              <div class="metrics-range-quick mb-1">
                <button type="button" class="btn btn-link btn-sm metrics-range-chip" id="btnRange7">
                  Últimos 7 días
                </button>
                <button type="button" class="btn btn-link btn-sm metrics-range-chip" id="btnRange30">
                  Últimos 30 días
                </button>
                <button type="button" class="btn btn-link btn-sm metrics-range-chip" id="btnRangeClear">
                  Todo
                </button>
              </div>
              <div class="metrics-range-inputs">
                <input type="date" id="filterStart" class="form-control form-control-sm" />
                <span class="metrics-range-separator">→</span>
                <input type="date" id="filterEnd" class="form-control form-control-sm" />
                <button type="button" id="btnApplyRange" class="btn btn-outline-soft btn-sm ms-1">
                  Aplicar
                </button>
              </div>
              <div class="metrics-range-label small mt-1" id="currentRangeLabel">
                Todo el histórico
              </div>
            </div>
          </div>

          <!-- Resumen tarjetas -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
              <div class="card metrics-summary-card h-100">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="metrics-summary-label">Vistas totales</div>
                    <div class="metrics-summary-icon">
                      <i class="bi bi-eye"></i>
                    </div>
                  </div>
                  <div class="metrics-summary-value" id="totalViewsValue">–</div>
                  <div class="metrics-summary-foot">
                    Eventos registrados en el rango actual.
                  </div>
                </div>
              </div>
            </div>

            <div class="col-6 col-md-4">
              <div class="card metrics-summary-card h-100">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="metrics-summary-label">QR</div>
                    <div class="metrics-summary-icon">
                      <i class="bi bi-qr-code-scan"></i>
                    </div>
                  </div>
                  <div class="metrics-summary-value" id="qrViewsValue">–</div>
                  <div class="metrics-summary-foot">
                    Vistas que vienen por escaneo.
                  </div>
                </div>
              </div>
            </div>

            <div class="col-6 col-md-4">
              <div class="card metrics-summary-card h-100">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="metrics-summary-label">Búsqueda</div>
                    <div class="metrics-summary-icon">
                      <i class="bi bi-search"></i>
                    </div>
                  </div>
                  <div class="metrics-summary-value" id="searchViewsValue">
                    –
                  </div>
                  <div class="metrics-summary-foot">
                    Vistas que llegan desde el buscador o catálogo.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Distribución QR vs Búsqueda -->
          <div class="metrics-distribution card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="metrics-summary-label">Distribución por canal</div>
                <div class="metrics-distribution-legend">
                  <span class="legend-dot legend-dot-qr"></span> QR
                  <span class="legend-separator">·</span>
                  <span class="legend-dot legend-dot-search"></span> Búsqueda
                </div>
              </div>
              <div class="metrics-distribution-bar mb-2">
                <div class="metrics-distribution-bar-qr" id="qrBar" style="width: 0%;"></div>
                <div class="metrics-distribution-bar-search" id="searchBar" style="width: 0%;"></div>
              </div>
              <div class="d-flex justify-content-between small text-muted">
                <span id="qrPercentText">QR: 0%</span>
                <span id="searchPercentText">Búsqueda: 0%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>


    <!-- 3) RESULTADOS DEL BUSCADOR (MÉTRICAS POR PRODUCTO) -->
    <section class="mb-4">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
          <div class="metrics-section-label mb-1">Explorar</div>
          <h2 class="metrics-section-title mb-0">Métricas por producto</h2>
        </div>
      </div>

      <div id="productMetricsResults" class="metrics-product-results">
        <p class="small text-muted mb-0">
          Escribí en el buscador de arriba para ver resultados y abrir el detalle de métricas.
        </p>
      </div>
    </section>

    <!-- 4) RANKING DE PRODUCTOS -->
    <section class="mb-4">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
          <div class="metrics-section-label mb-1">Productos</div>
          <h2 class="metrics-section-title mb-0">Ranking de vistas</h2>
        </div>
        <div class="small text-muted d-none d-md-block">
          Top de productos más vistos en el rango.
        </div>
      </div>

      <div class="card metrics-table-card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 metrics-table metrics-table-by-product">
              <thead>
                <tr>
                  <th>Producto</th>
                  <th>Código</th>
                  <th class="text-end">Total</th>
                  <th class="text-end">QR</th>
                  <th class="text-end">Búsqueda</th>
                </tr>
              </thead>
              <tbody id="viewsByProductBody">
                <tr>
                  <td colspan="5" class="text-center small text-muted py-3">
                    Cargando datos...
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer border-0 py-2">
          <div class="d-flex justify-content-between align-items-center small">
            <div id="viewsByProductStatus" class="text-muted"></div>
            <nav id="byProductPaginationWrapper" class="d-none" aria-label="Paginación vistas por producto">
              <ul class="pagination pagination-sm mb-0 admin-pagination">
                <li class="page-item">
                  <button class="page-link" id="btnByProductPrev" type="button">
                    <i class="bi bi-chevron-left"></i>
                  </button>
                </li>
                <li class="page-item disabled">
                  <span class="page-link" id="byProductPaginationInfo">
                    Página 1 de 1
                  </span>
                </li>
                <li class="page-item">
                  <button class="page-link" id="btnByProductNext" type="button">
                    <i class="bi bi-chevron-right"></i>
                  </button>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </section>

    <!-- 5) TIMELINE GENERAL -->
    <section class="mb-4">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
          <div class="metrics-section-label mb-1">Timeline</div>
          <h2 class="metrics-section-title mb-0">Vistas por día</h2>
        </div>
      </div>

      <div class="card metrics-table-card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 metrics-table metrics-table-timeline">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th class="text-end">Total</th>
                  <th class="text-end">QR</th>
                  <th class="text-end">Búsqueda</th>
                </tr>
              </thead>
              <tbody id="timelineBody">
                <tr>
                  <td colspan="4" class="text-center small text-muted py-3">
                    Cargando datos...
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/bottom-nav-admin.php'; ?>

  <!-- MODAL: DETALLE DE MÉTRICAS POR PRODUCTO -->
  <div class="modal fade" id="productMetricsModal" tabindex="-1" aria-labelledby="productMetricsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content metrics-product-modal">
        <div class="modal-header border-0 pb-0">
          <div>
            <h1 class="modal-title fs-5" id="productMetricsModalLabel">
              Métricas del producto
            </h1>
            <p class="metrics-modal-subtitle mb-0" id="productMetricsModalSubtitle"></p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body pt-2">
          <div class="row g-3 mb-3">
            <div class="col-4">
              <div class="metrics-modal-summary">
                <div class="metrics-modal-summary-label">Total</div>
                <div class="metrics-modal-summary-value" id="pmTotalViewsValue">–</div>
              </div>
            </div>
            <div class="col-4">
              <div class="metrics-modal-summary">
                <div class="metrics-modal-summary-label">QR</div>
                <div class="metrics-modal-summary-value" id="pmQrViewsValue">–</div>
              </div>
            </div>
            <div class="col-4">
              <div class="metrics-modal-summary">
                <div class="metrics-modal-summary-label">Búsqueda</div>
                <div class="metrics-modal-summary-value" id="pmSearchViewsValue">–</div>
              </div>
            </div>
          </div>

          <div class="metrics-modal-range small text-muted mb-2" id="productMetricsRangeLabel"></div>

          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 metrics-table metrics-table-timeline">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th class="text-end">Total</th>
                  <th class="text-end">QR</th>
                  <th class="text-end">Búsqueda</th>
                </tr>
              </thead>
              <tbody id="productMetricsTimelineBody">
                <tr>
                  <td colspan="4" class="text-center small text-muted py-3">
                    Cargando...
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-link btn-sm text-muted" data-bs-dismiss="modal">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL DE FILTROS DEL BUSCADOR (reutilizable) -->
  <div class="modal fade" id="metricsFiltersModal" tabindex="-1" aria-labelledby="metricsFiltersModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title small text-uppercase text-muted" id="metricsFiltersModalLabel">
            Filtros de búsqueda
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body pt-2">
          <div class="mb-3">
            <span class="small d-block text-muted mb-1">Buscar en:</span>
            <div class="d-flex flex-wrap gap-3 small">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="filterScopeName" value="1"
                  form="productMetricsSearchForm" checked />
                <label class="form-check-label" for="filterScopeName">
                  Nombre
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="filterScopeProducer" value="1"
                  form="productMetricsSearchForm" checked />
                <label class="form-check-label" for="filterScopeProducer">
                  Productor / bodega
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="filterScopeVarietal" value="1"
                  form="productMetricsSearchForm" checked />
                <label class="form-check-label" for="filterScopeVarietal">
                  Varietal / origen
                </label>
              </div>
            </div>
          </div>

          <div class="row g-2">
            <div class="col-6">
              <label for="filterMinPrice" class="form-label small">Precio mínimo</label>
              <input type="number" step="0.01" id="filterMinPrice" class="form-control form-control-sm"
                form="productMetricsSearchForm" />
            </div>
            <div class="col-6">
              <label for="filterMaxPrice" class="form-label small">Precio máximo</label>
              <input type="number" step="0.01" id="filterMaxPrice" class="form-control form-control-sm"
                form="productMetricsSearchForm" />
            </div>
            <div class="col-12">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="filterOnlyActive" form="productMetricsSearchForm"
                  checked />
                <label class="form-check-label small" for="filterOnlyActive">
                  Solo productos activos
                </label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-soft btn-sm" id="btnClearSearchFilters">
            Limpiar filtros
          </button>
          <button type="submit" class="btn btn-winepick btn-sm" form="productMetricsSearchForm" data-bs-dismiss="modal">
            Aplicar
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    crossorigin="anonymous"></script>

  <script>
    const METRICS_API_URL = "../api/metrics.php";
    const PRODUCT_API_URL = "../api/product.php";
    const BY_PRODUCT_PER_PAGE = 10;

    let byProductPage = 1;
    let byProductTotalPages = 1;
    let productMetricsModal = null;

    function escapeHtml(str) {
      if (str === null || str === undefined) return "";
      return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }

    function showMetricsAlert(message, type = "success") {
      const el = document.getElementById("metricsAlert");
      el.textContent = message;
      el.className = "alert alert-" + type + " metrics-alert";
      el.classList.remove("d-none");
      setTimeout(() => el.classList.add("d-none"), 3000);
    }

    function buildQuery(paramsObj) {
      const params = new URLSearchParams();
      for (const [key, value] of Object.entries(paramsObj)) {
        if (value !== null && value !== "" && value !== undefined) {
          params.set(key, value);
        }
      }
      return params.toString();
    }

    function getDateFilters() {
      const startInput = document.getElementById("filterStart");
      const endInput = document.getElementById("filterEnd");
      const start = startInput.value ? startInput.value : null;
      const end = endInput.value ? endInput.value : null;
      return { startDate: start, endDate: end };
    }

    function updateRangeLabel(startDate, endDate) {
      const labelEl = document.getElementById("currentRangeLabel");
      const toggleLabelEl = document.getElementById("metricsSummaryToggleRange");

      let text = "Todo el histórico";

      if (startDate && endDate) {
        text = "Del " + startDate + " al " + endDate;
      } else if (startDate) {
        text = "Desde " + startDate;
      } else if (endDate) {
        text = "Hasta " + endDate;
      }

      if (labelEl) labelEl.textContent = text;
      if (toggleLabelEl) toggleLabelEl.textContent = text;
    }


    function formatNumber(n) {
      return Number(n).toLocaleString("es-AR");
    }

    function formatDateISO(date) {
      const y = date.getFullYear();
      const m = String(date.getMonth() + 1).padStart(2, "0");
      const d = String(date.getDate()).padStart(2, "0");
      return y + "-" + m + "-" + d;
    }

    function setQuickRangeDays(days) {
      const end = new Date();
      const start = new Date();
      start.setDate(end.getDate() - (days - 1));

      document.getElementById("filterStart").value = formatDateISO(start);
      document.getElementById("filterEnd").value = formatDateISO(end);

      loadAllMetrics();
    }

    function clearRange() {
      document.getElementById("filterStart").value = "";
      document.getElementById("filterEnd").value = "";
      loadAllMetrics();
    }

    // ----------------- RESUMEN -----------------
    async function loadSummary() {
      const { startDate, endDate } = getDateFilters();
      const query = buildQuery({
        action: "views_summary",
        start_date: startDate,
        end_date: endDate
      });

      const totalEl = document.getElementById("totalViewsValue");
      const qrEl = document.getElementById("qrViewsValue");
      const searchEl = document.getElementById("searchViewsValue");

      totalEl.textContent = "…";
      qrEl.textContent = "…";
      searchEl.textContent = "…";

      try {
        const response = await fetch(METRICS_API_URL + "?" + query);
        if (!response.ok) {
          throw new Error("Error resumen (" + response.status + ")");
        }
        const data = await response.json();

        const totalViews = Number(data.total_views || 0);
        let qrViews = 0;
        let searchViews = 0;

        if (Array.isArray(data.views_by_channel)) {
          for (const row of data.views_by_channel) {
            if (row.channel === "QR") qrViews = Number(row.views || 0);
            if (row.channel === "SEARCH") searchViews = Number(row.views || 0);
          }
        }

        totalEl.textContent = formatNumber(totalViews);
        qrEl.textContent = formatNumber(qrViews);
        searchEl.textContent = formatNumber(searchViews);

        let qrPercent = 0;
        let searchPercent = 0;
        if (totalViews > 0) {
          qrPercent = Math.round((qrViews / totalViews) * 100);
          searchPercent = 100 - qrPercent;
        }

        document.getElementById("qrBar").style.width = qrPercent + "%";
        document.getElementById("searchBar").style.width = searchPercent + "%";

        document.getElementById("qrPercentText").textContent =
          "QR: " + qrPercent + "%";
        document.getElementById("searchPercentText").textContent =
          "Búsqueda: " + searchPercent + "%";

        updateRangeLabel(
          data.date_filter && data.date_filter.start_date
            ? data.date_filter.start_date
            : startDate,
          data.date_filter && data.date_filter.end_date
            ? data.date_filter.end_date
            : endDate
        );
      } catch (error) {
        console.error(error);
        showMetricsAlert("No se pudo cargar el resumen de métricas.", "danger");
      }
    }

    // ----------------- RANKING POR PRODUCTO -----------------
    async function loadViewsByProduct(page = 1) {
      byProductPage = page;
      const { startDate, endDate } = getDateFilters();

      const query = buildQuery({
        action: "views_by_product",
        start_date: startDate,
        end_date: endDate,
        page: page,
        per_page: BY_PRODUCT_PER_PAGE
      });

      const tbody = document.getElementById("viewsByProductBody");
      const statusEl = document.getElementById("viewsByProductStatus");
      const paginationWrapper = document.getElementById("byProductPaginationWrapper");
      const infoEl = document.getElementById("byProductPaginationInfo");
      const btnPrev = document.getElementById("btnByProductPrev");
      const btnNext = document.getElementById("btnByProductNext");

      tbody.innerHTML = `
          <tr>
            <td colspan="5" class="text-center small text-muted py-3">
              Cargando datos...
            </td>
          </tr>
        `;
      statusEl.textContent = "";

      try {
        const response = await fetch(METRICS_API_URL + "?" + query);
        if (!response.ok) {
          throw new Error("Error vistas por producto (" + response.status + ")");
        }
        const data = await response.json();

        const rows = Array.isArray(data.data) ? data.data : [];
        const pagination = data.pagination || {
          page: 1,
          per_page: BY_PRODUCT_PER_PAGE,
          total: 0,
          total_pages: 1
        };

        if (rows.length === 0) {
          tbody.innerHTML = `
              <tr>
                <td colspan="5" class="text-center small text-muted py-3">
                  No se registraron vistas para este rango de fechas.
                </td>
              </tr>
            `;
          paginationWrapper.classList.add("d-none");
          statusEl.textContent = "";
          return;
        }

        tbody.innerHTML = "";
        for (const row of rows) {
          const tr = document.createElement("tr");
          tr.innerHTML = `
              <td>
                <div class="fw-semibold">${escapeHtml(row.name)}</div>
                <div class="small text-muted">
                  ${escapeHtml(row.producer || "")}
                  ${row.varietal ? " · " + escapeHtml(row.varietal) : ""}
                </div>
              </td>
              <td><code class="small">${escapeHtml(row.pid)}</code></td>
              <td class="text-end">${formatNumber(row.views_total || 0)}</td>
              <td class="text-end">${formatNumber(row.views_qr || 0)}</td>
              <td class="text-end">${formatNumber(row.views_search || 0)}</td>
            `;
          tbody.appendChild(tr);
        }

        byProductPage = pagination.page || 1;
        byProductTotalPages = pagination.total_pages || 1;

        if (byProductTotalPages <= 1) {
          paginationWrapper.classList.add("d-none");
        } else {
          paginationWrapper.classList.remove("d-none");
        }

        infoEl.textContent =
          "Página " + byProductPage + " de " + byProductTotalPages;

        btnPrev.disabled = byProductPage <= 1;
        btnNext.disabled = byProductPage >= byProductTotalPages;

        statusEl.textContent =
          "Mostrando " +
          rows.length +
          " de " +
          (pagination.total || rows.length) +
          " productos con vistas.";
      } catch (error) {
        console.error(error);
        showMetricsAlert(
          "No se pudieron cargar las vistas por producto.",
          "danger"
        );
      }
    }

    // ----------------- TIMELINE GENERAL -----------------
    async function loadTimeline() {
      const { startDate, endDate } = getDateFilters();
      const query = buildQuery({
        action: "views_timeline",
        start_date: startDate,
        end_date: endDate
      });

      const tbody = document.getElementById("timelineBody");
      tbody.innerHTML = `
          <tr>
            <td colspan="4" class="text-center small text-muted py-3">
              Cargando datos...
            </td>
          </tr>
        `;

      try {
        const response = await fetch(METRICS_API_URL + "?" + query);
        if (!response.ok) {
          throw new Error("Error timeline (" + response.status + ")");
        }
        const data = await response.json();

        const rows = Array.isArray(data.data) ? data.data : [];

        if (rows.length === 0) {
          tbody.innerHTML = `
              <tr>
                <td colspan="4" class="text-center small text-muted py-3">
                  No hay vistas registradas para el rango seleccionado.
                </td>
              </tr>
            `;
          return;
        }

        tbody.innerHTML = "";
        rows.forEach((row) => {
          const tr = document.createElement("tr");
          tr.innerHTML = `
              <td>${escapeHtml(row.date)}</td>
              <td class="text-end">${formatNumber(row.views_total || 0)}</td>
              <td class="text-end">${formatNumber(row.views_qr || 0)}</td>
              <td class="text-end">${formatNumber(row.views_search || 0)}</td>
            `;
          tbody.appendChild(tr);
        });
      } catch (error) {
        console.error(error);
        showMetricsAlert("No se pudo cargar el timeline de vistas.", "danger");
      }
    }

    // ----------------- BUSCADOR (PRODUCTOS + MÉTRICAS) -----------------
    async function searchProductsForMetrics(term) {
      const container = document.getElementById("productMetricsResults");

      if (!term) {
        container.innerHTML =
          '<p class="small text-muted mb-0">Escribí en el buscador para ver productos.</p>';
        return;
      }

      container.innerHTML =
        '<p class="small text-muted mb-0">Buscando productos...</p>';

      try {
        const params = new URLSearchParams();
        params.set("action", "list");
        params.set("search", term);
        params.set("page", "1");
        params.set("per_page", "10");
        // Filtro "solo activos"
        if (document.getElementById("filterOnlyActive").checked) {
          params.set("active", "1");
        }

        const response = await fetch(
          PRODUCT_API_URL + "?" + params.toString()
        );
        if (!response.ok) {
          throw new Error("Error búsqueda productos (" + response.status + ")");
        }
        const data = await response.json();
        const items = Array.isArray(data.data) ? data.data : [];

        if (items.length === 0) {
          container.innerHTML =
            '<p class="small text-muted mb-0">No se encontraron productos para ese término.</p>';
          return;
        }

        renderProductMetricsResults(items);
      } catch (error) {
        console.error(error);
        showMetricsAlert(
          "No se pudieron buscar productos para métricas.",
          "danger"
        );
      }
    }

    function renderProductMetricsResults(items) {
      const container = document.getElementById("productMetricsResults");
      container.innerHTML = "";

      items.forEach((p) => {
        const metaParts = [];
        if (p.producer) metaParts.push(p.producer);
        if (p.varietal) metaParts.push(p.varietal);
        if (p.origin) metaParts.push(p.origin);
        const meta = metaParts.join(" · ");

        const card = document.createElement("article");
        card.className = "metrics-product-result-card";

        card.innerHTML = `
            <div class="metrics-product-result-main">
              <div class="metrics-product-result-name">
                ${escapeHtml(p.name)}
              </div>
              <div class="metrics-product-result-meta">
                ${escapeHtml(meta)}
              </div>
              <div class="metrics-product-result-pid">
                Código: <code>${escapeHtml(p.pid)}</code>
              </div>
            </div>
            <div class="metrics-product-result-actions">
              <button
                type="button"
                class="btn btn-outline-soft btn-sm"
                data-id="${p.id}"
                data-name="${escapeHtml(p.name)}"
                data-pid="${escapeHtml(p.pid)}"
                data-meta="${escapeHtml(meta)}"
              >
                Ver métricas
              </button>
            </div>
          `;

        const btn = card.querySelector("button");
        btn.addEventListener("click", () => {
          const product = {
            id: p.id,
            name: p.name,
            pid: p.pid,
            meta: meta
          };
          openProductMetricsModal(product);
        });

        container.appendChild(card);
      });
    }

    function openProductMetricsModal(product) {
      const titleEl = document.getElementById("productMetricsModalLabel");
      const subtitleEl = document.getElementById(
        "productMetricsModalSubtitle"
      );

      titleEl.textContent = product.name;
      let subtitle = "Código: " + product.pid;
      if (product.meta) {
        subtitle = product.meta + " · " + subtitle;
      }
      subtitleEl.textContent = subtitle;

      document.getElementById("pmTotalViewsValue").textContent = "…";
      document.getElementById("pmQrViewsValue").textContent = "…";
      document.getElementById("pmSearchViewsValue").textContent = "…";
      document.getElementById("productMetricsRangeLabel").textContent = "";

      const tbody = document.getElementById("productMetricsTimelineBody");
      tbody.innerHTML = `
          <tr>
            <td colspan="4" class="text-center small text-muted py-3">
              Cargando...
            </td>
          </tr>
        `;

      if (!productMetricsModal) {
        const modalEl = document.getElementById("productMetricsModal");
        productMetricsModal = new bootstrap.Modal(modalEl);
      }
      productMetricsModal.show();

      loadProductMetrics(product);
    }

    async function loadProductMetrics(product) {
      const { startDate, endDate } = getDateFilters();
      const query = buildQuery({
        action: "views_timeline",
        product_id: product.id,
        start_date: startDate,
        end_date: endDate
      });

      const tbody = document.getElementById("productMetricsTimelineBody");

      try {
        const response = await fetch(METRICS_API_URL + "?" + query);
        if (!response.ok) {
          throw new Error("Error métricas producto (" + response.status + ")");
        }
        const data = await response.json();
        const rows = Array.isArray(data.data) ? data.data : [];

        let total = 0;
        let qrTotal = 0;
        let searchTotal = 0;

        rows.forEach((r) => {
          total += Number(r.views_total || 0);
          qrTotal += Number(r.views_qr || 0);
          searchTotal += Number(r.views_search || 0);
        });

        document.getElementById("pmTotalViewsValue").textContent =
          formatNumber(total);
        document.getElementById("pmQrViewsValue").textContent =
          formatNumber(qrTotal);
        document.getElementById("pmSearchViewsValue").textContent =
          formatNumber(searchTotal);

        const rangeLabelEl = document.getElementById(
          "productMetricsRangeLabel"
        );
        const df = data.filters || {};
        const effectiveStart = df.start_date || startDate || "inicio";
        const effectiveEnd = df.end_date || endDate || "hoy";
        rangeLabelEl.textContent =
          "Rango: " + effectiveStart + " → " + effectiveEnd;

        if (rows.length === 0) {
          tbody.innerHTML = `
              <tr>
                <td colspan="4" class="text-center small text-muted py-3">
                  Este producto aún no tiene vistas en el rango seleccionado.
                </td>
              </tr>
            `;
          return;
        }

        tbody.innerHTML = "";
        rows.forEach((r) => {
          const tr = document.createElement("tr");
          tr.innerHTML = `
              <td>${escapeHtml(r.date)}</td>
              <td class="text-end">${formatNumber(r.views_total || 0)}</td>
              <td class="text-end">${formatNumber(r.views_qr || 0)}</td>
              <td class="text-end">${formatNumber(r.views_search || 0)}</td>
            `;
          tbody.appendChild(tr);
        });
      } catch (error) {
        console.error(error);
        showMetricsAlert(
          "No se pudieron cargar las métricas del producto.",
          "danger"
        );
      }
    }

    // ----------------- INIT -----------------
    function loadAllMetrics() {
      loadSummary();
      loadViewsByProduct(1);
      loadTimeline();
    }

    function setupMetricsEvents() {
      document
        .getElementById("btnApplyRange")
        .addEventListener("click", () => loadAllMetrics());

      document
        .getElementById("btnRange7")
        .addEventListener("click", () => setQuickRangeDays(7));

      document
        .getElementById("btnRange30")
        .addEventListener("click", () => setQuickRangeDays(30));

      document
        .getElementById("btnRangeClear")
        .addEventListener("click", () => clearRange());

      document
        .getElementById("btnByProductPrev")
        .addEventListener("click", () => {
          if (byProductPage > 1) {
            loadViewsByProduct(byProductPage - 1);
          }
        });

      document
        .getElementById("btnByProductNext")
        .addEventListener("click", () => {
          if (byProductPage < byProductTotalPages) {
            loadViewsByProduct(byProductPage + 1);
          }
        });

      // buscador
      const searchForm = document.getElementById("productMetricsSearchForm");
      searchForm.addEventListener("submit", (e) => {
        e.preventDefault();
        const term = document
          .getElementById("productMetricsSearchInput")
          .value.trim();
        searchProductsForMetrics(term);
      });

      // limpiar filtros de buscador
      document
        .getElementById("btnClearSearchFilters")
        .addEventListener("click", () => {
          document.getElementById("filterMinPrice").value = "";
          document.getElementById("filterMaxPrice").value = "";
          document.getElementById("filterOnlyActive").checked = true;
          document.getElementById("filterScopeName").checked = true;
          document.getElementById("filterScopeProducer").checked = true;
          document.getElementById("filterScopeVarietal").checked = true;
        });
    }

    document.addEventListener("DOMContentLoaded", () => {
      setupMetricsEvents();
      loadAllMetrics();
    });
  </script>
</body>

</html>