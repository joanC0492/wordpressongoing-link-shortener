<?php
/**
 * Metabox para crear y editar enlaces cortos
 * 
 * Maneja la interfaz y validaciones para crear enlaces
 */

if (!defined('ABSPATH')) {
  exit;
}

class LS_Metabox
{

  /**
   * Constructor
   */
  public function __construct()
  {
    add_action('add_meta_boxes', array($this, 'add_metabox'));
    add_action('save_post_ls_link', array($this, 'save_metabox'));
    add_action('edit_form_after_title', array($this, 'hide_title_field'));
    add_action('admin_head', array($this, 'hide_title_css'));
  }

  /**
   * Añade el metabox al CPT ls_link
   */
  public function add_metabox()
  {
    add_meta_box(
      'ls_link_data',
      __('Link Data', 'fulltimeforce-link-shortener'),
      array($this, 'metabox_callback'),
      'ls_link',
      'normal',
      'high'
    );
  }

  /**
   * Contenido del metabox
   */
  public function metabox_callback($post)
  {
    // Nonce para seguridad
    wp_nonce_field('ls_save_link_data', 'ls_link_nonce');

    // Obtener valores actuales
    $original_url = get_post_meta($post->ID, '_ls_original_url', true);
    $slug = get_post_meta($post->ID, '_ls_slug', true);
    $tag = get_post_meta($post->ID, '_ls_tag', true);
    $prefix_used = get_post_meta($post->ID, '_ls_prefix_used', true);

    // Si es nuevo post, obtener prefijo actual
    if (empty($prefix_used)) {
      $prefix_used = get_option('ls_current_prefix', '/l/');
    }

    ?>
    <table class="form-table">
      <tr>
        <th scope="row">
          <label for="ls_original_url"><?php echo esc_html__( 'Original URL', 'fulltimeforce-link-shortener' ); ?> <span class="required">*</span></label>
        </th>
        <td>
          <input type="url" id="ls_original_url" name="ls_original_url" value="<?php echo esc_attr($original_url); ?>"
            class="regular-text" placeholder="https://example.com/long-page" pattern="https?://.*"
            title="<?php echo esc_attr__( 'Must start with http:// or https://', 'fulltimeforce-link-shortener' ); ?>" required />
          <p class="description"><?php echo esc_html__( 'Full URL you want to shorten. Must include http:// or https://', 'fulltimeforce-link-shortener' ); ?></p>
          <div id="ls_url_validation" class="ls-validation-message"></div>
        </td>
      </tr>
      <tr>
        <th scope="row">
          <label for="ls_slug"><?php echo esc_html__( 'Custom slug', 'fulltimeforce-link-shortener' ); ?></label>
        </th>
        <td>
          <div class="ls-slug-preview">
            <span class="ls-domain"><?php echo esc_html(home_url($prefix_used)); ?></span>
            <input type="text" id="ls_slug" name="ls_slug" value="<?php echo esc_attr($slug); ?>" class="regular-text"
              placeholder="my-link" pattern="[a-zA-Z0-9\-_]+" />
          </div>
          <p class="description"><?php echo esc_html__( 'Optional. If left empty, one will be generated automatically. Only letters, numbers, dashes and underscores are allowed.', 'fulltimeforce-link-shortener' ); ?></p>
          <div id="ls_slug_validation" class="ls-validation-message"></div>
          <?php if ($slug): ?>
            <p class="ls-slug-info">
              <strong><?php echo esc_html__( 'Full link:', 'fulltimeforce-link-shortener' ); ?></strong>
              <code id="ls_full_url"><?php echo esc_html(home_url($prefix_used . $slug)); ?></code>
              <button type="button" class="button-secondary ls-copy-link"
                data-url="<?php echo esc_attr(home_url($prefix_used . $slug)); ?>"><?php echo esc_html__( 'Copy', 'fulltimeforce-link-shortener' ); ?></button>
            </p>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <th scope="row">
          <label for="ls_tag"><?php echo esc_html__( 'Tag', 'fulltimeforce-link-shortener' ); ?></label>
        </th>
        <td>
          <input type="text" id="ls_tag" name="ls_tag" value="<?php echo esc_attr($tag); ?>" class="regular-text"
            placeholder="" maxlength="50" />
          <p class="description"><?php echo esc_html__( 'Descriptive label to organize your links (maximum 50 characters)', 'fulltimeforce-link-shortener' ); ?></p>
        </td>
      </tr>
    </table>

    <?php if ($post->post_status === 'auto-draft'): ?>
      <p class="ls-save-notice">
        <strong><?php echo esc_html__( 'Note:', 'fulltimeforce-link-shortener' ); ?></strong> <?php echo esc_html__( 'The short link will be created when saving. If you do not specify a slug, one will be generated automatically.', 'fulltimeforce-link-shortener' ); ?>
      </p>
    <?php endif; ?>

    <style>
      .ls-slug-preview {
        display: flex;
        align-items: center;
        gap: 0;
      }

      .ls-domain {
        background: #f1f1f1;
        padding: 6px 8px;
        border: 1px solid #ddd;
        border-right: none;
        border-radius: 3px 0 0 3px;
        font-family: monospace;
        font-size: 13px;
      }

      #ls_slug {
        border-radius: 0 3px 3px 0 !important;
        font-family: monospace;
        font-size: 13px;
      }

      .ls-validation-message {
        margin-top: 5px;
        padding: 5px 10px;
        border-radius: 3px;
        display: none;
      }

      .ls-validation-message.error {
        background: #ffeaea;
        border: 1px solid #ff6b6b;
        color: #d63031;
        display: block;
      }

      .ls-validation-message.success {
        background: #eafaf1;
        border: 1px solid #27ae60;
        color: #00b894;
        display: block;
      }

      .ls-validation-message.warning {
        background: #fff3cd;
        border: 1px solid #ffc107;
        color: #856404;
        display: block;
      }

      .ls-slug-info {
        margin-top: 10px;
        padding: 10px;
        background: #f8f9fa;
        border-left: 4px solid #007cba;
      }

      .ls-save-notice {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        color: #856404;
        padding: 10px;
        border-radius: 4px;
        margin-top: 20px;
      }

      .required {
        color: #d63031;
      }
    </style>
    <?php
  }

  /**
   * Muestra los aliases de un enlace
   */
  private function display_aliases($post_id)
  {
    $aliases = get_post_meta($post_id, '_ls_aliases', true);
    $prefix_used = get_post_meta($post_id, '_ls_prefix_used', true);

    if (empty($aliases) || !is_array($aliases)) {
      echo '<p class="description">No hay aliases configurados para este enlace.</p>';
      return;
    }

    echo '<div class="ls-aliases-list">';
    foreach ($aliases as $alias) {
      $alias_url = home_url($prefix_used . $alias);
      echo '<div class="ls-alias-item">';
      echo '<code>' . esc_html($alias_url) . '</code>';
      echo '<button type="button" class="button-secondary ls-copy-link" data-url="' . esc_attr($alias_url) . '">Copiar</button>';
      echo '<button type="button" class="button-link-delete ls-remove-alias" data-alias="' . esc_attr($alias) . '" data-post-id="' . esc_attr($post_id) . '">Eliminar</button>';
      echo '</div>';
    }
    echo '</div>';
  }

  /**
   * Guarda los datos del metabox
   */
  public function save_metabox($post_id)
  {
    // Verificaciones de seguridad
    if (!isset($_POST['ls_link_nonce']) || !wp_verify_nonce($_POST['ls_link_nonce'], 'ls_save_link_data')) {
      return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }

    if (!current_user_can('edit_post', $post_id)) {
      return;
    }

    // Validar y sanitizar URL original
    $original_url = isset($_POST['ls_original_url']) ? esc_url_raw($_POST['ls_original_url']) : '';

    if (empty($original_url)) {
      add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>Error: La URL original es obligatoria.</p></div>';
      });
      return;
    }

    // Validar que sea una URL válida con protocolo
    if (!filter_var($original_url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//', $original_url)) {
      add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>Error: La URL debe ser válida y comenzar con http:// o https://</p></div>';
      });
      return;
    }

    // Verificar que el dominio existe (validación adicional)
    $parsed_url = parse_url($original_url);
    if (!isset($parsed_url['host']) || empty($parsed_url['host'])) {
      add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>Error: La URL debe incluir un dominio válido.</p></div>';
      });
      return;
    }

    // Prevenir enlaces circulares
    $site_url = home_url();
    if (strpos($original_url, $site_url) === 0) {
      $current_prefix = get_option('ls_current_prefix', '/l/');
      $prefix_history = get_option('ls_prefix_history', array('/l/'));

      foreach ($prefix_history as $prefix) {
        if (strpos($original_url, $site_url . ltrim($prefix, '/')) === 0) {
          add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>Error: No se puede crear un enlace corto que apunte a otro enlace corto del mismo sitio.</p></div>';
          });
          return;
        }
      }
    }

    // Procesar slug
    $slug = isset($_POST['ls_slug']) ? sanitize_title($_POST['ls_slug']) : '';

    // Si no hay slug, generar uno
    if (empty($slug)) {
      $slug = $this->generate_unique_slug($original_url);
    } else {
      // Validar que no esté reservado y sea único
      if ($this->is_reserved_slug($slug)) {
        add_action('admin_notices', function () use ($slug) {
          echo '<div class="notice notice-error"><p>Error: El slug "' . esc_html($slug) . '" está reservado. Por favor, elige otro.</p></div>';
        });
        return;
      }

      if (!$this->is_slug_unique($slug, $post_id)) {
        $suggested_slug = $this->generate_unique_slug($original_url, $slug);
        add_action('admin_notices', function () use ($slug, $suggested_slug) {
          echo '<div class="notice notice-error"><p>Error: El slug "' . esc_html($slug) . '" ya está en uso. Sugerencia: "' . esc_html($suggested_slug) . '"</p></div>';
        });
        return;
      }
    }

    // Sanitizar tag
    $tag = isset($_POST['ls_tag']) ? sanitize_text_field($_POST['ls_tag']) : '';
    if (strlen($tag) > 50) {
      $tag = substr($tag, 0, 50);
    }

    // Obtener prefijo actual
    $current_prefix = get_option('ls_current_prefix', '/l/');

    // Guardar metadatos
    update_post_meta($post_id, '_ls_original_url', $original_url);
    update_post_meta($post_id, '_ls_slug', $slug);
    update_post_meta($post_id, '_ls_tag', $tag);
    update_post_meta($post_id, '_ls_prefix_used', $current_prefix);

    // Generar título automático
    $parsed_url = parse_url($original_url);
    $host = isset($parsed_url['host']) ? $parsed_url['host'] : 'unknown';
    $auto_title = $slug . ' | ' . $host;

    // Actualizar título sin triggear save_post de nuevo
    remove_action('save_post_ls_link', array($this, 'save_metabox'));
    wp_update_post(array(
      'ID' => $post_id,
      'post_title' => $auto_title,
      'post_name' => $slug,
      'post_status' => 'publish',
    ));
    add_action('save_post_ls_link', array($this, 'save_metabox'));

    // Mensaje de éxito
    $short_url = home_url($current_prefix . $slug);
    add_action('admin_notices', function () use ($short_url) {
      echo '<div class="notice notice-success"><p>Enlace corto creado: <strong>' . esc_html($short_url) . '</strong> <button type="button" class="button-secondary ls-copy-link" data-url="' . esc_attr($short_url) . '">Copiar</button></p></div>';
    });
  }

  /**
   * Genera un slug único
   */
  private function generate_unique_slug($url, $base_slug = '')
  {
    if (!empty($base_slug)) {
      // Si se proporciona un slug base, usarlo
      $slug = $base_slug;
    } else {
      // Generar un slug corto aleatorio (6-7 caracteres)
      $slug = $this->generate_random_slug();
    }

    $original_slug = $slug;
    $counter = 1;

    // Si el slug no es único o está reservado, generar uno nuevo
    while (!$this->is_slug_unique($slug) || $this->is_reserved_slug($slug)) {
      if (!empty($base_slug)) {
        // Para slugs personalizados, añadir contador
        $slug = $original_slug . '-' . $counter;
        $counter++;
      } else {
        // Para slugs automáticos, generar uno completamente nuevo
        $slug = $this->generate_random_slug();

        // Evitar bucle infinito
        if ($counter > 10) {
          $slug = $original_slug . '-' . time();
          break;
        }
        $counter++;
      }
    }

    return $slug;
  }

  /**
   * Genera un slug aleatorio corto (6-7 caracteres)
   */
  private function generate_random_slug($length = 6)
  {
    // Caracteres permitidos: letras minúsculas y números (sin caracteres confusos como 0, o, l, 1)
    $characters = 'abcdefghijkmnpqrstuvwxyz23456789';
    $characters_length = strlen($characters);
    $random_slug = '';

    for ($i = 0; $i < $length; $i++) {
      $random_slug .= $characters[random_int(0, $characters_length - 1)];
    }

    return $random_slug;
  }

  /**
   * Verifica si un slug es único
   */
  private function is_slug_unique($slug, $exclude_post_id = 0)
  {
    global $wpdb;

    $query = $wpdb->prepare(
      "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_ls_slug' 
             AND meta_value = %s 
             AND post_id != %d",
      $slug,
      $exclude_post_id
    );

    $result = $wpdb->get_var($query);

    if ($result) {
      return false;
    }

    // Verificar también en aliases
    $alias_query = $wpdb->prepare(
      "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_ls_aliases' 
             AND meta_value LIKE %s 
             AND post_id != %d",
      '%"' . $slug . '"%',
      $exclude_post_id
    );

    $alias_result = $wpdb->get_var($alias_query);

    return !$alias_result;
  }

  /**
   * Verifica si un slug está reservado
   */
  private function is_reserved_slug($slug)
  {
    $reserved = array(
      'wp',
      'admin',
      'wp-admin',
      'wp-content',
      'wp-includes',
      'xmlrpc',
      'feed',
      'rss',
      'rss2',
      'rdf',
      'atom',
      'api',
      'rest',
      'sitemap',
      'robots',
      'favicon',
      'login',
      'register',
      'dashboard',
      'profile'
    );

    $custom_reserved = get_option('ls_reserved_slugs', array());
    $all_reserved = array_merge($reserved, $custom_reserved);

    return in_array(strtolower($slug), array_map('strtolower', $all_reserved));
  }

  /**
   * Oculta el campo título en el formulario
   */
  public function hide_title_field($post)
  {
    if ($post->post_type === 'ls_link') {
      echo '<div id="titlediv" style="display: none;">';
      echo '<input type="text" name="post_title" id="title" value="' . esc_attr($post->post_title) . '">';
      echo '</div>';
    }
  }

  /**
   * CSS para ocultar elementos del título
   */
  public function hide_title_css()
  {
    global $post_type;
    if ($post_type === 'ls_link') {
      echo '<style>
                #titlediv, #title, .wrap h1 .page-title-action { display: none !important; }
                #ls_link_data { margin-top: 20px; }
            </style>';
    }
  }
}

// Inicializar la clase
new LS_Metabox();
