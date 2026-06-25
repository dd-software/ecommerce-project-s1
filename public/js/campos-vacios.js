/**
 * campos-vacios.js - Componente de estados vacíos y datos de contacto
 * Uso: mostrarVacio(contenedor, { icono, titulo, descripcion, textoBoton, enlaceBoton, claseBoton, variante })
 */

const UI = {

    // En campos-vacios.js, dentro del objeto UI

    /**
     * Renderiza un spinner de carga estándar (mismo estilo en toda la app)
     * @param {string} mensaje - Texto opcional que aparece debajo del spinner
     * @returns {string} HTML del loader
     */
    loader(mensaje = 'Cargando...') {
        return `<div class="qc-loader">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">${mensaje}</span>
            </div>
            ${mensaje ? `<p>${mensaje}</p>` : ''}
        </div>`;
    },

    /**
     * Muestra un estado vacío en el contenedor especificado.
     * 
     * @param {HTMLElement} contenedor - Elemento DOM donde se inyectará el HTML.
     * @param {Object} opciones
     * @param {string} opciones.icono - Clase de Bootstrap Icons (ej: 'bi-cart-x').
     * @param {string} opciones.titulo - Texto principal.
     * @param {string} [opciones.descripcion] - Texto secundario (opcional).
     * @param {string} [opciones.textoBoton] - Texto del botón (opcional).
     * @param {string} [opciones.enlaceBoton] - URL del botón (opcional).
     * @param {string} [opciones.claseBoton] - Clases adicionales para el botón (por defecto 'btn-accent').
     * @param {string} [opciones.variante] - 'inline' para usar dentro de tarjetas (más compacto).
     */
    mostrarVacio(contenedor, opciones) {
        const {
            icono = 'bi-box-seam',
            titulo = 'Sin contenido',
            descripcion = '',
            textoBoton = null,
            enlaceBoton = '#',
            claseBoton = 'btn-accent',
            variante = '' // 'inline' para estados dentro de secciones
        } = opciones;

        const cssClase = variante === 'inline' ? 'empty-state-inline' : 'empty-state';
        const botonHtml = textoBoton ? `<a href="${enlaceBoton}" class="btn ${claseBoton} btn-sm mt-2">${textoBoton}</a>` : '';

        contenedor.innerHTML = `
            <div class="${cssClase}">
                <i class="bi ${icono}"></i>
                <h5>${titulo}</h5>
                ${descripcion ? `<p class="text-muted">${descripcion}</p>` : ''}
                ${botonHtml}
            </div>
        `;
    }
};

// Exponer globalmente (para uso desde otros scripts)
window.UI = UI;