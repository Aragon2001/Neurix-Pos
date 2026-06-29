# ✅ Checklist de Testing — AdminLTE 4 Migración

**Instrucciones**: Abre cada módulo en tu navegador y verifica que todo funciona. Marca con ✅ cuando esté confirmado.

---

## 🖥️ Navegador Recomendado
- Chrome/Edge 90+ (recomendado)
- Firefox 88+
- Safari 14+
- **Evitar**: IE11 (no soportado)

---

## 1️⃣ Componentes Básicos

### Navbar / Header
- [ ] Logo se renderiza correctamente
- [ ] Avatar de usuario visible
- [ ] Menú usuario dropdown abre/cierra
- [ ] Selector de idioma funciona
- [ ] Botón tema oscuro/claro funciona
- [ ] Tooltips en iconos funcionan
- [ ] Navbar responsive en móvil

### Sidebar
- [ ] Sidebar visible en desktop
- [ ] Sidebar items expandibles (treeview)
- [ ] Chevron/flecha rota al expandir
- [ ] Items treeview muestran submenu
- [ ] Solo un treeview expandido a la vez
- [ ] Sidebar desaparece en móvil (offcanvas)
- [ ] Botón hamburguesa abre sidebar en móvil
- [ ] Cierre de sidebar al navegar en móvil

### Tema Oscuro/Claro
- [ ] Click en botón tema cambia colores
- [ ] Transición suave al cambiar tema
- [ ] Label del botón actualiza (🌙 Oscuro / ☀️ Claro)
- [ ] Tema se mantiene al recargar (localStorage)
- [ ] Colores correctos en ambos temas
- [ ] Contraste legible en ambos temas

---

## 2️⃣ Módulos de Contenido

### Dashboard (inicio)
- [ ] Página carga rápido
- [ ] Cards se renderizan correctamente
- [ ] Colores de cards visibles
- [ ] Responsive en móvil

### Tablas (Productos, Clientes, etc.)
- [ ] Tabla renderiza todos los datos
- [ ] Encabezados con fondo claro
- [ ] Filas con hover effect (color al pasar mouse)
- [ ] Paginación funciona
- [ ] Búsqueda/filtro funciona (si existe)
- [ ] Responsive: scroll horizontal en móvil

### Formularios
- [ ] Inputs enfocados cambian color
- [ ] Select dropdowns funcionan
- [ ] Checkboxes visibles y funcionales
- [ ] Radio buttons visibles y funcionales
- [ ] Botones con colores correctos
- [ ] Validación funciona

### Botones
- [ ] Colores correctos
- [ ] Hover effect visible
- [ ] Disables state funciona
- [ ] Ripple/animación suave

---

## 3️⃣ Funcionalidad JavaScript

### Tom Select (si está en uso)
```
Ubicación: productos, clientes, proveedores → selects
```
- [ ] Dropdown abre al hacer click
- [ ] Búsqueda filtra opciones
- [ ] Selección actualiza valor
- [ ] Multiselecta si está habilitada
- [ ] Sin errores en consola

### Tempus Dominus Datepicker (si está en uso)
```
Ubicación: reportes, filtros de fecha
```
- [ ] Click en input abre calendario
- [ ] Calendario se renderiza correctamente
- [ ] Selección de fecha funciona
- [ ] Fecha se refleja en input
- [ ] Sin errores en consola

### Tabulator Tables (si está en uso)
```
Ubicación: reportes con datos grandes
```
- [ ] Tabla renderiza
- [ ] Paginación funciona
- [ ] Sorting en columnas funciona
- [ ] Filtrado funciona
- [ ] Exportar a CSV funciona (si existe)
- [ ] Sin errores en consola

### SweetAlert2 (si está en uso)
```
Ubicación: confirmaciones de delete, logout, etc.
```
- [ ] Modal abre correctamente
- [ ] Botones funcionan
- [ ] Animación suave
- [ ] Estilos correctos (tema oscuro/claro)

---

## 4️⃣ Módulos Críticos

### Autenticación
```
URL: /auth/login
```
- [ ] Login page se ve bien
- [ ] Formulario funciona
- [ ] Tema oscuro/claro aplica correctamente
- [ ] Error messages se muestran

### Hacienda (Facturación Electrónica)
```
URL: /shacienda/... 
```
- [ ] Páginas cargan sin errores
- [ ] Formatos se ven correctamente
- [ ] AJAX requests funcionan (si existen)
- [ ] Botones de envío funcionan
- [ ] Sin errores en consola de navegador

### Punto de Venta (POS)
```
URL: /pos
```
- [ ] Sistema POS funciona
- [ ] Caja abre/cierra correctamente
- [ ] Búsqueda de productos funciona
- [ ] Carrito actualiza
- [ ] Checkout funciona
- [ ] Impresión funciona (blanco + negro)

---

## 5️⃣ Responsive Design

### En móvil (ancho: 375px)
- [ ] Navbar se adapta
- [ ] Sidebar escondido (offcanvas)
- [ ] Botón hamburguesa visible
- [ ] Contenido legible
- [ ] Tablas scrolleable horizontalmente
- [ ] Botones clickeables
- [ ] Formularios funcionales

### En tablet (ancho: 768px)
- [ ] Sidebar visible (no offcanvas)
- [ ] Layout se adapta
- [ ] Contenido bien distribuido

### En desktop (ancho: 1920px)
- [ ] Todo visible
- [ ] Espaciamiento correcto
- [ ] Sin scroll horizontal innecesario

---

## 6️⃣ Performance

### Browser Console (F12)
```
Verificar que no haya:
```
- [ ] Errores rojos (❌)
- [ ] Warnings naranjas (⚠️) innecesarios
- [ ] `404 Not Found` en recursos
- [ ] Mixed content warnings

### Network Tab (F12 → Network)
```
Al cargar la página:
```
- [ ] `www.min.css` carga exitosamente
- [ ] `main.min.js` carga exitosamente
- [ ] Todos los recursos cargan
- [ ] Tiempo de carga < 3 segundos

### Lighthouse (F12 → Lighthouse)
```
Ejecutar audit:
```
- [ ] Performance > 70
- [ ] Accessibility > 80
- [ ] Best Practices > 80

---

## 7️⃣ Problemas Conocidos a Reportar

Si encuentras algo que NO funciona, anota:

```
[ ] Título del problema
    - URL donde ocurre: _______________
    - Navegador: _______________
    - Steps to reproduce: _______________
    - Error en consola: _______________
    - Captura de pantalla (adjuntar si es posible)
```

---

## 📋 Resumen de Testing

### Completado
- Fecha: _______________
- Navegador principal: _______________
- Dispositivos testeados:
  - [ ] Desktop (1920px)
  - [ ] Laptop (1366px)
  - [ ] Tablet (768px)
  - [ ] Mobile (375px)

### Problemas encontrados
```
Ninguno reportado:  _______________
```

### Notas adicionales
```
_______________________________________________
_______________________________________________
```

---

## ✅ Aprobación Final

```
Testeado por: _______________
Fecha: _______________
Resultado: ☐ APROBADO ☐ RECHAZADO (requiere fixes)
Comentarios: _______________
```

---

## 🚀 Si Todo Funciona

1. Ejecutar: `git tag v1.0-adminlte4-migrated`
2. Push a main
3. Crear Release en GitHub
4. Deploy a producción

## ⚠️ Si Hay Problemas

1. Abrir issue en GitHub
2. Adjuntar:
   - Steps to reproduce
   - Error message
   - Browser/device info
   - Captura de pantalla

---

**Gracias por testing! 🙏**
