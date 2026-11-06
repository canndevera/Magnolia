// Ocultar/Mostrar la tarjeta según el método de pago elegido
const optionCard = document.getElementById('optionCard');
const optionTransfer = document.getElementById('optionTransfer');
const optionCash = document.getElementById('optionCash');
const cardContainer = document.getElementById('cardPaymentContainer');
const altMessage = document.getElementById('alternativePaymentMessage');
const altText = document.getElementById('altText');

function updatePaymentView() {
    if (optionCard && optionCard.checked) {
        cardContainer.style.display = 'block';
        altMessage.style.display = 'none';
        // Hacer requeridos los campos de tarjeta
        document.getElementById('cardNumber').required = true;
        document.getElementById('cardHolderName').required = true;
    } else {
        cardContainer.style.display = 'none';
        altMessage.style.display = 'block';
        // Quitar la obligación de rellenar tarjeta si eligió otro método
        document.getElementById('cardNumber').required = false;
        document.getElementById('cardHolderName').required = false;

        if (optionTransfer.checked) {
            altText.textContent = "Te enviaremos el CBU y Alias por correo para que realices la transferencia.";
        } else if (optionCash.checked) {
            altText.textContent = " abonarás en efectivo al momento de recibir o retirar tu pedido de Magnolia.";
        }
    }
}

if (optionCard && optionTransfer && optionCash) {
    optionCard.addEventListener('change', updatePaymentView);
    optionTransfer.addEventListener('change', updatePaymentView);
    optionCash.addEventListener('change', updatePaymentView);
}