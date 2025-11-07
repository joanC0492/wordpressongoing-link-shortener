<?php
/**
 * Clase para manejo de reglas de reescritura y redirección
 * 
 * Se encarga de resolver los enlaces cortos y redirigir a las URLs originales
 */

if (!defined('ABSPATH')) {
  exit;
}

class LS_Rewrite
{

  /**
   * Constructor
   */
  public function __construct()
  {
    add_action('init', array($this, 'add_rewrite_rules'), 10);
    add_action('init', array($this, 'add_query_vars'), 10);
    add_action('template_redirect', array($this, 'handle_redirect'), 5);
    add_action('parse_request', array($this, 'parse_request'), 1);

    // Flush rules cuando se actualiza el historial de prefijos
    add_action('update_option_ls_prefix_history', array($this, 'flush_rules'));
  }

  /**
   * Añade las reglas de reescritura para todos los prefijos
   */
  public function add_rewrite_rules()
  {
    $prefix_history = get_option('ls_prefix_history', array('/l/'));

    foreach ($prefix_history as $prefix) {
      $prefix_clean = trim($prefix, '/');

      // Debug log

      // Regla: /prefix/slug/ -> index.php?ls_slug=slug&ls_prefix=prefix
      add_rewrite_rule(
        '^' . preg_quote($prefix_clean, '/') . '/([^/]+)/?$',
        'index.php?ls_slug=$matches[1]&ls_prefix=' . urlencode($prefix),
        'top'
      );
    }

    // Debug: log todas las reglas registradas
    add_action('wp_loaded', function () {
      global $wp_rewrite;
    });
  }

  /**
   * Añade las query vars personalizadas
   */
  public function add_query_vars()
  {
    global $wp;
    $wp->add_query_var('ls_slug');
    $wp->add_query_var('ls_prefix');
  }

  /**
   * Procesa la request para enlaces cortos
   */
  public function parse_request($wp)
  {
    // Debug: log la URL completa
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized with esc_url_raw() for logging
    $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';

    // Solo procesar si tenemos las query vars necesarias
    if (!isset($wp->query_vars['ls_slug']) || !isset($wp->query_vars['ls_prefix'])) {
      return;
    }

    $slug = sanitize_text_field($wp->query_vars['ls_slug']);
    $prefix = sanitize_text_field($wp->query_vars['ls_prefix']);


    // Verificar que el prefijo esté en el historial
    $prefix_history = get_option('ls_prefix_history', array('/l/'));
    if (!in_array($prefix, $prefix_history)) {
      return;
    }

    // Buscar el enlace
    $link_data = $this->find_link_by_slug($slug);

    if (!$link_data) {
      return;
    }


    // Realizar la redirección
    $this->perform_redirect($link_data['url'], $link_data['post_id'], $slug, $prefix);
  }

  /**
   * Maneja la redirección de template_redirect como fallback
   */
  public function handle_redirect()
  {
    global $wp_query;

    // Debug: información del estado actual
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized with esc_url_raw() for logging

    // Intentar detectar si es un enlace corto manualmente
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized with esc_url_raw()
    $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
    $site_url = wp_parse_url(home_url(), PHP_URL_PATH) ?? '';

    // Remover el path del sitio si existe
    if ($site_url && $site_url !== '/') {
      $request_uri = str_replace($site_url, '', $request_uri);
    }
    $request_uri = ltrim($request_uri, '/');

    // Verificar si coincide con algún prefijo
    $prefix_history = get_option('ls_prefix_history', array('/l/'));
    $found_match = false;

    foreach ($prefix_history as $prefix) {
      $prefix_clean = trim($prefix, '/');

      if (strpos($request_uri, $prefix_clean . '/') === 0) {
        $slug = substr($request_uri, strlen($prefix_clean) + 1);
        $slug = trim($slug, '/');

        if (!empty($slug)) {

          $link_data = $this->find_link_by_slug($slug);
          if ($link_data) {
            $this->perform_redirect($link_data['url'], $link_data['post_id'], $slug, $prefix);
            return;
          }
        }
        $found_match = true;
        break;
      }
    }

    if ($found_match) {
    }

    // Método original como fallback
    if (!is_404() || !isset($wp_query->query_vars['ls_slug'])) {
      return;
    }

    $slug = get_query_var('ls_slug');
    $prefix = get_query_var('ls_prefix');

    if (empty($slug)) {
      return;
    }

    // Si no se especificó prefijo, intentar con todos los del historial
    if (empty($prefix)) {
      foreach ($prefix_history as $test_prefix) {
        $link_data = $this->find_link_by_slug($slug);
        if ($link_data) {
          $this->perform_redirect($link_data['url'], $link_data['post_id'], $slug, $test_prefix);
          return;
        }
      }
    } else {
      $link_data = $this->find_link_by_slug($slug);
      if ($link_data) {
        $this->perform_redirect($link_data['url'], $link_data['post_id'], $slug, $prefix);
        return;
      }
    }
  }

  /**
   * Busca un enlace por slug (principal o alias)
   */
  private function find_link_by_slug($slug)
  {
    global $wpdb;


    // Buscar por slug principal
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for real-time link redirection
    $post_id = $wpdb->get_var($wpdb->prepare(
      "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_ls_slug' 
             AND meta_value = %s 
             LIMIT 1",
      $slug
    ));


    if ($post_id) {
      $original_url = get_post_meta($post_id, '_ls_original_url', true);

      if ($original_url) {
        return array(
          'post_id' => $post_id,
          'url' => $original_url,
          'type' => 'main'
        );
      }
    }

    // Buscar en aliases
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Alias search requires direct query with LIKE pattern
    $alias_posts = $wpdb->get_results($wpdb->prepare(
      "SELECT post_id, meta_value FROM {$wpdb->postmeta} 
             WHERE meta_key = '_ls_aliases'
             AND meta_value LIKE %s",
      '%"' . $wpdb->esc_like($slug) . '"%'
    ));


    foreach ($alias_posts as $alias_post) {
      $aliases = maybe_unserialize($alias_post->meta_value);
      if (is_array($aliases) && in_array($slug, $aliases)) {
        $original_url = get_post_meta($alias_post->post_id, '_ls_original_url', true);
        if ($original_url) {
          return array(
            'post_id' => $alias_post->post_id,
            'url' => $original_url,
            'type' => 'alias'
          );
        }
      }
    }

    return false;
  }

  /**
   * Realiza la redirección
   */
  private function perform_redirect($url, $post_id, $slug, $prefix)
  {
    // Verificar que la URL sea válida
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
      wp_die('Enlace no válido', 'Error 400', array('response' => 400));
      return;
    }

    // Hook para tracking o analytics
    do_action('ls_before_redirect', $post_id, $url, $slug, $prefix);

    // Log de acceso (opcional, para estadísticas futuras)
    $this->log_access($post_id, $slug, $prefix);

    // Redirección 302 (temporal)
    wp_redirect($url, 302);
    exit;
  }

  /**
   * Registra el acceso para estadísticas futuras
   */
  private function log_access($post_id, $slug, $prefix)
  {
    // Por ahora solo un meta simple con contador
    $access_count = get_post_meta($post_id, '_ls_access_count', true);
    $access_count = $access_count ? (int) $access_count + 1 : 1;
    update_post_meta($post_id, '_ls_access_count', $access_count);

    // Actualizar última fecha de acceso
    update_post_meta($post_id, '_ls_last_access', current_time('mysql'));

    // Hook para extensiones futuras de analytics
    do_action('ls_access_logged', $post_id, $slug, $prefix, $access_count);
  }

  /**
   * Flush de reglas de reescritura
   */
  public function flush_rules()
  {
    flush_rewrite_rules();
  }

  /**
   * Obtener estadísticas de un enlace
   */
  public function get_link_stats($post_id)
  {
    $access_count = get_post_meta($post_id, '_ls_access_count', true);
    $last_access = get_post_meta($post_id, '_ls_last_access', true);

    return array(
      'clicks' => $access_count ? (int) $access_count : 0,
      'last_access' => $last_access ? $last_access : null,
      'last_access_human' => $last_access ? human_time_diff(strtotime($last_access)) . ' ago' : 'Nunca',
    );
  }

  /**
   * Verificar si un slug está disponible
   */
  public function is_slug_available($slug, $exclude_post_id = 0)
  {
    global $wpdb;

    // Verificar slug principal
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for real-time slug availability check
    $main_check = $wpdb->get_var($wpdb->prepare(
      "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_ls_slug' 
             AND meta_value = %s 
             AND post_id != %d 
             LIMIT 1",
      $slug,
      $exclude_post_id
    ));

    if ($main_check) {
      return false;
    }

    // Verificar aliases
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for real-time slug availability check
    $alias_check = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT post_id, meta_value FROM {$wpdb->postmeta} 
               WHERE meta_key = '_ls_aliases'
               AND post_id != %d 
               AND meta_value LIKE %s",
        $exclude_post_id,
        '%' . $wpdb->esc_like($slug) . '%'
      )
    );

    foreach ($alias_check as $alias_row) {
      $aliases = maybe_unserialize($alias_row->meta_value);
      if (is_array($aliases) && in_array($slug, $aliases)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Obtener todos los slugs de un enlace (principal + aliases)
   */
  public function get_all_slugs($post_id)
  {
    $main_slug = get_post_meta($post_id, '_ls_slug', true);
    $aliases = get_post_meta($post_id, '_ls_aliases', true);

    $slugs = array();

    if ($main_slug) {
      $slugs[] = array(
        'slug' => $main_slug,
        'type' => 'main',
        'active' => true
      );
    }

    if (is_array($aliases)) {
      foreach ($aliases as $alias) {
        $slugs[] = array(
          'slug' => $alias,
          'type' => 'alias',
          'active' => true
        );
      }
    }

    return $slugs;
  }

  /**
   * Añadir alias a un enlace existente
   */
  public function add_alias($post_id, $new_slug)
  {
    if (!$this->is_slug_available($new_slug, $post_id)) {
      return false;
    }

    $aliases = get_post_meta($post_id, '_ls_aliases', true);
    if (!is_array($aliases)) {
      $aliases = array();
    }

    if (!in_array($new_slug, $aliases)) {
      $aliases[] = $new_slug;
      update_post_meta($post_id, '_ls_aliases', $aliases);
      return true;
    }

    return false;
  }

  /**
   * Remover alias de un enlace
   */
  public function remove_alias($post_id, $slug)
  {
    $aliases = get_post_meta($post_id, '_ls_aliases', true);
    if (!is_array($aliases)) {
      return false;
    }

    $key = array_search($slug, $aliases);
    if ($key !== false) {
      unset($aliases[$key]);
      update_post_meta($post_id, '_ls_aliases', array_values($aliases));
      return true;
    }

    return false;
  }
}

// Inicializar la clase
new LS_Rewrite();