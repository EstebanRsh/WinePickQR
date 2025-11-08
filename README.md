# WINE‑PICK‑QR

**App web pensada para una vinoteca.** Escaneás el **código QR** de la góndola o la botella y ves, en una sola pantalla, el **precio final** y las **promociones vigentes**. Si no tenés un QR a mano, también podés **buscar por texto** (nombre, bodega o varietal).

> Este proyecto forma parte del **Trabajo Final Integrador** que realizamos con mi compañero. La prioridad es la **claridad** para el cliente en el local: entender rápido cuánto va a pagar, sin vueltas ni lenguaje técnico.

---

## Resumen en 30 segundos

- **Qué resuelve:** dudas en la góndola sobre precio final y promos aplicables.
- **Cómo funciona:** escaneo de QR o búsqueda por texto → ficha del vino con precio final y promo vigente.
- **Para quién es:** propietario de una vinoteca que quiera mejorar su atencion, y personas que quieren confirmar rápido el valordel producto a pagar.

---

## Alcance de la primer versión (MVP)

**Incluye**

- Lectura de códigos QR desde la app.
- Búsqueda por texto (nombre, bodega o varietal).
- Ficha simple del vino con precio final y promoción vigente (y, cuando esté disponible, foto y descripción breve).
- Botón flotante con ícono de **QR** para acceso rápido al lector.
- Interfaz mínima, clara y legible en celulares.
- Panel de administración para la vinoteca con CRUD de productos (crear, ver, editar y eliminar), metricas basicas (producto mas escaneado) y login de administrador.

**No incluye** (queda fuera de alcance en esta primer etapa)

- Carrito, checkout o pagos.
- Registro de cuentas para clientes.
- Gestión de stock, pedidos.
- Métricas o reportes avanzados.

> La idea es hacer bien lo básico para la vinoteca: ver precio final y promos de manera rápida y confiable, y permitir que el administrador mantenga el catálogo al día de forma sencilla.

---

## Cómo se usa

1. **Abrí la app** en el celular. Si querés, **agregála a la pantalla de inicio** para entrar más rápido (según soporte del navegador).
2. Elegí una opción:

   - Tocá el **botón flotante** con el ícono de **QR** y apuntá la cámara al código de la góndola o la botella.
   - O bien, usá el **buscador** y escribí el **nombre**, **bodega** o **varietal**.

3. Vas a ver la **ficha del vino** con el **precio final** y, si corresponde, la **promoción vigente**.

Si el QR no se lee a la primera, probá con más luz o cambiando la distancia. Siempre podés recurrir al buscador por texto.

---

## ¿Qué voy a ver en pantalla?

- **Nombre del vino** y datos básicos (bodega y varietal).
- **Precio final** (el monto que pagás en caja) y **promo vigente** cuando aplica.
- Cuando esté disponible: foto y descripción breve del producto.
- Acciones claras para volver, buscar otro producto o relanzar el escaneo.

> El diseño usa tipografías legibles, contraste adecuado y botones grandes para que se pueda usar con **una sola mano** en el local.

---

Panel de administración (para la vinoteca)

- El local cuenta con un acceso privado para mantener el catálogo actualizado.

**Acceso**

- Login de administrador con usuario y contraseña (solo personal autorizado).

- Opción visible para cerrar sesión.

**Qué se puede hacer (CRUD)**

- Crear un producto nuevo: nombre, bodega, varietal, descripción breve, precio base y promoción vigente (opcionalmente, foto).

- Ver el listado de productos con buscador simple.

- Editar/Actualizar datos del producto (por ejemplo, precio o promo vigente) con validaciones y mensajes claros.

- Eliminar/Desactivar un producto (con confirmación para evitar errores).

> Aclaración importante: el login es solo para administración. Los clientes no necesitan cuenta para escanear o buscar.

---

## Privacidad y permisos

- **Cámara:** se pide permiso **solo** para leer códigos QR en ese momento. La app **no** graba ni guarda fotos o videos.
- **Cuentas:** los clientes no se registran. Solo existe el login de administrador para gestionar el catálogo.
- **Datos:** se guardan únicamente los necesarios para administrar los productos en la vinoteca. No se recolectan datos personales de clientes.

---

## Compatibilidad

Funciona en navegadores modernos de **Android** y **iOS**, y también desde escritorio (Chrome, Edge, Safari y Firefox actuales). Si el navegador lo permite, puede **agregarse como acceso directo** a la pantalla de inicio y abrirse en modo app.

---

## Limitaciones y advertencias

- Las promociones y precios pueden **cambiar según la sucursal o la fecha**. La app busca reflejar la información vigente al momento de la consulta, pero **siempre** recomiendo validar en caja ante cualquier duda.
- Algunas funciones dependen del soporte del navegador (por ejemplo, el acceso a cámara o la instalación como acceso directo).

---

## Preguntas frecuentes

**¿Necesito una cuenta para usarla?**
No. Las funciones principales (escaneo y búsqueda) se pueden usar sin registrarse.

**¿La app graba con la cámara?**
No. Solo utiliza la cámara para leer el QR en tiempo real.

**¿Qué hago si el QR no se lee?**
Probá con más luz, ajustá la distancia o usá el **buscador** por texto.

**¿Puedo usarla sin internet?**
La app necesita conexión para traer la información actualizada. Si no hay señal, te va a indicar que no pudo cargar y vas a poder reintentar.

---

## Estado del proyecto

Se trata de un **MVP** en evolución dentro del Trabajo Final Integrado que realizamos con mi compañero. A medida que avance, iremos mejorar tiempos de carga, accesibilidad y mensajes de error.

---

## Autor

Proyecto desarrollado por 2 estudiantes, como parte de nuestro **Trabajo Final Integrado**. Si tenés sugerencias o detectás algo que pueda confundir a un cliente en la góndola, te invito a dejar tus comentarios: ayudan a que la app sea más útil para la vinoteca y sus clientes.
