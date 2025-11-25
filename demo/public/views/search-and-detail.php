<section class="section pt-0" id="buscador">
  <div class="container py-3">
    <!-- Info de listado -->
    <div id="listInfo" class="small text-muted mb-2"></div>

    <!-- RESULTADOS -->
    <div id="listInfo" class="small text-muted mb-2"></div>

    <div class="vino-grid-wrapper mb-3">
      <div id="tablaResultadosBody" class="vino-grid">
        <!-- cards generadas por JavaScript -->
      </div>
    </div>

    <div id="paginacion" class="d-flex justify-content-center flex-wrap gap-2 mb-4"></div>
  </div>

  <!-- MODAL DE FILTROS -->
  <div class="modal fade" id="filtersModal" tabindex="-1" aria-labelledby="filtersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title small text-uppercase text-muted" id="filtersModalLabel">
            Filtros de búsqueda
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body pt-2">
          <!-- Campos que ya existían, ahora dentro del modal -->
          <div class="mb-3">
            <span class="small d-block text-muted mb-1">Buscar en:</span>
            <div class="d-flex flex-wrap gap-3 small">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="searchNombrePub" value="1" checked
                  form="searchForm" />
                <label class="form-check-label" for="searchNombrePub">
                  Nombre
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="searchBodegaPub" value="1" checked
                  form="searchForm" />
                <label class="form-check-label" for="searchBodegaPub">
                  Bodega
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="searchVarietalPub" value="1" checked
                  form="searchForm" />
                <label class="form-check-label" for="searchVarietalPub">
                  Varietal
                </label>
              </div>
            </div>
          </div>

          <div class="row g-2">
            <div class="col-6">
              <label for="minPriceInput" class="form-label small">
                Precio mínimo
              </label>
              <input id="minPriceInput" name="min_price" type="number" step="0.01" class="form-control form-control-sm"
                form="searchForm" />
            </div>

            <div class="col-6">
              <label for="maxPriceInput" class="form-label small">
                Precio máximo
              </label>
              <input id="maxPriceInput" name="max_price" type="number" step="0.01" class="form-control form-control-sm"
                form="searchForm" />
            </div>

            <div class="col-12">
              <div class="form-check mt-2">
                <input id="promoInput" name="promo" type="checkbox" value="1" class="form-check-input"
                  form="searchForm" />
                <label class="form-check-label small" for="promoInput">
                  Solo con promo
                </label>
              </div>
            </div>

            <div class="col-6">
              <label for="perPageInput" class="form-label small">
                Por página
              </label>
              <input id="perPageInput" name="per_page" type="number" value="5" min="1" max="50"
                class="form-control form-control-sm" form="searchForm" />
            </div>
          </div>
        </div>

        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-soft btn-sm" id="clearFiltersBtn">
            Limpiar filtros
          </button>
          <button type="submit" class="btn btn-winepick btn-sm" form="searchForm" data-bs-dismiss="modal">
            Aplicar
          </button>
        </div>
      </div>
    </div>
  </div>
</section>