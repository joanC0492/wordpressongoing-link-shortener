<?php
/**
 * Gestión del Custom Post Type para enlaces cortos
 *
 * @package    Fulltimeforce_Link_Shortener
 * @subpackage Fulltimeforce_Link_Shortener/includes
 */

// Prevenir acceso directo al archivo
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Clase para manejar el Custom Post Type de enlaces cortos
 */
class Link_Shortener_Post_Type
{

  /**
   * El nombre del post type
   */
  const POST_TYPE = 'short_link';

  /**
   * El nombre de la taxonomía
   */
  const TAXONOMY = 'link_category';

  /**
   * Inicializar los hooks
   */
  public function __construct()
  {
    // Registrar el CPT y la taxonomía
    add_action('init', array($this, 'register_post_type'));
    add_action('init', array($this, 'register_taxonomy'));
    // Agregar metaboxes
    add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
    add_action('save_post', array($this, 'save_meta_boxes'));

    // Personalizar las columnas del admin
    add_filter('manage_' . self::POST_TYPE . '_posts_columns', array($this, 'set_custom_columns'));
    add_action('manage_' . self::POST_TYPE . '_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
    add_filter('manage_edit-' . self::POST_TYPE . '_sortable_columns', array($this, 'set_sortable_columns'));

    // Ocultar el menú nativo de WordPress para este CPT
    add_action('admin_menu', array($this, 'remove_default_menu'), 999);
  }

  /**
   * Registrar el Custom Post Type
   */
  public function register_post_type()
  {
    $labels = array(
      'name' => 'Enlaces Cortos',
      'singular_name' => 'Enlace Corto',
      'menu_name' => 'Enlaces Cortos',
      'name_admin_bar' => 'Enlace Corto',
      'add_new' => 'Agregar Nuevo',
      'add_new_item' => 'Agregar Nuevo Enlace',
      'new_item' => 'Nuevo Enlace',
      'edit_item' => 'Editar Enlace',
      'view_item' => 'Ver Enlace',
      'all_items' => 'Todos los Enlaces',
      'search_items' => 'Buscar Enlaces',
      'parent_item_colon' => 'Enlaces Padre:',
      'not_found' => 'No se encontraron enlaces.',
      'not_found_in_trash' => 'No se encontraron enlaces en la papelera.'
    );

    $args = array(
      'labels' => $labels,
      'description' => 'Enlaces cortos gestionados por el plugin',
      'public' => false,
      'publicly_queryable' => true,  // Para que funcione la redirección
      'show_ui' => true,
      'show_in_menu' => false, // Ocultamos el menú nativo
      'query_var' => true,
      'rewrite' => array('slug' => 'go'),
      'capability_type' => 'post',
      'has_archive' => false,
      'hierarchical' => false,
      'menu_position' => null,
      'menu_icon' => 'dashicons-admin-links',
      'supports' => array('title'),
      'show_in_rest' => false, // No necesitamos Gutenberg
    );

    register_post_type(self::POST_TYPE, $args);
  }

  /**
   * Registrar la taxonomía para categorías
   */
  public function register_taxonomy()
  {
    $labels = array(
      'name' => 'Categorías de Enlaces',
      'singular_name' => 'Categoría de Enlace',
      'search_items' => 'Buscar Categorías',
      'all_items' => 'Todas las Categorías',
      'parent_item' => 'Categoría Padre',
      'parent_item_colon' => 'Categoría Padre:',
      'edit_item' => 'Editar Categoría',
      'update_item' => 'Actualizar Categoría',
      'add_new_item' => 'Agregar Nueva Categoría',
      'new_item_name' => 'Nuevo Nombre de Categoría',
      'menu_name' => 'Categorías',
    );

    $args = array(
      'hierarchical' => true,
      'labels' => $labels,
      'show_ui' => true,
      'show_admin_column' => true,
      'query_var' => true,
      'show_in_menu' => false,  // Lo manejamos con nuestro menú
      'rewrite' => array('slug' => 'link-category'),
    );

    register_taxonomy(self::TAXONOMY, array(self::POST_TYPE), $args);
  }

  /**
   * Agregar metaboxes para campos personalizados
   */
  public function add_meta_boxes()
  {
    add_meta_box(
      'link_shortener_details',
      'Detalles del Enlace Corto',
      array($this, 'meta_box_callback'),
      self::POST_TYPE,
      'normal',
      'high'
    );
  }

  /**
   * Callback para el metabox
   */
  public function meta_box_callback($post)
  {
    // Agregar nonce para seguridad
    wp_nonce_field('link_shortener_meta_box', 'link_shortener_meta_box_nonce');

    // Obtener valores existentes
    $original_url = get_post_meta($post->ID, '_original_url', true);
    $short_code = get_post_meta($post->ID, '_short_code', true);
    $description = get_post_meta($post->ID, '_description', true);

    ?>
    <table class="form-table">
      <tr>
        <th scope="row">
          <label for="original_url">URL Original</label>
        </th>
        <td>
          <input type="url" id="original_url" name="original_url" value="<?php echo esc_attr($original_url); ?>"
            class="regular-text" required>
          <p class="description">La URL completa a la que redirigirá el enlace corto</p>
        </td>
      </tr>
      <tr>
        <th scope="row">
          <label for="short_code">Código Corto</label>
        </th>
        <td>
          <input type="text" id="short_code" name="short_code" value="<?php echo esc_attr($short_code); ?>"
            class="regular-text">
          <p class="description">El código único para este enlace (ej: abc123). Se generará automáticamente si se deja
            vacío.</p>
        </td>
      </tr>
      <tr>
        <th scope="row">
          <label for="description">Descripción</label>
        </th>
        <td>
          <textarea id="description" name="description" rows="3"
            class="large-text"><?php echo esc_textarea($description); ?></textarea>
          <p class="description">Descripción opcional para referencia interna</p>
        </td>
      </tr>
    </table>
    <?php
  }

  /**
   * Guardar los datos del metabox
   */
  public function save_meta_boxes($post_id)
  {
    // Verificar nonce
    if (
      !isset($_POST['link_shortener_meta_box_nonce']) ||
      !wp_verify_nonce($_POST['link_shortener_meta_box_nonce'], 'link_shortener_meta_box')
    ) {
      return;
    }

    // Verificar autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }

    // Verificar permisos
    if (isset($_POST['post_type']) && $_POST['post_type'] === self::POST_TYPE) {
      if (!current_user_can('edit_post', $post_id)) {
        return;
      }
    }

    // Guardar URL original
    if (isset($_POST['original_url'])) {
      update_post_meta($post_id, '_original_url', sanitize_url($_POST['original_url']));
    }

    // Guardar o generar código corto
    if (isset($_POST['short_code']) && !empty($_POST['short_code'])) {
      $short_code = sanitize_text_field($_POST['short_code']);
      // Verificar que no exista otro enlace con el mismo código
      if (!$this->short_code_exists($short_code, $post_id)) {
        update_post_meta($post_id, '_short_code', $short_code);
      }
    } else {
      // Generar código automático si no se proporcionó
      $short_code = $this->generate_unique_short_code();
      update_post_meta($post_id, '_short_code', $short_code);
    }

    // Guardar descripción
    if (isset($_POST['description'])) {
      update_post_meta($post_id, '_description', sanitize_textarea_field($_POST['description']));
    }
  }

  /**
   * Verificar si un código corto ya existe
   */
  private function short_code_exists($short_code, $exclude_id = 0)
  {
    $query = new WP_Query(array(
      'post_type' => self::POST_TYPE,
      'meta_query' => array(
        array(
          'key' => '_short_code',
          'value' => $short_code,
          'compare' => '='
        )
      ),
      'post__not_in' => array($exclude_id),
      'posts_per_page' => 1
    ));

    return $query->have_posts();
  }

  /**
   * Generar un código corto único
   */
  private function generate_unique_short_code($length = 6)
  {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    do {
      $short_code = '';
      for ($i = 0; $i < $length; $i++) {
        $short_code .= $characters[rand(0, strlen($characters) - 1)];
      }
    } while ($this->short_code_exists($short_code));

    return $short_code;
  }

  /**
   * Definir columnas personalizadas
   */
  public function set_custom_columns($columns)
  {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb']; // Checkbox
    $new_columns['title'] = 'URL Original'; // Renombramos title
    $new_columns['short_url'] = 'Enlace Corto';
    $new_columns['taxonomy-' . self::TAXONOMY] = 'Categoría';
    $new_columns['date'] = $columns['date'];
    $new_columns['actions'] = 'Acciones';

    return $new_columns;
  }

  /**
   * Contenido de las columnas personalizadas
   */
  public function custom_column_content($column, $post_id)
  {
    switch ($column) {
      case 'short_url':
        $short_code = get_post_meta($post_id, '_short_code', true);
        if ($short_code) {
          $short_url = home_url('/go/' . $short_code);
          echo '<a href="' . esc_url($short_url) . '" target="_blank">' . esc_html($short_url) . '</a>';
          echo '<br><small><a href="#" class="copy-link" data-url="' . esc_attr($short_url) . '">Copiar enlace</a></small>';
        }
        break;

      case 'actions':
        $short_code = get_post_meta($post_id, '_short_code', true);
        $short_url = home_url('/go/' . $short_code);
        echo '<a href="' . get_edit_post_link($post_id) . '">Editar</a> | ';
        echo '<a href="#" class="copy-link" data-url="' . esc_attr($short_url) . '">Copiar</a> | ';
        echo '<a href="' . esc_url($short_url) . '" target="_blank">Probar</a>';
        break;
    }
  }

  /**
   * Hacer columnas ordenables
   */
  public function set_sortable_columns($columns)
  {
    $columns['title'] = 'title';
    $columns['date'] = 'date';
    return $columns;
  }

  /**
   * Remover el menú nativo de WordPress
   */
  public function remove_default_menu()
  {
    remove_menu_page('edit.php?post_type=' . self::POST_TYPE);
  }
}

// Inicializar la clase
new Link_Shortener_Post_Type();