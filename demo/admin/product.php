<?php
// admin/product.php
// VERSIÓN CORREGIDA - 19/11/2025
session_start();

if (!isset($_SESSION['admin_id'])) {
  header('Location: login.php');
  exit;
}

// Para marcar el ítem activo en el bottom nav
$activeAdminNav = 'products';
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8" />
  <title>Productos - WinePick QR</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://unpkg.com/cropperjs@1.5.13/dist/cropper.css">
  <!-- Google Fonts + Bootstrap + Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
    crossorigin="anonymous" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />

  <!-- Estilos WinePick -->
  <link rel="stylesheet" href="../public/css/winepick.css" />
  <link rel="stylesheet" href="../public/css/winepick-search.css" />
  <!-- CSS específico de la vista productos -->
  <link rel="stylesheet" href="../public/css/winepick-product.css" />
  <link rel="stylesheet" href="../public/css/winepick-product-images.css" />
  <!-- (Opcional) estilos del paginador reutilizable -->
  <link rel="stylesheet" href="../public/css/pagination.css" />
</head>

<!-- Clase de body específica para que use el mismo fondo que el resto -->

<body class="admin-products-body">
  <!-- Main con mismo layout que métricas/panel -->
  <main class="admin-products-main container">

    <!-- 1) BUSCADOR ARRIBA (igual estilo que la app pública) -->
    <section class="metrics-search-section">
      <form id="adminProductSearchForm" class="search-header-form mb-0">
        <div class="input-group search-header-input">
          <span class="input-group-text">
            <i class="bi bi-search"></i>
          </span>

          <input id="adminProductSearchInput" name="search" type="search" class="form-control"
            placeholder="Buscar productos por nombre, bodega o varietal…" autocomplete="off" />

          <!-- botón filtros (abre modal) -->
          <button type="button" class="btn btn-filters" data-bs-toggle="modal" data-bs-target="#productFiltersModal">
            <i class="bi bi-sliders"></i>
          </button>

          <!-- botón buscar -->
          <button type="submit" class="btn btn-winepick">
            Buscar
          </button>
        </div>
      </form>
    </section>

    <!-- ALERTAS debajo del buscador -->
    <div id="productAlert" class="alert d-none metrics-alert" role="alert"></div>

    <!-- 2) HEADER ADMIN PRODUCTOS -->
    <section class="mb-3">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <div class="admin-products-badge">Admin</div>
          <h1 class="admin-products-title mb-1">Productos</h1>
          <p class="admin-products-subtitle mb-0">
            Gestioná el catálogo: alta, edición, precios, promos y estado.
          </p>
        </div>

        <div class="d-flex flex-wrap gap-2 product-summary-chips">
          <span class="badge rounded-pill bg-light text-muted small">
            Total: <span id="chipTotalProducts">–</span>
          </span>
          <span class="badge rounded-pill bg-light text-muted small">
            Activos: <span id="chipActiveProducts">–</span>
          </span>
          <span class="badge rounded-pill bg-light text-muted small">
            Inactivos: <span id="chipInactiveProducts">–</span>
          </span>
        </div>
      </div>
    </section>

    <!-- 3) LISTADO DE PRODUCTOS -->
    <section class="mb-4">
      <div class="card metrics-table-card">
        <div class="card-body">
          <div id="productList" class="vino-grid-wrapper">
            <p class="small text-muted mb-0">
              Cargando productos...
            </p>
          </div>

          <div id="productListStatus" class="small text-muted mt-2"></div>

          <div id="productListPagination" class="mt-3 d-flex justify-content-center flex-wrap gap-2"></div>
        </div>
      </div>
    </section>

  </main>

  <!-- Modal de ALTA de producto -->
  <?php include __DIR__ . '/modalProductAdd.php'; ?>

  <!-- Modal de editor de imágenes (crop) -->
  <?php include __DIR__ . '/modalImageEditor.php'; ?>

  <!-- Bottom nav -->
  <?php include __DIR__ . '/bottom-nav-admin.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    crossorigin="anonymous"></script>
  <script src="https://unpkg.com/cropperjs@1.5.13/dist/cropper.js"></script>

  <!-- Paginador reutilizable -->
  <script src="../public/js/pagination.js"></script>

  <script>
    // ============================================================================
    // CONFIGURACIÓN DE APIs 
    // ============================================================================
    const PRODUCT_API_URL = "../api/product.php";
    const IMAGES_API_URL = "../api/product_images.php"; // ← NUEVA LÍNEA AGREGADA

    let currentPage = 1;

    // instancia del paginador reutilizable
    let productPager = null;

    // ============================================================================
    // FUNCIONES HELPER
    // ============================================================================

    function escapeHtml(str) {
      if (str === null || str === undefined) return "";
      return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }

    function formatNumber(n) {
      return Number(n || 0).toLocaleString("es-AR");
    }

    function showProductAlert(message, type = "success") {
      const el = document.getElementById("productAlert");
      el.textContent = message;
      el.className = "alert alert-" + type + " metrics-alert";
      el.classList.remove("d-none");
      setTimeout(() => el.classList.add("d-none"), 2800);
    }

    // ============================================================================
    // LISTADO DE PRODUCTOS
    // ============================================================================

    function buildListQuery(page = 1) {
      const params = new URLSearchParams();
      params.set("action", "list");
      params.set("page", page);
      params.set("per_page", 12);

      const term = document
        .getElementById("adminProductSearchInput")
        .value.trim();
      if (term) params.set("search", term);

      // filtros opcionales
      const onlyActive = document.getElementById("filterOnlyActive");
      const onlyPromo = document.getElementById("filterOnlyPromo");
      if (onlyActive && onlyActive.checked) params.set("active", "1");
      if (onlyPromo && onlyPromo.checked) params.set("promo", "1");

      const minPrice = document.getElementById("filterMinPrice")?.value;
      const maxPrice = document.getElementById("filterMaxPrice")?.value;
      if (minPrice) params.set("min_price", minPrice);
      if (maxPrice) params.set("max_price", maxPrice);

      return params.toString();
    }

    async function loadProducts(page = 1) {
      currentPage = page;
      const listWrapper = document.getElementById("productList");
      const statusEl = document.getElementById("productListStatus");
      const paginationEl = document.getElementById("productListPagination");

      listWrapper.innerHTML =
        '<p class="small text-muted mb-0">Cargando productos...</p>';
      statusEl.textContent = "";
      paginationEl.innerHTML = "";

      try {
        const query = buildListQuery(page);
        const resp = await fetch(PRODUCT_API_URL + "?" + query);
        if (!resp.ok) {
          throw new Error("Error al listar productos (" + resp.status + ")");
        }
        const json = await resp.json();
        const items = Array.isArray(json.data) ? json.data : [];
        const pag = json.pagination || {
          page: 1,
          per_page: items.length,
          total: items.length,
          total_pages: 1,
        };

        // Guardar en caché global para el modal de edición
        window.productsCache = items;

        renderProductList(items);
        renderProductPagination(pag); // ← se mantiene tu llamada original

        // chips de resumen
        if (json.meta) {
          const t = document.getElementById("chipTotalProducts");
          const a = document.getElementById("chipActiveProducts");
          const i = document.getElementById("chipInactiveProducts");
          if (t) t.textContent = json.meta.total || 0;
          if (a) a.textContent = json.meta.active || 0;
          if (i) i.textContent = json.meta.inactive || 0;
        }

        if (items.length === 0) {
          statusEl.textContent = "No se encontraron productos.";
        } else {
          statusEl.textContent = `Mostrando ${items.length} producto(s) de ${pag.total}`;
        }
      } catch (error) {
        console.error(error);
        listWrapper.innerHTML = `
          <p class="text-danger small mb-0">
            Error al cargar productos: ${escapeHtml(error.message)}
          </p>
        `;
      }
    }

    function renderProductList(items) {
      const wrapper = document.getElementById("productList");
      if (!wrapper) return;

      if (!Array.isArray(items) || items.length === 0) {
        wrapper.innerHTML = `
          <p class="small text-muted mb-0">
            No hay productos. Usá el botón "Nuevo producto" para crear uno.
          </p>
        `;
        return;
      }

      const grid = document.createElement("div");
      grid.className = "vino-grid";

      items.forEach((p) => {
        const card = document.createElement("article");
        card.className = "vino-card";

        const metaParts = [];
        if (p.producer) metaParts.push(p.producer);
        if (p.varietal) metaParts.push(p.varietal);
        if (p.origin) metaParts.push(p.origin);
        const meta = metaParts.join(" • ");

        const priceHtml =
          p.list_price != null ?
          `<p class="vino-card-price mb-0">$ ${Number(p.list_price).toFixed(2)}</p>` :
          "";

        const promoHtml =
          p.promo_label || p.promo ?
          `<p class="vino-card-promo mb-0">${escapeHtml(
              p.promo_label || "Producto en promo"
            )}</p>` :
          "";

        const activeBadge =
          String(p.active) === "1" ?
          '<span class="badge bg-success-subtle text-success small">Activo</span>' :
          '<span class="badge bg-secondary-subtle text-secondary small">Inactivo</span>';

        card.innerHTML = `
            <div class="vino-card-body">
              <p class="vino-card-title mb-0">${escapeHtml(p.name || "")}</p>
              <p class="vino-card-subtitle small text-muted mb-1">
                ${escapeHtml(meta)}
              </p>
              <div class="d-flex flex-wrap align-items-center gap-2 small mb-2">
                <span class="badge rounded-pill bg-light text-muted">ID #${p.id}</span>
                <span class="badge rounded-pill bg-light text-muted">PID ${escapeHtml(
          p.pid || ""
        )}</span>
                ${activeBadge}
              </div>
              ${priceHtml}
              ${promoHtml}
            </div>
            <div class="vino-card-footer mt-2">
              <button
                type="button"
                class="btn btn-outline-soft vino-card-btn btn-sm me-1"
                data-bs-toggle="modal"
                data-bs-target="#productModal"
                data-product-id="${p.id}"
              >
                Editar
              </button>
            </div>
          `;

        grid.appendChild(card);
      });

      wrapper.innerHTML = "";
      wrapper.appendChild(grid);
    }

    // === Paginación: MISMA API que ya usabas, ahora delega en pagination.js ===
    function renderProductPagination(pag) {
      const paginationEl = document.getElementById("productListPagination");
      const page = Number(pag.page || 1);
      const totalPages = Number(pag.total_pages || 1);

      if (!paginationEl) return;

      // 1) Crear instancia una sola vez
      if (!productPager && window.createPagination) {
        productPager = window.createPagination(paginationEl, {
          onChange: (nextPage) => loadProducts(nextPage) // respeta tu flujo
        });
      }

      // 2) Si no hay más de una página, vaciamos y salimos
      if (totalPages <= 1) {
        paginationEl.innerHTML = "";
        return;
      }

      // 3) Delegar el render al paginador reutilizable
      if (productPager) {
        productPager.render({
          page,
          total_pages: totalPages
        });
      } else {
        // Fallback improbable (si no cargó pagination.js), mantiene tu UI anterior
        const btnPrev = document.createElement("button");
        btnPrev.type = "button";
        btnPrev.className = "btn btn-sm btn-outline-soft";
        btnPrev.textContent = "Anterior";
        btnPrev.disabled = page <= 1;
        btnPrev.addEventListener("click", () => {
          if (page > 1) loadProducts(page - 1);
        });

        const info = document.createElement("span");
        info.className = "small text-muted mx-2";
        info.textContent = "Página " + page + " de " + totalPages;

        const btnNext = document.createElement("button");
        btnNext.type = "button";
        btnNext.className = "btn btn-sm btn-outline-soft";
        btnNext.textContent = "Siguiente";
        btnNext.disabled = page >= totalPages;
        btnNext.addEventListener("click", () => {
          if (page < totalPages) loadProducts(page + 1);
        });

        paginationEl.innerHTML = "";
        paginationEl.appendChild(btnPrev);
        paginationEl.appendChild(info);
        paginationEl.appendChild(btnNext);
      }
    }

    // ============================================================================
    // FILTROS
    // ============================================================================

    function clearProductFilters() {
      const min = document.getElementById("filterMinPrice");
      const max = document.getElementById("filterMaxPrice");
      const onlyActive = document.getElementById("filterOnlyActive");
      const onlyPromo = document.getElementById("filterOnlyPromo");

      if (min) min.value = "";
      if (max) max.value = "";
      if (onlyActive) onlyActive.checked = false;
      if (onlyPromo) onlyPromo.checked = false;
    }

    // ============================================================================
    // INICIALIZACIÓN
    // ============================================================================

    document.addEventListener("DOMContentLoaded", () => {
      console.log("✓ Página de productos cargada (versión corregida)");
      console.log("PRODUCT_API_URL:", PRODUCT_API_URL);
      console.log("IMAGES_API_URL:", IMAGES_API_URL);

      // submit del buscador
      const form = document.getElementById("adminProductSearchForm");
      form.addEventListener("submit", (e) => {
        e.preventDefault();
        loadProducts(1);
      });

      const btnClearFilters = document.getElementById("btnClearProductFilters");
      if (btnClearFilters) {
        btnClearFilters.addEventListener("click", () => {
          clearProductFilters();
        });
      }

      // primera carga del listado
      loadProducts(1);

      // Si viene ?new=1 en la URL, abrir el modal de "Nuevo producto"
      const params = new URLSearchParams(window.location.search);
      if (params.get("new") === "1") {
        const modalEl = document.getElementById("productModal");
        if (modalEl && window.bootstrap) {
          const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
          modalInstance.show();
        }
      }
    });
  </script>

  <!-- MODAL DE FILTROS (invocado por el botón de filtros del buscador) -->
  <div class="modal fade" id="productFiltersModal" tabindex="-1" aria-labelledby="productFiltersModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title small text-uppercase text-muted" id="productFiltersModalLabel">
            Filtros de productos
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body pt-2">
          <div class="row g-2">
            <div class="col-6">
              <label for="filterMinPrice" class="form-label small">Precio mínimo</label>
              <input type="number" step="0.01" id="filterMinPrice" class="form-control form-control-sm"
                form="adminProductSearchForm" />
            </div>
            <div class="col-6">
              <label for="filterMaxPrice" class="form-label small">Precio máximo</label>
              <input type="number" step="0.01" id="filterMaxPrice" class="form-control form-control-sm"
                form="adminProductSearchForm" />
            </div>
            <div class="col-12">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="filterOnlyActive" form="adminProductSearchForm"
                  checked />
                <label class="form-check-label small" for="filterOnlyActive">
                  Solo productos activos
                </label>
              </div>
              <div class="form-check mt-1">
                <input class="form-check-input" type="checkbox" id="filterOnlyPromo" form="adminProductSearchForm" />
                <label class="form-check-label small" for="filterOnlyPromo">
                  Solo con promo
                </label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-soft btn-sm" id="btnClearProductFilters">
            Limpiar filtros
          </button>
          <button type="submit" class="btn btn-winepick btn-sm" form="adminProductSearchForm" data-bs-dismiss="modal">
            Aplicar
          </button>
        </div>
      </div>
    </div>
  </div>
</body>

</html>