// qr-reader.js - VERSIÓN MEJORADA CON TOGGLE DE CÁMARA
// Lector de códigos QR con cambio de cámara funcional
// Fecha: 20/11/2025

(function (window, document) {
  "use strict";

  const qrModalEl = document.getElementById("qrModal");
  const qrRegionId = "qrReader";

  let html5QrCode = null;
  let isRunning = false;

  // lista de cámaras y la cámara actual
  let devices = [];
  let currentCameraIndex = 0;

  if (!qrModalEl) {
    console.warn("qr-reader.js: No se encontró el modal #qrModal");
    return;
  }

  /**
   * Extrae el PID del texto escaneado
   */
  function extractPidFromQR(decodedText) {
    const texto = String(decodedText || "").trim();

    if (!texto) {
      return null;
    }

    console.log("QR escaneado:", texto);

    // Caso 1: Es una URL completa
    if (texto.startsWith("http://") || texto.startsWith("https://")) {
      try {
        const url = new URL(texto);

        // Buscar en query string (?pid=...)
        const pidFromQuery = url.searchParams.get("pid");
        if (pidFromQuery) {
          console.log("PID extraído de query string:", pidFromQuery);
          return pidFromQuery;
        }

        // Buscar en hash (#pid=...)
        if (url.hash) {
          const hashParams = new URLSearchParams(url.hash.substring(1));
          const pidFromHash = hashParams.get("pid");
          if (pidFromHash) {
            console.log("PID extraído de hash:", pidFromHash);
            return pidFromHash;
          }
        }

        // Si la URL no tiene parámetro pid, usar el último segmento del path
        const pathSegments = url.pathname
          .split("/")
          .filter((s) => s.length > 0);
        if (pathSegments.length > 0) {
          const lastSegment = pathSegments[pathSegments.length - 1];
          console.log("PID extraído del path:", lastSegment);
          return lastSegment;
        }
      } catch (error) {
        console.error("Error al parsear URL:", error);
        return texto;
      }
    }

    // Caso 2: Es un PID directo
    console.log("Usando como PID directo:", texto);
    return texto;
  }

  /**
   * Callback cuando se lee un código QR válido
   */
  function onQrDecoded(decodedText) {
    const pid = extractPidFromQR(decodedText);

    if (!pid) {
      console.warn("No se pudo extraer el PID del código QR");
      return;
    }

    console.log("✓ PID identificado:", pid);

    // Lógica de detalle: usamos la función pública de app.js
    if (
      window.WINEPICK_APP &&
      typeof window.WINEPICK_APP.loadProductDetail === "function"
    ) {
      window.WINEPICK_APP.loadProductDetail(pid);
    } else if (
      window.WINEPICK_APP &&
      typeof window.WINEPICK_APP.cargarDetalleProducto === "function"
    ) {
      window.WINEPICK_APP.cargarDetalleProducto(pid);
    } else {
      console.error(
        "WINEPICK_APP.loadProductDetail no está disponible. Verifica el orden de los <script>."
      );
    }

    // Cerrar modal
    if (window.bootstrap && window.bootstrap.Modal) {
      const modalInstance = window.bootstrap.Modal.getInstance(qrModalEl);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }

  /**
   * Inicia el escáner de QR
   */
  async function startScanner() {
    if (isRunning) {
      console.log("El escáner ya está corriendo");
      return;
    }

    try {
      if (!html5QrCode) {
        if (typeof Html5Qrcode === "undefined") {
          console.error(
            "La librería Html5Qrcode no está cargada. Verifica que el script esté incluido."
          );
          return;
        }
        html5QrCode = new Html5Qrcode(qrRegionId);
      }

      // Obtener cámaras disponibles SOLO la primera vez
      if (!devices || devices.length === 0) {
        devices = await Html5Qrcode.getCameras();
      }

      if (!devices || devices.length === 0) {
        console.warn("No se encontraron cámaras disponibles");

        const readerEl = document.getElementById(qrRegionId);
        if (readerEl) {
          readerEl.innerHTML = `
              <div class="alert alert-warning m-3">
                <i class="bi bi-camera-video-off me-2"></i>
                No se encontraron cámaras disponibles. 
                Por favor, verifica los permisos de la cámara.
              </div>
            `;
        }
        return;
      }

      console.log(`${devices.length} cámara(s) encontrada(s)`);

      // A la PRIMERA vez, intentar que la cámara actual sea la trasera
      if (devices.length > 1 && currentCameraIndex === 0) {
        const backIndex = devices.findIndex((device) => {
          const label = (device.label || "").toLowerCase();
          return (
            label.includes("back") ||
            label.includes("rear") ||
            label.includes("environment")
          );
        });
        if (backIndex >= 0) {
          currentCameraIndex = backIndex;
        }
      }

      // Seguridad: si el índice se va de rango, volver a 0
      if (currentCameraIndex >= devices.length) {
        currentCameraIndex = 0;
      }

      const currentDevice = devices[currentCameraIndex];
      const cameraId = currentDevice.id;

      console.log(
        `Usando cámara ${currentCameraIndex + 1}/${devices.length}:`,
        currentDevice.label
      );

      // Actualizar botón para mostrar cuántas cámaras hay
      updateCameraButton();

      // Iniciar el escáner
      await html5QrCode.start(
        cameraId,
        {
          fps: 10,
          qrbox: { width: 250, height: 250 },
          aspectRatio: 1.0,
        },
        (decodedText, decodedResult) => {
          onQrDecoded(decodedText);
        },
        (errorMessage) => {
          // No mostrar errores continuos
        }
      );

      isRunning = true;
      console.log("✓ Escáner QR iniciado correctamente");
    } catch (err) {
      console.error("Error al iniciar el lector QR:", err);

      const readerEl = document.getElementById(qrRegionId);
      if (readerEl) {
        readerEl.innerHTML = `
            <div class="alert alert-danger m-3">
              <i class="bi bi-exclamation-triangle me-2"></i>
              Error al iniciar la cámara: ${err.message || "Desconocido"}
            </div>
          `;
      }
    }
  }

  /**
   * Detiene el escáner de QR
   */
  async function stopScanner() {
    if (!html5QrCode || !isRunning) {
      return;
    }

    try {
      await html5QrCode.stop();
      await html5QrCode.clear();
      console.log("✓ Escáner QR detenido");
    } catch (err) {
      console.error("Error al detener el lector QR:", err);
    } finally {
      isRunning = false;
    }
  }

  /**
   * Actualiza el texto del botón de cámara
   */
  function updateCameraButton() {
    const btn = document.getElementById("btnToggleCamera");
    if (!btn) return;

    if (!devices || devices.length <= 1) {
      btn.style.display = "none"; // Ocultar si solo hay 1 cámara
    } else {
      btn.style.display = "inline-block";
      const currentLabel = devices[currentCameraIndex]?.label || "";
      const isFront = currentLabel.toLowerCase().includes("front") || 
                      currentLabel.toLowerCase().includes("user");
      
      btn.innerHTML = `<i class="bi bi-arrow-repeat me-1"></i>${
        devices.length > 1 ? "Cambiar cámara" : ""
      }`;
    }
  }

  /**
   * CAMBIAR DE CÁMARA (CON FEEDBACK VISUAL)
   */
  async function toggleCamera() {
    if (!devices || devices.length <= 1) {
      console.log("Solo hay una cámara disponible");
      return;
    }

    const btn = document.getElementById("btnToggleCamera");
    
    console.log("Cambiando de cámara...");

    if (btn) {
      btn.disabled = true;
      btn.innerHTML =
        '<span class="spinner-border spinner-border-sm me-1"></span>Cambiando...';
    }

    try {
      // Apagar la cámara actual
      await stopScanner();

      // Pasar a la siguiente cámara
      currentCameraIndex = (currentCameraIndex + 1) % devices.length;

      // Volver a iniciar con la nueva cámara
      await startScanner();

      // Feedback de éxito
      if (btn) {
        const currentLabel = devices[currentCameraIndex]?.label || "";
        const isFront = currentLabel.toLowerCase().includes("front") || 
                        currentLabel.toLowerCase().includes("user");
        const cameraType = isFront ? "Frontal" : "Trasera";
        
        btn.innerHTML = `<i class="bi bi-check-circle me-1"></i>${cameraType}`;
        
        setTimeout(() => {
          updateCameraButton();
          btn.disabled = false;
        }, 1500);
      }
    } catch (error) {
      console.error("Error al cambiar de cámara:", error);

      if (btn) {
        btn.innerHTML = '<i class="bi bi-x-circle me-1"></i>Error';
        setTimeout(() => {
          updateCameraButton();
          btn.disabled = false;
        }, 2000);
      }
    }
  }

  // ============================================================================
  // EVENTOS DEL MODAL
  // ============================================================================

  qrModalEl.addEventListener("shown.bs.modal", () => {
    console.log("Modal QR abierto, iniciando escáner...");
    startScanner();
  });

  qrModalEl.addEventListener("hidden.bs.modal", () => {
    console.log("Modal QR cerrado, deteniendo escáner...");
    stopScanner();
  });

  // BOTÓN PARA CAMBIAR DE CÁMARA
  const btnToggleCamera = document.getElementById("btnToggleCamera");
  if (btnToggleCamera) {
    btnToggleCamera.addEventListener("click", () => {
      console.log("Click en botón Cambiar cámara");
      toggleCamera();
    });
  } else {
    console.warn(
      "No se encontró el botón #btnToggleCamera. Asegúrate de que exista en el HTML."
    );
  }

  console.log("✓ QR Reader inicializado correctamente - v2.0");
})(window, document);