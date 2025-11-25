<div class="modal fade modal-producto" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
    <div class="modal-content shadow-lg">

      <div class="modal-header border-0 p-0 position-relative">
        <button type="button" class="btn-close btn-close-absolute" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body pt-0 pb-4 px-4">
        <div class="row g-4 align-items-center">

          <div class="col-12 col-md-5">
            <div class="product-showcase">
              <img id="productModalImagen" src="" alt="Botella" class="product-showcase-img" style="display: none;" />
            </div>
          </div>

          <div class="col-12 col-md-7">
            <div class="product-info-col">

              <span id="productModalBodegaVarietal" class="product-kicker"></span>

              <h2 class="product-title" id="productModalNombre"></h2>

              <div class="product-price-box">
                <span class="product-price-currency">$</span>
                <span id="productModalPrecio" class="product-price-value"></span>
                <div id="productModalPromo" class="product-promo-badge ms-2" style="display:none"></div>
              </div>

              <div class="product-description mb-4">
                <p id="productModalDescripcion"></p>
              </div>

              <div class="product-meta border-top pt-3 d-flex align-items-center text-muted small">
                <i class="bi bi-qr-code me-2"></i>
                <span>Cod: <span id="productModalQr" class="font-monospace"></span></span>
              </div>

            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer border-0 d-md-none justify-content-center pb-4 px-4">
        <button type="button" class="btn btn-winepick w-100 py-2 shadow-sm" data-bs-dismiss="modal">
          Cerrar ficha
        </button>
      </div>

    </div>
  </div>
</div>