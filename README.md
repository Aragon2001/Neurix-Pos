# Sistema de Facturación Electrónica — Costa Rica

Sistema de Punto de Venta (POS) con integración de Facturación Electrónica para Costa Rica según las especificaciones del Ministerio de Hacienda.

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Lenguaje | PHP >= 7.4 |
| Framework | CodeIgniter 3.1.9 |
| Base de datos | MySQL / MariaDB |
| PDF | mPDF 8.x |
| Email | SwiftMailer 6.x + PHPMailer 6.9 |
| Pagos | Stripe PHP 7.x |
| Código de barras | Laminas Barcode 2.x |
| Impresión térmica | mike42/escpos-php |
| Firma XML | Firmadocr (PHP nativo, XAdES-EPES) |

## Requisitos

- PHP >= 7.4 (recomendado 8.1+)
- MySQL 5.7+ / MariaDB 10.3+
- Extensiones PHP: `curl`, `imap`, `dom`, `openssl`, `mbstring`, `gd`
- Composer
- Servidor web: Apache con `mod_rewrite` habilitado

## Instalación

```bash
# 1. Clonar el repositorio
git clone <repo-url> facturacion
cd facturacion

# 2. Instalar dependencias
composer install

# 3. Configurar base de datos
cp app/config/database.php.example app/config/database.php
# Editar con credenciales reales

# 4. Configurar entorno
# En Apache/Nginx, definir:  CI_ENV=production
# Para desarrollo local:     CI_ENV=development

# 5. Permisos (Linux/Mac)
chmod -R 755 app/cache app/logs uploads
```

## Configuración

### Variables de entorno

El archivo `conf.env` contiene configuración para las utilidades .NET de sincronización de escritorio. **No subir al repositorio** (está en `.gitignore`).

La configuración de base de datos va en `app/config/database.php` (también excluido de git).

### Entornos de Hacienda

En la tabla de settings de la base de datos, el campo `ambiente` controla el destino de los comprobantes:

| Valor | Descripción |
|---|---|
| `test` | API sandbox de Hacienda (pruebas) |
| `prod` | API producción TRIBU-CR |

## Estructura del proyecto

```
www/
├── index.php              # Punto de entrada CodeIgniter
├── .htaccess              # Rewrite rules Apache
├── .gitignore
├── composer.json
├── app/                   # Aplicación principal
│   ├── config/            # Configuración CI3
│   ├── controllers/       # Controladores HTTP
│   ├── models/            # Modelos de datos
│   ├── libraries/         # Librerías custom
│   │   ├── Crearxml.php   # Generador XML Hacienda v4.4
│   │   ├── Firmadocr.php  # Firmado XAdES-EPES
│   │   ├── Apiclient.php  # Cliente API TRIBU-CR
│   │   └── ...
│   └── views/             # Plantillas HTML
├── system/                # Núcleo CodeIgniter 3.1.9
├── vendor/                # Dependencias Composer
├── themes/                # Estilos y assets del POS
├── files/                 # Archivos del sistema
└── uploads/               # XMLs y documentos generados (no en git)
```

## Módulos principales

| Módulo | Controlador | Descripción |
|---|---|---|
| POS | `Pos.php` | Punto de venta, ventas, pagos |
| Hacienda | `Shacienda.php` | Envío y consulta de comprobantes |
| Facturas de compra | `Facturascompras.php` | FEC - Facturas electrónicas de compra |
| Notas de crédito | `Creditnotes.php` | Notas de crédito electrónicas |
| Reportes | `Reports.php` | Reportes de ventas e impuestos |
| Productos | `Products.php` | Catálogo con códigos CABYS |
| Clientes | `Customers.php` | Gestión de receptores fiscales |
| Configuración | `Settings.php` | Parámetros del sistema y certificados |

## Tipos de comprobante soportados (v4.4)

| Código | Tipo | Estado |
|---|---|---|
| 01 | Factura Electrónica | Activo |
| 03 | Nota de Crédito Electrónica | Activo |
| 04 | Tiquete Electrónico | Activo |
| 08 | Factura Electrónica de Compra | Activo |
| 02 | Nota de Débito Electrónica | Pendiente reescritura |
| 09 | Recibo Electrónico de Pago (REP) | Pendiente implementación |

## Métodos de pago soportados

| Código | Descripción |
|---|---|
| 01 | Efectivo |
| 02 | Tarjeta |
| 03 | Cheque |
| 04 | Transferencia / Depósito |
| 08 | SINPE Móvil |
| 09 | Plataformas digitales (PayPal, etc.) |
| 99 | Otros |

## Firma digital

El sistema usa firma en PHP puro (`Firmadocr.php`) siguiendo el estándar XAdES-EPES requerido por Hacienda. Requiere certificado digital `.p12` del contribuyente configurado en Settings.

Alternativamente, puede configurarse un servidor externo de firmado (campo `server_lic` en Settings).

## Pendientes conocidos

1. **`getNotaDebito()`** en `Crearxml.php` — función rota, contiene código Laravel no migrado a CI3. No genera Notas de Débito funcionales.
2. **REP (tipo 09)** — Recibo Electrónico de Pago no implementado, requerido para ventas a crédito con IVA diferido.
3. **`codigo_actividad` del receptor** — falta campo en tabla `customers` para almacenar el código de actividad económica del receptor (requerido en v4.4 para facturas fiscales).
4. **API TRIBU-CR producción** — verificar credenciales y flujo de autenticación con el nuevo endpoint `api.hacienda.go.cr/fe/ae` antes de ir a producción.
5. **PHP 8.3 + CI4/Laravel** — migración mayor pendiente para cumplimiento de largo plazo.
