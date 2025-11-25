# API WINE-PICK-QR (MVP)

Base común (ejemplos):

- Local: `http://localhost/api/`
- Producción (ejemplo): `https://tu-dominio.com/api/`

Archivos principales de la API:

- `login.php` → inicio de sesión admin  
- `product.php` → CRUD de productos + paginación + búsqueda  
- `promotion.php` → promociones individuales + combos  
- `metrics.php` → métricas de vistas

Todas las respuestas son **JSON**.

---

## 1. Autenticación — `login.php`

### POST `login.php`

Inicia sesión del usuario admin.

**Body (JSON o form-data)**

```json
{
  "username": "admin",
  "password": "tu_password"
}
```

**Respuesta OK (200)**

```json
{
  "success": true,
  "user": {
    "id": 1,
    "username": "admin"
  }
}
```

**Errores**

- `400` → faltan credenciales
- `401` → credenciales inválidas
- `500` → error de base de datos

> Nota: Por ahora no devolvemos token ni manejamos sesión del lado del servidor. Eso se puede agregar después.

---

## 2. Productos — `product.php`

El endpoint se controla con el parámetro `action`.

### 2.1. Listar productos (con filtros y paginación)

**GET `product.php?action=list`**

Parámetros opcionales (query):

- `search` → texto a buscar en: nombre, varietal, origen, productor, pid  
- `stock_status` → `AVAILABLE` | `LOW` | `OUT`  
- `active` → `1` (activos) o `0` (inactivos)  
- `page` → página (por defecto `1`)  
- `per_page` → registros por página (por defecto `10`, máx `100`)

Ejemplo:

```http
GET /api/product.php?action=list&search=malbec&stock_status=AVAILABLE&page=1&per_page=10
```

**Respuesta:**

```json
{
  "data": [
    {
      "id": 1,
      "pid": "MALBEC-QR-001",
      "name": "Malbec Reserva 2020",
      "producer": "Bodega X",
      "varietal": "Malbec",
      "origin": "Mendoza",
      "year": 2020,
      "short_description": "Malbec intenso...",
      "list_price": "15000.00",
      "stock_status": "AVAILABLE",
      "main_image": "https://...",
      "active": 1,
      "created_at": "2025-11-16 12:00:00",
      "updated_at": null
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 10,
    "total": 1,
    "total_pages": 1
  },
  "filters": {
    "search": "malbec",
    "stock_status": "AVAILABLE",
    "active": "1"
  }
}
```

---

### 2.2. Detalle de producto

**GET `product.php?action=detail&id=...`**  
o  
**GET `product.php?action=detail&pid=...`**

Ejemplos:

```http
GET /api/product.php?action=detail&id=1
GET /api/product.php?action=detail&pid=MALBEC-QR-001
```

**Respuesta OK:**

```json
{
  "data": {
    "id": 1,
    "pid": "MALBEC-QR-001",
    "name": "Malbec Reserva 2020",
    "...": "..."
  }
}
```

---

### 2.3. Crear producto

**POST `product.php?action=create`**

Body (JSON o form-data):

```json
{
  "pid": "GIN-QR-001",
  "name": "Gin Premium",
  "producer": "Destilería X",
  "varietal": null,
  "origin": "Argentina",
  "year": 2024,
  "short_description": "Gin de prueba",
  "list_price": 25000,
  "stock_status": "AVAILABLE",
  "main_image": "https://...",
  "active": 1
}
```

Campos obligatorios: `pid`, `name`, `list_price`, `stock_status`.  

**Respuesta OK (201):**

```json
{
  "success": true,
  "id": 5
}
```

**Errores típicos:**

- `400` → datos inválidos (falta algún campo, price negativo, etc.)
- `409` → ya existe un producto con el mismo `pid`
- `500` → error en base de datos

---

### 2.4. Actualizar producto

**POST `product.php?action=update`**

Body:

```json
{
  "id": 1,
  "pid": "MALBEC-QR-001",
  "name": "Malbec Reserva 2020 (nuevo nombre)",
  "producer": "Bodega X",
  "varietal": "Malbec",
  "origin": "Mendoza",
  "year": 2020,
  "short_description": "Texto actualizado",
  "list_price": 16000,
  "stock_status": "LOW",
  "main_image": "https://...",
  "active": 1
}
```

**Respuesta OK:**

```json
{ "success": true }
```

---

### 2.5. Eliminar producto

**POST `product.php?action=delete`**

- Por defecto hace **soft delete** (`active = 0`).  
- Si querés borrar físicamente, mandás `hard_delete = true`.

Body:

```json
{
  "id": 1,
  "hard_delete": false
}
```

**Respuesta:**

```json
{
  "success": true,
  "hard_delete": false
}
```

---

## 3. Promociones — `promotion.php`

Se divide en dos grupos:

- Promos individuales (tabla `product_promotions`)
- Combos (tabla `combo_promotions`)

El parámetro `action` define qué hacer.

---

### 3.1. Promos individuales (ProductPromotion)

#### Listar

**GET `promotion.php?action=list_product`**

Parámetros opcionales:

- `product_id` → filtrar por producto
- `active` → `1` o `0`
- `page`, `per_page`

```http
GET /api/promotion.php?action=list_product&product_id=1&active=1&page=1&per_page=10
```

**Respuesta:**

```json
{
  "data": [
    {
      "id": 1,
      "product_id": 1,
      "product_name": "Malbec Reserva 2020",
      "percent": "20.00",
      "pack_size": null,
      "pack_price": null,
      "start_date": "2025-11-01",
      "end_date": "2025-11-30",
      "note": "Promo lanzamiento",
      "active": 1
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 10,
    "total": 1,
    "total_pages": 1
  },
  "filters": {
    "product_id": 1,
    "active": "1"
  }
}
```

#### Detalle

**GET `promotion.php?action=detail_product&id=...`**

```http
GET /api/promotion.php?action=detail_product&id=1
```

---

#### Crear promo individual

**POST `promotion.php?action=create_product`**

Regla: **o** `percent` **o** (`pack_size` + `pack_price`) pero no ambos.

Ejemplo 1 – 20% OFF:

```json
{
  "product_id": 1,
  "percent": 20,
  "pack_size": null,
  "pack_price": null,
  "start_date": "2025-11-01",
  "end_date": "2025-11-30",
  "note": "20% OFF noviembre",
  "active": 1
}
```

Ejemplo 2 – Pack x6:

```json
{
  "product_id": 3,
  "percent": null,
  "pack_size": 6,
  "pack_price": 60000,
  "start_date": "2025-11-05",
  "end_date": "2025-11-20",
  "note": "Pack x6",
  "active": 1
}
```

**Respuesta OK:**

```json
{
  "success": true,
  "id": 10
}
```

---

#### Actualizar promo individual

**POST `promotion.php?action=update_product`**

Body igual que create, pero con `id`:

```json
{
  "id": 10,
  "product_id": 1,
  "percent": 25,
  "pack_size": null,
  "pack_price": null,
  "start_date": "2025-11-01",
  "end_date": "2025-11-30",
  "note": "Ahora 25% OFF",
  "active": 1
}
```

#### Eliminar promo individual

**POST `promotion.php?action=delete_product`**

```json
{
  "id": 10
}
```

---

### 3.2. Promos combo (ComboPromotion)

#### Listar combos

**GET `promotion.php?action=list_combo`**

Parámetros opcionales:

- `product_id` → combos que incluyan ese producto
- `active` → `1` o `0`
- `page`, `per_page`

```http
GET /api/promotion.php?action=list_combo&product_id=1&active=1&page=1&per_page=10
```

#### Detalle combo

**GET `promotion.php?action=detail_combo&id=...`**

```http
GET /api/promotion.php?action=detail_combo&id=1
```

---

#### Crear combo

**POST `promotion.php?action=create_combo`**

Reglas:

- `product1_id` y `product2_id` > 0 y **distintos**  
- El backend ordena internamente para mantener la convención (`product1_id < product2_id`).

Ejemplo:

```json
{
  "name": "Combo Malbec + Gin",
  "product1_id": 1,
  "product2_id": 5,
  "combo_price": 38000,
  "start_date": "2025-11-10",
  "end_date": "2025-11-30",
  "note": "Duo especial",
  "active": 1
}
```

**Respuesta OK:**

```json
{
  "success": true,
  "id": 3
}
```

---

#### Actualizar combo

**POST `promotion.php?action=update_combo`**

Body igual que create, pero con `id`:

```json
{
  "id": 3,
  "name": "Combo Malbec + Gin (actualizado)",
  "product1_id": 1,
  "product2_id": 5,
  "combo_price": 39000,
  "start_date": "2025-11-10",
  "end_date": "2025-11-30",
  "note": "Duo especial actualizado",
  "active": 1
}
```

#### Eliminar combo

**POST `promotion.php?action=delete_combo`**

```json
{
  "id": 3
}
```

---

## 4. Métricas — `metrics.php`

Solo **GET**, solo lectura.  
Filtros comunes (query):

- `start_date` → `YYYY-MM-DD` (opcional)
- `end_date` → `YYYY-MM-DD` (opcional)

---

### 4.1. Resumen de vistas

**GET `metrics.php?action=views_summary`**

Ejemplo:

```http
GET /api/metrics.php?action=views_summary&start_date=2025-11-01&end_date=2025-11-30
```

**Respuesta:**

```json
{
  "total_views": 123,
  "views_by_channel": [
    { "channel": "QR", "views": 80 },
    { "channel": "SEARCH", "views": 43 }
  ],
  "date_filter": {
    "start_date": "2025-11-01",
    "end_date": "2025-11-30"
  }
}
```

---

### 4.2. Vistas por producto

**GET `metrics.php?action=views_by_product`**

Parámetros:

- `start_date`, `end_date`
- `page`, `per_page`

Ejemplo:

```http
GET /api/metrics.php?action=views_by_product&start_date=2025-11-01&end_date=2025-11-30&page=1&per_page=10
```

**Respuesta:**

```json
{
  "data": [
    {
      "product_id": 1,
      "pid": "MALBEC-QR-001",
      "name": "Malbec Reserva 2020",
      "views_total": 50,
      "views_qr": 30,
      "views_search": 20
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 10,
    "total": 1,
    "total_pages": 1
  },
  "date_filter": {
    "start_date": "2025-11-01",
    "end_date": "2025-11-30"
  }
}
```

---

### 4.3. Timeline de vistas (serie temporal)

**GET `metrics.php?action=views_timeline`**

Parámetros:

- `product_id` (opcional; si no se envía, usa todos)  
- `start_date`, `end_date`

Ejemplo:

```http
GET /api/metrics.php?action=views_timeline&product_id=1&start_date=2025-11-01&end_date=2025-11-30
```

**Respuesta:**

```json
{
  "data": [
    {
      "date": "2025-11-10",
      "views_total": 10,
      "views_qr": 7,
      "views_search": 3
    }
  ],
  "filters": {
    "product_id": 1,
    "start_date": "2025-11-01",
    "end_date": "2025-11-30"
  }
}
```

---

## 5. Resumen rápido

- `login.php` → login admin (devuelve `success` + datos básicos de usuario).  
- `product.php` → CRUD de productos, con búsqueda y paginación.  
- `promotion.php` → altas/bajas/modificaciones de promos individuales y combos.  
- `metrics.php` → consultas de vistas (resumen general, por producto, timelines).

Este README debería ser suficiente para que cualquier persona del equipo frontend
entienda cómo consumir la API del MVP de WINE-PICK-QR.
