<?php
/**
 * Custom Post Type para Link Shortener
 * 
 * Maneja el registro del CPT ls_link y las columnas del admin
 */

if (!defined('ABSPATH')) {
  exit;
}

class LS_CPT
{

  /**
   * Constructor
   */
  public function __construct()
  {
    add_action('init', array($this, 'register_cpt'));
    add_action('init', array($this, 'add_columns_to_post_types'));

    // Columnas para el CPT ls_link
    add_filter('manage_ls_link_posts_columns', array($this, 'ls_link_columns'));
    add_action('manage_ls_link_posts_custom_column', array($this, 'ls_link_column_content'), 10, 2);

    // Búsqueda personalizada
    add_filter('posts_search', array($this, 'extend_search'), 20, 2);

    // Ocultar metaboxes de SEO
    add_action('add_meta_boxes', array($this, 'remove_seo_metaboxes'), 99);

    // Excluir de sitemaps
    add_filter('wp_sitemaps_post_types', array($this, 'exclude_from_sitemaps'));
    
    // Controlar acciones de fila para ls_link
    add_filter('post_row_actions', array($this, 'remove_row_actions'), 10, 2);
    add_filter('page_row_actions', array($this, 'remove_row_actions'), 10, 2);
    
    // Remover bulk actions problemáticas
    add_filter('bulk_actions-edit-ls_link', array($this, 'remove_bulk_actions'));
    
    // Deshabilitar Quick Edit para ls_link
    add_action('admin_head-edit.php', array($this, 'disable_quick_edit'));
  }

  /**
   * Registra el Custom Post Type ls_link
   */
  public function register_cpt()
  {
    $args = array(
      'labels' => array(
        'name' => __('Short Links', 'link-shortener-wordpressongoing'),
        'singular_name' => __('Short Link', 'link-shortener-wordpressongoing'),
        'add_new' => __('Add New', 'link-shortener-wordpressongoing'),
        'add_new_item' => __('Add New Link', 'link-shortener-wordpressongoing'),
        'edit_item' => __('Edit Link', 'link-shortener-wordpressongoing'),
        'new_item' => __('New Link', 'link-shortener-wordpressongoing'),
        'view_item' => __('View Link', 'link-shortener-wordpressongoing'),
        'search_items' => __('Search Links', 'link-shortener-wordpressongoing'),
        'not_found' => __('No links found', 'link-shortener-wordpressongoing'),
        'not_found_in_trash' => __('No links in trash', 'link-shortener-wordpressongoing'),
        'all_items' => __('All Links', 'link-shortener-wordpressongoing'),
      ),
      'public' => false,
      'show_ui' => true,
      'show_in_menu' => false, // Lo añadiremos manualmente
      'capability_type' => 'post',
      'map_meta_cap' => true,
      'hierarchical' => false,
      'supports' => array('title'), // Solo título, el resto por metabox
      'has_archive' => false,
      'query_var' => false,
      'can_export' => true,
      'rewrite' => false,
      'show_in_rest' => false,
      'exclude_from_search' => true,
      'publicly_queryable' => false,
    );

    register_post_type('ls_link', $args);
  }

  /**
   * Añade columnas Short Link a otros post types
   */
  public function add_columns_to_post_types()
  {
    // Obtener todos los post types públicos excepto ls_link
    $post_types = get_post_types(array('public' => true), 'names');
    unset($post_types['ls_link'], $post_types['attachment']);

    foreach ($post_types as $post_type) {
      add_filter("manage_{$post_type}_posts_columns", array($this, 'add_short_link_column'));
      add_action("manage_{$post_type}_posts_custom_column", array($this, 'short_link_column_content'), 10, 2);
    }
  }

  /**
   * Columnas para el listado de ls_link
   */
  public function ls_link_columns($columns)
  {
    // Remover columnas innecesarias
    unset($columns['date']);

    // Añadir nuestras columnas
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['original_url'] = __('Original URL', 'link-shortener-wordpressongoing');
    $new_columns['short_link'] = __('Short Link', 'link-shortener-wordpressongoing');
    $new_columns['tag'] = __('Tag', 'link-shortener-wordpressongoing');
    $new_columns['actions'] = __('Actions', 'link-shortener-wordpressongoing');

    return $new_columns;
  }

  /**
   * Contenido de las columnas de ls_link
   */
  public function ls_link_column_content($column, $post_id)
  {
    switch ($column) {
      case 'original_url':
        $url = get_post_meta($post_id, '_ls_original_url', true);
        if ($url) {
          echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">';
          echo esc_html($url);
          echo ' <span class="dashicons dashicons-external" style="font-size: 12px;"></span></a>';
        }
        break;

      case 'short_link':
        $this->display_short_link_column($post_id);
        break;

      case 'tag':
        $tag = get_post_meta($post_id, '_ls_tag', true);
        echo esc_html($tag);
        break;

      case 'actions':
        $this->display_actions_column($post_id);
        break;
    }
  }

  /**
   * Añade columna Short Link a otros post types
   */
  public function add_short_link_column($columns)
  {
    $columns['short_link'] = 'Short Link';
    return $columns;
  }

  /**
   * Contenido de la columna Short Link para otros post types
   */
  public function short_link_column_content($column, $post_id)
  {
    if ($column === 'short_link') {
      $post_url = get_permalink($post_id);
      $link_post = $this->get_link_by_url($post_url);

      if ($link_post && is_object($link_post)) {
        $this->display_short_link_column($link_post->ID, $post_url);
      } else {
        echo '<button type="button" class="button ls-generate-link" data-post-id="' . esc_attr($post_id) . '" data-url="' . esc_attr($post_url) . '">' . esc_html__( 'Generate short link', 'link-shortener-wordpressongoing' ) . '</button>';
      }
    }
  }

  /**
   * Muestra el contenido de la columna short link
   */
  private function display_short_link_column($link_post_id, $original_url = null)
  {
    $slug = get_post_meta($link_post_id, '_ls_slug', true);
    $prefix_used = get_post_meta($link_post_id, '_ls_prefix_used', true);

    if ($slug && $prefix_used) {
      $short_url = home_url($prefix_used . $slug);
      echo '<div class="ls-short-link-display">';
      echo '<code>' . esc_html($short_url) . '</code>';
      echo '<div class="ls-link-actions">';
      echo '<button type="button" class="button-secondary ls-copy-link" data-url="' . esc_attr($short_url) . '">' . esc_html__( 'Copy', 'link-shortener-wordpressongoing' ) . '</button>';
      echo '</div>';
      echo '</div>';
    }
  }

  /**
   * Muestra las acciones para ls_link
   */
  private function display_actions_column($post_id)
  {
    $slug = get_post_meta($post_id, '_ls_slug', true);
    $prefix_used = get_post_meta($post_id, '_ls_prefix_used', true);

    if ($slug && $prefix_used) {
      $short_url = home_url($prefix_used . $slug);
      $edit_url = admin_url('post.php?post=' . $post_id . '&action=edit');
      
      echo '<div class="ls-actions-column" style="display: flex; gap: 5px; align-items: center;">';
      // echo '<a href="' . esc_url($edit_url) . '" class="button button-secondary">Editar</a>';
      // echo '<button type="button" class="button-secondary ls-copy-link" data-url="' . esc_attr($short_url) . '">Copiar</button>';
      // echo '<button type="button" class="button-link-delete ls-delete-link" data-link-id="' . esc_attr($post_id) . '">Eliminar</button>';
      
      // width: 16px; height: 16px; vertical-align: middle;
      echo '  <a href="' . esc_url($edit_url) . '" class="ls_link__btn ls_link__btn--edit" title="' . esc_attr__( 'Edit', 'link-shortener-wordpressongoing' ) . '">';
      echo '    <img src="' . esc_url(plugin_dir_url(__FILE__) . '../assets/img/plugin-icon-edit.png') . '" alt="' . esc_attr__( 'Edit', 'link-shortener-wordpressongoing' ) . '" style="">';
      echo '  </a>';
      // 
      echo '  <button type="button" class="ls_link__btn ls_link__btn--copy button-secondary ls-copy-link" data-url="' . esc_attr($short_url) . '" title="' . esc_attr__( 'Copy', 'link-shortener-wordpressongoing' ) . '">';
      echo '    <img src="' . esc_url(plugin_dir_url(__FILE__) . '../assets/img/plugin-icon-copy.png') . '" alt="' . esc_attr__( 'Copy', 'link-shortener-wordpressongoing' ) . '" style="">';
      echo '  </button>';
      // 
      echo '  <button type="button" class="ls_link__btn ls_link__btn--delete button-link-delete ls-delete-link" data-link-id="' . esc_attr($post_id) . '" title="' . esc_attr__( 'Delete', 'link-shortener-wordpressongoing' ) . '">';
      echo '    <img src="' . esc_url(plugin_dir_url(__FILE__) . '../assets/img/plugin-icon-delete.png') . '" alt="' . esc_attr__( 'Delete', 'link-shortener-wordpressongoing' ) . '" style="">';
      echo '  </button>';
      echo '</div>';
    }
  }

  /**
   * Busca un enlace por URL original
   */
  private function get_link_by_url($url)
  {
    $posts = get_posts(array(
      'post_type' => 'ls_link',
      // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Necessary to find existing link by URL
      'meta_key' => '_ls_original_url',
      // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Necessary to find existing link by URL
      'meta_value' => $url,
      'posts_per_page' => 1,
      'post_status' => 'publish',
      'no_found_rows' => true,
    ));

    return !empty($posts) ? $posts[0] : null;
  }

  /**
   * Extiende la búsqueda para incluir metadatos
   */
  public function extend_search($search, $query)
  {
    global $wpdb;

    if (!is_admin() || !$query->is_main_query() || !$query->is_search()) {
      return $search;
    }

    if ($query->get('post_type') !== 'ls_link') {
      return $search;
    }

    $search_term = $query->get('s');
    if (empty($search_term)) {
      return $search;
    }

    // Búsqueda en metadatos
    $meta_search = $wpdb->prepare(
      " OR {$wpdb->posts}.ID IN (
                SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key IN ('_ls_original_url', '_ls_slug', '_ls_tag') 
                AND meta_value LIKE %s
            )",
      '%' . $wpdb->esc_like($search_term) . '%'
    );

    $search = preg_replace('/\)\s*$/', $meta_search . ')', $search);

    return $search;
  }

  /**
   * Remueve metaboxes de SEO del CPT ls_link
   */
  public function remove_seo_metaboxes()
  {
    // Yoast SEO
    remove_meta_box('wpseo_meta', 'ls_link', 'normal');

    // RankMath
    remove_meta_box('rank_math_metabox', 'ls_link', 'normal');

    // All in One SEO
    remove_meta_box('aioseo-settings', 'ls_link', 'normal');
  }

  /**
   * Excluye ls_link de los sitemaps
   */
  public function exclude_from_sitemaps($post_types)
  {
    unset($post_types['ls_link']);
    return $post_types;
  }

  /**
   * Remueve acciones de fila no deseadas para ls_link
   */
  public function remove_row_actions($actions, $post)
  {
    if ($post->post_type === 'ls_link') {
      // Remover TODAS las acciones para ls_link
      // Ya no queremos ninguna acción en el hover de la primera columna
      return array();
    }
    
    return $actions;
  }

  /**
   * Remueve bulk actions problemáticas
   */
  public function remove_bulk_actions($actions)
  {
    // Remover acciones masivas no deseadas
    unset($actions['trash']);
    unset($actions['edit']);  // Bulk edit
    
    return $actions;
  }

  /**
   * Deshabilita Quick Edit y Bulk Actions para ls_link
   */
  public function disable_quick_edit()
  {
    global $current_screen;
    
    if ($current_screen && $current_screen->post_type === 'ls_link') {
      echo '<style>
        .editinline { display: none !important; }
        .row-actions { display: none !important; }
        .bulkactions { display: none !important; }
        .check-column, .column-cb { display: none !important; }
        .tablenav-pages { display: none !important; }
      </style>';
      
      echo '<script>
        jQuery(document).ready(function($) {
          // Remover todos los elementos problemáticos
          $(".editinline").remove();
          $(".row-actions").remove();
          $(".bulkactions").remove();
          $(".check-column, .column-cb").remove();
          
          // Prevenir quick edit en doble click
          $("#the-list tr").off("dblclick");
          
          // Remover checkboxes de selección
          $("#the-list input[type=checkbox]").remove();
          $(".check-column").remove();
          
          console.log("🔒 Link Shortener: Interfaz simplificada aplicada");
        });
      </script>';
    }
  }
}

// Inicializar la clase
new LS_CPT();
