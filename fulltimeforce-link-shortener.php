<?php
/**
 * Plugin Name: Wordpressongoing Link Shortener
 * Plugin URI: https://github.com/joanC0492/fulltimeforce-link-shortener
 * Description: Plugin to shorten links with prefix management.
 * Version: 1.0.0
 * Author: Joan Cochachi
 * Author URI: https://wordpressongoing.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fulltimeforce-link-shortener
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package FulltimeforceLS
 * @version 1.0.0
 * @since 1.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Clase principal del plugin Fulltimeforce Link Shortener
 */
class Fulltimeforce_Link_Shortener
{

  /**
   * Versión del plugin
   */
  const VERSION = '1.0.0';

  /**
   * Instancia singleton
   */
  private static $instance = null;

  /**
   * Ruta del plugin
   */
  private $plugin_path;

  /**
   * URL del plugin
   */
  private $plugin_url;

  /**
   * Constructor privado para singleton
   */
  private function __construct()
  {
    $this->plugin_path = plugin_dir_path(__FILE__);
    $this->plugin_url = plugin_dir_url(__FILE__);

    $this->init();
  }

  /**
   * Obtener instancia singleton
   */
  public static function get_instance()
  {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Inicialización del plugin
   */
  private function init()
  {
    // Hooks de activación y desactivación
    register_activation_hook(__FILE__, array($this, 'activate'));
    register_deactivation_hook(__FILE__, array($this, 'deactivate'));

    // Cargar clases
    add_action('plugins_loaded', array($this, 'load_classes'));

    // Cargar textdomain - IMPORTANTE: Prioridad alta para cargar antes
    add_action('plugins_loaded', array($this, 'load_textdomain'), 1);

    // Debug de traducción (solo si WP_DEBUG está activo)
    if (defined('WP_DEBUG') && WP_DEBUG) {
      add_action('admin_footer', array($this, 'debug_translations'));
    }

    // Enqueue scripts y styles
    add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

    // Añadir nonce para AJAX
    add_action('admin_head', array($this, 'add_admin_nonce'));

    // Añadir enlaces en la página de plugins
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links'));

    // Hook para flush de reglas en init (después de todas las clases)
    add_action('init', array($this, 'maybe_flush_rewrite_rules'), 999);
  }

  /**
   * Cargar clases del plugin
   */
  public function load_classes()
  {
    // Verificar que WordPress esté cargado
    if (!function_exists('wp_get_current_user')) {
      return;
    }

    // Incluir clases principales
    $classes = array(
      'includes/class-ls-cpt.php',
      'includes/class-ls-metabox.php',
      'includes/class-ls-settings.php',
      'includes/class-ls-rewrite.php',
      'includes/class-ls-ajax.php'
    );

    foreach ($classes as $class_file) {
      $file_path = $this->plugin_path . $class_file;
      if (file_exists($file_path)) {
        require_once $file_path;
      }
    }

    // Hook para después de cargar clases
    do_action('ls_classes_loaded');
  }

  /**
   * Cargar textdomain para traducciones
   */
  public function load_textdomain()
  {
    // Determinar el idioma de WordPress
    $locale = apply_filters('plugin_locale', determine_locale(), 'fulltimeforce-link-shortener');
    
    // Forzar carga de traducciones
    unload_textdomain('fulltimeforce-link-shortener');
    
    // Cargar desde idiomas del plugin
    $loaded = load_textdomain(
      'fulltimeforce-link-shortener',
      $this->plugin_path . 'languages/fulltimeforce-link-shortener-' . $locale . '.mo'
    );
    
    // Si no se cargó, intentar con formato corto (es_ES.mo en lugar de fulltimeforce-link-shortener-es_ES.mo)
    if (!$loaded) {
      $loaded = load_textdomain(
        'fulltimeforce-link-shortener',
        $this->plugin_path . 'languages/' . $locale . '.mo'
      );
    }
    
    // Fallback a load_plugin_textdomain
    if (!$loaded) {
      load_plugin_textdomain(
        'fulltimeforce-link-shortener',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
      );
    }
    
    // Log para debug
    if (defined('WP_DEBUG') && WP_DEBUG) {
      error_log("LS i18n: Locale: $locale, Loaded: " . ($loaded ? 'yes' : 'no'));
    }
  }

  /**
   * Enqueue scripts y styles del admin
   */
  public function admin_enqueue_scripts($hook)
  {
    // Solo cargar en páginas relevantes
    $relevant_pages = array(
      'edit.php',
      'post.php',
      'post-new.php',
      'toplevel_page_link-shortener',
      'link-shortener_page_link-shortener-settings'
    );

    $is_relevant_page = false;
    foreach ($relevant_pages as $page) {
      if (strpos($hook, $page) !== false) {
        $is_relevant_page = true;
        break;
      }
    }

    // Also load on legacy FF Link Shortener pages
    if (strpos($hook, 'ff-link-shortener') !== false) {
      $is_relevant_page = true;
    }

    // También cargar si estamos en el CPT ls_link
    if (isset($_GET['post_type']) && $_GET['post_type'] === 'ls_link') {
      $is_relevant_page = true;
    }

    // O si estamos editando un post ls_link
    if ($hook === 'post.php' || $hook === 'post-new.php') {
      global $post;
      if ($post && $post->post_type === 'ls_link') {
        $is_relevant_page = true;
      }
    }

    // También cargar en cualquier página de edición con posts públicos
    if (strpos($hook, 'edit.php') !== false) {
      $is_relevant_page = true;
    }

    if (!$is_relevant_page) {
      return;
    }

    // CSS
    wp_enqueue_style(
      'ls-admin-css',
      $this->plugin_url . 'assets/admin.css',
      array(),
      self::VERSION
    );

    // JavaScript
    wp_enqueue_script(
      'ls-admin-js',
      $this->plugin_url . 'assets/admin.js',
      array('jquery'),
      self::VERSION,
      true
    );

    // Localizar script
    wp_localize_script('ls-admin-js', 'lsAdmin', array(
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('ls_admin_nonce'),
      'homeUrl' => home_url('/'),
      'currentPrefix' => get_option('ls_current_prefix', '/l/'),
      'strings' => array(
        'confirmDelete' => __('Are you sure you want to delete this link? This action cannot be undone.', 'fulltimeforce-link-shortener'),
        'confirmDeleteAlias' => __('Delete this alias? The link will stop working.', 'fulltimeforce-link-shortener'),
        'copySuccess' => __('Copied!', 'fulltimeforce-link-shortener'),
        'copyError' => __('Copy error', 'fulltimeforce-link-shortener'),
        'generating' => __('Generating...', 'fulltimeforce-link-shortener'),
        'rotating' => __('Rotating...', 'fulltimeforce-link-shortener'),
        'deleting' => __('Deleting...', 'fulltimeforce-link-shortener'),
        'errorPrefix' => __('Error:', 'fulltimeforce-link-shortener'),
        'successPrefix' => __('Success:', 'fulltimeforce-link-shortener'),
        'errorIncompleteData' => __('Incomplete data', 'fulltimeforce-link-shortener'),
        'errorNoNonce' => __('Could not obtain security token', 'fulltimeforce-link-shortener'),
        'connectionError' => __('Connection error', 'fulltimeforce-link-shortener'),
        'copyFallbackError' => __('Copy failed. Select and copy manually:', 'fulltimeforce-link-shortener'),
        'linkCopiedPrefix' => __('Link copied:', 'fulltimeforce-link-shortener'),
        'generateShortLink' => __('Generate short link', 'fulltimeforce-link-shortener'),
        'noUrlToCopy' => __('No URL to copy', 'fulltimeforce-link-shortener'),
        'urlMustStartWithHttp' => __('URL must start with http:// or https:// and include a valid domain', 'fulltimeforce-link-shortener'),
        'domainInvalid' => __('Domain does not seem valid', 'fulltimeforce-link-shortener'),
        'errorUrlValidation' => __('Error validating URL', 'fulltimeforce-link-shortener'),
        'errorUrlValidationConnection' => __('Connection error while validating URL', 'fulltimeforce-link-shortener'),
        'cannotSaveSlugInUse' => __('Cannot save: the slug is already in use. Please choose another slug.', 'fulltimeforce-link-shortener'),
        'confirmMoveToTrash' => __('Are you sure you want to move this link to trash?', 'fulltimeforce-link-shortener')
      ,
        // Additional strings used in JS UI
        'rotateSlug' => __('Rotate Slug', 'fulltimeforce-link-shortener'),
        'delete' => __('Delete', 'fulltimeforce-link-shortener'),
        'regenerate' => __('Regenerate', 'fulltimeforce-link-shortener'),
        'regenerating' => __('Regenerating...', 'fulltimeforce-link-shortener'),
        'generatedAndCopiedPrefix' => __('Link generated and copied to clipboard:', 'fulltimeforce-link-shortener'),
        'generatedCopyFailedPrefix' => __('Link generated. Could not copy automatically:', 'fulltimeforce-link-shortener'),
        'linkIdRequired' => __('Link ID required', 'fulltimeforce-link-shortener'),
        'originalUrlRequired' => __('Original URL required', 'fulltimeforce-link-shortener'),
        'confirmRegenerate' => __('Generate a new short link? The current link will keep working.', 'fulltimeforce-link-shortener'),
        'cancelButton' => __('Cancel', 'fulltimeforce-link-shortener'),
        'rotateModalTitle' => __('Rotate Slug', 'fulltimeforce-link-shortener'),
        'closeModalAria' => __('Close modal', 'fulltimeforce-link-shortener'),
        'rotateModalIntro' => __('Choose how you want to update this link\'s slug:', 'fulltimeforce-link-shortener'),
        'replaceSlug' => __('Replace slug', 'fulltimeforce-link-shortener'),
        'replaceSlugDesc' => __('The previous slug will stop working', 'fulltimeforce-link-shortener'),
        'addAlias' => __('Add alias', 'fulltimeforce-link-shortener'),
        'addAliasDesc' => __('The previous slug will keep working', 'fulltimeforce-link-shortener'),
        'pleaseEnterOriginalUrl' => __('Please enter an original URL.', 'fulltimeforce-link-shortener'),
        'pleaseEnterValidUrl' => __('Please enter a valid URL (must start with http:// or https://).', 'fulltimeforce-link-shortener')
      )
    ));
  }

  /**
   * Añadir nonce para AJAX en el admin
   */
  public function add_admin_nonce()
  {
    // Solo en páginas relevantes
    $screen = get_current_screen();
    if (!$screen) {
      return;
    }

    $relevant_screens = array('ls_link', 'edit-ls_link', 'link-shortener_page_link-shortener-settings', 'edit-page', 'edit-post');

    if (in_array($screen->id, $relevant_screens) || strpos($screen->id, 'link-shortener') !== false || strpos($screen->id, 'edit-') === 0) {
      echo '<input type="hidden" id="ls_admin_nonce" name="ls_admin_nonce" value="' . wp_create_nonce('ls_admin_nonce') . '">';
    }
  }

  /**
   * Enlaces de acción en la página de plugins
   */
  public function plugin_action_links($links)
  {
    $settings_link = '<a href="' . admin_url('admin.php?page=link-shortener-settings') . '">' . __('Settings', 'fulltimeforce-link-shortener') . '</a>';
    $links_link = '<a href="' . admin_url('edit.php?post_type=ls_link') . '">' . __('Links', 'fulltimeforce-link-shortener') . '</a>';

    array_unshift($links, $settings_link, $links_link);

    return $links;
  }

  /**
   * Función de debug para verificar traducciones (solo en modo debug)
   */
  public function debug_translations() {
    if (!is_admin()) return;
    
    $locale = get_locale();
    $textdomain = 'fulltimeforce-link-shortener';
    
    // Test de traducciones
    $test_string = __('Settings', $textdomain);
    $spanish_test = ($test_string === 'Ajustes');
    
    echo "<!-- LS i18n Debug: Locale: $locale, Test: '$test_string', ES: " . ($spanish_test ? 'SI' : 'NO') . " -->";
  }

  /**
   * Flush de reglas de reescritura si es necesario
   */
  public function maybe_flush_rewrite_rules()
  {
    $version = get_option('ls_rewrite_version', '0');

    // Forzar flush en desarrollo
    if (version_compare($version, self::VERSION, '<') || defined('WP_DEBUG') && WP_DEBUG) {
      error_log('LS Debug: Flushing rewrite rules - version check o debug mode');
      flush_rewrite_rules();
      update_option('ls_rewrite_version', self::VERSION);
    }
  }

  /**
   * Activación del plugin
   */
  public function activate()
  {
    // Verificar requisitos mínimos
    if (version_compare(PHP_VERSION, '7.4', '<')) {
      deactivate_plugins(plugin_basename(__FILE__));
      wp_die(__('This plugin requires PHP 7.4 or higher.', 'fulltimeforce-link-shortener'));
    }

    if (version_compare($GLOBALS['wp_version'], '5.0', '<')) {
      deactivate_plugins(plugin_basename(__FILE__));
      wp_die(__('This plugin requires WordPress 5.0 or higher.', 'fulltimeforce-link-shortener'));
    }

    // Crear opciones por defecto
    $this->create_default_options();

    // Crear capabilities personalizadas
    $this->create_capabilities();

    // Forzar carga de clases para registrar CPT
    $this->load_classes();

    // Flush reglas de reescritura
    flush_rewrite_rules();

    // Marcar que necesitamos flush de reglas
    update_option('ls_rewrite_version', self::VERSION);

    // Log de activación
    error_log('Fulltimeforce Link Shortener activado - Versión ' . self::VERSION);
  }

  /**
   * Desactivación del plugin
   */
  public function deactivate()
  {
    // Flush reglas de reescritura para limpiar
    flush_rewrite_rules();

    // Remover capabilities (opcional, comentado para preservar datos)
    // $this->remove_capabilities();

    // Log de desactivación
    error_log('Fulltimeforce Link Shortener desactivado');
  }

  /**
   * Crear opciones por defecto
   */
  private function create_default_options()
  {
    // Prefijo actual
    if (get_option('ls_current_prefix') === false) {
      add_option('ls_current_prefix', '/l/');
    }

    // Historial de prefijos
    if (get_option('ls_prefix_history') === false) {
      add_option('ls_prefix_history', array('/l/'));
    }

    // Slugs reservados (opcional)
    if (get_option('ls_reserved_slugs') === false) {
      $reserved_slugs = array(
        'admin',
        'api',
        'wp-admin',
        'wp-content',
        'wp-includes',
        'feed',
        'rss',
        'rss2',
        'atom',
        'rdf',
        'sitemap',
        'robots'
      );
      add_option('ls_reserved_slugs', $reserved_slugs);
    }

    // Versión del plugin
    update_option('ls_plugin_version', self::VERSION);

    // Fecha de activación
    if (get_option('ls_activation_date') === false) {
      add_option('ls_activation_date', current_time('mysql'));
    }
  }

  /**
   * Crear capabilities personalizadas
   */
  private function create_capabilities()
  {
    // Obtener roles
    $admin = get_role('administrator');
    $editor = get_role('editor');

    // Capabilities para el CPT ls_link
    $caps = array(
      'edit_ls_link',
      'read_ls_link',
      'delete_ls_link',
      'edit_ls_links',
      'edit_others_ls_links',
      'publish_ls_links',
      'read_private_ls_links',
      'delete_ls_links',
      'delete_private_ls_links',
      'delete_published_ls_links',
      'delete_others_ls_links',
      'edit_private_ls_links',
      'edit_published_ls_links'
    );

    // Asignar capabilities
    if ($admin) {
      foreach ($caps as $cap) {
        $admin->add_cap($cap);
      }
    }

    if ($editor) {
      foreach ($caps as $cap) {
        $editor->add_cap($cap);
      }
    }
  }

  /**
   * Remover capabilities (para desinstalación)
   */
  private function remove_capabilities()
  {
    $roles = array('administrator', 'editor');
    $caps = array(
      'edit_ls_link',
      'read_ls_link',
      'delete_ls_link',
      'edit_ls_links',
      'edit_others_ls_links',
      'publish_ls_links',
      'read_private_ls_links',
      'delete_ls_links',
      'delete_private_ls_links',
      'delete_published_ls_links',
      'delete_others_ls_links',
      'edit_private_ls_links',
      'edit_published_ls_links'
    );

    foreach ($roles as $role_name) {
      $role = get_role($role_name);
      if ($role) {
        foreach ($caps as $cap) {
          $role->remove_cap($cap);
        }
      }
    }
  }

  /**
   * Obtener ruta del plugin
   */
  public function get_plugin_path()
  {
    return $this->plugin_path;
  }

  /**
   * Obtener URL del plugin
   */
  public function get_plugin_url()
  {
    return $this->plugin_url;
  }

  /**
   * Obtener versión del plugin
   */
  public function get_version()
  {
    return self::VERSION;
  }
}

/**
 * Función de acceso global al plugin
 */
function fulltimeforce_link_shortener()
{
  return Fulltimeforce_Link_Shortener::get_instance();
}

// Inicializar el plugin
fulltimeforce_link_shortener();

/**
 * Hook de desinstalación
 */
register_uninstall_hook(__FILE__, 'ls_uninstall_plugin');

/**
 * Función de desinstalación
 */
function ls_uninstall_plugin()
{
  // Solo ejecutar si realmente queremos eliminar todos los datos
  if (!defined('WP_UNINSTALL_PLUGIN')) {
    return;
  }

  // Eliminar todas las entradas del CPT
  $posts = get_posts(array(
    'post_type' => 'ls_link',
    'numberposts' => -1,
    'post_status' => 'any'
  ));

  foreach ($posts as $post) {
    if (is_object($post) && isset($post->ID)) {
      wp_delete_post($post->ID, true);
    }
  }

  // Eliminar opciones del plugin
  delete_option('ls_current_prefix');
  delete_option('ls_prefix_history');
  delete_option('ls_reserved_slugs');
  delete_option('ls_plugin_version');
  delete_option('ls_activation_date');
  delete_option('ls_rewrite_version');

  // Limpiar transients si los hay
  delete_transient('ls_stats_cache');

  // Flush reglas de reescritura
  flush_rewrite_rules();

  // Log de desinstalación
  error_log('Fulltimeforce Link Shortener desinstalado completamente');
}
