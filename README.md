# 🏦 WALLy Street — Simulador de Inversiones

API REST desarrollada con **PHP + Slim Framework** para el Seminario de Lenguajes (opción PHP, React y API Rest) — UNLP 2026.

## 📋 Descripción

WALLy Street es un simulador de mercado financiero. Los usuarios registrados reciben un bono inicial de **1000 USD virtuales** y pueden invertir en siete activos: Bitcoin, YPF, Oro, Plata, Petróleo, Apple y Soja. Los precios varían aleatoriamente con el tiempo, simulando la volatilidad del mercado.

## 🛠️ Tecnologías

- PHP 8+
- [Slim Framework 4](https://www.slimframework.com/)
- MySQL
- XAMPP (entorno local)
- Composer

## ⚙️ Instalación

### Requisitos previos

- XAMPP instalado y corriendo (Apache + MySQL)
- Composer instalado

### Pasos

1. Clonar el repositorio en la carpeta `htdocs` de XAMPP:

```bash
git clone https://github.com/JuanBlasGonzalez/seminariophp-xampp.git
cd seminariophp-xampp
```

2. Instalar las dependencias:

```bash
composer install
```

3. Crear la base de datos. En phpMyAdmin o desde MySQL, ejecutar el siguiente script:

```sql
CREATE DATABASE IF NOT EXISTS seminariophp;
USE seminariophp;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    balance DECIMAL(16, 2) DEFAULT 1000.00,
    is_admin TINYINT(1) DEFAULT 0,
    token VARCHAR(500) NULL,
    token_expired_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    current_price DECIMAL(16, 2) NOT NULL,
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE portfolio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    asset_id INT NOT NULL,
    quantity DECIMAL(16, 8) NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (asset_id) REFERENCES assets(id),
    UNIQUE KEY unique_user_asset (user_id, asset_id)
);

CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    asset_id INT NOT NULL,
    transaction_type ENUM('buy', 'sell') NOT NULL,
    quantity INT NOT NULL,
    price_per_unit DECIMAL(16, 2) NOT NULL,
    total_amount DECIMAL(16, 2) NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (asset_id) REFERENCES assets(id)
);

INSERT INTO assets (name, current_price) VALUES
    ('Bitcoin', 65000.50),
    ('YPF', 25.30),
    ('Gold', 2300.15),
    ('Silver', 28.45),
    ('Petroleum', 85.20),
    ('Apple', 175.10),
    ('Soybean', 430.00);
```

4. Configurar la conexión a la base de datos en `src/config/DB.php` con tus credenciales locales.

5. Apuntar el servidor al directorio `public/`. En XAMPP podés configurar un VirtualHost o acceder directamente via `http://localhost/seminariophp-xampp/public/`.

---

## 🔐 Autenticación

El sistema usa tokens Bearer con expiración de 5 minutos. Cada request autenticado extiende el token 5 minutos más.

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| POST | `/login` | Iniciar sesión | No |
| POST | `/logout` | Cerrar sesión | Sí |

**Ejemplo de header de autenticación:**
```
Authorization: Bearer <token>
```

---

## 📡 Endpoints

### Usuarios

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| POST | `/users` | Registrar nuevo usuario (recibe bono de 1000 USD) | No |
| GET | `/users` | Listar todos los inversores (solo admin) | Sí |
| GET | `/users/{user_id}` | Ver perfil, saldo y valor del portfolio | Sí |
| PUT | `/users/{user_id}` | Editar nombre y/o contraseña | Sí |

**Validaciones al registrarse:**
- `name`: solo letras, no puede estar vacío
- `email`: formato válido y único
- `password`: mínimo 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial

### Activos (El Mercado)

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `/assets` | Listar activos con precio actual (soporta filtros) | No |
| PUT | `/assets` | Actualizar precios aleatoriamente (solo admin) | Sí |
| GET | `/assets/{asset_id}/history/{quantity}` | Historial de precio de un activo (máx. 5) | No |

**Filtros disponibles en GET /assets:**
```
?type=Bitcoin
?min_price=100
?max_price=5000
```

### Operaciones

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| POST | `/trade/buy` | Comprar un activo | Sí |
| POST | `/trade/sell` | Vender un activo | Sí |

**Cuerpo del request:**
```json
{
    "asset_id": 1,
    "quantity": 2
}
```

### Portfolio e Historial

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `/portfolio` | Ver activos en cartera con valor actual | Sí |
| DELETE | `/portfolio/{asset_id}` | Eliminar activo del portfolio (solo si quantity = 0) | Sí |
| GET | `/transactions` | Historial de transacciones del usuario | Sí |

**Filtros disponibles en GET /transactions:**
```
?type=buy
?type=sell
?asset_id=1
```

---

## 📦 Estructura del proyecto

```
seminariophp-xampp/
├── public/
│   └── index.php          # Entry point y definición de rutas
├── src/
│   ├── config/
│   │   └── DB.php         # Conexión a la base de datos
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   ├── AssetController.php
│   │   ├── TransactionController.php
│   │   └── PortfolioController.php
│   ├── middleware/
│   │   └── AuthMiddleware.php
│   └── models/
│       ├── User.php
│       ├── Asset.php
│       ├── Portfolio.php
│       └── Transaction.php
├── composer.json
└── composer.lock
```

---

## 📌 Códigos de respuesta HTTP

| Código | Significado |
|--------|-------------|
| 200 | OK — operación exitosa |
| 400 | Bad Request — datos faltantes o inválidos |
| 401 | Unauthorized — no autenticado o sin permisos |
| 404 | Not Found — recurso no encontrado |
| 409 | Conflict — operación no permitida por el estado actual |
| 500 | Internal Server Error |

---

## 👥 Autores

Proyecto grupal — Seminario de Lenguajes, opción PHP · UNLP 2026
