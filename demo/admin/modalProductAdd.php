<?php
// admin/modalProductAdd.php
// VERSIÓN CORREGIDA - 19/11/2025 (con generación de QR para productos)
?>
<div
  class="modal fade"
  id="productModal"
  tabindex="-1"
  aria-labelledby="productModalLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content admin-modal admin-modal-product">
      <div class="modal-header border-0 pb-0">
        <div>
          <h1 class="modal-title fs-5" id="productModalLabel">Nuevo producto</h1>
          <p class="admin-modal-subtitle mb-0">
            Completá los datos básicos y sumá fotos como si lo publicaras en un marketplace.
          </p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body pt-3">
        <form id="productForm" class="row g-3">
          <!-- Campos de producto -->
          <input type="hidden" id="fieldId" />

          <div class="col-12 col-md-4">
            <label for="fieldPid" class="form-label form-label-sm">SKU / Código interno *</label>
            <input
              type="text"
              class="form-control form-control-sm"
              id="fieldPid"
              maxlength="50"
              placeholder="Ej: WP-2024-MALB-001"
              required />
            <div class="form-text form-text-sm">
              Tiene que ser único. Si repetís un SKU, el sistema te va a avisar.
            </div>
          </div>

          <!-- Sección: Código QR del producto -->
          <div class="col-12 col-md-4">
            <label class="form-label form-label-sm d-flex align-items-center">
              Código QR
              <span class="badge bg-light text-muted ms-2">Se genera al guardar</span>
            </label>
            <div
              id="productQrContainer"
              class="border rounded p-2 text-center bg-light-subtle small text-muted">
              <span id="productQrPlaceholder" class="d-block mb-1">
                Guardá el producto para generar el código QR.
              </span>
              <img
                id="productQrImage"
                src=""
                alt="Código QR del producto"
                class="img-fluid d-none"
                style="max-height: 140px;" />
              <button
                type="button"
                id="productQrDownload"
                class="btn btn-outline-secondary btn-sm mt-2 d-none">
                Descargar QR
              </button>
            </div>
          </div>

          <div class="col-12 col-md-8">
            <label for="fieldName" class="form-label form-label-sm">Nombre del producto *</label>
            <input
              type="text"
              class="form-control form-control-sm"
              id="fieldName"
              maxlength="100"
              placeholder="Malbec Reserva 2020 - Bodega X"
              required />
          </div>

          <div class="col-12 col-md-4">
            <label for="fieldProducer" class="form-label form-label-sm">Bodega / Productor</label>
            <input
              type="text"
              class="form-control form-control-sm"
              id="fieldProducer"
              maxlength="100"
              placeholder="Bodega X / Productor Y" />
          </div>

          <div class="col-12 col-md-4">
            <label for="fieldVarietal" class="form-label form-label-sm">Varietal / Tipo</label>
            <input
              type="text"
              class="form-control form-control-sm"
              id="fieldVarietal"
              maxlength="100"
              placeholder="Malbec / Blend / Gin / etc." />
          </div>

          <div class="col-12 col-md-4">
            <label for="fieldOrigin" class="form-label form-label-sm">Origen</label>
            <input
              type="text"
              class="form-control form-control-sm"
              id="fieldOrigin"
              maxlength="100"
              placeholder="Mendoza, Argentina" />
          </div>

          <div class="col-12 col-md-2">
            <label for="fieldYear" class="form-label form-label-sm">Año</label>
            <input
              type="number"
              class="form-control form-control-sm"
              id="fieldYear"
              min="1900"
              max="2100"
              step="1"
              placeholder="2020" />
          </div>

          <div class="col-12 col-md-3">
            <label for="fieldPrice" class="form-label form-label-sm">Precio de lista *</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text">$</span>
              <input
                type="number"
                class="form-control form-control-sm"
                id="fieldPrice"
                min="0"
                step="0.01"
                required />
            </div>
            <div class="form-text form-text-sm">
              Precio final al público (con impuestos).
            </div>
          </div>

          <div class="col-12 col-md-3">
            <label for="fieldStock" class="form-label form-label-sm">Estado de stock *</label>
            <select
              id="fieldStock"
              class="form-select form-select-sm"
              required>
              <option value="AVAILABLE">Disponible</option>
              <option value="LOW_STOCK">Poco stock</option>
              <option value="OUT_OF_STOCK">Sin stock</option>
            </select>
          </div>

          <div class="col-12 col-md-2 d-flex align-items-end">
            <div class="form-check form-switch">
              <input
                class="form-check-input"
                type="checkbox"
                id="fieldActive"
                checked />
              <label class="form-check-label form-check-label-sm" for="fieldActive">
                Publicado
              </label>
            </div>
          </div>

          <div class="col-12">
            <label for="fieldShortDescription" class="form-label form-label-sm">
              Descripción corta
            </label>
            <textarea
              class="form-control form-control-sm"
              id="fieldShortDescription"
              rows="2"
              maxlength="300"
              placeholder="Un resumen rápido que ayude a vender el producto.">
            </textarea>
            <div class="d-flex justify-content-between">
              <div class="form-text form-text-sm">
                Se muestra en listados y resultados de búsqueda.
              </div>
              <div class="form-text form-text-sm">
                <span id="shortDescriptionCounter">0</span>/300 caracteres
              </div>
            </div>
          </div>

          <!-- Separador -->
          <div class="col-12">
            <hr class="my-3" />
          </div>

          <!-- Sección de imágenes -->
          <div class="col-12 col-lg-5">
            <label class="form-label form-label-sm d-flex justify-content-between align-items-center">
              Imágenes del producto
              <span class="badge bg-light text-muted">
                <span id="pendingImagesCounter">0</span> pendientes de subir
              </span>
            </label>

            <div
              id="imageDropZone"
              class="border border-dashed rounded-3 p-3 text-center mb-2 admin-dropzone">
              <p class="mb-1 small">
                Arrastrá y soltá imágenes aquí, o hacé clic para seleccionarlas.
              </p>
              <p class="mb-0 text-muted small">
                Formatos soportados: JPG, PNG, WEBP. Tamaño máx. 5 MB por imagen.
              </p>
              <input
                type="file"
                id="fieldImages"
                class="d-none"
                accept="image/jpeg,image/png,image/webp"
                multiple />
              <button
                type="button"
                id="btnSelectImages"
                class="btn btn-outline-secondary btn-sm mt-2">
                Seleccionar imágenes
              </button>
            </div>

            <div class="form-text form-text-sm">
              Podés recortar las imágenes antes de subirlas. El orden se respeta para el carousel.
            </div>
          </div>

          <div class="col-12 col-lg-7">
            <label class="form-label form-label-sm d-flex justify-content-between align-items-center">
              Vista previa
              <span class="text-muted small">Hacé clic en una imagen para recortarla</span>
            </label>
            <div
              id="pendingImagesPreview"
              class="admin-pending-images-preview row g-2">
              <!-- Acá se inyectan las previews de imágenes pendientes -->
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-0 pt-2">
        <div class="me-auto">
          <div id="productModalAlertContainer"></div>
        </div>
        <button
          type="button"
          class="btn btn-outline-secondary btn-sm"
          data-bs-dismiss="modal">
          Cancelar
        </button>
        <button
          type="submit"
          form="productForm"
          class="btn btn-primary btn-sm"
          id="btnSaveProduct">
          Guardar producto
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  (function() {
    "use strict";

    // ============================================================================
    // VARIABLES GLOBALES
    // ============================================================================
    let productModal = null;
    let isSaving = false;
    let pendingImages = []; // Imágenes seleccionadas pero aún no subidas

    // ============================================================================
    // FUNCIONES HELPER
    // ============================================================================

    /**
     * Muestra un alert local dentro del modal, sin refrescar la página
     */
    function showLocalAlert(message, type = "success") {
      const alertContainer = document.getElementById("productModalAlertContainer");
      if (!alertContainer) return;

      alertContainer.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show py-2 px-3 mb-0" role="alert">
          <small>${message}</small>
          <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      `;

      // Auto-ocultar después de 3 segundos
      setTimeout(() => {
        const alert = alertContainer.querySelector(".alert");
        if (alert) {
          alert.classList.remove("show");
          setTimeout(() => {
            alertContainer.innerHTML = "";
          }, 150);
        }
      }, 3000);
    }

    /**
     * Actualiza el contador de caracteres de la descripción
     */
    function updateShortDescriptionCounter() {
      const textarea = document.getElementById("fieldShortDescription");
      const counter = document.getElementById("shortDescriptionCounter");
      if (textarea && counter) {
        counter.textContent = textarea.value.length;
      }
    }

    /**
     * Descarga un archivo dado su URL y nombre sugerido
     */
    function downloadFile(url, fileName) {
      if (!url) return;

      const link = document.createElement("a");
      link.href = url;
      if (fileName) {
        link.download = fileName;
      }
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }

    /**
     * Genera el código QR en el backend y actualiza la vista.
     * Se llama luego de guardar el producto.
     *
     * @param {number} productId
     * @param {string} pid
     */
    async function handleQrAfterSave(productId, pid) {
      try {
        if (!productId || Number.isNaN(Number(productId))) {
          console.warn("handleQrAfterSave: productId inválido", productId);
          return;
        }

        const qrEndpoint =
          typeof QR_GENERATOR_API_URL !== "undefined"
            ? QR_GENERATOR_API_URL
            : "../api/generadorcodeqr.php";

        const response = await fetch(qrEndpoint, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            product_id: productId,
            pid: pid || undefined,
          }),
        });

        if (!response.ok) {
          throw new Error("No se pudo generar el código QR.");
        }

        const data = await response.json();

        if (!data || data.success !== true || !data.qr_url) {
          throw new Error(
            data && data.error ? data.error : "Respuesta inválida del generador QR."
          );
        }

        const qrUrl = data.qr_url;
        const fileName = data.file_name || `product_${productId}_qr.png`;

        // Actualizar UI en el modal
        const qrImg = document.getElementById("productQrImage");
        const qrContainer = document.getElementById("productQrContainer");
        const qrPlaceholder = document.getElementById("productQrPlaceholder");
        const qrDownloadBtn = document.getElementById("productQrDownload");

        if (qrImg) {
          // Cache busting
          qrImg.src = qrUrl + "?t=" + Date.now();
          qrImg.classList.remove("d-none");
        }

        if (qrPlaceholder) {
          qrPlaceholder.classList.add("d-none");
        }

        if (qrContainer) {
          qrContainer.classList.remove("bg-light-subtle");
        }

        if (qrDownloadBtn) {
          qrDownloadBtn.classList.remove("d-none");
          qrDownloadBtn.onclick = function() {
            downloadFile(qrUrl, fileName);
          };
        }

        const shouldDownload = window.confirm(
          "El producto se guardó correctamente.\n¿Querés descargar ahora el código QR del producto?"
        );

        if (shouldDownload) {
          downloadFile(qrUrl, fileName);
        }
      } catch (error) {
        console.error("Error al generar/mostrar QR:", error);
        if (typeof showLocalAlert === "function") {
          showLocalAlert(
            "El producto se guardó, pero no se pudo generar el código QR.",
            "warning"
          );
        }
      }
    }

    // ============================================================================
    // GESTIÓN DE IMÁGENES PENDIENTES
    // ============================================================================

    /**
     * Maneja la selección de imágenes desde el input file
     */
    function handleImageInputChange(event) {
      const files = Array.from(event.target.files || []);
      if (!files.length) return;

      const maxSize = 5 * 1024 * 1024; // 5MB

      files.forEach((file) => {
        if (!file.type.match(/^image\/(jpeg|png|webp)$/)) {
          showLocalAlert(
            `⚠️ "${file.name}" no es un formato válido (JPG, PNG, WEBP)`,
            "warning"
          );
          return;
        }

        if (file.size > maxSize) {
          showLocalAlert(
            `⚠️ "${file.name}" supera el tamaño máximo permitido (5MB).`,
            "warning"
          );
          return;
        }

        // Crear preview con FileReader
        const reader = new FileReader();
        const tempId = "pending_" + Date.now() + "_" + Math.random();

        reader.onload = function(e) {
          const previewUrl = e.target.result;

          pendingImages.push({
            id: tempId,
            file: file,
            blob: null,
            filename: file.name,
            previewUrl: previewUrl,
          });

          console.log(`Imagen agregada a pendientes: ${file.name}`);
          renderPendingImages();
        };

        reader.onerror = function() {
          console.error(`Error al leer ${file.name}`);
          showLocalAlert(`✗ Error al leer "${file.name}"`, "danger");
        };

        reader.readAsDataURL(file);
      });

      // Limpia el input para permitir volver a elegir las mismas imágenes
      event.target.value = "";
    }

    /**
     * Renderiza las imágenes pendientes en el contenedor
     */
    function renderPendingImages() {
      const container = document.getElementById("pendingImagesPreview");
      const counter = document.getElementById("pendingImagesCounter");
      if (!container) return;

      container.innerHTML = "";

      if (!pendingImages.length) {
        container.innerHTML =
          '<p class="text-muted small mb-0">Todavía no seleccionaste imágenes.</p>';
      } else {
        pendingImages.forEach((img) => {
          const col = document.createElement("div");
          col.className = "col-4 col-md-3";

          col.innerHTML = `
            <div class="position-relative admin-pending-image-item">
              <img
                src="${img.previewUrl}"
                alt="${img.filename}"
                class="img-fluid rounded shadow-sm admin-pending-image-thumb"
                data-pending-id="${img.id}" />
              <button
                type="button"
                class="btn btn-sm btn-danger admin-pending-image-remove"
                data-pending-id="${img.id}"
                title="Quitar imagen">
                &times;
              </button>
            </div>
          `;

          container.appendChild(col);
        });
      }

      if (counter) {
        counter.textContent = pendingImages.length.toString();
      }

      // Atachar handlers de recorte y eliminación
      container.querySelectorAll(".admin-pending-image-thumb").forEach((imgEl) => {
        imgEl.addEventListener("click", handleCropPendingImage);
      });

      container.querySelectorAll(".admin-pending-image-remove").forEach((btn) => {
        btn.addEventListener("click", handleRemovePendingImage);
      });
    }

    /**
     * Maneja el recorte de una imagen pendiente usando Cropper.js (si está disponible)
     */
    function handleCropPendingImage(event) {
      const imgEl = event.currentTarget;
      const pendingId = imgEl.getAttribute("data-pending-id");
      if (!pendingId) return;

      const imgData = pendingImages.find((i) => i.id === pendingId);
      if (!imgData) return;

      if (typeof Cropper === "undefined") {
        console.warn("Cropper.js no está disponible. No se puede recortar.");
        return;
      }

      const modalEl = document.createElement("div");
      modalEl.className = "modal fade";
      modalEl.setAttribute("tabindex", "-1");
      modalEl.innerHTML = `
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header py-2">
              <h5 class="modal-title">Recortar imagen</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
              <div class="ratio ratio-4x3">
                <img src="${imgData.previewUrl}" alt="Imagen a recortar" id="cropperImage" />
              </div>
            </div>
            <div class="modal-footer py-2">
              <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                Cancelar
              </button>
              <button type="button" class="btn btn-primary btn-sm" id="btnApplyCrop">
                Aplicar recorte
              </button>
            </div>
          </div>
        </div>
      `;

      document.body.appendChild(modalEl);
      const bsModal = new bootstrap.Modal(modalEl);

      bsModal.show();

      const imgCropperEl = modalEl.querySelector("#cropperImage");
      let cropper = null;

      modalEl.addEventListener("shown.bs.modal", function() {
        cropper = new Cropper(imgCropperEl, {
          viewMode: 1,
          aspectRatio: 3 / 4,
        });
      });

      modalEl.addEventListener("hidden.bs.modal", function() {
        if (cropper) {
          cropper.destroy();
          cropper = null;
        }
        modalEl.remove();
      });

      const btnApplyCrop = modalEl.querySelector("#btnApplyCrop");
      btnApplyCrop.addEventListener("click", function() {
        if (!cropper) return;

        cropper.getCroppedCanvas().toBlob(
          function(croppedBlob) {
            if (!croppedBlob) {
              console.error("No se pudo obtener el blob recortado.");
              return;
            }

            console.log("Imagen recortada exitosamente");

            imgData.blob = croppedBlob;

            if (imgData.previewUrl) {
              URL.revokeObjectURL(imgData.previewUrl);
            }

            imgData.previewUrl = URL.createObjectURL(croppedBlob);
            renderPendingImages();

            bsModal.hide();
          },
          "image/jpeg",
          0.9
        );
      });
    }

    /**
     * Elimina una imagen pendiente
     */
    function handleRemovePendingImage(event) {
      const btn = event.currentTarget;
      const pendingId = btn.getAttribute("data-pending-id");
      if (!pendingId) return;

      pendingImages = pendingImages.filter((img) => img.id !== pendingId);
      renderPendingImages();
    }

    /**
     * Sube las imágenes pendientes al backend
     * @param {number} productId
     */
    async function uploadPendingImages(productId) {
      if (!pendingImages.length || !productId) {
        console.log("No hay imágenes pendientes o falta productId");
        return;
      }

      const url = "../api/product_images.php?action=upload";

      for (const img of pendingImages) {
        const formData = new FormData();
        formData.append("product_id", productId);

        if (img.blob) {
          formData.append("image", img.blob, img.filename || "image.jpg");
        } else if (img.file) {
          formData.append("image", img.file, img.filename || img.file.name);
        } else {
          console.warn("Imagen sin blob ni file, se ignora:", img);
          continue;
        }

        try {
          const response = await fetch(url, {
            method: "POST",
            body: formData,
          });

          if (!response.ok) {
            const text = await response.text();
            console.error("Error al subir imagen:", text);
            showLocalAlert("No se pudo subir una de las imágenes.", "warning");
          } else {
            const data = await response.json();
            if (!data || data.success !== true) {
              console.error("Respuesta inesperada al subir imagen:", data);
            }
          }
        } catch (error) {
          console.error("Error al subir imagen:", error);
          showLocalAlert("Error de red al subir una imagen.", "warning");
        }
      }

      // Limpia la cola de pendientes
      pendingImages.forEach((img) => {
        if (img.previewUrl && img.previewUrl.startsWith("blob:")) {
          URL.revokeObjectURL(img.previewUrl);
        }
      });
      pendingImages = [];
      renderPendingImages();

      const counter = document.getElementById("pendingImagesCounter");
      if (counter) counter.textContent = "0";
    }

    // ============================================================================
    // MANEJO DEL FORMULARIO
    // ============================================================================

    /**
     * Limpia el formulario de producto
     */
    function resetProductForm() {
      const form = document.getElementById("productForm");
      if (!form) return;

      form.reset();
      document.getElementById("fieldId").value = "";
      const counter = document.getElementById("shortDescriptionCounter");
      if (counter) counter.textContent = "0";

      // Limpia imágenes pendientes
      pendingImages.forEach((img) => {
        if (img.previewUrl && img.previewUrl.startsWith("blob:")) {
          URL.revokeObjectURL(img.previewUrl);
        }
      });
      pendingImages = [];
      renderPendingImages();

      // Reset sección QR
      const qrImg = document.getElementById("productQrImage");
      const qrPlaceholder = document.getElementById("productQrPlaceholder");
      const qrDownloadBtn = document.getElementById("productQrDownload");
      const qrContainer = document.getElementById("productQrContainer");

      if (qrImg) {
        qrImg.src = "";
        qrImg.classList.add("d-none");
      }
      if (qrPlaceholder) {
        qrPlaceholder.classList.remove("d-none");
      }
      if (qrDownloadBtn) {
        qrDownloadBtn.classList.add("d-none");
        qrDownloadBtn.onclick = null;
      }
      if (qrContainer) {
        if (!qrContainer.classList.contains("bg-light-subtle")) {
          qrContainer.classList.add("bg-light-subtle");
        }
      }
    }

    /**
     * Maneja el envío del formulario
     */
    async function handleProductFormSubmit(event) {
      event.preventDefault();

      if (isSaving) {
        console.log("Ya hay un guardado en proceso");
        return;
      }

      isSaving = true;

      const id = document.getElementById("fieldId").value;
      const pid = document.getElementById("fieldPid").value.trim();
      const name = document.getElementById("fieldName").value.trim();
      const producer = document.getElementById("fieldProducer").value.trim();
      const varietal = document.getElementById("fieldVarietal").value.trim();
      const origin = document.getElementById("fieldOrigin").value.trim();
      const yearVal = document.getElementById("fieldYear").value;
      const priceStr = document.getElementById("fieldPrice").value;
      const stock = document.getElementById("fieldStock").value;
      const shortDesc = document.getElementById("fieldShortDescription").value.trim();

      if (!pid || !name || !priceStr || !stock) {
        showLocalAlert("Por favor completá todos los campos obligatorios.", "danger");
        isSaving = false;
        return;
      }

      const payload = {
        pid: pid,
        name: name,
        producer: producer || null,
        varietal: varietal || null,
        origin: origin || null,
        year: yearVal ? Number(yearVal) : null,
        short_description: shortDesc || null,
        list_price: priceStr ? Number(priceStr) : null,
        stock_status: stock,
        active: document.getElementById("fieldActive").checked ? 1 : 0,
      };

      const isEdit = !!id;
      const action = isEdit ? "update" : "create";
      if (isEdit) {
        payload.id = Number(id);
      }

      const url = "../api/product.php?action=" + action;

      try {
        const response = await fetch(url, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(payload),
        });

        if (!response.ok) {
          let errorMessage = "No se pudo guardar el producto.";

          try {
            const err = await response.json();
            if (err && typeof err === "object") {
              if (err.details) {
                errorMessage = err.details;
              } else if (err.error) {
                errorMessage = err.error;
              }
            }
          } catch (parseError) {
            try {
              const text = await response.text();
              console.error("Error (respuesta cruda):", text);
            } catch (e) {}
          }

          console.error("Error al guardar producto:", errorMessage);
          showLocalAlert(errorMessage, "danger");
          return;
        }

        const data = await response.json();

        let productId;
        if (isEdit) {
          productId = Number(id);
        } else {
          productId = data.id;
          const idInput = document.getElementById("fieldId");
          if (idInput) idInput.value = productId;
        }

        try {
          await uploadPendingImages(productId);
        } catch (err) {
          console.error("Error al subir imágenes:", err);
        }

        // Generar y manejar el código QR del producto
        try {
          await handleQrAfterSave(productId, pid);
        } catch (err) {
          console.error("Error al generar/mostrar el QR:", err);
        }

        showLocalAlert(
          isEdit ? "Producto actualizado." : "Producto creado correctamente.",
          "success"
        );

        if (productModal) {
          productModal.hide();
        }

        if (typeof loadProducts === "function") {
          if (typeof currentPage === "number" && currentPage > 0) {
            await loadProducts(currentPage);
          } else {
            await loadProducts(1);
          }
        }
      } catch (error) {
        console.error(error);
        const msg =
          error && error.message ?
          error.message :
          "No se pudo guardar el producto.";
        showLocalAlert(msg, "danger");
      } finally {
        isSaving = false;
      }
    }

    /**
     * Limpia recursos al cerrar el modal
     */
    function handleModalHidden() {
      resetProductForm();
    }

    // ============================================================================
    // INICIALIZACIÓN
    // ============================================================================

    document.addEventListener("DOMContentLoaded", function() {
      console.log("✓ Modal de producto inicializado (versión corregida con QR)");

      const modalEl = document.getElementById("productModal");
      if (modalEl && typeof bootstrap !== "undefined") {
        productModal = new bootstrap.Modal(modalEl, {
          backdrop: "static",
          keyboard: false,
        });

        modalEl.addEventListener("hidden.bs.modal", handleModalHidden);

        modalEl.addEventListener("show.bs.modal", function(event) {
          const triggerButton = event.relatedTarget;
          const id =
            triggerButton && triggerButton.getAttribute("data-product-id");

          const titleEl = document.getElementById("productModalLabel");
          if (titleEl) {
            titleEl.textContent = id ? "Editar producto" : "Nuevo producto";
          }

          if (!id) {
            resetProductForm();
          } else {
            const product = window.productsCache ?
              window.productsCache.find(function(p) {
                return p.id === Number(id);
              }) :
              null;

            if (product) {
              document.getElementById("fieldId").value = product.id;
              document.getElementById("fieldPid").value = product.pid || "";
              document.getElementById("fieldName").value = product.name || "";
              document.getElementById("fieldProducer").value =
                product.producer || "";
              document.getElementById("fieldVarietal").value =
                product.varietal || "";
              document.getElementById("fieldOrigin").value =
                product.origin || "";
              document.getElementById("fieldYear").value =
                product.year || "";
              document.getElementById("fieldPrice").value =
                product.list_price || "";
              document.getElementById("fieldStock").value =
                product.stock_status || "AVAILABLE";
              document.getElementById("fieldActive").checked =
                product.active == 1;
              document.getElementById("fieldShortDescription").value =
                product.short_description || "";
            } else {
              resetProductForm();
            }
          }

          const btnSave = document.getElementById("btnSaveProduct");
          if (btnSave) {
            btnSave.textContent = id ? "Guardar cambios" : "Guardar producto";
          }
        });
      }

      const form = document.getElementById("productForm");
      if (form) {
        form.addEventListener("submit", handleProductFormSubmit);
      }

      const inputImages = document.getElementById("fieldImages");
      if (inputImages) {
        inputImages.addEventListener("change", handleImageInputChange);
      }

      const btnSelectImages = document.getElementById("btnSelectImages");
      const dropZone = document.getElementById("imageDropZone");
      if (btnSelectImages && inputImages) {
        btnSelectImages.addEventListener("click", function() {
          inputImages.click();
        });
      }

      if (dropZone) {
        dropZone.addEventListener("dragover", function(event) {
          event.preventDefault();
          dropZone.classList.add("admin-dropzone-hover");
        });

        dropZone.addEventListener("dragleave", function(event) {
          event.preventDefault();
          dropZone.classList.remove("admin-dropzone-hover");
        });

        dropZone.addEventListener("drop", function(event) {
          event.preventDefault();
          dropZone.classList.remove("admin-dropzone-hover");

          const dtFiles = Array.from(event.dataTransfer.files || []);
          if (!dtFiles.length) return;

          const fakeEvent = {
            target: {
              files: dtFiles
            }
          };
          handleImageInputChange(fakeEvent);
        });
      }

      const shortDesc = document.getElementById("fieldShortDescription");
      if (shortDesc) {
        shortDesc.addEventListener("input", updateShortDescriptionCounter);
        updateShortDescriptionCounter();
      }
    });
  })();
</script>
