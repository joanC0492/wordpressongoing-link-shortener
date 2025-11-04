# Wordpressongoing Link Shortener - Documentación Técnica

## 📋 Información General

**Plugin Name:** Wordpressongoing Link Shortener  
**Version:** 1.0.0  
**Author:** Joan Caballero  
**License:** GPL v2 or later  
**Requires PHP:** 7.4+  
**Requires WordPress:** 5.0+  
**Tested up to:** WordPress 6.8  

## 🎯 Descripción

Plugin profesional para WordPress que permite acortar enlaces con gestión avanzada de prefijos, aliases y sistema de reescritura de URLs. Desarrollado con arquitectura modular y siguiendo las mejores prácticas de WordPress.

## 🏗️ Arquitectura del Plugin

### Estructura de Archivos
```
wordpressongoing-link-shortener/
├── admin/                          # Funcionalidad del administrador
│   ├── class-link-shortener-admin.php
│   ├── js/admin.js
│   └── partials/                   # Vistas del administrador
│       ├── link-shortener-admin-display.php
│       ├── link-shortener-add-new-display.php
│       ├── link-shortener-all-links-display.php
│       ├── link-shortener-categories-display.php
│       └── link-shortener-settings-display.php
├── assets/                         # Archivos estáticos
│   ├── admin.css
│   └── admin.js
├── includes/                       # Clases principales
│   ├── class-ls-ajax.php          # Manejo de peticiones AJAX
│   ├── class-ls-cpt.php           # Custom Post Type
│   ├── class-ls-metabox.php       # Metaboxes
│   ├── class-ls-rewrite.php       # Sistema de reescritura
│   └── class-ls-settings.php      # Configuraciones
├── languages/                      # Internacionalización
│   ├── es_ES.po
│   ├── es_ES.mo
│   └── fulltimeforce-link-shortener.pot
└── fulltimeforce-link-shortener.php # Archivo principal
```

### Patrón de Diseño
- **Singleton Pattern:** Clase principal con instancia única
- **Hook-based Architecture:** Uso extensivo de WordPress hooks
- **Modular Design:** Separación de responsabilidades en clases especializadas
- **AJAX Pattern:** Comunicación asíncrona para UX mejorada

## 🔧 Componentes Principales

### 1. Clase Principal (`Fulltimeforce_Link_Shortener`)
- **Patrón:** Singleton
- **Responsabilidades:**
  - Inicialización del plugin
  - Carga de dependencias
  - Manejo de hooks principales
  - Gestión de traducciones

### 2. Custom Post Type (`LS_CPT`)
- **CPT:** `ls_link`
- **Capabilities:** Sistema personalizado de permisos
- **Features:**
  - Columnas personalizadas en admin
  - Búsqueda extendida
  - Integración con otros post types

### 3. Sistema de Reescritura (`LS_Rewrite`)
- **Funcionalidad:** Intercepta URLs cortas y redirige
- **Pattern:** `/prefijo/slug` → URL original
- **Features:**
  - Soporte para múltiples prefijos
  - Historial de prefijos (compatibilidad backward)
  - Validación de slugs

### 4. AJAX Handler (`LS_AJAX`)
- **Endpoints disponibles:**
  - `ls_generate_short_link` - Generación de enlaces
  - `ls_rotate_slug` - Rotación de slugs
  - `ls_regenerate_link` - Regeneración de enlaces
  - `ls_delete_link` - Eliminación de enlaces
  - `ls_add_alias` - Añadir aliases
  - `ls_remove_alias` - Remover aliases
  - `ls_check_slug` - Verificación de disponibilidad
  - `ls_validate_url` - Validación de URLs

### 5. Metaboxes (`LS_Metabox`)
- **Interface:** Formularios para crear/editar enlaces
- **Validación:** Real-time JavaScript + server-side PHP
- **Features:**
  - Preview en tiempo real
  - Validación de URLs
  - Generación automática de slugs

## 🗄️ Base de Datos

### Post Meta para `ls_link`
```php
_ls_original_url    // URL de destino
_ls_slug           // Slug único del enlace corto
_ls_tag            // Tag descriptivo (opcional)
_ls_prefix_used    // Prefijo usado al crear el enlace
_ls_aliases        // Array de aliases (JSON)
_ls_clicks         // Contador de clics (futuro)
```

### Opciones de WordPress
```php
ls_current_prefix     // Prefijo actual (ej: '/l/')
ls_prefix_history     // Array de prefijos históricos
ls_reserved_slugs     // Array de slugs reservados
ls_plugin_version     // Versión del plugin
ls_activation_date    // Fecha de activación
ls_rewrite_version    // Control de flush de reglas
```

## 🌐 Sistema de URLs

### Estructura de URLs Cortas
```
https://dominio.com/prefijo/slug
```

### Ejemplos
```
https://ejemplo.com/l/abc123    -> https://google.com
https://ejemplo.com/go/xyz789   -> https://facebook.com
```

### Proceso de Redirección
1. WordPress intercepta la URL via `parse_request`
2. `LS_Rewrite` extrae prefijo y slug
3. Búsqueda en base de datos por slug
4. Redirección 301/302 a URL original
5. Registro de estadísticas (opcional)

## 🎨 Frontend JavaScript

### Archivo: `assets/admin.js`
- **Framework:** jQuery
- **Features:**
  - Generación AJAX de enlaces
  - Validación en tiempo real
  - Sistema de modales
  - Copy-to-clipboard
  - UI responsiva

### Principales Funciones
```javascript
// Generación de enlaces cortos
generateShortLink()

// Validación de URLs
validateUrl()

// Manejo de aliases
handleAddAlias()
handleRemoveAlias()

// Rotación de slugs
handleRotateSlug()
```

## 🔒 Seguridad

### Medidas Implementadas
- **Nonce Verification:** Todas las peticiones AJAX
- **Capability Checks:** Verificación de permisos
- **Data Sanitization:** Sanitización de inputs
- **URL Validation:** Validación estricta de URLs
- **SQL Injection Prevention:** Uso de WordPress DB API

### Validaciones
```php
// Nonce
check_ajax_referer('ls_admin_nonce', 'nonce', false)

// Capabilities
current_user_can('edit_posts')

// Sanitización
sanitize_url($url)
sanitize_text_field($slug)
esc_url_raw($input)
```

## 🌍 Internacionalización (i18n)

### Configuración
- **Text Domain:** `fulltimeforce-link-shortener`
- **Domain Path:** `/languages`
- **Idiomas soportados:** Inglés (base), Español

### Archivos de Traducción
- `es_ES.po` - Traducciones en español
- `es_ES.mo` - Archivo compilado
- `fulltimeforce-link-shortener.pot` - Template

### Uso en Código
```php
__('Text to translate', 'fulltimeforce-link-shortener')
_e('Direct echo text', 'fulltimeforce-link-shortener')
```

## 🔧 Hooks y Filtros

### Action Hooks
```php
// Inicialización
add_action('plugins_loaded', 'load_classes')
add_action('init', 'register_cpt')

// Admin
add_action('admin_enqueue_scripts', 'admin_scripts')
add_action('add_meta_boxes', 'add_metabox')

// AJAX
add_action('wp_ajax_ls_generate_short_link', 'generate_short_link')
```

### Filter Hooks
```php
// Columnas admin
add_filter('manage_ls_link_posts_columns', 'ls_link_columns')

// Búsqueda extendida
add_filter('posts_search', 'extend_search')

// Enlaces de plugin
add_filter('plugin_action_links_', 'plugin_action_links')
```

## 📊 Métricas y Performance

### Optimizaciones
- **Conditional Loading:** Scripts solo en páginas relevantes
- **Debounced Validation:** Evita requests excesivos
- **Efficient Queries:** Uso optimizado de WP_Query
- **Cached Redirects:** Sistema de cache para redirecciones

### Database Queries
```php
// Búsqueda de slug existente
$existing = new WP_Query([
    'post_type' => 'ls_link',
    'meta_query' => [[
        'key' => '_ls_slug',
        'value' => $slug,
        'compare' => '='
    ]]
]);
```

## 🧪 Testing y Desarrollo

### Herramientas de Desarrollo
- **PHP Stubs:** WordPress, ACF Pro, WooCommerce
- **Composer:** Gestión de dependencias de desarrollo
- **Debug Mode:** Logs detallados en modo desarrollo

### Environment Setup
```bash
composer install
# Instala stubs para development
```

## 🚀 Deployment

### Archivos a Incluir
- ✅ Código fuente PHP
- ✅ Assets (JS/CSS)
- ✅ Traducciones (.po/.mo)
- ✅ composer.json

### Archivos a Excluir
- ❌ vendor/ (dependencias dev)
- ❌ test-*.php (archivos de test)
- ❌ debug-*.php (archivos debug)
- ❌ *.log (logs)

## 📈 Roadmap Técnico

### Versión 1.1 (Planificada)
- [ ] Sistema de estadísticas completo
- [ ] API REST endpoints
- [ ] Bulk operations
- [ ] QR code generation

### Versión 1.2 (Planificada)
- [ ] Multi-site support
- [ ] Advanced analytics
- [ ] Custom domains
- [ ] A/B testing

## 🐛 Debugging

### Debug Mode
```php
// Activar en wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Logs del plugin
error_log('LS Debug: ' . $message);
```

### Common Issues
1. **Rewrite Rules:** `flush_rewrite_rules()` después de cambios
2. **AJAX Nonce:** Verificar que el nonce se pasa correctamente
3. **Permisos:** Asegurar capabilities correctas
4. **URL Validation:** Verificar formato de URLs

## 📞 Soporte Técnico

- **GitHub:** https://github.com/joanC0492/fulltimeforce-link-shortener
- **Author:** Joan Caballero
- **Email:** Disponible via GitHub

---

*Este plugin ha sido desarrollado siguiendo los estándares de WordPress Coding Standards y las mejores prácticas de seguridad.*