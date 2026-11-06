<?php
require_once __DIR__ . "/includes/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: carrito.html");
    exit;
}

$nombre = trim($_POST["nombre_cliente"] ?? "");
$email = trim($_POST["email_cliente"] ?? "");
$titular = trim($_POST["titular"] ?? "");

$numeroTarjeta = preg_replace(
    '/\s+/',
    '',
    $_POST["numero_tarjeta"] ?? ""
);

$vencimiento = trim($_POST["vencimiento"] ?? "");
$cvv = trim($_POST["cvv"] ?? "");
$carritoJson = $_POST["carrito_json"] ?? "";

$carrito = json_decode($carritoJson, true);

function mostrarResultado($titulo, $mensaje, $exito = false) {
    $color = $exito ? "#647456" : "#a94442";

    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <title>Pago | Magnolia</title>
        <style>
            body {
                margin: 0;
                padding: 25px;
                background: #f5f4ef;
                font-family: Arial, sans-serif;
            }
            .contenedor {
                max-width: 520px;
                margin: 60px auto;
                padding: 32px;
                background: white;
                border-radius: 16px;
                text-align: center;
                box-shadow: 0 4px 20px #00000010;
            }
            h1 { color: " . $color . "; }
            a {
                display: inline-block;
                margin-top: 18px;
                padding: 12px 20px;
                background: #647456;
                color: white;
                text-decoration: none;
                border-radius: 8px;
            }
        </style>
    </head>
    <body>
        <div class='contenedor'>
            <h1>" .
            htmlspecialchars($titulo, ENT_QUOTES, "UTF-8") .
            "</h1>
            <p>" .
            htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") .
            "</p>
            <a href='index.html'>Volver a Magnolia</a>
        </div>";

    if ($exito) {
        echo "<script>
            localStorage.removeItem('magnoliaCarrito');
        </script>";
    }

    echo "</body></html>";
    exit;
}

if (
    $nombre === "" ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    $titular === "" ||
    !is_array($carrito) ||
    count($carrito) === 0 ||
    count($carrito) > 50
) {
    mostrarResultado(
        "Datos incorrectos",
        "Revisá tus datos y el carrito e intentá nuevamente."
    );
}

if (
    !preg_match('/^[0-9]{16}$/', $numeroTarjeta) ||
    !preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $vencimiento) ||
    !preg_match('/^[0-9]{3,4}$/', $cvv)
) {
    mostrarResultado(
        "Datos de tarjeta inválidos",
        "Revisá el número de tarjeta, el vencimiento y el código de seguridad."
    );
}

// Comprobar que la tarjeta no esté vencida.
list($mes, $anio) = explode("/", $vencimiento);
$mes = (int)$mes;
$anio = 2000 + (int)$anio;

$ahora = new DateTimeImmutable("now");
$finVigencia = DateTimeImmutable::createFromFormat(
    "!Y-n-j",
    $anio . "-" . $mes . "-1"
);

if (
    !$finVigencia ||
    $finVigencia->modify("last day of this month 23:59:59") < $ahora
) {
    mostrarResultado(
        "Tarjeta vencida",
        "Ingresá una fecha de vencimiento futura."
    );
}

// Tarjeta de prueba de la simulación.
$tarjetaPrueba = "4111111111111111";
$cvvPrueba = "123";

if (
    $numeroTarjeta !== $tarjetaPrueba ||
    $cvv !== $cvvPrueba
) {
    mostrarResultado(
        "Pago rechazado",
        "La tarjeta de prueba fue rechazada. Verificá los datos e intentá nuevamente."
    );
}

// Agrupar productos repetidos y validar cantidades.
$items = [];

foreach ($carrito as $item) {
    $id = (int)($item["id"] ?? 0);
    $cantidad = (int)($item["cantidad"] ?? 0);

    if ($id <= 0 || $cantidad <= 0 || $cantidad > 100) {
        mostrarResultado(
            "Carrito inválido",
            "Hay un producto o una cantidad incorrecta."
        );
    }

    if (isset($items[$id])) {
        $items[$id] += $cantidad;
    } else {
        $items[$id] = $cantidad;
    }

    if ($items[$id] > 100) {
        mostrarResultado(
            "Cantidad inválida",
            "La cantidad solicitada supera el límite permitido."
        );
    }
}

try {
    $conexion->begin_transaction();

    $total = 0;
    $detalles = [];

    $buscar = $conexion->prepare(
        "SELECT nombre, precio, stock
         FROM productos
         WHERE id = ? AND activo = 1
         FOR UPDATE"
    );

    foreach ($items as $id => $cantidad) {
        $buscar->bind_param("i", $id);
        $buscar->execute();

        $producto = $buscar->get_result()->fetch_assoc();

        if (
            !$producto ||
            (int)$producto["stock"] < $cantidad
        ) {
            throw new Exception(
                "Un producto ya no está disponible en la cantidad solicitada."
            );
        }

        $precio = (float)$producto["precio"];
        $total += $precio * $cantidad;

        $detalles[] = [
            "id" => $id,
            "cantidad" => $cantidad,
            "precio" => $precio
        ];
    }

    $buscar->close();

    if ($total <= 0) {
        throw new Exception("El total del pedido no es válido.");
    }

    // Registrar el pedido.
    $pedido = $conexion->prepare(
        "INSERT INTO pedidos
         (nombre_cliente, email_cliente, total)
         VALUES (?, ?, ?)"
    );

    $pedido->bind_param("ssd", $nombre, $email, $total);
    $pedido->execute();

    $pedidoId = $conexion->insert_id;
    $pedido->close();

    // Registrar el detalle y descontar el stock.
    $detalle = $conexion->prepare(
        "INSERT INTO detalle_pedido
         (pedido_id, producto_id, cantidad, precio_unitario)
         VALUES (?, ?, ?, ?)"
    );

    $stock = $conexion->prepare(
        "UPDATE productos
         SET stock = stock - ?
         WHERE id = ? AND stock >= ?"
    );

    foreach ($detalles as $d) {
        $idProducto = $d["id"];
        $cantidad = $d["cantidad"];
        $precio = $d["precio"];

        $detalle->bind_param(
            "iiid",
            $pedidoId,
            $idProducto,
            $cantidad,
            $precio
        );

        $detalle->execute();

        $stock->bind_param(
            "iii",
            $cantidad,
            $idProducto,
            $cantidad
        );

        $stock->execute();

        if ($stock->affected_rows !== 1) {
            throw new Exception(
                "El stock cambió durante la compra. Intentá nuevamente."
            );
        }
    }

    $detalle->close();
    $stock->close();

    // Confirmar el pedido y los cambios de stock.
    $conexion->commit();

    mostrarResultado(
        "¡Pago aprobado!",
        "Tu compra fue registrada correctamente. " .
        "Número de pedido: #" . $pedidoId .
        ". Total: $" . number_format($total, 2, ",", ".") .
        ". Este pago es una simulación educativa.",
        true
    );

} catch (Throwable $e) {
    try {
        $conexion->rollback();
    } catch (Throwable $ignorado) {
        // No se pudo revertir la transacción.
    }

    error_log("Error en pedido Magnolia: " . $e->getMessage());

    mostrarResultado(
        "No se pudo completar la compra",
        "Verificá la disponibilidad de los productos e intentá nuevamente."
    );
}
?>