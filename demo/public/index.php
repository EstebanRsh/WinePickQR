<?php
// public/index.php
// Versión de cache busting - actualiza este número cuando modifiques JS/CSS
$version = '2.0.1'; // ⭐ ACTUALIZADO para forzar recarga
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8" />
  <title>WinePick QR - App</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Fuente Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
    rel="stylesheet" />

  <!-- Bootstrap -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
    crossorigin="anonymous" />
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />

  <!-- Estilos propios con cache busting -->
  <link rel="stylesheet" href="css/winepick.css?v=<?php echo $version; ?>" />
  <link rel="stylesheet" href="css/navbar.css?v=<?php echo $version; ?>" />
  <link rel="stylesheet" href="css/winepick-search.css?v=<?php echo $version; ?>" />
  <link rel="stylesheet" href="css/winepick-product.css?v=<?php echo $version; ?>" />
  <link rel="stylesheet" href="css/winepick-lector-qr.css?v=<?php echo $version; ?>" />
</head>

<body>
  <!-- HEADER / BUSCADOR -->
  <header class="hero border-bottom d-md-none">
    <div class="container">
      <!-- BARRA DE BÚSQUEDA EN EL HEADER -->
      <form id="searchForm" class="search-header-form mb-2">
        <div class="input-group search-header-input">
          <span class="input-group-text">
            <i class="bi bi-search"></i>
          </span>

          <input id="searchInput" name="search" type="search" class="form-control"
            placeholder="Buscar productos por nombre, bodega o destilería" />

          <!-- Botón de filtros dentro de la barra -->
          <button type="button" class="btn btn-filters" data-bs-toggle="modal" data-bs-target="#filtersModal">
            <i class="bi bi-sliders"></i>
          </button>

          <!-- Botón de buscar -->
          <button type="submit" class="btn btn-winepick">
            Buscar
          </button>
        </div>
      </form>
    </div>
  </header>

  <main>
    <?php include __DIR__ . '/views/search-and-detail.php'; ?>
    <?php include __DIR__ . '/views/qr-modal.php'; ?>
    <?php include __DIR__ . '/views/product-modal.php'; ?>
  </main>

  <?php include __DIR__ . '/views/navegation.php'; ?>

  <!-- Scripts Bootstrap -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    crossorigin="anonymous"></script>

  <!-- Config global de la app -->
  <script>
    window.WINEPICK_CONFIG = {
      // Ruta de la API para XAMPP
      // Desde /demo/public/index.php hacia /demo/api/public_product.php
      apiBaseUrl: "../api/public_product.php"
    };
  </script>

  <!-- ⭐ ORDEN CORRECTO DE CARGA DE SCRIPTS -->
  
  <!-- 1. Primero: product-detail.js (define funciones de modal) -->
  <script src="js/product-detail.js?v=<?php echo $version; ?>"></script>
  
  <!-- 2. Segundo: pagination.js (define paginador) -->
  <script src="js/pagination.js?v=<?php echo $version; ?>"></script> 
  
  <!-- 3. Tercero: app.js (core de la app, exporta WINEPICK_APP) -->
  <script src="js/app.js?v=<?php echo $version; ?>"></script>
  
  <!-- 4. Cuarto: search-bar.js (usa WINEPICK_APP) -->
  <script src="js/search-bar.js?v=<?php echo $version; ?>"></script>
  
  <!-- 5. Librería externa: html5-qrcode -->
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  
  <!-- 6. Por último: qr-reader.js (usa WINEPICK_APP y html5-qrcode) -->
  <script src="js/qr-reader.js?v=<?php echo $version; ?>"></script>

</body>

</html>