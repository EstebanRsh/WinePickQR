/*
 No Funciona, esta obsoleto
*/

<?php
// admin/vinos.php
// CRUD de vinos desde el panel de administración (usa la API pública).

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$activeAdminNav = 'vinos';
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8" />
    <title>Vinos - Panel WinePick QR</title>
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
  </head>
  <body>
    <main class="container py-4" style="padding-bottom: 5.5rem;">
      <header class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h1 class="h4 mb-1">Administrar vinos</h1>
          <p class="small text-muted mb-0">
            Alta, baja y modificación de vinos usados en la demo.
          </p>
        </div>
        <div class="text-end d-none d-md-block small text-muted">
          Admin:
          <strong>
            <?php echo htmlspecialchars($_SESSION["admin_username"] ?? ""); ?>
          </strong>
        </div>
      </header>

      <!-- Filtros / búsqueda -->
      <section class="mb-4">
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-body">
            <form
              id="filtrosForm"
              class="row gy-2 gx-3 align-items-end"
            >
              <div class="col-12 col-md-4">
                <label class="form-label small" for="search">Buscar</label>
                <input
                  type="text"
                  class="form-control form-control-sm"
                  id="search"
                  name="search"
                  placeholder="Nombre, bodega o varietal"
                />
              </div>

              <div class="col-6 col-md-2">
                <label class="form-label small" for="min_price">Precio mín.</label>
                <input
                  type="number"
                  step="0.01"
                  class="form-control form-control-sm"
                  id="min_price"
                  name="min_price"
                />
              </div>

              <div class="col-6 col-md-2">
                <label class="form-label small" for="max_price">Precio máx.</label>
                <input
                  type="number"
                  step="0.01"
                  class="form-control form-control-sm"
                  id="max_price"
                  name="max_price"
                />
              </div>

              <div class="col-6 col-md-2">
                <div class="form-check mt-3">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="promo_only"
                    name="promo_only"
                  />
                  <label class="form-check-label small" for="promo_only">
                    Solo con promo
                  </label>
                </div>
              </div>

              <div class="col-6 col-md-2 text-md-end">
                <button
                  type="submit"
                  class="btn btn-winepick btn-sm w-100"
                >
                  Buscar
                </button>
              </div>
            </form>
          </div>
        </div>
      </section>

      <!-- Listado -->
      <section class="mb-4">
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h2 class="h6 mb-0">Listado de vinos</h2>
              <button
                id="btnNuevo"
                type="button"
                class="btn btn-outline-soft btn-sm"
              >
                Nuevo vino
              </button>
            </div>

            <div class="table-responsive">
              <table class="table table-sm align-middle mb-2">
                <thead class="table-light">
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Bodega</th>
                    <th>Varietal</th>
                    <th>Precio</th>
                    <th>Promo</th>
                    <th>QR</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody id="tablaResultadosBody">
                  <!-- filas por JavaScript -->
                </tbody>
              </table>
            </div>

            <div
              id="paginacion"
              class="d-flex justify-content-center flex-wrap gap-2 mt-2"
            ></div>
          </div>
        </div>
      </section>

      <!-- Formulario de alta/edición -->
      <section>
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-body">
            <h2 class="h6 mb-3" id="formTitle">Nuevo vino</h2>

            <form id="vinoForm" enctype="multipart/form-data">
              <input type="hidden" name="id" id="vinoId" />

              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <label for="vinoNombre" class="form-label small">Nombre*</label>
                  <input
                    type="text"
                    id="vinoNombre"
                    name="nombre"
                    class="form-control form-control-sm"
                    required
                  />
                </div>

                <div class="col-12 col-md-6">
                  <label for="vinoBodega" class="form-label small">Bodega</label>
                  <input
                    type="text"
                    id="vinoBodega"
                    name="bodega"
                    class="form-control form-control-sm"
                  />
                </div>

                <div class="col-12 col-md-6">
                  <label for="vinoVarietal" class="form-label small">Varietal</label>
                  <input
                    type="text"
                    id="vinoVarietal"
                    name="varietal"
                    class="form-control form-control-sm"
                  />
                </div>

                <div class="col-12 col-md-6">
                  <label for="vinoPrecio" class="form-label small">Precio*</label>
                  <input
                    type="number"
                    step="0.01"
                    id="vinoPrecio"
                    name="precio"
                    class="form-control form-control-sm"
                    required
                  />
                </div>

                <div class="col-12">
                  <label for="vinoDescripcion" class="form-label small">
                    Descripción
                  </label>
                  <textarea
                    id="vinoDescripcion"
                    name="descripcion"
                    rows="3"
                    class="form-control form-control-sm"
                  ></textarea>
                </div>

                <div class="col-12 col-md-6">
                  <label for="vinoPromoTexto" class="form-label small">
                    Texto de promo
                  </label>
                  <input
                    type="text"
                    id="vinoPromoTexto"
                    name="promo_texto"
                    class="form-control form-control-sm"
                    placeholder="Ej: 2x1 los miércoles"
                  />
                </div>

                <div class="col-6 col-md-3">
                  <label for="promoDesde" class="form-label small">
                    Promo desde
                  </label>
                  <input
                    type="date"
                    id="promoDesde"
                    name="promo_desde"
                    class="form-control form-control-sm"
                  />
                </div>

                <div class="col-6 col-md-3">
                  <label for="promoHasta" class="form-label small">
                    Promo hasta
                  </label>
                  <input
                    type="date"
                    id="promoHasta"
                    name="promo_hasta"
                    class="form-control form-control-sm"
                  />
                </div>

                <div class="col-12 col-md-4">
                  <label class="form-label small d-block">Promo activa</label>
                  <div class="form-check form-switch">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="promoActiva"
                      name="promo_activa"
                      value="1"
                    />
                    <label
                      class="form-check-label small"
                      for="promoActiva"
                    >
                      Marcar si la promo está vigente
                    </label>
                  </div>
                </div>

                <div class="col-12 col-md-4">
                  <label for="vinoQrCode" class="form-label small">
                    Código QR (texto)
                  </label>
                  <input
                    type="text"
                    id="vinoQrCode"
                    name="qr_code"
                    class="form-control form-control-sm"
                    placeholder="Ej: QR-0001-MALBEC"
                  />
                </div>

                <div class="col-12 col-md-4">
                  <label for="vinoImagenUrl" class="form-label small">
                    URL de imagen (opcional)
                  </label>
                  <input
                    type="text"
                    id="vinoImagenUrl"
                    name="imagen_url"
                    class="form-control form-control-sm"
                  />
                </div>

                <div class="col-12 col-md-6">
                  <label for="vinoImagenFile" class="form-label small">
                    Subir imagen (opcional)
                  </label>
                  <input
                    type="file"
                    id="vinoImagenFile"
                    name="imagen_file"
                    class="form-control form-control-sm"
                    accept="image/*"
                  />
                  <div class="form-text small">
                    Si subís una imagen, el backend debería guardar el archivo y
                    completar la URL final en la base.
                  </div>
                </div>

                <div class="col-12 d-flex justify-content-between mt-2">
                  <button
                    type="submit"
                    class="btn btn-winepick btn-sm"
                  >
                    Guardar
                  </button>
                  <button
                    type="button"
                    id="btnLimpiar"
                    class="btn btn-outline-soft btn-sm"
                  >
                    Limpiar formulario
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </section>
    </main>

    <?php include __DIR__ . '/bottom-nav-admin.php'; ?>

    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      crossorigin="anonymous"
    ></script>

    <script>
      const API_BASE = "../api/vinos.php";

      const filtrosForm = document.getElementById("filtrosForm");
      const tablaBody   = document.getElementById("tablaResultadosBody");
      const paginacion  = document.getElementById("paginacion");

      const vinoForm    = document.getElementById("vinoForm");
      const formTitle   = document.getElementById("formTitle");

      const campoId          = document.getElementById("vinoId");
      const campoNombre      = document.getElementById("vinoNombre");
      const campoBodega      = document.getElementById("vinoBodega");
      const campoVarietal    = document.getElementById("vinoVarietal");
      const campoPrecio      = document.getElementById("vinoPrecio");
      const campoDescripcion = document.getElementById("vinoDescripcion");
      const campoPromoTexto  = document.getElementById("vinoPromoTexto");
      const campoPromoDesde  = document.getElementById("promoDesde");
      const campoPromoHasta  = document.getElementById("promoHasta");
      const campoPromoActiva = document.getElementById("promoActiva");
      const campoQrCode      = document.getElementById("vinoQrCode");
      const campoImagenUrl   = document.getElementById("vinoImagenUrl");
      const campoImagenFile  = document.getElementById("vinoImagenFile");

      const btnNuevo   = document.getElementById("btnNuevo");
      const btnLimpiar = document.getElementById("btnLimpiar");

      let currentPage = 1;

      // --- Filtros / listado ---
      function getFiltros(page = 1) {
        const formData = new FormData(filtrosForm);
        const params   = {};

        const search = formData.get("search");
        if (search) {
          params["search"] = search;
        }

        const minPrice = formData.get("min_price");
        if (minPrice) {
          params["min_price"] = minPrice;
        }

        const maxPrice = formData.get("max_price");
        if (maxPrice) {
          params["max_price"] = maxPrice;
        }

        if (formData.get("promo_only")) {
          params["promo_only"] = "1";
        }

        params["accion"] = "listar";
        params["page"]   = String(page);

        return params;
      }

      async function cargarPagina(page = 1) {
        const filtros     = getFiltros(page);
        const queryString = new URLSearchParams(filtros).toString();

        try {
          const response = await fetch(API_BASE + "?" + queryString);
          const json     = await response.json();

          renderListado(json);
        } catch (error) {
          console.error(error);
          tablaBody.innerHTML =
            "<tr><td colspan='8' class='text-muted'>Error al cargar la lista.</td></tr>";
        }
      }

      function renderListado(json) {
        const data       = json.data || [];
        const page       = json.page || 1;
        const totalPages = json.total_pages || 1;

        tablaBody.innerHTML = "";

        if (!data.length) {
          tablaBody.innerHTML =
            "<tr><td colspan='8' class='text-muted'>No hay resultados.</td></tr>";
        } else {
          data.forEach((vino) => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
              <td>${vino.id}</td>
              <td>${escapeHtml(vino.nombre || "")}</td>
              <td>${vino.bodega ? escapeHtml(vino.bodega) : ""}</td>
              <td>${vino.varietal ? escapeHtml(vino.varietal) : ""}</td>
              <td>$ ${vino.precio != null ? Number(vino.precio).toFixed(2) : ""}</td>
              <td>${vino.promo_texto ? escapeHtml(vino.promo_texto) : ""}</td>
              <td>${vino.qr_code ? escapeHtml(vino.qr_code) : ""}</td>
              <td>
                <button
                  type="button"
                  class="btn btn-sm btn-outline-soft me-1"
                  data-accion="editar"
                  data-id="${vino.id}"
                >
                  Editar
                </button>
                <button
                  type="button"
                  class="btn btn-sm btn-outline-danger"
                  data-accion="eliminar"
                  data-id="${vino.id}"
                >
                  Eliminar
                </button>
              </td>
            `;
            tablaBody.appendChild(tr);
          });
        }

        renderPaginacion(page, totalPages);
        conectarAccionesFila();
        currentPage = page;
      }

      function renderPaginacion(page, totalPages) {
        paginacion.innerHTML = "";

        const btnPrev = document.createElement("button");
        btnPrev.type = "button";
        btnPrev.textContent = "Anterior";
        btnPrev.className   = "btn btn-sm btn-outline-soft";
        btnPrev.disabled    = page <= 1;
        btnPrev.addEventListener("click", () => {
          if (page > 1) cargarPagina(page - 1);
        });

        const spanInfo = document.createElement("span");
        spanInfo.className = "small text-muted mx-2";
        spanInfo.textContent = `Página ${page} de ${totalPages}`;

        const btnNext = document.createElement("button");
        btnNext.type = "button";
        btnNext.textContent = "Siguiente";
        btnNext.className   = "btn btn-sm btn-outline-soft";
        btnNext.disabled    = page >= totalPages;
        btnNext.addEventListener("click", () => {
          if (page < totalPages) cargarPagina(page + 1);
        });

        paginacion.appendChild(btnPrev);
        paginacion.appendChild(spanInfo);
        paginacion.appendChild(btnNext);
      }

      function conectarAccionesFila() {
        const botones = tablaBody.querySelectorAll("button[data-accion]");

        botones.forEach((button) => {
          const accion = button.getAttribute("data-accion");
          const id     = button.getAttribute("data-id");

          if (accion === "editar") {
            button.addEventListener("click", () => {
              cargarDetalle(id);
            });
          }

          if (accion === "eliminar") {
            button.addEventListener("click", () => {
              eliminarVino(id);
            });
          }
        });
      }

      async function cargarDetalle(id) {
        try {
          const response = await fetch(
            API_BASE + "?accion=detalle&id=" + encodeURIComponent(id)
          );
          const vino = await response.json();
          llenarFormulario(vino);
        } catch (error) {
          console.error(error);
          alert("Error al cargar el vino.");
        }
      }

      function llenarFormulario(vino) {
        formTitle.textContent = "Editar vino #" + vino.id;
        campoId.value         = vino.id || "";
        campoNombre.value     = vino.nombre || "";
        campoBodega.value     = vino.bodega || "";
        campoVarietal.value   = vino.varietal || "";
        campoPrecio.value     = vino.precio || "";
        campoDescripcion.value= vino.descripcion || "";
        campoPromoTexto.value = vino.promo_texto || "";
        campoPromoDesde.value = vino.promo_desde || "";
        campoPromoHasta.value = vino.promo_hasta || "";
        campoPromoActiva.checked = !!Number(vino.promo_activa || 0);
        campoQrCode.value     = vino.qr_code || "";
        campoImagenUrl.value  = vino.imagen_url || "";
        campoImagenFile.value = "";
      }

      function limpiarFormulario() {
        formTitle.textContent = "Nuevo vino";
        campoId.value         = "";
        campoNombre.value     = "";
        campoBodega.value     = "";
        campoVarietal.value   = "";
        campoPrecio.value     = "";
        campoDescripcion.value= "";
        campoPromoTexto.value = "";
        campoPromoDesde.value = "";
        campoPromoHasta.value = "";
        campoPromoActiva.checked = false;
        campoQrCode.value     = "";
        campoImagenUrl.value  = "";
        campoImagenFile.value = "";
      }

      async function guardarVino(event) {
        event.preventDefault();

        if (!vinoForm.reportValidity()) {
          return;
        }

        const id    = campoId.value ? Number(campoId.value) : null;
        const data  = new FormData(vinoForm);
        const accion = id ? "actualizar" : "crear";

        data.append("accion", accion);

        try {
          const response = await fetch(API_BASE, {
            method: "POST",
            body: data,
          });

          const json = await response.json();

          if (json.error) {
            alert("Error: " + json.error);
            return;
          }

          alert("Vino guardado correctamente.");
          limpiarFormulario();
          cargarPagina(currentPage);
        } catch (error) {
          console.error(error);
          alert("Error al guardar el vino.");
        }
      }

      async function eliminarVino(id) {
        const confirmado = confirm(
          "¿Seguro que querés eliminar el vino #" + id + "?"
        );
        if (!confirmado) return;

        const data = new FormData();
        data.append("accion", "eliminar");
        data.append("id", String(id));

        try {
          const response = await fetch(API_BASE, {
            method: "POST",
            body: data,
          });

          const json = await response.json();

          if (json.error) {
            alert("Error al eliminar: " + json.error);
            return;
          }

          alert("Vino eliminado correctamente.");
          cargarPagina(currentPage);
        } catch (error) {
          console.error(error);
          alert("Error al eliminar.");
        }
      }

      function escapeHtml(text) {
        if (text == null) return "";
        return String(text)
          .replace(/&/g, "&amp;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/"/g, "&quot;")
          .replace(/'/g, "&#039;");
      }

      // Eventos principales
      filtrosForm.addEventListener("submit", (event) => {
        event.preventDefault();
        cargarPagina(1);
      });

      vinoForm.addEventListener("submit", guardarVino);
      btnNuevo.addEventListener("click", limpiarFormulario);
      btnLimpiar.addEventListener("click", limpiarFormulario);

      // Carga inicial
      cargarPagina(currentPage);
    </script>
  </body>
</html>
