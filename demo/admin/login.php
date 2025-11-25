<?php
// admin/login.php
// Formulario de inicio de sesión para administradores.

session_start();
require_once __DIR__ . "/../api/config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($username === "" || $password === "") {
        $error = "Completá usuario y contraseña.";
    } else {
        try {
            $pdo = getPDO();

            // Usamos la tabla `users` con contraseña hasheada (SHA2)
            $stmt = $pdo->prepare(
                "SELECT id, username
                 FROM users
                 WHERE username = :u
                   AND password_hash = SHA2(:p, 256)
                   AND active = 1"
            );
            $stmt->execute([
                ":u" => $username,
                ":p" => $password,
            ]);

            $user = $stmt->fetch();

            if ($user) {
                // Guardamos datos mínimos en sesión para el panel
                $_SESSION["admin_id"]       = $user["id"];
                $_SESSION["admin_username"] = $user["username"];

                header("Location: panel.php");
                exit;
            } else {
                $error = "Usuario o contraseña incorrectos.";
            }
        } catch (Throwable $e) {
            // Error de conexión o consulta
            $error = "Error al conectar con la base de datos.";
            // Si quieres debug, puedes loguear el error en un archivo:
            // error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8" />
    <title>WinePick QR - Login administración</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Fuente Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />

    <!-- Bootstrap 5 -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      crossorigin="anonymous"
    />

    <!-- Estilos WinePick compartidos + login -->
    <link rel="stylesheet" href="../public/css/winepick.css" />
    <link rel="stylesheet" href="../public/css/winepick-login.css" />
  </head>

  <body class="login-body">
    <main class="login-page d-flex align-items-center justify-content-center">
      <div class="login-card-wrapper">
        <div class="login-brand text-center mb-3">
          <div class="login-logo-circle">WP</div>
          <h1 class="login-title">Panel de administración</h1>
          <p class="login-subtitle">
            Iniciá sesión para gestionar vinos, precios y promociones.
          </p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2 small mb-3">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <form method="post" class="login-form">
          <div class="mb-3">
            <label for="username" class="form-label">Usuario</label>
            <input
              type="text"
              name="username"
              id="username"
              class="form-control"
              required
              autocomplete="username"
            />
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <input
              type="password"
              name="password"
              id="password"
              class="form-control"
              required
              autocomplete="current-password"
            />
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="../public/index.php" class="small text-muted">
              ← Volver a la app
            </a>
          </div>

          <button type="submit" class="btn btn-winepick w-100">
            Entrar
          </button>
        </form>
      </div>
    </main>

    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      crossorigin="anonymous"
    ></script>
  </body>
</html>
