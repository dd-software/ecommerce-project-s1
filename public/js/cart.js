// ── Estado global ─────────────────────────────────────────────────────────────
let paypalScriptLoaded = false;
let currentOrderId    = null;

// ── Carga del carrito ─────────────────────────────────────────────────────────
async function loadCart() {
    try {
        const res   = await api.request('/cart');
        const items = res.data;
        const container = document.getElementById('cart-items');
        const summary   = document.getElementById('cart-summary');

        if (items.length === 0) {
            container.innerHTML = '<div class="alert alert-info">Tu carrito está vacío. Agrega productos desde el catálogo.</div>';
            summary.innerHTML   = '';
            return;
        }

        let total = 0;
        container.innerHTML = items.map(item => {
            const subtotal = item.quantity * parseFloat(item.price);
            total += subtotal;
            return `
                <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                    <div>
                        <h5 class="mb-0">${item.product_name}
                            <span class="badge text-bg-secondary ms-2">${item.sku}</span>
                        </h5>
                        <small class="text-muted d-block mt-1">
                            Cantidad: ${item.quantity} × $${item.price}
                        </small>
                    </div>
                    <div class="text-end">
                        <h5 class="mb-2 text-primary">$${subtotal.toFixed(2)}</h5>
                        <button class="btn btn-sm btn-outline-danger"
                                onclick="removeFromCart(${item.variant_id})">
                            Eliminar
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        summary.innerHTML = `
            <div class="card bg-white shadow-sm border-0 p-4 rounded-3">
                <div class="text-end">
                    <h4 class="mb-4">
                        Total a Pagar:<br>
                        <span class="text-primary fw-bold fs-2">$${total.toFixed(2)}</span>
                    </h4>
                    <button class="btn btn-success btn-lg w-100" onclick="checkout()">
                        <i class="bi bi-lock-fill me-2"></i>Procesar Pago Seguro
                    </button>
                </div>
            </div>
        `;
    } catch (e) {
        if (e.message.toLowerCase().includes('token') || e.message.toLowerCase().includes('inválido')) {
            window.location.href = 'login.html';
        }
    }
}

async function removeFromCart(variantId) {
    try {
        await api.request(`/cart/${variantId}`, 'DELETE');
        ui.showToast('Producto removido del carrito', 'info');
        loadCart();
    } catch(e) {}
}

// ── Checkout: crear orden y mostrar selector de método de pago ────────────────
async function checkout() {
    const btn = document.querySelector('#cart-summary button');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creando orden...'; }

    try {
        const res      = await api.request('/checkout', 'POST');
        currentOrderId = res.order_id;
        ui.showToast('Orden creada. Elige cómo pagar.', 'info');
        renderPaymentMethods(currentOrderId);
    } catch (e) {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-lock-fill me-2"></i>Procesar Pago Seguro'; }
    }
}

// ── Selector de método de pago ────────────────────────────────────────────────
function renderPaymentMethods(orderId) {
    document.getElementById('cart-summary').innerHTML = `
        <div class="card bg-white shadow-sm border-0 p-4 rounded-3">
            <h5 class="fw-bold text-center mb-4">
                <i class="bi bi-shield-lock-fill text-success me-2"></i>Método de pago
            </h5>

            <!-- Transbank Webpay Plus -->
            <button class="btn btn-danger btn-lg w-100 mb-3 py-3" id="btn-transbank"
                    onclick="checkoutWithTransbank(${orderId})">
                <i class="bi bi-credit-card-2-front-fill fs-5 me-2"></i>
                <span class="fw-bold">Pagar con Tarjeta</span>
                <small class="d-block mt-1 fw-normal opacity-75">
                    Visa · Mastercard · RedCompra — Webpay Plus
                </small>
            </button>

            <!-- PayPal -->
            <button class="btn btn-warning btn-lg w-100 mb-2 py-3" id="btn-paypal"
                    onclick="initPayPal(${orderId})">
                <i class="bi bi-paypal fs-5 me-2"></i>
                <span class="fw-bold">Pagar con PayPal</span>
                <small class="d-block mt-1 fw-normal opacity-75">Pago internacional en USD</small>
            </button>
            <div id="paypal-button-container" class="mb-3"></div>

            <hr class="my-3">

            <!-- Simulación -->
            <button class="btn btn-outline-secondary w-100"
                    onclick="simulatePayment(${orderId})">
                <i class="bi bi-lightning-fill me-1"></i>
                Simular pago (solo entorno de pruebas)
            </button>
        </div>
    `;
}

// ── Pago con Transbank Webpay Plus ────────────────────────────────────────────
async function checkoutWithTransbank(orderId) {
    const btn = document.getElementById('btn-transbank');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Conectando con Transbank...';

    try {
        const res = await api.request('/payment/transbank/create', 'POST', { order_id: orderId });

        // Transbank requiere una redirección mediante form POST (no GET)
        const form       = document.createElement('form');
        form.method      = 'POST';
        form.action      = res.url;

        const tokenInput = document.createElement('input');
        tokenInput.type  = 'hidden';
        tokenInput.name  = 'token_ws';
        tokenInput.value = res.token;

        form.appendChild(tokenInput);
        document.body.appendChild(form);
        form.submit();
    } catch (e) {
        btn.disabled = false;
        btn.innerHTML = `
            <i class="bi bi-credit-card-2-front-fill fs-5 me-2"></i>
            <span class="fw-bold">Pagar con Tarjeta</span>
            <small class="d-block mt-1 fw-normal opacity-75">Visa · Mastercard · RedCompra — Webpay Plus</small>
        `;
        ui.showToast('Error al conectar con Transbank: ' + e.message, 'danger');
    }
}

// ── Pago con PayPal ───────────────────────────────────────────────────────────
async function loadPayPalScript() {
    if (paypalScriptLoaded) return true;
    try {
        const res = await api.request('/config/paypal');
        if (!res.client_id) return false;
        return new Promise((resolve, reject) => {
            const script  = document.createElement('script');
            script.src    = `https://www.paypal.com/sdk/js?client-id=${res.client_id}&currency=USD`;
            script.onload = () => { paypalScriptLoaded = true; resolve(true); };
            script.onerror = () => reject(false);
            document.head.appendChild(script);
        });
    } catch(e) { return false; }
}

async function initPayPal(orderId) {
    const btn = document.getElementById('btn-paypal');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cargando PayPal...';

    const hasPayPal = await loadPayPalScript();
    if (hasPayPal) {
        btn.style.display = 'none';
        renderPayPalButtons(orderId);
    } else {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-paypal fs-5 me-2"></i><span class="fw-bold">Pagar con PayPal</span>';
        ui.showToast('PayPal no está disponible. Usa otra opción.', 'warning');
    }
}

function renderPayPalButtons(orderId) {
    paypal.Buttons({
        createOrder: async (data, actions) => {
            try {
                const res = await api.request('/payment/paypal/create', 'POST', { order_id: orderId });
                return res.paypal_order_id;
            } catch (err) {
                ui.showToast('Error al iniciar PayPal: ' + err.message, 'danger');
                throw err;
            }
        },
        onApprove: async (data, actions) => {
            ui.showToast('Procesando tu pago...', 'info');
            try {
                const res = await api.request('/payment/paypal/capture', 'POST', {
                    paypal_order_id: data.orderID,
                    order_id: orderId,
                });
                ui.showToast(res.message, 'success');
                setTimeout(() => window.location.href = 'mis-compras.html', 3000);
            } catch (err) {
                ui.showToast('Error capturando el pago: ' + err.message, 'danger');
                loadCart();
            }
        },
        onError: () => {
            ui.showToast('El pago fue cancelado o no se pudo completar.', 'warning');
        }
    }).render('#paypal-button-container');
}

// ── Simulación de pago ────────────────────────────────────────────────────────
async function simulatePayment(orderId) {
    ui.showToast('Conectando con la pasarela simulada...', 'info');
    try {
        const res = await api.request('/payment/simulate', 'POST', { order_id: orderId });
        ui.showToast(res.message, 'success');
        setTimeout(() => window.location.href = 'mis-compras.html', 3000);
    } catch(err) {
        ui.showToast('El pago falló: ' + err.message, 'danger');
        loadCart();
    }
}

document.addEventListener('DOMContentLoaded', loadCart);
