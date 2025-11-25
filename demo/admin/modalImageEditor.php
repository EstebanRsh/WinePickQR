<?php
// admin/modalImageEditor.php
// Este archivo ya estaba correcto - No necesita modificaciones
?>
<style>
  /* ====== Editor de recorte: tamaño seguro y responsive ====== */
  .cropper-container-wrapper {
    position: relative;
    width: 100%;
    height: min(70vh, 520px);
    /* 70% de alto de pantalla; máx 520px */
    overflow: hidden;
  }

  .cropper-container-wrapper img {
    max-width: 100%;
    display: block;
  }
</style>

<div class="modal fade" id="imageCropModal" tabindex="-1" aria-labelledby="imageCropModalLabel" aria-hidden="true">
  <!-- fullscreen en pantallas chicas; grande en desktop -->
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg">
    <div class="modal-content admin-product-modal">
      <div class="modal-header border-0 pb-0">
        <h1 class="modal-title fs-6" id="imageCropModalLabel">Ajustar foto</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body pt-2">
        <p class="small text-muted">
          Encuadrá la botella. Podés mover y hacer zoom con la rueda.
        </p>
        <div class="cropper-container-wrapper">
          <img id="imageCropTarget" alt="Imagen a recortar" />
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary btn-sm" id="btnCropApply">Aplicar recorte</button>
      </div>
    </div>
  </div>
</div>

<script>
  // Editor reutilizable: openImageEditor({ src, aspectRatio }) -> Promise<Blob>
  (function() {
    let cropModal = null;
    let cropper = null;
    let currentOptions = null;
    let resolveFn = null;
    let rejectFn = null;

    function cleanup() {
      if (cropper) {
        cropper.destroy();
        cropper = null;
      }
      currentOptions = null;
      resolveFn = null;
      rejectFn = null;
    }

    /**
     * Función global para abrir el editor de imágenes
     * @param {Object} options - { src: string, aspectRatio: number }
     * @returns {Promise<Blob>} - Promesa que resuelve con el Blob de la imagen recortada
     */
    window.openImageEditor = function(options) {
      return new Promise(function(resolve, reject) {
        currentOptions = options || {};
        resolveFn = resolve;
        rejectFn = reject;

        const imgEl = document.getElementById("imageCropTarget");
        const modalEl = document.getElementById("imageCropModal");
        if (!imgEl || !modalEl) {
          reject(new Error("Modal de recorte no disponible."));
          return;
        }
        imgEl.src = currentOptions.src;

        if (!cropModal && typeof bootstrap !== "undefined") {
          cropModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        }
        if (!cropModal) {
          reject(new Error("No se pudo crear el modal de recorte."));
          return;
        }
        cropModal.show();
      });
    };

    document.addEventListener("DOMContentLoaded", function() {
      const modalEl = document.getElementById("imageCropModal");
      const imgEl = document.getElementById("imageCropTarget");
      const btnApply = document.getElementById("btnCropApply");

      if (modalEl && typeof bootstrap !== "undefined") {
        cropModal = bootstrap.Modal.getOrCreateInstance(modalEl);

        modalEl.addEventListener("shown.bs.modal", function() {
          if (!imgEl || !currentOptions) return;
          if (cropper) {
            cropper.destroy();
            cropper = null;
          }

          const aspect = currentOptions.aspectRatio || (3 / 4);
          cropper = new Cropper(imgEl, {
            aspectRatio: aspect,
            viewMode: 2,
            dragMode: "move",
            autoCropArea: 0.9,
            responsive: true,
            background: false,
            zoomOnWheel: true
          });
        });

        modalEl.addEventListener("hidden.bs.modal", function() {
          if (rejectFn) rejectFn(new Error("Recorte cancelado"));
          cleanup();
        });
      }

      if (btnApply) {
        btnApply.addEventListener("click", function() {
          if (!cropper || !resolveFn) return;

          const canvas = cropper.getCroppedCanvas({
            width: 900,
            height: 1200
          });
          if (!canvas) {
            if (rejectFn) rejectFn(new Error("No se pudo generar el recorte."));
            if (cropModal) cropModal.hide();
            return;
          }
          canvas.toBlob(function(blob) {
            if (!blob) {
              if (rejectFn) rejectFn(new Error("No se pudo generar el recorte."));
            } else {
              resolveFn(blob);
            }
            if (cropModal) cropModal.hide();
          }, "image/jpeg", 0.9);
        });
      }
    });

    console.log("✓ Editor de imágenes cargado correctamente");
  })();
</script>