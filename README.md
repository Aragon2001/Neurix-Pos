# NEURIX POS — Facturación Electrónica Costa Rica

[![CI](https://github.com/Aragon2001/Neurix-Pos/actions/workflows/ci.yml/badge.svg)](https://github.com/Aragon2001/Neurix-Pos/actions/workflows/ci.yml)
![PHP](https://img.shields.io/badge/PHP-%3E%3D7.4-777bb4)
![CodeIgniter](https://img.shields.io/badge/CodeIgniter-3.1.9-ee4323)
![Hacienda](https://img.shields.io/badge/Hacienda%20CR-v4.4-blue)
![License](https://img.shields.io/badge/license-proprietary-lightgrey)

Sistema de Punto de Venta (POS) con Facturación Electrónica integrada para Costa Rica, conforme a las especificaciones **v4.4 del Ministerio de Hacienda** (obligatorias desde septiembre de 2025). Cubre todo el ciclo: ventas en POS, generación y firma de comprobantes XML, envío a TRIBU-CR, consulta de estado, notas de crédito/débito, recibos electrónicos de pago (REP) y reportería financiera.

**Desarrollado y mantenido por [ARASOFT SOLUTIONS](mailto:arasoftsolutions@outlook.com)** (Jostin Aragón Barboza).

---

## Índice

- [Características](#características)
- [Stack tecnológico](#stack-tecnológico)
- [Requisitos del servidor](#requisitos-del-servidor)
- [Instalación](#instalación)
- [Frontend (Vite)](#frontend-vite)
- [Bases de datos](#bases-de-datos)
- [Configuración](#configuración)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Comprobantes electrónicos soportados (v4.4)](#comprobantes-electrónicos-soportados-v44)
- [Integraciones con Hacienda](#integraciones-con-hacienda)
- [Métodos de pago](#métodos-de-pago)
- [Calidad y CI](#calidad-y-ci)
- [Seguridad](#seguridad)
- [Pendientes](#pendientes)

---

## Características

- **Punto de venta** completo con apertura/cierre de caja, ventas suspendidas, cotizaciones e impresión térmica (ESC/POS vía QZ Tray).
- **Facturación Electrónica v4.4**: generación de XML, firma XAdES-EPES nativa en PHP y envío/consulta contra la API TRIBU-CR de Hacienda.
- **Proxy Hacienda con caché**: consulta de Actividades Económicas y catálogo CABYS directamente desde la UI (clientes y productos), respetando el límite de 100 solicitudes/5s de la API pública.
- **Reportería y dashboard financiero**: ventas, compras, gastos y estado de comprobantes en ventanas de 12 meses.
- **Interfaz modernizada**: listados de datos migrados a un sistema de tablas propio (`NxTable`) con toolbar, filtros, chips, totales y exportación — reemplaza el stack legacy jQuery/DataTables.
- **PWA**: service worker con estrategia network-first para assets versionados por build.

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Lenguaje | PHP >= 7.4 (probado en 8.x) |
| Framework | CodeIgniter 3.1.9 |
| Base de datos | MySQL 5.7+ / MariaDB 10.3+ |
| Frontend build | Vite 8, Bootstrap 5.3, AdminLTE 4 |
| Tablas de datos | NxTable (motor propio, vanilla JS) |
| PDF | mPDF 8.x |
| Email | SwiftMailer 6.x + PHPMailer 6.9 |
| Pagos en línea | Stripe PHP 13.x |
| Código de barras | Laminas Barcode 2.x |
| Impresión térmica | mike42/escpos-php + QZ Tray |
| Firma XML | Firmadocr (PHP nativo, XAdES-EPES) |
| Testing | PHPUnit 10 + PHPStan (nivel configurado en `phpstan.neon`) |
| CI | GitHub Actions |

## Requisitos del servidor

- PHP >= 7.4 con extensiones: `curl`, `dom`, `openssl`, `mbstring`, `gd`, `imap`
- MySQL 5.7+ / MariaDB 10.3+
- Apache con `mod_rewrite` habilitado
- Composer 2.x
- Node.js 18+ y npm (solo para compilar assets del frontend)

## Instalación

```bash
# 1. Clonar
git clone https://github.com/Aragon2001/Neurix-Pos.git
cd Neurix-Pos

# 2. Dependencias PHP
composer install

# 3. Variables de entorno
cp .env.example .env
# Editar APP_ENV, DB_HOST, DB_USER, DB_PASS, DB_NAME

# 4. Base de datos
cp app/config/database.php.example app/config/database.php
# Editar hostname, username, password, database

# 5. Permisos (Linux)
chmod -R 755 app/cache app/logs uploads

# 6. Certificados Hacienda
# Copiar el .p12 del contribuyente a:
#   files/certificados/prod/{cedula}.p12   ← producción
#   files/certificados/test/{cedula}.p12   ← sandbox
# (también se puede subir desde la UI en Configuración > Ajustes)
```

## Frontend (Vite)

```bash
npm install

npm run dev      # build en modo watch para desarrollo
npm run build    # build de producción → dist/
npm run preview  # previsualizar el build
```

> ⚠️ El build usa `emptyOutDir`. Tras cada `npm run build`, recarga el navegador con caché limpia (Ctrl+Shift+R) al menos una vez — el service worker (`sw.js`) sirve CSS/JS con estrategia network-first, versionados por `?v=<filemtime>`.

## Bases de datos

El proyecto incluye dos scripts SQL con el mismo esquema completo (61 tablas, catálogo geográfico de Costa Rica y catálogo de impuestos v4.4), pensados para distintos escenarios. Ver [README-BASES-DATOS.md](README-BASES-DATOS.md) para el detalle completo.

| Archivo | Uso | Contenido de negocio |
|---|---|---|
| `NeurixBD-Inicial.sql` | Producción — arranque limpio | Sin datos de negocio (0 productos/clientes/ventas) |
| `database_ejemplo.sql` | Desarrollo / testing / demo | ~24 productos, 16 clientes, ~131 ventas y 12 meses de historial |

```bash
mysql -u root -p < NeurixBD-Inicial.sql      # producción
# o
mysql -u root -p < database_ejemplo.sql      # desarrollo
```

## Configuración

| Archivo | Descripción |
|---|---|
| `.env` | Entorno de la aplicación y credenciales de BD (excluido de git, usar `.env.example`) |
| `app/config/database.php` | Credenciales de BD (excluido de git, usar `.example`) |
| `app/config/config.php` | URL base, cifrado de sesión |
| `app/config/constants.php` | `AMBIENTE` (test/prod) y `DEMO` |
| Settings en BD | Parámetros del emisor, certificado, credenciales TRIBU-CR |

El campo `ambiente` en Settings (editable desde la UI) controla el destino de los comprobantes:

| Valor | API |
|---|---|
| `test` | `api-sandbox.comprobanteselectronicos.go.cr` |
| `prod` | `api.hacienda.go.cr/fe/ae` (TRIBU-CR) |

## Estructura del proyecto

```
Neurix-Pos/
├── index.php                     # Punto de entrada CI3
├── app/
│   ├── config/
│   │   └── database.php.example
│   ├── controllers/
│   │   ├── Shacienda.php         # Envío/consulta de comprobantes + REP
│   │   ├── Hacienda_proxy.php    # Proxy con caché para CABYS y AE
│   │   ├── Pos.php               # Punto de venta
│   │   ├── Creditnotes.php       # Notas de crédito
│   │   ├── Debitnotes.php        # Notas de débito
│   │   └── ...
│   ├── libraries/
│   │   ├── Crearxml.php          # Generador XML v4.4 (todos los tipos)
│   │   ├── Firmadocr.php         # Firmado XAdES-EPES
│   │   └── Apiclient.php         # Cliente REST TRIBU-CR
│   └── models/
│       └── Hacienda_model.php
├── files/
│   ├── certificados/
│   │   ├── prod/                 # Certificado .p12 producción (no en git)
│   │   └── test/                 # Certificado .p12 sandbox (no en git)
│   └── updates/db_updates/       # Scripts SQL de migración
├── themes/                       # UI del POS (default + variantes)
│   └── default/assets/src/       # Fuentes de assets Vite (nx-tables.css, nx-table.js, ...)
├── tests/                        # PHPUnit
├── uploads/                      # Imágenes y archivos (parcialmente en git)
├── vendor/                       # Dependencias Composer (no en git)
├── node_modules/                 # Dependencias npm (no en git)
├── dist/                         # Build de Vite (generado, no en git)
├── NeurixBD-Inicial.sql          # BD de producción (sin datos de negocio)
├── database_ejemplo.sql          # BD de desarrollo (con datos de ejemplo)
├── vite.config.js
├── composer.json
└── package.json
```

## Comprobantes electrónicos soportados (v4.4)

| Código | Tipo | Estado |
|---|---|---|
| 01 | Factura Electrónica | ✅ Activo |
| 02 | Nota de Débito Electrónica | ✅ Activo |
| 03 | Nota de Crédito Electrónica | ✅ Activo |
| 04 | Tiquete Electrónico | ✅ Activo |
| 05/06/07 | Mensaje Receptor | ✅ Activo |
| 08 | Factura Electrónica de Compra | ✅ Activo |
| 09 | Recibo Electrónico de Pago (REP) | ✅ Activo |

## Integraciones con Hacienda

Además del envío y firma de comprobantes, el sistema consulta en tiempo real la API pública de Hacienda a través de un proxy propio (`Hacienda_proxy.php`) con caché local (`tec_hacienda_cache`):

- **Actividades Económicas**: en el formulario de clientes, autocompleta nombre, tipo de identificación y actividades económicas a partir de la cédula, y alerta si el contribuyente está moroso u omiso.
- **CABYS**: en el formulario de productos, autocompletado del catálogo de bienes y servicios (código, descripción e IVA aplicable) usado como `CodigoComercial Tipo=05` en el XML del comprobante.

Todas las llamadas del navegador pasan por el proxy — no se realizan solicitudes directas a `api.hacienda.go.cr` desde JavaScript, para respetar el límite de tasa de la API.

## Métodos de pago

| Código v4.4 | Descripción | `paid_by` en BD |
|---|---|---|
| 01 | Efectivo | `cash` |
| 02 | Tarjeta | `CC` |
| 03 | Cheque | `Cheque` |
| 04 | Transferencia / Depósito | `TransDep` |
| 08 | SINPE Móvil | `SINPE` |
| 09 | Plataformas digitales (PayPal) | `digital` |
| 99 | Otros | — |

## Calidad y CI

El workflow de GitHub Actions (`.github/workflows/ci.yml`) corre en cada push/PR a `main`:

```bash
composer install --no-interaction --prefer-dist --no-progress
vendor/bin/phpunit --testdox
vendor/bin/phpstan analyse --no-progress
```

## Seguridad

- Consultas parametrizadas (`?` binding) en modelos críticos como `Hacienda_model.php`.
- Credenciales de BD, `.env` y certificados `.p12` excluidos del repositorio.
- Modo producción por defecto (`CI_ENV=production`); definir `CI_ENV=development` para debug local.
- Contraseñas por defecto de las bases de instalación deben cambiarse inmediatamente tras el primer login (ver [README-BASES-DATOS.md](README-BASES-DATOS.md)).

## Pendientes

| # | Tarea | Prioridad |
|---|---|---|
| 1 | Verificar credenciales TRIBU-CR de producción antes de activar `ambiente=prod` | Alta |
| 2 | Completar migración de listados legacy restantes a NxTable | Media |
| 3 | Verificar códigos CABYS 2025 en catálogo de productos | Media |
| 4 | Migración a PHP 8.3 + CodeIgniter 4 o Laravel | Largo plazo |

---

© ARASOFT SOLUTIONS. Todos los derechos reservados.
