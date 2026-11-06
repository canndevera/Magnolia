
const productosDestacados = [
    {
        id: 1,
        nombre: "Cuadro botánico",
        precio: 12990,
        imagen: "assets/images/cuadro.jpg"
    },
    {
        id: 2,
        nombre: "Vela aromática",
        precio: 8990,
        imagen: "assets/images/vela.jpg"
    },
    {
        id: 3,
        nombre: "Almohadón de lino",
        precio: 14990,
        imagen: "assets/images/almohadon.jpg"
    },
    {
        id: 4,
        nombre: "Lámpara de mesa",
        precio: 22990,
        imagen: "assets/images/lampara.jpg"
    }
];

// Recuperar el carrito guardado
function obtenerCarrito() {
    try {
        return JSON.parse(localStorage.getItem("magnoliaCarrito")) || [];
    } catch {
        return [];
    }
}

// Guardar el carrito
function guardarCarrito(carrito) {
    localStorage.setItem("magnoliaCarrito", JSON.stringify(carrito));
}

// Actualizar el numerito del carrito
function actualizarContador() {
    const carrito = obtenerCarrito();

    const cantidad = carrito.reduce(
        (total, item) => total + item.cantidad,
        0
    );

    const contador = document.getElementById("cart-count");

    if (contador) {
        contador.textContent = cantidad;
    }
}

// Mostrar los productos destacados
function mostrarProductosDestacados() {
    const contenedor = document.getElementById("featured-products");

    if (!contenedor) return;

    let html = "";

    productosDestacados.forEach(producto => {
        html += `
            <article class="product-card">
                <img
                    src="${producto.imagen}"
                    alt="${producto.nombre}"
                    onerror="this.style.display='none'"
                >

                <h4>${producto.nombre}</h4>

                <p class="price">
                    $ ${producto.precio.toLocaleString("es-AR")}
                </p>

                <button
                    class="btn-add"
                    onclick="agregarAlCarrito(${producto.id})">
                    Agregar al carrito
                </button>
            </article>
        `;
    });

    contenedor.innerHTML = html;
}

// Agregar un producto al carrito
function agregarAlCarrito(id) {
    const producto = productosDestacados.find(
        item => item.id === id
    );

    if (!producto) return;

    const carrito = obtenerCarrito();

    const existente = carrito.find(item => item.id === id);

    if (existente) {
        existente.cantidad++;
    } else {
        carrito.push({
            id: producto.id,
            nombre: producto.nombre,
            precio: producto.precio,
            imagen: producto.imagen,
            cantidad: 1
        });
    }

    guardarCarrito(carrito);
    actualizarContador();

    alert(`${producto.nombre} se agregó al carrito.`);
}

// Inicializar la página
document.addEventListener("DOMContentLoaded", () => {
    mostrarProductosDestacados();
    actualizarContador();
});
