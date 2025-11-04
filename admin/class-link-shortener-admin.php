<?php
/**
 * La funcionalidad específica del admin del plugin.
 *
 * @package    Fulltimeforce_Link_Shortener
 * @subpackage Fulltimeforce_Link_Shortener/admin
 */

// Prevenir acceso directo al archivo
if (!defined('ABSPATH')) {
  exit;
}

/**
 * La funcionalidad específica del admin del plugin.
 *
 * Define el nombre del plugin, la versión, y dos ejemplos hooks para
 * cómo encolar las hojas de estilo específicas del admin y JavaScript.
 */
class Link_Shortener_Admin
{
  /**
   * El ID de este plugin.
   */
  private $plugin_name;

  /**
   * La versión de este plugin.
   */
  private $version;

  /**
   * Inicializar la clase y establecer sus propiedades.
   */
  public function __construct($plugin_name, $version)
  {
    $this->plugin_name = $plugin_name;
    $this->version = $version;

    // Agregar scripts y estilos del admin
    add_action(
      'admin_enqueue_scripts',
      array($this, 'enqueue_scripts')
    );
  }

  /**
   * Crear el menú del admin
   */
  public function crear_menu()
  {
    add_menu_page(
      'Link Shortener',
      'Link Shortener',
      'manage_options',
      'ff-link-shortener',
      array($this, 'link_shortener_settings'),
      'dashicons-admin-links',
      4
    );

    // Submenú para todos los enlaces
    add_submenu_page(
      'ff-link-shortener',
      'Todos los enlaces',
      'Todos los enlaces',
      'manage_options',
      'ff-link-shortener-all-links',
      array($this, 'link_shortener_all_links')
    );

    // Submenú para agregar nuevo enlace
    add_submenu_page(
      'ff-link-shortener',
      'Agregar nuevo enlace',
      'Agregar nuevo enlace',
      'manage_options',
      'ff-link-shortener-add-new',
      array($this, 'link_shortener_add_new')
    );

    // Submenú para categorías
    add_submenu_page(
      'ff-link-shortener',
      'Categorías',
      'Categorías',
      'manage_options',
      'ff-link-shortener-categories',
      array($this, 'link_shortener_categories_page')
    );

    // Submenú para ajustes
    add_submenu_page(
      'ff-link-shortener',
      'Ajustes',
      'Ajustes',
      'manage_options',
      'ff-link-shortener-settings',
      array($this, 'link_shortener_settings_page')
    );
  }

  /**
   * Agregar scripts y estilos del admin
   */
  public function enqueue_scripts($hook)
  {
    // Solo cargar en nuestras páginas
    if (strpos($hook, 'ff-link-shortener') !== false) {
      wp_enqueue_script(
        $this->plugin_name . '-admin',
        plugin_dir_url(__FILE__) . 'js/admin.js',
        array('jquery'),
        $this->version,
        false
      );
    }
  }

  /**
   * Página principal de configuración
   */
  public function link_shortener_settings()
  {
    include_once plugin_dir_path(__FILE__) . 'partials/link-shortener-admin-display.php';
  }

  /**
   * Página de todos los enlaces
   */
  public function link_shortener_all_links()
  {
    include_once plugin_dir_path(__FILE__) . 'partials/link-shortener-all-links-display.php';
  }

  /**
   * Página para agregar nuevo enlace
   */
  public function link_shortener_add_new()
  {
    include_once plugin_dir_path(__FILE__) . 'partials/link-shortener-add-new-display.php';
  }

  /**
   * Página de categorías
   */
  public function link_shortener_categories_page()
  {
    include_once plugin_dir_path(__FILE__) . 'partials/link-shortener-categories-display.php';
  }

  /**
   * Página de ajustes
   */
  public function link_shortener_settings_page()
  {
    include_once plugin_dir_path(__FILE__) . 'partials/link-shortener-settings-display.php';
  }

  /* Función auxiliar para cargar vistas parciales de manera segura */
  private function load_partial($filename)
  {
    $file_path = plugin_dir_path(__FILE__) . 'partials/' . $filename;

    if (file_exists($file_path)) {
      include_once $file_path;
    } else {
      echo '<div class="notice notice-error">';
      echo '<p>Error: No se pudo cargar la vista ' . esc_html($filename) . '. Contacta al desarrollador.</p>';
      echo '</div>';
    }
  }
}