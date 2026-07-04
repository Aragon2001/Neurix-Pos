# Bases de Datos — Neurix POS

## Descripción

Neurix POS incluye **dos archivos SQL** para cubrir diferentes escenarios de instalación:

### 1. **NeurixBD-Inicial.sql** — Para Producción
**Uso:** Instalación en servidor de producción

**Contenido:**
- ✅ Estructura completa de todas las tablas
- ✅ Datos **mínimos obligatorios**:
  - Grupos de usuario (Admin, Staff)
  - Usuario administrador por defecto
  - Tienda principal (ID 1)
  - Provincias/cantones/distritos de Costa Rica
  - Actividades económicas básicas (Hacienda)
- ❌ **Sin datos de prueba**: 0 productos, 0 clientes, 0 ventas

**Cuándo usarla:**
```bash
mysql -u root -p < NeurixBD-Inicial.sql
```

**Usuario admin por defecto:**
- Email: `admin@neurix.local`
- Contraseña: `123456` (cámbiala en producción)

---

### 2. **database_ejemplo.sql** — Para Desarrollo/Testing
**Uso:** Ambiente de desarrollo, testing, demostraciones

**Contenido:**
- ✅ Estructura completa de todas las tablas
- ✅ Datos de prueba:
  - ✓ 6 productos (abarrotes, bebidas, limpieza)
  - ✓ 3 clientes (de contado + 2 clientes recurrentes)
  - ✓ 1 proveedor
  - ✓ 2 métodos de envío
  - ✓ Todas las provincias/cantones de CR
  - ✓ Categorías, impuestos, métodos de pago

**Cuándo usarla:**
```bash
mysql -u root -p < database_ejemplo.sql
```

---

## Comparación Rápida

| Aspecto | NeurixBD-Inicial.sql | database_ejemplo.sql |
|--------|:---:|:---:|
| Estructura | ✅ Completa | ✅ Completa |
| Usuario admin | ✅ 1 | ✅ 1 |
| Productos | ❌ 0 | ✅ 6 |
| Clientes | ❌ 0 | ✅ 3 |
| Ventas | ❌ 0 | ❌ 0 |
| Proveedores | ❌ 0 | ✅ 1 |
| Categorías | ❌ 0 | ✅ 3 |
| Geografía CR | ✅ Sí | ✅ Sí |
| Tamaño archivo | ~140 KB | ~280 KB |
| Ambiente recomendado | **PRODUCCIÓN** | Desarrollo/Testing |

---

## Procedimiento de Instalación

### En Producción

```bash
# 1. Crear base de datos limpia
mysql -u root -p < NeurixBD-Inicial.sql

# 2. Configurar app/config/database.php
# database  = NeurixBD
# username  = root (o el usuario específico)
# password  = ****
# hostname  = localhost

# 3. Acceder al sistema
# URL: http://tudominio.com
# Email: admin@neurix.local
# Contraseña: 123456 (CAMBIAR INMEDIATAMENTE EN CONFIGURACIÓN)

# 4. En Configuración > Ajustes, completar:
#  - Datos de emisor (razón social, cédula, teléfono, etc.)
#  - Credenciales Hacienda (TRIBU-CR)
#  - Ambiente: cambiar de 'test' a 'prod' cuando esté listo
#  - Certificado digital .p12 (Hacienda)
```

### En Desarrollo

```bash
# 1. Crear base de datos con datos de prueba
mysql -u root -p < database_ejemplo.sql

# 2. Configurar app/config/database.php
# database  = NeurixBD
# username  = root
# password  = (vacío en Laragon)
# hostname  = localhost

# 3. Acceder con datos de prueba
# Email: admin@example.com
# Contraseña: admin123
# (Ver base de datos para más detalles)

# 4. Jugar con:
#  - POS (vender productos de prueba)
#  - Reportes (ver cómo se ven con datos)
#  - Facturación electrónica (ambiente test)
```

---

## Primeros Pasos Después de Instalar

### Obligatorio en Producción

1. **Cambiar contraseña del admin**
   - Ir a: Configuración > Usuarios > admin
   - Cambiar contraseña

2. **Configurar Hacienda**
   - Ir a: Configuración > Hacienda
   - Ingresa cedula, nombre comercial, teléfono, etc.
   - Carga certificado digital .p12
   - Configura ambiente y credenciales TRIBU-CR

3. **Verificar ubicación**
   - Ir a: Configuración > Datos emisor
   - Verifica provincia, cantón, distrito

4. **Agregar Productos**
   - Ir a: Productos > Nuevo
   - Crea tu catálogo

5. **Agregar Clientes**
   - Ir a: Clientes > Nuevo
   - Para facturación electrónica requiere: tipo de ID + número

---

## Estructura de Datos

**Total de tablas: 49**

| Grupo | Tablas | Descripción |
|-------|--------|-------------|
| Autenticación | 4 | Usuarios, grupos, sesiones |
| Configuración | 5 | Settings, hacienda-cache, tiendas, impresoras |
| Catálogo | 6 | Productos, categorías, precios, stock |
| Clientes/Proveedores | 2 | Customers, suppliers |
| Ventas | 8 | Sales, items, pagos, otros textos, registros |
| Créditos/Débitos | 3 | Note credits/debits + items + Hacienda |
| Hacienda | 8 | Tiketes, NC, ND, REP, FEC + documentos |
| Geografía | 4 | Provincias, cantones, distritos, barrios |
| Otros | 6 | Inventario, depósitos, sesiones, esperas |

---

## Notas Importantes

⚠️ **Ambiente**
- Las bases de datos vienen con `ambiente='test'` por defecto
- Para producción, cambiar a `ambiente='prod'` después de verificar credenciales

🔐 **Seguridad**
- Cambiar las contraseñas por defecto inmediatamente
- Usar HTTPS en producción
- Proteger el acceso al phpMyAdmin

💾 **Backups**
- Hacer backup de la BD regularmente
- Antes de actualizaciones: `mysqldump -u root -p NeurixBD > backup.sql`

---

## Problemas Comunes

**P: "Error: Unknown column..."**
- R: Asegúrate de haber importado el archivo SQL correcto, o ejecuta la migración: `app/core/MY_Controller.php`

**P: "No se ve el usuario admin"**
- R: Ejecutaste database_ejemplo.sql pero importaste con usuario diferente. Usuario es: `admin@example.com`

**P: "Base de datos vacía después de importar"**
- R: Verifica que seleccionaste la BD correcta antes de importar, o que MySQL no tuvo errores.

---

## Soporte

Para más información, consulta:
- [Documentación del Proyecto](./README.md)
- [Guía de Hacienda](./docs/hacienda.md)
- [Reportes de Bugs](https://github.com/Aragon2001/Neurix-Pos/issues)

---

**Versión:** 1.0.0  
**Última actualización:** julio 2026
