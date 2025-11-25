// app.js - WinePick App Core
// Version: 1.0.6 - FIXED: Added compatibility aliases
(function (window, document) {
  "use strict";

  // ============================
  // CONFIGURACIÓN
  // ============================
  const API_BASE_URL =
    (window.WINEPICK_CONFIG && window.WINEPICK_CONFIG.apiBaseUrl) ||
    "../api/public_product.php";
  const DEFAULT_PRODUCT_IMAGE = "img/product-placeholder.png";

  const state = {
    currentQuery: "",
    currentPage: 1,
    perPage: 8,
    totalPages: 1,
    isLoading: false,
  };

  // ============================
  // REFERENCIAS AL DOM (Solo contenedor de resultados)
  // ============================
  // Nota: Los inputs de búsqueda ahora se manejan en search-bar.js
  const resultsContainer = document.getElementById("tablaResultadosBody");
  const paginationContainer = document.getElementById("paginacion");
  const listInfo = document.getElementById("listInfo");

  // Instancia del paginador
  let pager = null;

  // ============================
  // HELPERS
  // ============================

  function buildUrl(action, params = {}) {
    const url = new URL(
      API_BASE_URL,
      window.location.origin + window.location.pathname
    );
    url.searchParams.set("action", action);
    Object.keys(params).forEach((key) => {
      const value = params[key];
      if (value !== undefined && value !== null && value !== "") {
        url.searchParams.set(key, value);
      }
    });
    return url.toString();
  }

  async function apiGet(action, params = {}) {
    const url = buildUrl(action, params);
    try {
      const response = await fetch(url);
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      return await response.json();
    } catch (error) {
      console.error("apiGet error:", error);
      throw error;
    }
  }

  function formatPrice(value) {
    if (value === null || value === undefined || value === "") return "";
    return Number(value).toLocaleString("es-AR", {
      style: "currency",
      currency: "ARS",
      minimumFractionDigits: 2,
    });
  }

  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  // ============================
  // RENDERIZADO
  // ============================

  function setLoading(isLoading) {
    state.isLoading = isLoading;
    if (resultsContainer) {
      resultsContainer.style.opacity = isLoading ? "0.5" : "1";
    }
    if (listInfo) {
      listInfo.textContent = isLoading ? "Buscando..." : "";
      listInfo.style.display = isLoading ? "block" : "none";
    }
  }

  function renderResults(list) {
    if (!resultsContainer) return;
    resultsContainer.innerHTML = "";
    resultsContainer.classList.add("vino-grid");

    if (!Array.isArray(list) || list.length === 0) {
      if (listInfo) {
        listInfo.textContent = "No se encontraron productos con ese criterio.";
        listInfo.style.display = "block";
      }
      return;
    }

    if (listInfo) listInfo.style.display = "none";

    const fragment = document.createDocumentFragment();

    list.forEach((product) => {
      const imgUrl =
        product.image_url || product.main_image || DEFAULT_PRODUCT_IMAGE;
      const priceText = formatPrice(product.list_price);
      const safeName = escapeHtml(product.name || "Sin nombre");
      const safeProducer = escapeHtml(product.producer || "");
      const safeVarietal = product.varietal ? escapeHtml(product.varietal) : "";

      const card = document.createElement("article");
      card.className = "vino-card";
      card.innerHTML = `
        <div class="vino-card-image-wrapper">
          <img src="${imgUrl}" alt="${safeName}" class="vino-card-image" 
               onerror="this.onerror=null;this.src='${DEFAULT_PRODUCT_IMAGE}';"/>
        </div>
        <div class="vino-card-body">
          <h6 class="vino-card-title">${safeName}</h6>
          <p class="vino-card-subtitle">
            ${safeProducer}${safeVarietal ? " • " + safeVarietal : ""}
          </p>
          <p class="vino-card-price">${priceText}</p>
          <div class="vino-card-footer">
            <button class="btn btn-sm btn-outline-primary vino-card-btn btn-detalle" 
                    data-pid="${product.pid || product.id}">
              Ver
            </button>
          </div>
        </div>
      `;
      fragment.appendChild(card);
    });

    resultsContainer.appendChild(fragment);
  }

  // ============================
  // LÓGICA PRINCIPAL (Expuesta)
  // ============================

  /**
   * Función Core: Busca productos y actualiza la grilla
   */
  async function searchProducts(query, page = 1) {
    state.currentQuery = query;
    setLoading(true);

    try {
      const json = await apiGet("search", {
        q: query,
        page: page,
        per_page: state.perPage,
      });

      const products = json.data || json.products || [];
      const pagination = json.pagination || {
        page: page,
        total_pages: json.total_pages || 1,
      };

      state.currentPage = Number(pagination.page || page);
      state.totalPages = Number(pagination.total_pages || 1);

      renderResults(products);

      if (pager) {
        pager.render({
          page: state.currentPage,
          total_pages: state.totalPages,
        });
      }
    } catch (error) {
      console.error("Search error:", error);
      if (listInfo) {
        listInfo.textContent = "Error de conexión.";
        listInfo.style.display = "block";
      }
    } finally {
      setLoading(false);
    }
  }

  function mapDetailToView(product) {
    return {
      nombre: product.name,
      bodega: product.producer || product.winery,
      varietal: product.varietal || product.variety,
      precio: product.list_price,
      descripcion: product.short_description || product.description,
      qr_code: product.pid || product.id,
      imagen_url: product.main_image || product.image_url,
      promo_texto: product.promo_text || null, // Agregado soporte para promo
    };
  }

  async function loadProductDetail(pid) {
    if (!pid) return;

    const showMsg = window.mostrarMensajeDetalle;
    if (showMsg) showMsg("Cargando ficha...");

    try {
      const json = await apiGet("detail", { pid });
      const productData = json.product || json;

      if (productData && (productData.name || productData.id)) {
        const viewData = mapDetailToView(productData);
        if (window.mostrarDetalle) window.mostrarDetalle(viewData);
      } else {
        throw new Error("Producto vacío");
      }
    } catch (error) {
      if (showMsg) showMsg("No se pudo cargar el producto.");
    }
  }

  // ============================
  // INICIO
  // ============================
  document.addEventListener("DOMContentLoaded", () => {
    console.log("WinePick App Core Loaded - v1.0.6 FIXED");

    // Inicializar paginador
    if (paginationContainer && window.createPagination) {
      pager = window.createPagination(paginationContainer, {
        onChange: (nextPage) => searchProducts(state.currentQuery, nextPage),
      });
    }

    // Evento Delegado para botones "Ver"
    if (resultsContainer) {
      resultsContainer.addEventListener("click", (e) => {
        const btn = e.target.closest(".btn-detalle");
        if (btn && btn.dataset.pid) {
          loadProductDetail(btn.dataset.pid);
        }
      });
    }

    // Carga inicial por defecto (vacío o destacados)
    searchProducts("", 1);
  });

  // ============================
  // EXPORTAR FUNCIONES
  // ============================
  window.WINEPICK_APP = {
    searchProducts, // Usado por search-bar.js
    loadProductDetail,
    cargarDetalleProducto: loadProductDetail, // Alias en español
    version: "1.0.6",
  };
  
  // ⭐ IMPORTANTE: Exponer también directamente en window para compatibilidad
  window.loadProductDetail = loadProductDetail;
  window.cargarDetalleProducto = loadProductDetail;
  
  console.log("✓ Funciones exportadas:", Object.keys(window.WINEPICK_APP));
})(window, document);