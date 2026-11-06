document.addEventListener("DOMContentLoaded", function () {
    const contenedor = document.getElementById("cart-items");
    const totalElemento = document.getElementById("cart-total");
    const botonVaciar = document.getElementById("clear-cart");

    function obtenerCarrito() {
        try {
            return JSON.parse(localStorage.getItem("magnoliaCarrito")) || [];
        } catch (error) {
            return [];
        }
    }

    function guardarCarrito(carrito) {
        localStorage.setItem("magnoliaCarrito", JSON.stringify(carrito));
    }

    function mostrarCarrito() {
        const carrito = obtenerCarrito();
        contenedor.innerHTML = "";

        let total = 0;

        if (carrito.length === 0) {
            contenedor.innerHTML = "<p>Tu carrito está vacío. ¡Agregá algo lindo para tu hogar!</p>";
            totalElemento.textContent = "0";
            actualizarContador();
            return;
        }

        carrito.forEach(function (producto, indice) {
            const cantidad = Number(producto.cantidad) || 1;
            const precio = Number(producto.precio) || 0;
            const subtotal = precio * cantidad;

            total += subtotal;

            const tarjeta = document.createElement("article");
            tarjeta.className = "cart-item";

            tarjeta.innerHTML = `
                <img src="${producto.imagen || producto.image || ""}"
                     alt="${producto.nombre || producto.name || "Producto"}"
                     class="cart-item-image">

                <div class="cart-item-info">
                    <h3>${producto.nombre || producto.name || "Producto"}</h3>
                    <p>Precio: $${precio.toLocaleString("es-AR")}</p>
                    <p>Subtotal: $${subtotal.toLocaleString("es-AR")}</p>

                    <div class="cart-quantity">
                        <button type="button" class="quantity-minus">−</button>
                        <span>${cantidad}</span>
                        <button type="button" class="quantity-plus">+</button>
                        <button type="button" class="remove-item">Eliminar</button>
                    </div>
                </div>
            `;

            tarjeta.querySelector(".quantity-minus").addEventListener("click", function () {
                const actual = obtenerCarrito();

                if (actual[indice].cantidad > 1) {
                    actual[indice].cantidad--;
                } else {
                    actual.splice(indice, 1);
                }

                guardarCarrito(actual);
                mostrarCarrito();
            });

            tarjeta.querySelector(".quantity-plus").addEventListener("click", function () {
                const actual = obtenerCarrito();
                actual[indice].cantidad++;
                guardarCarrito(actual);
                mostrarCarrito();
            });

            tarjeta.querySelector(".remove-item").addEventListener("click", function () {
                const actual = obtenerCarrito();
                actual.splice(indice, 1);
                guardarCarrito(actual);
                mostrarCarrito();
            });

            contenedor.appendChild(tarjeta);
        });

        totalElemento.textContent = total.toLocaleString("es-AR");
        actualizarContador();
    }

    function actualizarContador() {
        const contador = document.getElementById("cart-count");

        if (contador) {
            const carrito = obtenerCarrito();
            const cantidad = carrito.reduce(function (suma, producto) {
                return suma + (Number(producto.cantidad) || 1);
            }, 0);

            contador.textContent = cantidad;
        }
    }

    botonVaciar.addEventListener("click", function () {
        guardarCarrito([]);
        mostrarCarrito();
    });

    mostrarCarrito();
});