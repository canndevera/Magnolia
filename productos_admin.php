
<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION["rol"] !== "administrador") {
    http_response_code(403);
    exit("No tenés permisos para administrar productos.");
}

require_once "conexion.php";

$mensaje = "";

// Agregar o editar producto
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = (int)($_POST["id"] ?? 0);
    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $precio = (float)($_POST["precio"] ?? 0);
    $imagen = trim($_POST["imagen"] ?? "");
    $stock = (int)($_POST["stock"] ?? 0);
    $categoria_id = (int)($_POST["categoria_id"] ?? 0);

    if ($nombre === "" || $precio <= 0 || $stock < 0 || $categoria_id <= 0) {
        $mensaje = "Completá los datos correctamente.";
    } else {
        if ($id > 0) {
            $consulta = $conexion->prepare(
                "UPDATE productos
                 SET nombre=?, descripcion=?, precio=?, imagen=?,
                     stock=?, categoria_id=?
                 WHERE id=?"
            );
            $consulta->bind_param(
                "ssdsiii",
                $nombre, $descripcion, $precio, $imagen,
                $stock, $categoria_id, $id
            );
        } else {
            $consulta = $conexion->prepare(
                "INSERT INTO productos
                 (nombre, descripcion, precio, imagen, stock, categoria_id)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $consulta->bind_param(
                "ssdsii",
                $nombre, $descripcion, $precio, $imagen,
                $stock, $categoria_id
            );
        }

        if ($consulta->execute()) {
            header("Location: productos_admin.php?guardado=1");
            exit;
        } else {
            $mensaje = "No se pudo guardar el producto.";
        }

        $consulta->close();
    }
}

// Eliminar (desactivar) producto
if (isset($_GET["eliminar"])) {
    $idEliminar = (int)$_GET["eliminar"];

    $consulta = $conexion->prepare(
        "UPDATE productos SET activo=0 WHERE id=?"
    );
    $consulta->bind_param("i", $idEliminar);
    $consulta->execute();
    $consulta->close();

    header("Location: productos_admin.php?eliminado=1");
    exit;
}

// Producto que se va a editar
$productoEditar = null;

if (isset($_GET["editar"])) {
    $idEditar = (int)$_GET["editar"];

    $consulta = $conexion->prepare(
        "SELECT * FROM productos WHERE id=? AND activo=1"
    );
    $consulta->bind_param("i", $idEditar);
    $consulta->execute();
    $productoEditar = $consulta->get_result()->fetch_assoc();
    $consulta->close();
}

// Categorías
$categorias = $conexion->query(
    "SELECT id, nombre FROM categorias ORDER BY nombre"
);

// Productos activos
$productos = $conexion->query(
    "SELECT p.*, c.nombre AS categoria
     FROM productos p
     LEFT JOIN categorias c ON p.categoria_id=c.id
     WHERE p.activo=1
     ORDER BY p.id DESC"
);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos | Magnolia</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<main style="max-width:1100px; margin:35px auto; padding:20px;">
    <h1>Gestión de productos</h1>
    <p>Agregá, modificá o quitá productos del catálogo.</p>

    <?php if ($mensaje !== ""): ?>
        <p><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <?php if (isset($_GET["guardado"])): ?>
        <p>Producto guardado correctamente.</p>
    <?php endif; ?>

    <?php if (isset($_GET["eliminado"])): ?>
        <p>Producto eliminado del catálogo.</p>
    <?php endif; ?>

    <h2><?= $productoEditar ? "Editar producto" : "Agregar producto" ?></h2>

    <form method="POST" action="productos_admin.php"
          style="display:grid; gap:12px; max-width:550px;">

        <input type="hidden" name="id"
               value="<?= (int)($productoEditar["id"] ?? 0) ?>">

        <label>Nombre
            <input type="text" name="nombre" required maxlength="150"
                   value="<?= htmlspecialchars($productoEditar["nombre"] ?? "") ?>">
        </label>

        <label>Descripción
            <textarea name="descripcion" rows="3"><?= htmlspecialchars($productoEditar["descripcion"] ?? "") ?></textarea>
        </label>

        <label>Precio
            <input type="number" name="precio" min="0.01" step="0.01" required
                   value="<?= htmlspecialchars((string)($productoEditar["precio"] ?? "")) ?>">
        </label>

        <label>Ruta de la imagen
            <input type="text" name="imagen"
                   placeholder="assets/images/cuadro.jpg"
                   value="<?= htmlspecialchars($productoEditar["imagen"] ?? "") ?>">
        </label>

        <label>Stock
            <input type="number" name="stock" min="0" required
                   value="<?= (int)($productoEditar["stock"] ?? 0) ?>">
        </label>

        <label>Categoría
            <select name="categoria_id" required>
                <option value="">Elegí una categoría</option>
                <?php while ($cat = $categorias->fetch_assoc()): ?>
                    <option value="<?= (int)$cat["id"] ?>"
                        <?= (int)($productoEditar["categoria_id"] ?? 0) === (int)$cat["id"] ? "selected" : "" ?>>
                        <?= htmlspecialchars($cat["nombre"]) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </label>

        <button type="submit">Guardar producto</button>

        <?php if ($productoEditar): ?>
            <a href="productos_admin.php">Cancelar edición</a>
        <?php endif; ?>
    </form>

    <hr style="margin:30px 0;">

    <h2>Productos cargados</h2>

    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Categoría</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($productos->num_rows === 0): ?>
                <tr><td colspan="5">Todavía no hay productos cargados.</td></tr>
            <?php else: ?>
                <?php while ($p = $productos->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($p["nombre"]) ?></td>
                        <td>$<?= number_format((float)$p["precio"], 2, ",", ".") ?></td>
                        <td><?= (int)$p["stock"] ?></td>
                        <td><?= htmlspecialchars($p["categoria"] ?? "Sin categoría") ?></td>
                        <td>
                            <a href="productos_admin.php?editar=<?= (int)$p["id"] ?>">Editar</a>
                            |
                            <a href="productos_admin.php?eliminar=<?= (int)$p["id"] ?>"
                               onclick="return confirm('¿Querés quitar este producto del catálogo?')">
                               Eliminar
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p><a href="panel.php">Volver al panel</a></p>
    <p><a href="index.html">Volver a la tienda</a></p>
</main>
</body>
</html>
