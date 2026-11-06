
<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "conexion.php";

$rol = $_SESSION["rol"];

if ($rol !== "administrador") {
    http_response_code(403);
    $sinPermiso = true;
} else {
    $sinPermiso = false;

    $totalProductos = $conexion->query(
        "SELECT COUNT(*) AS total FROM productos WHERE activo = 1"
    )->fetch_assoc()["total"];

    $totalUsuarios = $conexion->query(
        "SELECT COUNT(*) AS total FROM usuarios"
    )->fetch_assoc()["total"];

    $totalPedidos = $conexion->query(
        "SELECT COUNT(*) AS total FROM pedidos"
    )->fetch_assoc()["total"];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel administrativo | Magnolia</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <main style="max-width: 1000px; margin: 40px auto; padding: 25px;">
        <h1>Panel administrativo</h1>

        <p>
            Bienvenida/o,
            <strong><?= htmlspecialchars($_SESSION["nombre"]) ?></strong>
        </p>

        <?php if ($sinPermiso): ?>

            <h2>Acceso denegado</h2>
            <p>No tenés permisos para acceder al panel administrativo.</p>

        <?php else: ?>

            <p>Resumen general de Magnolia.</p>

            <section style="display:flex; flex-wrap:wrap; gap:20px; margin:25px 0;">
                <article style="padding:20px; border-radius:15px; background:#E8E5E1;">
                    <h3>Productos activos</h3>
                    <p><?= (int)$totalProductos ?></p>
                </article>

                <article style="padding:20px; border-radius:15px; background:#D9CCBC;">
                    <h3>Usuarios</h3>
                    <p><?= (int)$totalUsuarios ?></p>
                </article>

                <article style="padding:20px; border-radius:15px; background:#CFC9C2;">
                    <h3>Pedidos</h3>
                    <p><?= (int)$totalPedidos ?></p>
                </article>
            </section>

            <h2>Administración</h2>
            <ul>
                <li><a href="productos_admin.php">Gestionar productos</a></li>
                <li><a href="usuarios_admin.php">Gestionar usuarios</a></li>
                <li><a href="pedidos_admin.php">Gestionar pedidos</a></li>
            </ul>

        <?php endif; ?>

        <p><a href="logout.php">Cerrar sesión</a></p>
        <p><a href="index.html">Volver a la tienda</a></p>
    </main>
</body>
</html>
