/**
 * search-bar.js
 * Maneja la interacción de las barras de búsqueda (Mobile y Desktop).
 * Sincroniza los inputs y delega la búsqueda a WINEPICK_APP.
 */

(function (window, document) {
  "use strict";

  // IDs de los formularios y sus inputs correspondientes
  const SEARCH_CONFIG = [
    { formId: "searchForm", inputId: "searchInput" }, // Móvil
    { formId: "searchFormDesktop", inputId: "searchInputDesktop" }, // Desktop
  ];

  /**
   * Sincroniza el texto entre todos los buscadores.
   * Si escribes en el de PC, se copia al de móvil por si cambias de tamaño de pantalla.
   */
  function syncInputs(value) {
    SEARCH_CONFIG.forEach((config) => {
      const input = document.getElementById(config.inputId);
      if (input && input.value !== value) {
        input.value = value;
      }
    });
  }

  /**
   * Maneja el envío del formulario
   */
  function handleSearchSubmit(e) {
    e.preventDefault();

    // 1. Obtener el valor del input que disparó el evento
    const form = e.currentTarget;
    const input = form.querySelector("input[type='search']");
    const query = input ? input.value.trim() : "";

    // 2. Sincronizar el otro input (UX)
    syncInputs(query);

    // 3. Llamar a la lógica principal de la App
    if (
      window.WINEPICK_APP &&
      typeof window.WINEPICK_APP.searchProducts === "function"
    ) {
      // Resetear a página 1 en cada nueva búsqueda
      window.WINEPICK_APP.searchProducts(query, 1);

      // Opcional: Quitar foco para esconder teclado en móvil
      if (input) input.blur();
    } else {
      console.error("WINEPICK_APP no está listo o no se ha cargado app.js");
    }
  }

  // Inicialización
  document.addEventListener("DOMContentLoaded", () => {
    console.log("Search Bar Logic Loaded");

    SEARCH_CONFIG.forEach((config) => {
      const form = document.getElementById(config.formId);

      if (form) {
        // Escuchar el evento submit (Enter o clic en lupa)
        form.addEventListener("submit", handleSearchSubmit);
      }
    });
  });
})(window, document);
