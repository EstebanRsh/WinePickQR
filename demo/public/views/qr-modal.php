<div
  class="modal fade"
  id="qrModal"
  tabindex="-1"
  aria-labelledby="qrModalLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <div>
          <span class="section-tag d-block mb-1">Lector QR</span>
          <h5 class="modal-title" id="qrModalLabel">
            Alineá el código en el recuadro
          </h5>
        </div>
      </div>

      <div class="modal-body pt-2">
        <div class="qr-camera-shell">
          <!-- Acá html5-qrcode pone el video -->
          <div id="qrReader"></div>
        </div>

        <p class="text-muted mt-3 small">
          Cuando el código se lea correctamente, se cargará la ficha del vino.
        </p>
      </div>

      <div class="modal-footer border-0 pt-0">
        <button
          type="button"
          id="btnToggleCamera"
          class="btn btn-outline-light btn-sm">
          Cambiar cámara
        </button>
        <button
          type="button"
          class="btn btn-outline-soft"
          data-bs-dismiss="modal">
          Cerrar
        </button>
      </div>
    </div>
  </div>
</div>