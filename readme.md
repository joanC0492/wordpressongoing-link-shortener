Plugin: Wordpressongoing Link Shortener

# ✅ Flujo funcional (UX)

## Menú principal

**Link Shortener**

* 🔗 **Todos los enlaces** (CPT `ls_link`)
* ➕ **Crear nuevo** (pantalla *Add New* del CPT con metabox propio)
* ⚙️ **Ajustes** (gestión de prefijo)

---

## 🔗 Todos los enlaces (CPT `ls_link`)

**Columnas:**

* **Original URL** (click abre en nueva pestaña)
* **Short Link** (muestra `https://dominio.com/<prefijo-usado>/<slug>` con botón **Copiar**)
    * **Tag**
    * **Acciones**: **Copiar**, **Eliminar**

    * **Eliminar** → **modal de confirmación** accesible (teclado/ARIA).

    > **No hay** columna de **Fecha** (y por tanto, no hay filtrado por fecha).

    **Búsqueda:**

    * Cuadro de búsqueda nativo de WP (por URL original, slug y tag).

    **Acciones en lote:**

    * Eliminar (confirmación por lote).

    ---

    ## ➕ Crear nuevo (Add New `ls_link`)

    **Metabox “Link Data”:**

    * **URL Original** (required; validar formato `http/https`)
    * **Slug** (opcional; si está vacío se **autogenera** único)
    * **Tag** (opcional, texto corto visible en lista)

    **Botón:** **Crear enlace corto** / **Actualizar**

    **Comportamiento clave:**

    * El **slug no puede repetirse** (tanto si es autogenerado como si lo introduces manualmente).
    Si colisiona, se notifica y se sugiere uno alternativo disponible.
    * El **título del post** no lo escribe el usuario (campo oculto). Se autodefine (p. ej. `slug | host(URL)`).
    * El CPT **no tiene front** ni afecta SEO (excluido de sitemaps, Rank Math/Yoast ocultos).

    ---

    ## ⚙️ Ajustes

    **Prefijo del enlace corto**

    * Campo texto con valor por defecto **`/l/`** (debe empezar y terminar con `/`).

    **Al cambiar el prefijo:**

    * **Los enlaces ya creados continúan funcionando con su prefijo anterior.**
    * **Solo los nuevos** enlaces creados **después del cambio** usarán el **nuevo prefijo**.
    * Mensaje de confirmación tras guardar.
    * Internamente, se conserva un **historial de prefijos** para mantener reglas de reescritura de **todos** los
    prefijos usados (ver sección técnica).

    ---

    ## 📎 Columna “Short Link” en los listados de **Posts**, **Pages** y **CPTs** públicos (excepto `ls_link`)

    **Columna final:** **Short Link**

    * **Si existe** un enlace corto para la URL de esa entrada:

    * Mostrar **short link** completo + botones **Copiar** | **Rotar slug** *(mejor que “Regenerar”)*

    * **Rotar slug** abre **modal** con 2 opciones:

    1. **Reemplazar slug** (desactiva el slug anterior; el nuevo queda activo).
    2. **Añadir alias** (mantiene el slug anterior activo y crea un **nuevo slug adicional** apuntando a la misma URL).
    *Útil si el viejo slug ya está en campañas activas.*
    * **Si no existe**:

    * Botón **Generar short link** (AJAX)

    * Tras crear: sustituir por **short link** + **Copiar** | **Rotar slug**

    > Los textos sugeridos para mayor claridad:
    >
    > * “**Generar short link**”
    > * “**Rotar slug**” (alternativas: “Nuevo slug”, “Actualizar slug”)

    ---

    # 🧱 Decisiones de datos y comportamiento

    * **CPT**: `ls_link` (sin front; solo UI admin).

    * **Metadatos por entrada**:

    * `_ls_original_url` (string)
    * `_ls_slug` (string, **único** globalmente)
    * `_ls_tag` (string corto)
    * `_ls_prefix_used` (**prefijo exacto** con el que se creó/actualizó este link; así cada link “recuerda” su
    prefijo).
    * `_ls_aliases` (array opcional de slugs adicionales activos para la misma URL; cuando se elige “Añadir alias”).

    * **Opciones (wp_options)**:

    * `ls_current_prefix` (string; por defecto `/l/`)
    * `ls_prefix_history` (array de **todos** los prefijos usados **incluido** el actual; se mantiene para
    reescrituras).
    * (Opcional) `ls_reserved_slugs` (lista de protección ante colisiones con rutas del sitio).

    * **Unicidad del slug**:

    * **Global** en el plugin (no depende del prefijo).
    Esto evita conflictos cuando existen varios prefijos históricos.

    ---

    # 🌐 Resolución de short links (front)

    * Se registran **rewrite rules** para **cada** prefijo presente en `ls_prefix_history`.
    Patrón: `^<prefijo-sin-barras>/([^/]+)/?$` → query vars (`ls_slug` y `ls_prefix`).
      * Petición entrante:

      1. Buscar coincidencia por **slug** en `_ls_slug` o en `_ls_aliases`.
      2. Verificar que el **prefijo** de la URL entrante esté en `ls_prefix_history`.

      > Esto permite que el mismo slug responda en su **prefijo original** (y, si elegiste “añadir alias”, también
      seguirá activo el anterior).
      3. `wp_redirect( original_url, 302 ); exit;`

      ---

      # 🔒 Reglas/validaciones clave

      * **URL Original**: `esc_url_raw`, protocolo `http/https`, evitar chaining hacia otro short link del mismo
      dominio/prefijo.
      * **Slug**:

      * `sanitize_title`, bloquear **reservados** (wp, admin, xmlrpc, etc. + endpoints existentes).
      * **Chequear unicidad** antes de guardar.
      * **Prefijo**:

      * Debe empezar/terminar con `/`.
      * Al cambiarlo, **agregar al historial** (si no existe) y mantener reglas previas activas.
      * **No** se “migra” el prefijo de los links existentes (justo lo que pediste).
      * **Permisos**:

      * Ajustes → `manage_options`.
      * CRUD `ls_link` → caps propias (map_meta_cap).
      * **Accesibilidad**:

      * Modales con ARIA y cierre con `Esc`.

      ---

      # 🧭 Flujo detallado (paso a paso)

      1. **Activación del plugin**

      * Registra CPT y metabox.
      * Crea opción `ls_current_prefix = /l/` y `ls_prefix_history = ['/l/']` (si no existen).
      * Registra rewrite rules para **cada** prefijo del historial.
      * `flush_rewrite_rules()` en activación y al guardar ajustes.

      2. **Crear desde “Crear nuevo”**

      * Usuario rellena **URL** (obligatorio), **Slug** (opcional), **Tag** (opcional).
      * Validaciones → **slug único**.
      * Guarda:

      * `_ls_prefix_used = ls_current_prefix` (el prefijo **vigente** en ese momento).
      * `post_title` autogenerado (campo título oculto en UI).
      * Notificación y botón **Copiar** en admin.

      3. **Listar “Todos los enlaces”**

      * Columnas: Original URL | Short Link | Tag | Acciones
      * Sin columna de fecha.
      * Acciones: **Copiar**, **Eliminar** (con modal).

      4. **Generar desde listados de contenido**

      * En Posts/Pages/CPTs → columna **Short Link**:

      * Si **no existe**: **Generar short link** (AJAX); crea `ls_link` con `_ls_prefix_used = ls_current_prefix` →
      reemplaza por short link + **Copiar** | **Rotar slug**.
      * Si **existe**: mostrar short link + **Copiar** | **Rotar slug**

      * **Rotar slug** → modal:

      * **Reemplazar slug** (el anterior queda **inactivo**; se guarda `_ls_slug` nuevo).
      * **Añadir alias** (se **agrega** a `_ls_aliases` y el anterior **sigue activo**).

      5. **Cambiar prefijo en Ajustes**

      * Guardar nuevo prefijo → se **añade** a `ls_prefix_history` y pasa a ser `ls_current_prefix`.
      * **No** se reescriben los existentes.
      * **Flush** de reglas y aviso: “Nuevo prefijo activo. Enlaces previos continúan operando con sus prefijos
      originales.”

      ---

      # 🛠️ Checklist técnico (alto nivel)

      * **Estructura del plugin**

      * Carpeta: `fulltimeforce-link-shortener/`
      * Archivo principal: `fulltimeforce-link-shortener.php` (header que enviaste)
      * Sugerido:

      * `/includes/class-ls-cpt.php` (registro CPT + columnas admin)
      * `/includes/class-ls-metabox.php` (UI y guardado)
      * `/includes/class-ls-settings.php` (página ajustes + prefix history)
      * `/includes/class-ls-rewrite.php` (rules + template_redirect)
      * `/includes/class-ls-ajax.php` (generate/rotate/alias vía AJAX)
      * `/assets/admin.js` y `/assets/admin.css`

      * **Hooks clave**

      * `init` → CPT, rewrite (para cada prefijo del historial), query vars
      * `template_redirect` → resolver slug/alias y redirigir 302
      * `register_activation_hook` / `register_deactivation_hook` → flush rules
      * `admin_menu` / `admin_init` → ajustes
      * `add_meta_box`, `save_post_ls_link` → metabox
      * `manage_ls_link_posts_columns`, `manage_ls_link_posts_custom_column` → columnas CPT
      * `manage_{post_type}_posts_columns`, `manage_{post_type}_posts_custom_column` → columna “Short Link” en
      posts/pages/CPTs
      * `wp_ajax_ls_generate_short_link`, `wp_ajax_ls_rotate_slug` → AJAX
      * Filtros para ocultar metaboxes SEO de Yoast/Rank Math en `ls_link`
      * Exclusión de `ls_link` de sitemaps (`wp_sitemaps_post_types` + filtros propios de SEO plugins)

      ---

      # 📣 Microcopys (UI)

      * Botón en listados de contenido (cuando no existe): **Generar short link**
      * Botón junto a un link existente: **Copiar**
      * Acción avanzada: **Rotar slug**

      * Modal:

      * Título: “Rotar slug”
      * Opción A (primaria): **Reemplazar slug**
      * Opción B (segura): **Añadir alias**
      * Nota: “Reemplazar desactiva el slug anterior. Añadir alias lo mantiene activo.”

      ---
      
      # 🧩 Notas de mantenimiento/calidad

      * **Unicidad de slug** probada a nivel de BD (consulta rápida con índices en `postmeta`).
      * **Historial de prefijos** evita regresiones cuando marketing cambia el prefijo (no se rompen campañas antiguas).
      * **Accesibilidad** en modales y botones (roles ARIA, focus trap, cierre con `Esc`).
      * **Seguridad**: Nonces en AJAX, `current_user_can()` por acción.
      * **Rendimiento**: resolver por slug/alias con `get_posts` optimizado (`no_found_rows`, `fields => ids`,
      `meta_key` directo).
      ---