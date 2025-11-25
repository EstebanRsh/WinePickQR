<nav class="bottom-nav d-md-none">
  <button class="bottom-nav-item active" type="button" data-nav="inicio">
    <i class="bi bi-house-door"></i>
    <span>Inicio</span>
  </button>

  <button class="bottom-nav-scan" type="button" id="scanBtnMobile" data-bs-toggle="modal" data-bs-target="#qrModal">
    <i class="bi bi-qr-code-scan bottom-nav-scan-icon"></i>
  </button>

  <a class="bottom-nav-item" data-nav="mas" href="../admin/login.php">
    <i class="bi bi-three-dots"></i>
    <span>Ingresar</span>
  </a>
</nav>

<nav class="desktop-nav d-none d-md-block">
  <div class="container desktop-nav-container">

    <a href="#" class="desktop-brand">
      <i class="bi bi-droplet-half"></i> WinePick
    </a>

    <div class="desktop-search-wrapper">
      <form id="searchFormDesktop" class="mb-0">
        <div class="input-group search-header-input bg-light border-0">
          <span class="input-group-text bg-transparent border-0 ps-3">
            <i class="bi bi-search text-muted"></i>
          </span>

          <input
            id="searchInputDesktop"
            name="search"
            type="search"
            class="form-control bg-transparent border-0 shadow-none"
            placeholder="Buscar vinos, bodegas..." />

          <button type="button" class="btn btn-filters bg-transparent border-start" data-bs-toggle="modal" data-bs-target="#filtersModal">
            <i class="bi bi-sliders"></i>
          </button>

          <button type="submit" class="btn btn-winepick rounded-end-pill px-4">
            Buscar
          </button>
        </div>
      </form>
    </div>

    <div class="desktop-actions">
      <a href="../admin/login.php" class="btn btn-outline-soft btn-sm fw-medium">
        Ingresar
      </a>
    </div>

  </div>
</nav>