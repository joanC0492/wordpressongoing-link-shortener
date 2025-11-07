=== Link Shortener by WP ongoing ===
Contributors: joancochachi
Donate link: https://wordpressongoing.com
Tags: link shortener, short links, url shortener, redirect, marketing, analytics, wordpress shortener
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin profesional para acortar enlaces con gestión avanzada de prefijos y administración completa desde WordPress.

== Description ==

**Link Shortener by WP ongoing** es un plugin completo y profesional que te permite crear, gestionar enlaces cortos directamente desde tu panel de WordPress.

= Características principales =

* **Gestión completa de enlaces cortos** - Crea y administra todos tus enlaces desde un panel centralizado
* **Prefijos personalizables** - Define tu propio prefijo (ej: `/l/`, `/go/`, `/link/`) para tus enlaces cortos
* **Integración nativa** - Genera enlaces cortos directamente desde cualquier post o página
* **Historial de prefijos** - Los enlaces antiguos siguen funcionando aunque cambies el prefijo
* **Interfaz intuitiva** - Diseño limpio y profesional integrado con WordPress
* **Búsqueda avanzada** - Encuentra enlaces por URL, slug o etiqueta
* **Validación de URLs** - Verifica automáticamente que las URLs sean válidas
* **Slugs únicos** - Previene duplicados automáticamente
* **Multiidioma** - Incluye traducciones en español

= Casos de uso =

* **Marketing digital** - Crear enlaces para campañas
* **Redes sociales** - Enlaces cortos y profesionales
* **Email marketing** - URLs limpias y fáciles de recordar
* **Gestión de contenido** - Enlaces internos más manejables

= Cómo funciona =

1. **Instala y activa** el plugin
2. **Configura tu prefijo** en Ajustes > Link Shortener
3. **Crea enlaces cortos** desde el menú Link Shortener o directamente desde cualquier post/página
4. **Comparte tus enlaces** - Se redirigen automáticamente con código HTTP 302
5. **Gestiona y actualiza** tus enlaces cuando lo necesites

= Características técnicas =

* Pages y Posts para gestión de enlaces
* Rewrite rules dinámicas para todos los prefijos
* Sistema de metaboxes personalizado
* AJAX para operaciones rápidas
* Validación y sanitización completa
* Compatible con SEO (excluido de sitemaps)
* Capabilities personalizadas para control de acceso

El plugin está diseñado pensando en la usabilidad y el rendimiento, ofreciendo una experiencia profesional tanto para usuarios básicos como avanzados.

== Installation ==

= Instalación automática =

1. Ve a tu panel de WordPress > Plugins > Añadir nuevo
2. Busca "Link Shortener by WP ongoing"
3. Haz clic en "Instalar ahora"
4. Activa el plugin

= Instalación manual =

1. Descarga el archivo .zip del plugin
2. Ve a Plugins > Añadir nuevo > Subir plugin
3. Selecciona el archivo .zip y súbelo
4. Activa el plugin

= Configuración inicial =

1. Ve a **Link Shortener > Ajustes**
2. Configura tu prefijo preferido (por defecto `/l/`)
3. Guarda los cambios
4. ¡Ya puedes empezar a crear enlaces cortos!

== Frequently Asked Questions ==

= ¿Puedo cambiar el prefijo después de crear enlaces? =

Sí, puedes cambiar el prefijo en cualquier momento. Los enlaces existentes seguirán funcionando con su prefijo original, mientras que los nuevos enlaces usarán el nuevo prefijo.

= ¿Los enlaces cortos afectan al SEO de mi sitio? =

No, el plugin está diseñado para excluir los enlaces cortos de los sitemaps y ocultar los metaboxes de SEO para este tipo de contenido.

= ¿Puedo crear enlaces cortos desde cualquier post o página? =

Sí, el plugin añade una columna "Short Link" en todos los listados de posts y páginas públicas donde puedes generar enlaces directamente.

= ¿Hay límite en la cantidad de enlaces cortos? =

No hay límite técnico impuesto por el plugin. El límite dependerá de tu hosting y configuración de WordPress.

= ¿Es compatible con WordPress multisite? =

El plugin funciona en instalaciones multisite, pero cada sitio gestiona sus propios enlaces independientemente.

= ¿Qué código de redirección usa? =

Utiliza redirecciones HTTP 302 (temporales), que son las recomendadas para acortadores de enlaces y marketing.

== Screenshots ==

1. **Panel principal** - Listado completo de todos tus enlaces cortos con opciones de gestión
2. **Crear nuevo enlace** - Interfaz simple para crear enlaces cortos con validación en tiempo real
3. **Ajustes del plugin** - Configuración de prefijos y opciones avanzadas
4. **Integración en listados** - Columna "Short Link" en posts y páginas para generación rápida
5. **Modal de rotación** - Opciones para actualizar slugs manteniendo compatibilidad

== Changelog ==

= 1.0.0 =
* Lanzamiento inicial del plugin
* Sistema completo de gestión de enlaces cortos
* Prefijos personalizables con historial
* Sistema de aliases y rotación de slugs
* Integración nativa con posts y páginas
* Interfaz de administración completa
* Validación y sanitización de URLs
* Soporte multiidioma (español incluido)
* Custom Post Type para enlaces
* Rewrite rules dinámicas
* Sistema AJAX para operaciones rápidas
* Exclusión automática de SEO y sitemaps

== Upgrade Notice ==

= 1.0.0 =
Primera versión del plugin. Instala para empezar a crear y gestionar enlaces cortos profesionales directamente desde WordPress.

== Características técnicas ==

= Requisitos del sistema =
* WordPress 5.0 o superior
* PHP 7.4 o superior
* Memoria PHP recomendada: 128MB+

= Estructura del plugin =
* Custom Post Type: `ls_link`
* Prefijo de metadatos: `_ls_*`
* Opciones de configuración: `ls_*`
* Capabilities personalizadas para control de acceso

= Hooks disponibles =
* `ls_classes_loaded` - Después de cargar las clases del plugin
* Filtros para personalizar comportamiento y UI

= Compatibilidad =
* Compatible con la mayoría de themes de WordPress
* Testado con plugins SEO populares (Yoast, Rank Math)
* Compatible con plugins de caché
* Funciona con WordPress multisite

Para más información técnica y documentación de desarrollador, visita: [https://wordpressongoing.com](https://wordpressongoing.com)