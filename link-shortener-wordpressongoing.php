<?php
/**
 * Plugin Name: Link Shortener by WP Ongoing
 * Description: A complete WordPress plugin for creating and managing shortened links with customizable prefixes
 * Version: 1.0.0
 * Author: Wordpress Ongoing
 * Author URI: https://wordpressongoing.com
 * Text Domain: link-shortener-wordpressongoing
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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
    $locale = apply_filters('plugin_locale', determine_locale(), 'link-shortener-wordpressongoing');

    // Forzar carga de traducciones
    unload_textdomain('link-shortener-wordpressongoing');

    // Cargar desde idiomas del plugin
    $loaded = load_textdomain(
      'link-shortener-wordpressongoing',
      $this->plugin_path . 'languages/link-shortener-wordpressongoing-' . $locale . '.mo'
    );

    // Si no se cargó, intentar con formato corto (es_ES.mo en lugar de link-shortener-wordpressongoing-es_ES.mo)
    if (!$loaded) {
      $loaded = load_textdomain(
        'link-shortener-wordpressongoing',
        $this->plugin_path . 'languages/' . $locale . '.mo'
      );
    }

    // Fallback a load_plugin_textdomain
    if (!$loaded) {
      load_plugin_textdomain(
        'link-shortener-wordpressongoing',
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
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo para determinar si cargar scripts
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
        'confirmDelete' => __('Are you sure you want to delete this link? This action cannot be undone.', 'link-shortener-wordpressongoing'),
        'confirmDeleteAlias' => __('Delete this alias? The link will stop working.', 'link-shortener-wordpressongoing'),
        'copySuccess' => __('Copied!', 'link-shortener-wordpressongoing'),
        'copyError' => __('Copy error', 'link-shortener-wordpressongoing'),
        'generating' => __('Generating...', 'link-shortener-wordpressongoing'),
        'rotating' => __('Rotating...', 'link-shortener-wordpressongoing'),
        'deleting' => __('Deleting...', 'link-shortener-wordpressongoing'),
        'errorPrefix' => __('Error:', 'link-shortener-wordpressongoing'),
        'successPrefix' => __('Success:', 'link-shortener-wordpressongoing'),
        'errorIncompleteData' => __('Incomplete data', 'link-shortener-wordpressongoing'),
        'errorNoNonce' => __('Could not obtain security token', 'link-shortener-wordpressongoing'),
        'connectionError' => __('Connection error', 'link-shortener-wordpressongoing'),
        'copyFallbackError' => __('Copy failed. Select and copy manually:', 'link-shortener-wordpressongoing'),
        'linkCopiedPrefix' => __('Link copied:', 'link-shortener-wordpressongoing'),
        'generateShortLink' => __('Generate short link', 'link-shortener-wordpressongoing'),
        'noUrlToCopy' => __('No URL to copy', 'link-shortener-wordpressongoing'),
        'urlMustStartWithHttp' => __('URL must start with http:// or https:// and include a valid domain', 'link-shortener-wordpressongoing'),
        'domainInvalid' => __('Domain does not seem valid', 'link-shortener-wordpressongoing'),
        'errorUrlValidation' => __('Error validating URL', 'link-shortener-wordpressongoing'),
        'errorUrlValidationConnection' => __('Connection error while validating URL', 'link-shortener-wordpressongoing'),
        'cannotSaveSlugInUse' => __('Cannot save: the slug is already in use. Please choose another slug.', 'link-shortener-wordpressongoing'),
        'confirmMoveToTrash' => __('Are you sure you want to move this link to trash?', 'link-shortener-wordpressongoing')
        ,
        // Additional strings used in JS UI
        'rotateSlug' => __('Rotate Slug', 'link-shortener-wordpressongoing'),
        'delete' => __('Delete', 'link-shortener-wordpressongoing'),
        'regenerate' => __('Regenerate', 'link-shortener-wordpressongoing'),
        'regenerating' => __('Regenerating...', 'link-shortener-wordpressongoing'),
        'generatedAndCopiedPrefix' => __('Link generated and copied to clipboard:', 'link-shortener-wordpressongoing'),
        'generatedCopyFailedPrefix' => __('Link generated. Could not copy automatically:', 'link-shortener-wordpressongoing'),
        'linkIdRequired' => __('Link ID required', 'link-shortener-wordpressongoing'),
        'originalUrlRequired' => __('Original URL required', 'link-shortener-wordpressongoing'),
        'confirmRegenerate' => __('Generate a new short link? The current link will keep working.', 'link-shortener-wordpressongoing'),
        'cancelButton' => __('Cancel', 'link-shortener-wordpressongoing'),
        'rotateModalTitle' => __('Rotate Slug', 'link-shortener-wordpressongoing'),
        'closeModalAria' => __('Close modal', 'link-shortener-wordpressongoing'),
        'rotateModalIntro' => __('Choose how you want to update this link\'s slug:', 'link-shortener-wordpressongoing'),
        'replaceSlug' => __('Replace slug', 'link-shortener-wordpressongoing'),
        'replaceSlugDesc' => __('The previous slug will stop working', 'link-shortener-wordpressongoing'),
        'addAlias' => __('Add alias', 'link-shortener-wordpressongoing'),
        'addAliasDesc' => __('The previous slug will keep working', 'link-shortener-wordpressongoing'),
        'newSlugOptional' => __('New slug (optional):', 'link-shortener-wordpressongoing'),
        'leaveEmptyAutoGenerate' => __('Leave empty to auto-generate', 'link-shortener-wordpressongoing'),
        'slugFormatError' => __('The slug can only contain letters, numbers, hyphens and underscores', 'link-shortener-wordpressongoing'),
        'slugValidationError' => __('Error validating slug', 'link-shortener-wordpressongoing'),
        'slugValidationConnectionError' => __('Connection error while validating slug', 'link-shortener-wordpressongoing'),
        'suggestionPrefix' => __('Suggestion:', 'link-shortener-wordpressongoing'),
        'pleaseEnterOriginalUrl' => __('Please enter an original URL.', 'link-shortener-wordpressongoing'),
        'pleaseEnterValidUrl' => __('Please enter a valid URL (must start with http:// or https://).', 'link-shortener-wordpressongoing')
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
      echo '<input type="hidden" id="ls_admin_nonce" name="ls_admin_nonce" value="' . esc_attr(wp_create_nonce('ls_admin_nonce')) . '">';
    }
  }

  /**
   * Enlaces de acción en la página de plugins
   */
  public function plugin_action_links($links)
  {
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=link-shortener-settings')) . '">' . esc_html__('Settings', 'link-shortener-wordpressongoing') . '</a>';
    $links_link = '<a href="' . esc_url(admin_url('edit.php?post_type=ls_link')) . '">' . esc_html__('Links', 'link-shortener-wordpressongoing') . '</a>';

    array_unshift($links, $settings_link, $links_link);

    return $links;
  }

  /**
   * Función de debug para verificar traducciones (solo en modo debug)
   */
  public function debug_translations()
  {
    if (!is_admin())
      return;

    $locale = get_locale();

    // Test de traducciones
    $test_string = __('Settings', 'link-shortener-wordpressongoing');
    $spanish_test = ($test_string === 'Ajustes');

    echo '<!-- LS i18n Debug: Locale: ' . esc_html($locale) . ', Test: \'' . esc_html($test_string) . '\', ES: ' . ($spanish_test ? 'SI' : 'NO') . ' -->';
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
      wp_die(esc_html__('This plugin requires PHP 7.4 or higher.', 'link-shortener-wordpressongoing'));
    }

    if (version_compare($GLOBALS['wp_version'], '5.0', '<')) {
      deactivate_plugins(plugin_basename(__FILE__));
      wp_die(esc_html__('This plugin requires WordPress 5.0 or higher.', 'link-shortener-wordpressongoing'));
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
