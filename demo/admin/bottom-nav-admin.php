<?php
// admin/bottom-nav-admin.php
// $activeAdminNav lo definís en cada página: 'panel', 'products', 'metrics', 'settings', etc.
if (!isset($activeAdminNav)) {
    $activeAdminNav = '';
}
?>
<nav class="bottom-nav">
  <!-- Panel -->
  <a
    href="panel.php"
    class="bottom-nav-item <?php echo $activeAdminNav === 'panel' ? 'active' : ''; ?>"
  >
    <i class="bi bi-house"></i>
    <span>Panel</span>
  </a>

  <!-- Productos -->
  <a
    href="product.php"
    class="bottom-nav-item <?php echo $activeAdminNav === 'products' ? 'active' : ''; ?>"
  >
    <i class="bi bi-collection"></i>
    <span>Productos</span>
  </a>

  <!-- Botón central: NUEVO PRODUCTO (lleva a product.php y abre modal allí) -->
  <a
    href="product.php?new=1"
    class="bottom-nav-scan"
  >
    <i class="bi bi-plus-lg bottom-nav-scan-icon"></i>
  </a>


  <!-- Métricas -->
  <a
    href="metricas.php"
    class="bottom-nav-item <?php echo $activeAdminNav === 'metrics' ? 'active' : ''; ?>"
  >
    <i class="bi bi-graph-up"></i>
    <span>Métricas</span>
  </a>

  <!-- Salir / Cerrar sesión -->
  <a
    href="logout.php"
    class="bottom-nav-item <?php echo $activeAdminNav === 'logout' ? 'active' : ''; ?>"
  >
    <i class="bi bi-box-arrow-right"></i>
    <span>Salir</span>
  </a>
</nav>
