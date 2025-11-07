<?php
/**
 * Clase para manejar las funciones AJAX del plugin
 * 
 * Gestiona generación, rotación y copiado de enlaces vía AJAX
 */

if (!defined('ABSPATH')) {
  exit;
}

class LS_AJAX
{

  /**
   * Constructor
   */
  public function __construct()
  {
    add_action('wp_ajax_ls_generate_short_link', array($this, 'generate_short_link'));
    add_action('wp_ajax_ls_rotate_slug', array($this, 'rotate_slug'));
    add_action('wp_ajax_ls_regenerate_link', array($this, 'regenerate_link'));
    add_action('wp_ajax_ls_delete_link', array($this, 'delete_link'));
    add_action('wp_ajax_ls_add_alias', array($this, 'add_alias'));
    add_action('wp_ajax_ls_remove_alias', array($this, 'remove_alias'));
    add_action('wp_ajax_ls_check_slug', array($this, 'check_slug_availability'));
    add_action('wp_ajax_ls_validate_url', array($this, 'validate_url'));
  }

  /**
   * Genera un enlace corto desde los listados de contenido
   */
  public function generate_short_link()
  {
    // Verificar nonce
    if (!check_ajax_referer('ls_admin_nonce', 'nonce', false)) {
      wp_send_json_error(__('Invalid nonce', 'link-shortener-wordpressongoing'));
    }

    // Verificar permisos
    if (!current_user_can('edit_posts')) {
      wp_send_json_error(__('Insufficient permissions', 'link-shortener-wordpressongoing'));
    }

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';

    if (!$post_id || !$url) {
      wp_send_json_error(__('Incomplete data', 'link-shortener-wordpressongoing'));
    }

    // Verificar que el post existe y es público
    $post = get_post($post_id);
    if (!$post || $post->post_status !== 'publish') {
      wp_send_json_error(__('Content is not public', 'link-shortener-wordpressongoing'));
    }

    // Verificar que no existe ya un enlace para esta URL
    $existing_link = $this->get_link_by_url($url);
    if ($existing_link) {
      wp_send_json_error(__('A short link already exists for this URL', 'link-shortener-wordpressongoing'));
    }

    // Validar URL
    if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//', $url)) {
      wp_send_json_error(__('Invalid URL', 'link-shortener-wordpressongoing'));
    }

    // Generar tag automático basado en el tipo de post
    $auto_tag = $this->generate_auto_tag($post->post_type);

    // Remover tag del enlace anterior si existe (para mantener solo uno activo por tipo)
    $this->remove_tag_from_previous_links($url, $auto_tag);

    // Crear el enlace corto con tag automático
    $link_id = $this->create_short_link($url, '', $auto_tag);

    if (!$link_id) {
      wp_send_json_error(__('Error creating short link', 'link-shortener-wordpressongoing'));
    }

    // Obtener datos del enlace creado
    $slug = get_post_meta($link_id, '_ls_slug', true);
    $prefix_used = get_post_meta($link_id, '_ls_prefix_used', true);
    $short_url = home_url($prefix_used . $slug);

    wp_send_json_success(array(
      'link_id' => $link_id,
      'short_url' => $short_url,
      'slug' => $slug,
      'message' => __('Short link generated successfully', 'link-shortener-wordpressongoing'),
      'html' => $this->render_short_link_display($link_id, $short_url, $url)
    ));
  }

  /**
   * Rota el slug de un enlace existente
   */
  public function rotate_slug()
  {
    // Verificar nonce
    if (!check_ajax_referer('ls_admin_nonce', 'nonce', false)) {
      wp_send_json_error(__('Invalid nonce', 'link-shortener-wordpressongoing'));
    }

    // Verificar permisos
    if (!current_user_can('edit_posts')) {
      wp_send_json_error(__('Insufficient permissions', 'link-shortener-wordpressongoing'));
    }

    $link_id = isset($_POST['link_id']) ? absint($_POST['link_id']) : 0;
    $action_type = isset($_POST['action_type']) ? sanitize_text_field(wp_unslash($_POST['action_type'])) : 'replace';
    $new_slug = isset($_POST['new_slug']) ? sanitize_title(wp_unslash($_POST['new_slug'])) : '';

    if (!$link_id) {
      wp_send_json_error(__('Link ID required', 'link-shortener-wordpressongoing'));
    }

    // Verificar que el enlace existe
    $link_post = get_post($link_id);
    if (!$link_post || $link_post->post_type !== 'ls_link') {
      wp_send_json_error(__('Link not found', 'link-shortener-wordpressongoing'));
    }

    $original_url = get_post_meta($link_id, '_ls_original_url', true);
    $current_slug = get_post_meta($link_id, '_ls_slug', true);
    $prefix_used = get_post_meta($link_id, '_ls_prefix_used', true);

    // Generar nuevo slug si no se proporcionó
    if (empty($new_slug)) {
      $new_slug = $this->generate_unique_slug($original_url);
    }

    // Verificar que el nuevo slug es único
    if (!$this->is_slug_unique($new_slug, $link_id)) {
      // translators: %s: the slug that is already in use
      wp_send_json_error(sprintf(__('The slug "%s" is already in use. Try another one.', 'link-shortener-wordpressongoing'), $new_slug));
    }

    // Verificar que no esté reservado
    if ($this->is_reserved_slug($new_slug)) {
      // translators: %s: the slug that is reserved
      wp_send_json_error(sprintf(__('The slug "%s" is reserved.', 'link-shortener-wordpressongoing'), $new_slug));
    }

    if ($action_type === 'replace') {
      // Reemplazar slug actual
      update_post_meta($link_id, '_ls_slug', $new_slug);

      $new_url = home_url($prefix_used . $new_slug);
      $message = __('Slug replaced successfully. The previous one is no longer valid.', 'link-shortener-wordpressongoing');

    } elseif ($action_type === 'alias') {
      // Añadir como alias
      $aliases = get_post_meta($link_id, '_ls_aliases', true);
      if (!is_array($aliases)) {
        $aliases = array();
      }

      $aliases[] = $new_slug;
      update_post_meta($link_id, '_ls_aliases', $aliases);

      $new_url = home_url($prefix_used . $new_slug);
      $message = __('Alias added successfully. Both slugs remain valid.', 'link-shortener-wordpressongoing');
    } else {
      wp_send_json_error(__('Invalid action type', 'link-shortener-wordpressongoing'));
    }

    // Actualizar título del post
    $parsed_url = wp_parse_url($original_url);
    $host = isset($parsed_url['host']) ? $parsed_url['host'] : 'unknown';
    $auto_title = $new_slug . ' | ' . $host;

    wp_update_post(array(
      'ID' => $link_id,
      'post_title' => $auto_title,
      'post_name' => $new_slug,
    ));

    wp_send_json_success(array(
      'new_slug' => $new_slug,
      'new_url' => $new_url,
      'action_type' => $action_type,
      'message' => $message,
      'html' => $this->render_short_link_display($link_id, $new_url, $original_url)
    ));
  }

  /**
   * Regenera un enlace corto (crea uno nuevo sin eliminar el anterior)
   */
  public function regenerate_link()
  {
    // Verificar nonce
    if (!check_ajax_referer('ls_admin_nonce', 'nonce', false)) {
      wp_send_json_error(__('Invalid nonce', 'link-shortener-wordpressongoing'));
    }

    // Verificar permisos
    if (!current_user_can('edit_posts')) {
      wp_send_json_error(__('Insufficient permissions', 'link-shortener-wordpressongoing'));
    }

    $original_url = isset($_POST['original_url']) ? esc_url_raw(wp_unslash($_POST['original_url'])) : '';

    if (empty($original_url)) {
      wp_send_json_error(__('Original URL required', 'link-shortener-wordpressongoing'));
    }

    // Validar URL
    if (!filter_var($original_url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//', $original_url)) {
      wp_send_json_error(__('Invalid URL', 'link-shortener-wordpressongoing'));
    }

    // Detectar el tipo de post desde la URL para asignar tag automático
    $post_id = url_to_postid($original_url);
    $auto_tag = '';

    if ($post_id) {
      $post = get_post($post_id);
      if ($post) {
        $auto_tag = $this->generate_auto_tag($post->post_type);

        // Remover tag del enlace anterior
        $this->remove_tag_from_previous_links($original_url, $auto_tag);
      }
    }

    // Crear un nuevo enlace corto para la misma URL con tag automático
    $new_link_id = $this->create_short_link($original_url, '', $auto_tag);

    if (!$new_link_id) {
      wp_send_json_error(__('Error creating new short link', 'link-shortener-wordpressongoing'));
    }

    // Obtener datos del nuevo enlace creado
    $new_slug = get_post_meta($new_link_id, '_ls_slug', true);
    $prefix_used = get_post_meta($new_link_id, '_ls_prefix_used', true);
    $new_short_url = home_url($prefix_used . $new_slug);

    wp_send_json_success(array(
      'new_link_id' => $new_link_id,
      'new_slug' => $new_slug,
      'new_short_url' => $new_short_url,
      'original_url' => $original_url,
      'message' => __('New short link generated successfully. The previous one still works.', 'link-shortener-wordpressongoing'),
      'html' => $this->render_short_link_display($new_link_id, $new_short_url, $original_url)
    ));
  }

  /**
   * Elimina un enlace corto
   */
  public function delete_link()
  {
    // Verificar nonce
    if (!check_ajax_referer('ls_admin_nonce', 'nonce', false)) {
      wp_send_json_error(__('Invalid nonce', 'link-shortener-wordpressongoing'));
    }

    // Verificar permisos
    if (!current_user_can('delete_posts')) {
      wp_send_json_error(__('Insufficient permissions', 'link-shortener-wordpressongoing'));
    }

    $link_id = isset($_POST['link_id']) ? absint($_POST['link_id']) : 0;

    if (!$link_id) {
      wp_send_json_error(__('Link ID required', 'link-shortener-wordpressongoing'));
    }

    // Verificar que el enlace existe
    $link_post = get_post($link_id);
    if (!$link_post || $link_post->post_type !== 'ls_link') {
      wp_send_json_error(__('Link not found', 'link-shortener-wordpressongoing'));
    }

    // Eliminar el post
    $result = wp_delete_post($link_id, true);

    if ($result) {
      wp_send_json_success(array(
        'message' => __('Link deleted successfully', 'link-shortener-wordpressongoing')
      ));
    } else {
      wp_send_json_error(__('Error deleting link', 'link-shortener-wordpressongoing'));
    }
  }

  /**
   * Añade un alias a un enlace existente
   */
  public function add_alias()
  {
    // Verificar nonce y permisos
    if (!check_ajax_referer('ls_admin_nonce', 'nonce', false)) {
      wp_send_json_error(__('Invalid nonce', 'link-shortener-wordpressongoing'));
    }

    if (!current_user_can('edit_posts')) {
      wp_send_json_error(__('Insufficient permissions', 'link-shortener-wordpressongoing'));
    }

    $link_id = isset($_POST['link_id']) ? absint($_POST['link_id']) : 0;
    $alias_slug = isset($_POST['alias_slug']) ? sanitize_title(wp_unslash($_POST['alias_slug'])) : '';

    if (!$link_id || empty($alias_slug)) {
      wp_send_json_error(__('Missing required data', 'link-shortener-wordpressongoing'));
    }

    // Verificar unicidad y reservas
    if (!$this->is_slug_unique($alias_slug, $link_id)) {
      wp_send_json_error(__('Alias is already in use', 'link-shortener-wordpressongoing'));
    }

    if ($this->is_reserved_slug($alias_slug)) {
      wp_send_json_error(__('Alias is reserved', 'link-shortener-wordpressongoing'));
    }

    // Añadir alias
    $aliases = get_post_meta($link_id, '_ls_aliases', true);
    if (!is_array($aliases)) {
      $aliases = array();
    }

    $aliases[] = $alias_slug;
    update_post_meta($link_id, '_ls_aliases', $aliases);

    $prefix_used = get_post_meta($link_id, '_ls_prefix_used', true);
    $alias_url = home_url($prefix_used . $alias_slug);

    wp_send_json_success(array(
      'alias_slug' => $alias_slug,
      'alias_url' => $alias_url,
      'message' => __('Alias added successfully', 'link-shortener-wordpressongoing')
    ));
  }

  /**
   * Remueve un alias de un enlace
   */
  public function remove_alias()
  {
    // Verificar nonce y permisos
    if (!check_ajax_referer('ls_admin_nonce', 'nonce', false)) {
      wp_send_json_error(__('Invalid nonce', 'link-shortener-wordpressongoing'));
    }

    if (!current_user_can('edit_posts')) {
      wp_send_json_error(__('Insufficient permissions', 'link-shortener-wordpressongoing'));
    }

    $link_id = isset($_POST['link_id']) ? absint($_POST['link_id']) : 0;
    $alias_slug = isset($_POST['alias_slug']) ? sanitize_text_field(wp_unslash($_POST['alias_slug'])) : '';

    if (!$link_id || empty($alias_slug)) {
      wp_send_json_error(__('Missing required data', 'link-shortener-wordpressongoing'));
    }

    // Remover alias
    $aliases = get_post_meta($link_id, '_ls_aliases', true);
    if (!is_array($aliases)) {
      wp_send_json_error(__('No aliases to remove', 'link-shortener-wordpressongoing'));
    }

    $key = array_search($alias_slug, $aliases);
    if ($key !== false) {
      unset($aliases[$key]);
      update_post_meta($link_id, '_ls_aliases', array_values($aliases));

      wp_send_json_success(array(
        'message' => __('Alias removed successfully', 'link-shortener-wordpressongoing')
      ));
    } else {
      wp_send_json_error(__('Alias not found', 'link-shortener-wordpressongoing'));
    }
  }

  /**
   * Verifica disponibilidad de slug en tiempo real
   */
  public function check_slug_availability()
  {
    // Verificar nonce
    if (!check_ajax_referer('ls_admin_nonce', 'nonce', false)) {
      wp_send_json_error(__('Invalid nonce', 'link-shortener-wordpressongoing'));
    }

    $slug = isset($_POST['slug']) ? sanitize_title(wp_unslash($_POST['slug'])) : '';
    $exclude_id = isset($_POST['exclude_id']) ? intval($_POST['exclude_id']) : 0;

    if (empty($slug)) {
      wp_send_json_error(__('Slug required', 'link-shortener-wordpressongoing'));
    }

    $is_available = $this->is_slug_unique($slug, $exclude_id);
    $is_reserved = $this->is_reserved_slug($slug);

    if ($is_reserved) {
      wp_send_json_success(array(
        'available' => false,
        'message' => __('This slug is reserved', 'link-shortener-wordpressongoing'),
        'type' => 'reserved'
      ));
    } elseif ($is_available) {
      wp_send_json_success(array(
        'available' => true,
        'message' => __('Slug available', 'link-shortener-wordpressongoing'),
        'type' => 'available'
      ));
    } else {
      $suggested = $this->generate_unique_slug('', $slug);
      wp_send_json_success(array(
        'available' => false,
        'message' => __('Slug not available', 'link-shortener-wordpressongoing'),
        'suggestion' => $suggested,
        'type' => 'taken'
      ));
    }
  }

  /**
   * Valida una URL en tiempo real
   */
  public function validate_url()
  {
    // Verificar nonce
    if (!check_ajax_referer('ls_admin_nonce', 'nonce', false)) {
      wp_send_json_error(__('Invalid nonce', 'link-shortener-wordpressongoing'));
    }

    $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';

    if (empty($url)) {
      wp_send_json_success(array(
        'valid' => false,
        'message' => __( 'Valid URL', 'link-shortener-wordpressongoing' ),
        'type' => 'empty'
      ));
      return;
    }

    // Verificar que tenga protocolo
    if (!preg_match('/^https?:\/\//', $url)) {
      wp_send_json_success(array(
        'valid' => false,
        'message' => __( 'URL must start with http:// or https://', 'link-shortener-wordpressongoing' ),
        'type' => 'no_protocol'
      ));
      return;
    }

    // Sanitizar y validar formato
    $sanitized_url = esc_url_raw($url);
    if (!filter_var($sanitized_url, FILTER_VALIDATE_URL)) {
      wp_send_json_success(array(
        'valid' => false,
        'message' => __('Invalid URL format', 'link-shortener-wordpressongoing'),
        'type' => 'invalid_format'
      ));
      return;
    }

    // Verificar que tenga un dominio válido
    $parsed_url = wp_parse_url($sanitized_url);
    if (!isset($parsed_url['host']) || empty($parsed_url['host'])) {
      wp_send_json_success(array(
        'valid' => false,
        'message' => __('URL must include a valid domain', 'link-shortener-wordpressongoing'),
        'type' => 'no_domain'
      ));
      return;
    }

    // Verificar que el dominio no sea solo texto
    if (!filter_var($parsed_url['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
      wp_send_json_success(array(
        'valid' => false,
        'message' => __('Domain is not valid', 'link-shortener-wordpressongoing'),
        'type' => 'invalid_domain'
      ));
      return;
    }

    // Verificar enlaces circulares
    $site_url = home_url();
    if (strpos($sanitized_url, $site_url) === 0) {
      $prefix_history = get_option('ls_prefix_history', array('/l/'));
      foreach ($prefix_history as $prefix) {
        if (strpos($sanitized_url, $site_url . ltrim($prefix, '/')) === 0) {
          wp_send_json_success(array(
            'valid' => false,
            'message' => __('Cannot create a short link that points to another short link', 'link-shortener-wordpressongoing'),
            'type' => 'circular'
          ));
          return;
        }
      }
    }

    // Verificar si ya existe
    $existing = $this->get_link_by_url($sanitized_url);
    if ($existing && is_object($existing)) {
      $slug = get_post_meta($existing->ID, '_ls_slug', true);
      $prefix = get_post_meta($existing->ID, '_ls_prefix_used', true);
      $existing_short_url = home_url($prefix . $slug);

      wp_send_json_success(array(
        'valid' => false,
        'message' => __('A short link already exists for this URL', 'link-shortener-wordpressongoing'),
        'existing_url' => $existing_short_url,
        'type' => 'exists'
      ));
      return;
    }

    wp_send_json_success(array(
      'valid' => true,
      'message' => __( 'Valid URL', 'link-shortener-wordpressongoing' ),
      'type' => 'valid'
    ));
  }

  /**
   * Métodos auxiliares
   */

  private function create_short_link($url, $custom_slug = '', $tag = '')
  {
    $slug = !empty($custom_slug) ? sanitize_title($custom_slug) : $this->generate_unique_slug($url);

    // Verificar unicidad
    if (!$this->is_slug_unique($slug)) {
      $slug = $this->generate_unique_slug($url, $slug);
    }

    $current_prefix = get_option('ls_current_prefix', '/l/');

    // Crear post
    $post_data = array(
      'post_type' => 'ls_link',
      'post_status' => 'publish',
      'post_title' => $slug . ' | ' . wp_parse_url($url, PHP_URL_HOST),
      'post_name' => $slug,
    );

    $post_id = wp_insert_post($post_data);

    if ($post_id) {
      update_post_meta($post_id, '_ls_original_url', $url);
      update_post_meta($post_id, '_ls_slug', $slug);
      update_post_meta($post_id, '_ls_tag', $tag);
      update_post_meta($post_id, '_ls_prefix_used', $current_prefix);
    }

    return $post_id;
  }

  private function get_link_by_url($url)
  {
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Necessary to find existing link by URL
    $posts = get_posts(array(
      'post_type' => 'ls_link',
      'meta_key' => '_ls_original_url',
      'meta_value' => $url,
      'posts_per_page' => 1,
      'post_status' => 'publish',
      'no_found_rows' => true,
    ));

    return !empty($posts) ? $posts[0] : null;
  }

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

  private function is_slug_unique($slug, $exclude_post_id = 0)
  {
    global $wpdb;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for real-time slug uniqueness validation
    $result = $wpdb->get_var(
      $wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} 
               WHERE meta_key = '_ls_slug' 
               AND meta_value = %s 
               AND post_id != %d",
        $slug,
        $exclude_post_id
      )
    );

    if ($result) {
      return false;
    }

    // Verificar también en aliases
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for real-time slug uniqueness validation
    $alias_result = $wpdb->get_var(
      $wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} 
               WHERE meta_key = '_ls_aliases' 
               AND meta_value LIKE %s 
               AND post_id != %d",
        '%' . $wpdb->esc_like('"' . $slug . '"') . '%',
        $exclude_post_id
      )
    );

    return !$alias_result;
  }

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

  private function render_short_link_display($link_id, $short_url, $original_url = null)
  {
    ob_start();
    ?>
    <div class="ls-short-link-display">
      <code><?php echo esc_html($short_url); ?></code>
      <div class="ls-link-actions">
        <button type="button" class="button-secondary ls-copy-link"
          data-url="<?php echo esc_attr($short_url); ?>">Copiar</button>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }

  /**
   * Genera tag automático basado en el tipo de post
   */
  private function generate_auto_tag($post_type)
  {
    $post_type_object = get_post_type_object($post_type);

    if ($post_type_object && isset($post_type_object->labels->singular_name)) {
      return $post_type_object->labels->singular_name;
    }

    // Fallback para tipos comunes
    $type_map = array(
      'post' => 'Post',
      'page' => 'Page',
      'product' => 'Product',
      'event' => 'Event'
    );

    return isset($type_map[$post_type]) ? $type_map[$post_type] : ucfirst($post_type);
  }

  /**
   * Remueve el tag de enlaces anteriores para la misma URL
   */
  private function remove_tag_from_previous_links($url, $tag)
  {
    global $wpdb;

    // Buscar todos los enlaces existentes para esta URL
    $existing_links = $wpdb->get_results($wpdb->prepare(
      "SELECT p.ID FROM {$wpdb->posts} p
       INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
       WHERE p.post_type = 'ls_link'
       AND p.post_status = 'publish'
       AND pm.meta_key = '_ls_original_url'
       AND pm.meta_value = %s",
      $url
    ));

    // Remover el tag de todos los enlaces anteriores
    foreach ($existing_links as $link) {
      $current_tag = get_post_meta($link->ID, '_ls_tag', true);
      if ($current_tag === $tag) {
        update_post_meta($link->ID, '_ls_tag', '');
      }
    }
  }
}

// Inicializar la clase
new LS_AJAX();
