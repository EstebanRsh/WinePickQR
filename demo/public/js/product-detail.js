(function (window) {
  // Imagen placeholder
  const DEFAULT_PRODUCT_IMAGE = "img/product-placeholder.png";

  // --------- FICHA INLINE (sección "Ficha del producto") ---------
  // Compatibilidad de IDs: primero intento los nuevos en inglés y, si no existen, uso los viejos en español.
  const detailMessage =
    document.getElementById("detailMessage") ||
    document.getElementById("detalleMensaje");

  const detailContent =
    document.getElementById("detailContent") ||
    document.getElementById("detalleContenido");

  const productName =
    document.getElementById("productName") ||
    document.getElementById("vinoNombre");

  const productWineryVarietal =
    document.getElementById("productWineryVarietal") ||
    document.getElementById("vinoBodegaVarietal");

  const productPrice =
    document.getElementById("productPrice") ||
    document.getElementById("vinoPrecio");

  const productPromo =
    document.getElementById("productPromo") ||
    document.getElementById("vinoPromo");

  const productDescription =
    document.getElementById("productDescription") ||
    document.getElementById("vinoDescripcion");

  const productQr =
    document.getElementById("productQr") || document.getElementById("vinoQr");

  const productImage =
    document.getElementById("productImage") ||
    document.getElementById("vinoImagen");

  // --------- MODAL DE PRODUCTO ---------
  const productModalElement = document.getElementById("productModal");
  let productModalInstance = null;

  if (productModalElement && window.bootstrap && window.bootstrap.Modal) {
    productModalInstance = new bootstrap.Modal(productModalElement);
  }

  const modalProductName =
    document.getElementById("productModalName") ||
    document.getElementById("productModalNombre");

  const modalProductWineryVarietal =
    document.getElementById("productModalWineryVarietal") ||
    document.getElementById("productModalBodegaVarietal");

  const modalProductPrice =
    document.getElementById("productModalPrice") ||
    document.getElementById("productModalPrecio");

  const modalProductPromo = document.getElementById("productModalPromo"); // (ID ya coincide)

  const modalProductDescription =
    document.getElementById("productModalDescription") ||
    document.getElementById("productModalDescripcion");

  const modalProductQr = document.getElementById("productModalQr"); // (ID ya coincide)

  const modalProductImage =
    document.getElementById("productModalImage") ||
    document.getElementById("productModalImagen");

  // Nota: dejo nombres de propiedades del objeto en español (nombre, bodega, varietal, etc.)
  // porque vienen del backend así. Cambiarlas aquí rompería la integración.

  function showProductDetail(product) {
    if (!product) return;

    // --- Ficha inline ---
    if (detailMessage && detailContent) {
      detailMessage.style.display = "none";
      detailContent.style.display = "block";
    }

    const name = product.nombre || "";
    if (productName) {
      productName.textContent = name;
    }

    const parts = [];
    if (product.bodega) parts.push(product.bodega);
    if (product.varietal) parts.push(product.varietal);
    const wineryVarietalText = parts.join(" - ");

    if (productWineryVarietal) {
      productWineryVarietal.textContent = wineryVarietalText;
    }

    const priceNumber = Number(product.precio);
    const priceText = !isNaN(priceNumber) ? "$ " + priceNumber.toFixed(2) : "";

    if (productPrice) {
      productPrice.textContent = priceText;
    }

    if (productPromo) {
      productPromo.textContent = product.promo_texto
        ? "Promo: " + product.promo_texto
        : "";
    }

    if (productDescription) {
      productDescription.textContent = product.descripcion || "";
    }

    if (productQr) {
      productQr.textContent = product.qr_code || "";
    }

    const hasImage =
      product.imagen_url && String(product.imagen_url).trim() !== "";

    if (productImage) {
      productImage.src = hasImage ? product.imagen_url : DEFAULT_PRODUCT_IMAGE;
      productImage.style.display = "block";
      productImage.onerror = function () {
        this.onerror = null;
        this.src = DEFAULT_PRODUCT_IMAGE;
      };
    }

    // --- Modal de producto ---
    if (modalProductName) {
      modalProductName.textContent = name;
    }
    if (modalProductWineryVarietal) {
      modalProductWineryVarietal.textContent = wineryVarietalText;
    }
    if (modalProductPrice) {
      // Solo el número; el signo $ puede estar en el HTML
      modalProductPrice.textContent = !isNaN(priceNumber)
        ? priceNumber.toFixed(2)
        : "--";
    }
    if (modalProductPromo) {
      if (product.promo_texto) {
        modalProductPromo.textContent = product.promo_texto; // Solo "2x1", etc.
        modalProductPromo.style.display = "inline-block";
      } else {
        modalProductPromo.style.display = "none";
      }
    }

    if (modalProductDescription) {
      modalProductDescription.textContent =
        product.descripcion || "No description available.";
    }
    if (modalProductQr) {
      modalProductQr.textContent = product.qr_code || "---";
    }

    if (modalProductImage) {
      modalProductImage.src = hasImage
        ? product.imagen_url
        : DEFAULT_PRODUCT_IMAGE;
      modalProductImage.style.display = "block";
      modalProductImage.onerror = function () {
        this.onerror = null;
        this.src = DEFAULT_PRODUCT_IMAGE;
      };
    }

    // Mostrar modal (tipo ficha flotante de producto)
    if (productModalInstance) {
      productModalInstance.show();
    }
  }

  function showDetailMessage(text) {
    if (detailContent) {
      detailContent.style.display = "none";
    }
    if (detailMessage) {
      detailMessage.textContent = text || "Could not load product.";
      detailMessage.style.display = "block";
    }

    // Cerrar modal si hubiera uno abierto
    if (productModalInstance) {
      productModalInstance.hide();
    }
  }

  // Exponer funciones en inglés y alias en español (compatibilidad hacia atrás)
  window.showProductDetail = showProductDetail;
  window.showDetailMessage = showDetailMessage;
  window.mostrarDetalle = showProductDetail;
  window.mostrarMensajeDetalle = showDetailMessage;
})(window);
