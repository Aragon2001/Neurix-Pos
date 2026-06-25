# Neurix POS — Facturación Electrónica Costa Rica

Sistema de Punto de Venta con integración de Facturación Electrónica para Costa Rica, cumpliendo las especificaciones v4.4 del Ministerio de Hacienda (obligatorio desde septiembre 2025).

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Lenguaje | PHP >= 7.4 (probado en 8.x) |
| Framework | CodeIgniter 3.1.9 |
| Base de datos | MySQL 5.7+ / MariaDB 10.3+ |
| PDF | mPDF 8.x |
| Email | SwiftMailer 6.x + PHPMailer 6.9 |
| Pagos en línea | Stripe PHP 7.x |
| Código de barras | Laminas Barcode 2.x |
| Impresión térmica | mike42/escpos-php |
| Firma XML | Firmadocr (PHP nativo, XAdES-EPES) |

## Requisitos del servidor

- PHP >= 7.4 con extensiones: `curl`, `dom`, `openssl`, `mbstring`, `gd`, `imap`
- MySQL 5.7+ / MariaDB 10.3+
- Apache con `mod_rewrite` habilitado
- Composer

## Instalación

```bash
# 1. Clonar
git clone https://github.com/Aragon2001/Neurix-Pos.git
cd Neurix-Pos

# 2. Dependencias PHP
composer install

# 3. Base de datos
cp app/config/database.php.example app/config/database.php
# Editar hostname, username, password, database

# 4. Permisos (Linux)
chmod -R 755 app/cache app/logs uploads

# 5. Migraciones
# Importar el dump base de la BD y luego aplicar en orden:
# files/updates/db_updates/4_0_*.sql

# 6. Certificados Hacienda
# Copiar el .p12 del contribuyente a:
#   files/certificados/prod/{cedula}.p12   ← producción
#   files/certificados/test/{cedula}.p12   ← sandbox
```

## Configuración

| Archivo | Descripción |
|---|---|
| `app/config/database.php` | Credenciales de BD (excluido de git, usar `.example`) |
| `app/config/config.php` | URL base, cifrado de sesión |
| Settings en BD | Parámetros del emisor, certificado, tokens TRIBU-CR |

El campo `ambiente` en Settings controla el destino de los comprobantes:

| Valor | API |
|---|---|
| `test` | `api-sandbox.comprobanteselectronicos.go.cr` |
| `prod` | `api.hacienda.go.cr/fe/ae` (TRIBU-CR) |

## Estructura

```
Neurix-Pos/
├── index.php                  # Punto de entrada CI3
├── app/
│   ├── config/
│   │   └── database.php.example
│   ├── controllers/
│   │   ├── Shacienda.php      # Envío/consulta comprobantes + REP
│   │   ├── Pos.php            # Punto de venta
│   │   ├── Creditnotes.php    # Notas de crédito
│   │   └── ...
│   ├── libraries/
│   │   ├── Crearxml.php       # Generador XML v4.4 (todos los tipos)
│   │   ├── Firmadocr.php      # Firmado XAdES-EPES
│   │   └── Apiclient.php      # Cliente REST TRIBU-CR
│   └── models/
│       └── Hacienda_model.php
├── files/
│   ├── certificados/
│   │   ├── prod/              # Certificado .p12 producción (no en git)
│   │   └── test/              # Certificado .p12 sandbox (no en git)
│   └── updates/db_updates/    # Scripts SQL de migración
├── themes/                    # UI del POS
├── uploads/                   # Imágenes y archivos (parcialmente en git)
├── vendor/                    # Dependencias Composer (no en git)
└── composer.json
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

## Pendientes

| # | Tarea | Prioridad |
|---|---|---|
| 1 | Verificar credenciales TRIBU-CR producción antes de activar | Alta |
| 2 | Agregar botón "Generar REP" en UI de detalle de pago | Media |
| 3 | Verificar códigos CABYS 2025 en catálogo de productos | Media |
| 4 | Migración a PHP 8.3 + CodeIgniter 4 o Laravel | Largo plazo |

## Seguridad

- SQL injection corregida en `Hacienda_model.php` (query binding con `?`)
- Credenciales de BD y certificados `.p12` excluidos del repositorio
- Modo producción por defecto (`CI_ENV=production`); definir `CI_ENV=development` para debug local
