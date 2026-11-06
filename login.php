
<?php
session_start();
require_once "conexion.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $consulta = $conexion->prepare(
        "SELECT id, nombre, email, password, rol
         FROM usuarios
         WHERE email = ?"
    );

    $consulta->bind_param("s", $email);
    $consulta->execute();

    $resultado = $consulta->get_result();

    if ($usuario = $resultado->fetch_assoc()) {
        if (password_verify($password, $usuario["password"])) {
            session_regenerate_id(true);

            $_SESSION["usuario_id"] = $usuario["id"];
            $_SESSION["nombre"] = $usuario["nombre"];
            $_SESSION["email"] = $usuario["email"];
            $_SESSION["rol"] = $usuario["rol"];

            header("Location: panel.php");
            exit;
        }
    }

    $error = "Correo o contraseña incorrectos.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar | Magnolia</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <main style="max-width: 420px; margin: 60px auto; padding: 25px;">
        <h1>Ingresar a Magnolia</h1>
        <p>Acceso para el personal de la tienda.</p>

        <?php if ($error !== ""): ?>
            <p style="color: #b42318;">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="email">Correo electrónico</label>
            <input
                type="email"
                id="email"
                name="email"
                required
                style="display:block; width:100%; margin:8px 0 16px; padding:10px;"
            >

            <label for="password">Contraseña</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                style="display:block; width:100%; margin:8px 0 16px; padding:10px;"
            >

            <button type="submit">Iniciar sesión</button>
        </form>

        <p><a href="index.html">Volver a Magnolia</a></p>
    </main>
</body>
</html>
