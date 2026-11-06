document.addEventListener("DOMContentLoaded", function () {
    const contenedor = document.getElementById("catalog-products-grid");
    const contador = document.getElementById("product-count");
    const botones = document.querySelectorAll("[data-category]");
    const buscador = document.getElementById("search-product");
    let categoriaActual = "Todos";

    const productos = [
        {
            id: 1,
            nombre: "Cuadro decorativo",
            precio: 12990,
            imagen: "assets/cuadro.jpg",
            categoria: "Cuadros"
        },
        {
            id: 2,
            nombre: "Vela aromática",
            precio: 8990,
            imagen: "assets/vela.jpg",
            categoria: "Velas"
        },
        {
            id: 3,
            nombre: "Almohadón de lino",
            precio: 14990,
            imagen: "assets/almohadones.jpg",
            categoria: "Almohadones"
        },
        {
            id: 4,
            nombre: "Lámpara de mesa",
            precio: 22990,
            imagen: "assets/lampara.jpg",
            categoria: "Iluminación"
        },
        {
            id: 5,
            nombre: "Florero decorativo",
            precio: 10990,
            imagen: "assets/florero.jpg",
            categoria: "Decoración"
        },
        {
            id: 6,
            nombre: "Portavelas minimalista",
            precio: 6990,
            imagen: "assets/portavela.jpg",
            categoria: "Decoración"
        }
    ];

    function obtenerCarrito() {
        try {
            return JSON.parse(localStorage.getItem("magnoliaCarrito")) || [];
        } catch (error) {
            return [];
        }
    }

    function actualizarContador() {
        const elemento = document.getElementById("cart-count");

        if (elemento) {
            const carrito = obtenerCarrito();
            const cantidad = carrito.reduce(function (suma, producto) {
                return suma + (Number(producto.cantidad) || 1);
            }, 0);

            elemento.textContent = cantidad;
        }
    }

    function mostrarProductos(categoria = "Todos") {
        contenedor.innerHTML = "";

       categoriaActual = categoria;

const texto = buscador ? buscador.value.trim().toLowerCase() : "";

const filtrados = productos.filter(function (producto) {
    const coincideCategoria =
        categoria === "Todos" || producto.categoria === categoria;

    const coincideTexto =
        producto.nombre.toLowerCase().includes(texto) ||
        producto.categoria.toLowerCase().includes(texto);

    return coincideCategoria && coincideTexto;
});

        contador.textContent = filtrados.length + " productos";

        filtrados.forEach(function (producto) {
            const tarjeta = document.createElement("article");
            tarjeta.className = "product-card";

            tarjeta.innerHTML = `
                <img src="${producto.imagen}" alt="${producto.nombre}">
                <div class="product-info">
                    <p class="product-category">${producto.categoria}</p>
                    <h3>${producto.nombre}</h3>
                    <p class="product-price">$${producto.precio.toLocaleString("es-AR")}</p>
                    <button type="button" class="btn-primary">
                        Agregar al carrito
                    </button>
                </div>
            `;

            tarjeta.querySelector("button").addEventListener("click", function () {
                const carrito = obtenerCarrito();
                const existente = carrito.find(function (item) {
                    return Number(item.id) === producto.id;
                });

                if (existente) {
                    existente.cantidad = (Number(existente.cantidad) || 1) + 1;
                } else {
                    carrito.push({
                        id: producto.id,
                        nombre: producto.nombre,
                        precio: producto.precio,
                        imagen: producto.imagen,
                        cantidad: 1
                    });
                }

                localStorage.setItem("magnoliaCarrito", JSON.stringify(carrito));
                actualizarContador();
                alert("¡Producto agregado al carrito!");
            });

            contenedor.appendChild(tarjeta);
        });
    }

    botones.forEach(function (boton) {
        boton.addEventListener("click", function () {
            botones.forEach(function (otro) {
                otro.classList.remove("selected");
            });

            boton.classList.add("selected");
            mostrarProductos(boton.dataset.category);
        });
    });

    if (buscador) {
    buscador.addEventListener("input", function () {
        mostrarProductos(categoriaActual);
    });
}

mostrarProductos();
actualizarContador();

});