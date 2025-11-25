(function (window, document) {
  "use strict";
  // Utilidad de paginación simple (renderiza controles y notifica cambios)

  /**
   * Crea un paginador asociado a un contenedor
   * @param {HTMLElement} container - Contenedor donde se dibuja la paginación
   * @param {{onChange: (page:number)=>void}} options - Callbacks de eventos
   * @returns {{ render: (pagination:{page:number,total_pages:number})=>void }}
   */
  function createPagination(container, options) {
    // opciones de configuración (solo onChange por ahora)
    const opts = options || {};

    /**
     * Renderiza los controles de paginación
     * @param {{page:number, total_pages:number}} pagination
     */
    function render(pagination) {
      if (!container || !pagination) return;

      const current = Number(pagination.page || 1);
      const total = Number(pagination.total_pages || 1);

      container.innerHTML = "";
      if (total <= 1) return;

      // Botón Anterior
      const btnPrev = document.createElement("button");
      btnPrev.className = "btn btn-outline-secondary btn-sm";
      btnPrev.disabled = current <= 1;
      btnPrev.innerHTML = `<i class="bi bi-chevron-left"></i> Anterior`;
      btnPrev.onclick = () => opts.onChange && opts.onChange(current - 1);
      container.appendChild(btnPrev);

      // Info de página
      const spanInfo = document.createElement("span");
      spanInfo.className = "align-self-center small text-muted mx-2";
      spanInfo.textContent = `pag ${current} de ${total}`;
      container.appendChild(spanInfo);

      // Botón Siguiente
      const btnNext = document.createElement("button");
      btnNext.className = "btn btn-outline-secondary btn-sm";
      btnNext.disabled = current >= total;
      btnNext.innerHTML = `Siguiente <i class="bi bi-chevron-right"></i>`;
      btnNext.onclick = () => opts.onChange && opts.onChange(current + 1);
      container.appendChild(btnNext);
    }

    return { render };
  }

  // Exponer en el scope global (sin ES modules)
  window.createPagination = createPagination;
})(window, document);
