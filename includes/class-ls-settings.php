<?php
/**
 * Clase para la página de ajustes del plugin
 * 
 * Maneja la configuración de prefijos y otras opciones
 */

if (!defined('ABSPATH')) {
  exit;
}

define('HIDDEN', true);
class LS_Settings
{

  /**
   * Constructor
   */
  public function __construct()
  {
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_init', array($this, 'init_settings'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

    // Arreglar el menú activo para páginas de edición de ls_link
    add_filter('parent_file', array($this, 'fix_menu_highlight'));
    add_filter('submenu_file', array($this, 'fix_submenu_highlight'));
  }

  /**
   * Añade el menú de administración
   */
  public function add_admin_menu()
  {
    // Menú principal
    add_menu_page(
      __('Link Shortener', 'link-shortener-wordpressongoing'),
      __('Link Shortener', 'link-shortener-wordpressongoing'),
      'manage_options',
      'link-shortener',
      array($this, 'admin_page'),
      'dashicons-admin-links',
      30
    );

    // Submenús
    add_submenu_page(
      'link-shortener',
      __('All Links', 'link-shortener-wordpressongoing'),
      __('All Links', 'link-shortener-wordpressongoing'),
      'edit_posts',
      'edit.php?post_type=ls_link'
    );

    add_submenu_page(
      'link-shortener',
      __('Add New', 'link-shortener-wordpressongoing'),
      __('Add New', 'link-shortener-wordpressongoing'),
      'edit_posts',
      'post-new.php?post_type=ls_link'
    );

    add_submenu_page(
      'link-shortener',
      __('Settings', 'link-shortener-wordpressongoing'),
      __('Settings', 'link-shortener-wordpressongoing'),
      'manage_options',
      'link-shortener-settings',
      array($this, 'settings_page')
    );

    // Remover el primer submenu duplicado
    remove_submenu_page('link-shortener', 'link-shortener');
  }

  /**
   * Página principal del admin (redirige a la lista)
   */
  public function admin_page()
  {
    wp_redirect(admin_url('edit.php?post_type=ls_link'));
    exit;
  }

  /**
   * Inicializa las configuraciones
   */
  public function init_settings()
  {
    register_setting(
      'ls_settings_group',
      'ls_current_prefix',
      array(
        'sanitize_callback' => array($this, 'sanitize_prefix'),
        'default' => '/l/'
      )
    );

    add_settings_section(
      'ls_general_section',
      __('General Settings', 'link-shortener-wordpressongoing'),
      array($this, 'general_section_callback'),
      'ls_settings'
    );

    add_settings_field(
      'ls_current_prefix',
      __('Short link prefix', 'link-shortener-wordpressongoing'),
      array($this, 'prefix_field_callback'),
      'ls_settings',
      'ls_general_section'
    );

    // Inicializar opciones por defecto si no existen
    if (get_option('ls_current_prefix') === false) {
      add_option('ls_current_prefix', '/l/');
    }

    if (get_option('ls_prefix_history') === false) {
      add_option('ls_prefix_history', array('/l/'));
    }
  }

  /**
   * Página de ajustes
   */
  public function settings_page()
  {
    $current_prefix = get_option('ls_current_prefix', '/l/');
    $prefix_history = get_option('ls_prefix_history', array('/l/'));
    $stats = $this->get_link_stats();

    ?>
    <div class="wrap">
      <h1><?php echo esc_html__('Link Shortener Settings', 'link-shortener-wordpressongoing'); ?></h1>

      <?php settings_errors(); ?>

      <div class="ls-settings-container">
        <div class="ls-settings-main">
          <form method="post" action="options.php">
            <?php
            settings_fields('ls_settings_group');
            do_settings_sections('ls_settings');
            ?>

            <div class="ls-prefix-preview">
              <h4><?php echo esc_html__('Link preview:', 'link-shortener-wordpressongoing'); ?></h4>
              <code id="ls-preview-url"><?php echo esc_html(home_url() . $current_prefix . 'example'); ?></code>
            </div>

            <?php submit_button(__('Save Settings', 'link-shortener-wordpressongoing')); ?>
          </form>

          <?php if (HIDDEN != true): ?>
            <?php if (count($prefix_history) > 1): ?>
              <div class="ls-prefix-history">
                <h3><?php echo esc_html__('Prefix History', 'link-shortener-wordpressongoing'); ?></h3>
                <p class="description">
                  <?php echo esc_html__('All previously used prefixes remain active to preserve existing links. New links will use the current prefix.', 'link-shortener-wordpressongoing'); ?>
                </p>
                <ul class="ls-prefix-list">
                  <?php foreach ($prefix_history as $prefix): ?>
                    <li>
                      <code><?php echo esc_html($prefix); ?></code>
                      <?php if ($prefix === $current_prefix): ?>
                        <span class="ls-current-badge"><?php echo esc_html__('Current', 'link-shortener-wordpressongoing'); ?></span>
                      <?php endif; ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>

        <?php if (HIDDEN != true): ?>
          <div class="ls-settings-sidebar">
            <div class="ls-stats-widget">
              <h3><?php echo esc_html__('Statistics', 'link-shortener-wordpressongoing'); ?></h3>
              <div class="ls-stat-item">
                <span class="ls-stat-number"><?php echo number_format($stats['total_links']); ?></span>
                <span class="ls-stat-label"><?php echo esc_html__('Links created', 'link-shortener-wordpressongoing'); ?></span>
              </div>
              <div class="ls-stat-item">
                <span class="ls-stat-number"><?php echo count($prefix_history); ?></span>
                <span class="ls-stat-label"><?php echo esc_html__('Prefixes used', 'link-shortener-wordpressongoing'); ?></span>
              </div>
            </div>

            <div class="ls-help-widget">
              <h3><?php echo esc_html__('Help', 'link-shortener-wordpressongoing'); ?></h3>
              <ul>
                <li><strong><?php echo esc_html__('Prefix:', 'link-shortener-wordpressongoing'); ?></strong>
                  <?php echo esc_html__('Only enter the prefix without slashes (e.g., l, short, go)', 'link-shortener-wordpressongoing'); ?>
                </li>
                <li><strong><?php echo esc_html__('Changes:', 'link-shortener-wordpressongoing'); ?></strong>
                  <?php echo esc_html__('Existing links will keep their original prefix', 'link-shortener-wordpressongoing'); ?>
                </li>
                <li><strong><?php echo esc_html__('Example:', 'link-shortener-wordpressongoing'); ?></strong>
                  <?php echo esc_html__('With prefix "l" the link will be domain.com/l/slug', 'link-shortener-wordpressongoing'); ?>
                </li>
              </ul>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <style>
      .ls-settings-container {
        display: flex;
        gap: 30px;
        margin-top: 20px;
      }

      .ls-settings-main {
        flex: 2;
      }

      .ls-settings-sidebar {
        flex: 1;
      }

      .ls-prefix-preview {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        padding: 15px;
        margin: 20px 0;
        border-radius: 4px;
      }

      .ls-prefix-preview h4 {
        margin: 0 0 10px 0;
        color: #495057;
      }

      .ls-prefix-preview code {
        font-size: 14px;
        font-weight: bold;
        color: #007cba;
      }

      .ls-prefix-history {
        margin-top: 30px;
        padding: 20px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
      }

      .ls-prefix-list {
        list-style: none;
        padding: 0;
        margin: 15px 0 0 0;
      }

      .ls-prefix-list li {
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .ls-prefix-list li:last-child {
        border-bottom: none;
      }

      .ls-current-badge {
        background: #00a32a;
        color: white;
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
      }

      .ls-stats-widget,
      .ls-help-widget {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 20px;
        margin-bottom: 20px;
      }

      .ls-stats-widget h3,
      .ls-help-widget h3 {
        margin: 0 0 15px 0;
        color: #495057;
      }

      .ls-stat-item {
        display: flex;
        flex-direction: column;
        margin-bottom: 15px;
      }

      .ls-stat-number {
        font-size: 24px;
        font-weight: bold;
        color: #007cba;
        line-height: 1;
      }

      .ls-stat-label {
        font-size: 13px;
        color: #666;
        margin-top: 2px;
      }

      .ls-help-widget ul {
        margin: 0;
        padding-left: 15px;
      }

      .ls-help-widget li {
        margin-bottom: 8px;
        font-size: 13px;
        line-height: 1.4;
      }
    </style>
    <?php
  }

  /**
   * Callback para la sección general
   */
  public function general_section_callback()
  {
    echo '<p>' . esc_html__('Configure the prefix used for new short links.', 'link-shortener-wordpressongoing') . '</p>';
  }

  /**
   * Campo para el prefijo
   */
  public function prefix_field_callback()
  {
    $current_prefix = get_option('ls_current_prefix', '/l/');
    // Remover los slashes para mostrar solo el prefijo
    $display_prefix = trim($current_prefix, '/');
    $home_url = home_url('/');

    ?>
    <div class="ls-prefix-input-container">
      <span class="ls-domain-preview"><?php echo esc_html(rtrim($home_url, '/')); ?>/</span>
      <input type="text" id="ls_current_prefix" name="ls_current_prefix" value="<?php echo esc_attr($display_prefix); ?>"
        class="regular-text" placeholder="l" pattern="^[a-zA-Z0-9\-_]+$" onkeypress="return lsValidateChar(event)"
        oninput="lsValidateInput(this)" />
      <span class="ls-slug-preview">/slug</span>
    </div>
    <p class="description">
      <?php echo esc_html__('Only enter the prefix (e.g., l, short, go). Only letters, numbers, dashes (-) and underscores (_) are allowed.', 'link-shortener-wordpressongoing'); ?>
    </p>
    <div id="ls-prefix-validation" class="ls-validation-message"></div>

    <style>
      .ls-prefix-input-container {
        display: inline-flex;
        align-items: center;
        border: 1px solid #ddd;
        border-radius: 4px;
        overflow: hidden;
        background: #fff;
      }

      .ls-domain-preview {
        background: #f8f9fa;
        padding: 8px 12px;
        border-right: 1px solid #ddd;
        font-family: monospace;
        font-size: 13px;
        color: #495057;
      }

      #ls_current_prefix {
        border: none !important;
        box-shadow: none !important;
        margin: 0 !important;
        font-family: monospace;
        font-size: 13px;
        min-width: 100px;
      }

      .ls-slug-preview {
        background: #e9ecef;
        padding: 8px 12px;
        border-left: 1px solid #ddd;
        font-family: monospace;
        font-size: 13px;
        color: #6c757d;
        font-style: italic;
      }

      .ls-validation-message {
        margin-top: 5px;
        font-size: 12px;
      }

      .ls-validation-message.error {
        color: #dc3545;
      }

      .ls-validation-message.success {
        color: #28a745;
      }
    </style>

    <script>
      function lsValidateChar(event) {
        const char = String.fromCharCode(event.which);
        const allowedPattern = /[a-zA-Z0-9\-_]/;

        if (!allowedPattern.test(char)) {
          event.preventDefault();
          return false;
        }
        return true;
      }

      function lsValidateInput(input) {
        const value = input.value;

        const S = (typeof lsSettings !== 'undefined' && lsSettings.strings) ? lsSettings.strings : {};

        // Limpiar caracteres no válidos que puedan haber sido pegados
        const cleanValue = value.replace(/[^a-zA-Z0-9\-_]/g, '');
        if (cleanValue !== value) {
          input.value = cleanValue;
        }

        // Validar longitud y contenido
        if (cleanValue.length === 0) {
          validationDiv.innerHTML = '';
          validationDiv.className = 'ls-validation-message';
        } else if (cleanValue.length < 1) {
          validationDiv.innerHTML = (S.prefixTooShort || 'Prefix must be at least 1 character');
          validationDiv.className = 'ls-validation-message error';
        } else if (cleanValue.length > 20) {
          validationDiv.innerHTML = (S.prefixTooLong || 'Prefix cannot be more than 20 characters');
          validationDiv.className = 'ls-validation-message error';
        } else if (!/^[a-zA-Z0-9\-_]+$/.test(cleanValue)) {
          validationDiv.innerHTML = (S.prefixAllowedChars || 'Only letters, numbers, dashes (-) and underscores (_) are allowed');
          validationDiv.className = 'ls-validation-message error';
        } else {
          validationDiv.innerHTML = (S.prefixValid || 'Valid prefix');
          validationDiv.className = 'ls-validation-message success';
        }

        // La actualización de vista previa se maneja en admin.js
      }
    </script>
    <?php
  }

  /**
   * Sanitiza el prefijo
   */
  public function sanitize_prefix($prefix)
  {
    // Sanitizar
    $prefix = sanitize_text_field($prefix);

    // Remover slashes si los tiene para trabajar solo con el prefijo
    $clean_prefix = trim($prefix, '/');

    // Limpiar caracteres no válidos
    $clean_prefix = preg_replace('/[^a-zA-Z0-9\-_]/', '', $clean_prefix);

    // Validar que no esté vacío
    if (empty($clean_prefix)) {
      add_settings_error(
        'ls_current_prefix',
        'empty_prefix',
        __('Prefix cannot be empty.', 'link-shortener-wordpressongoing'),
        'error'
      );
      return get_option('ls_current_prefix', '/l/');
    }

    // Validar longitud
    if (strlen($clean_prefix) > 20) {
      add_settings_error(
        'ls_current_prefix',
        'prefix_too_long',
        __('Prefix cannot be more than 20 characters.', 'link-shortener-wordpressongoing'),
        'error'
      );
      return get_option('ls_current_prefix', '/l/');
    }

    // Validar formato final
    if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $clean_prefix)) {
      add_settings_error(
        'ls_current_prefix',
        'invalid_prefix',
        __('Prefix must contain only letters, numbers, dashes (-) and underscores (_).', 'link-shortener-wordpressongoing'),
        'error'
      );
      return get_option('ls_current_prefix', '/l/');
    }

    // Agregar slashes automáticamente
    $formatted_prefix = '/' . $clean_prefix . '/';

    // Obtener prefijo actual y historial
    $old_prefix = get_option('ls_current_prefix', '/l/');
    $prefix_history = get_option('ls_prefix_history', array('/l/'));

    // Si es diferente al actual, actualizar historial
    if ($formatted_prefix !== $old_prefix) {
      if (!in_array($formatted_prefix, $prefix_history)) {
        $prefix_history[] = $formatted_prefix;
        update_option('ls_prefix_history', $prefix_history);
      }

      // Flush rewrite rules
      add_action('admin_init', function () {
        flush_rewrite_rules();
      });

      add_settings_error(
        'ls_current_prefix',
        'prefix_updated',
        sprintf(
          /* translators: %s: the new prefix value */
          __('Prefix updated to %s. Existing links continue to work with their original prefixes.', 'link-shortener-wordpressongoing'),
          $formatted_prefix
        ),
        'success'
      );
    }

    return $formatted_prefix;
  }

  /**
   * Obtiene estadísticas de enlaces
   */
  private function get_link_stats()
  {
    global $wpdb;

    // Total de enlaces
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Statistics query for dashboard display
    $total = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'ls_link' 
            AND post_status = 'publish'
        ");

    return array(
      'total_links' => (int) $total,
    );
  }

  /**
   * Cargar scripts del admin
   */
  public function enqueue_scripts($hook)
  {
    if (strpos($hook, 'link-shortener') === false) {
      return;
    }

    wp_enqueue_script(
      'ls-settings-js',
      plugin_dir_url(dirname(__FILE__)) . 'assets/admin.js',
      array('jquery'),
      '1.0.0',
      true
    );

    wp_localize_script('ls-settings-js', 'lsSettings', array(
      'homeUrl' => home_url('/'),
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('ls_settings_nonce'),
      'strings' => array(
        'prefixTooShort' => __('Prefix must be at least 1 character', 'link-shortener-wordpressongoing'),
        'prefixTooLong' => __('Prefix cannot be more than 20 characters', 'link-shortener-wordpressongoing'),
        'prefixAllowedChars' => __('Only letters, numbers, dashes (-) and underscores (_) are allowed', 'link-shortener-wordpressongoing'),
        'prefixValid' => __('Valid prefix', 'link-shortener-wordpressongoing'),
      ),
    ));
  }

  /**
   * Corrige el resaltado del menú principal para páginas del CPT ls_link
   */
  public function fix_menu_highlight($parent_file)
  {
    global $current_screen;

    if (!$current_screen) {
      return $parent_file;
    }

    // Si estamos en cualquier página relacionada con ls_link
    if ($current_screen->post_type === 'ls_link') {
      return 'link-shortener';
    }

    return $parent_file;
  }

  /**
   * Corrige el resaltado del submenú para páginas del CPT ls_link
   */
  public function fix_submenu_highlight($submenu_file)
  {
    global $current_screen;

    if (!$current_screen) {
      return $submenu_file;
    }

    // Para la página de listado de enlaces
    if ($current_screen->id === 'edit-ls_link') {
      return 'edit.php?post_type=ls_link';
    }

    // Para la página de crear nuevo enlace
    if ($current_screen->id === 'ls_link' && $current_screen->action === 'add') {
      return 'post-new.php?post_type=ls_link';
    }

    // Para la página de editar enlace individual
    if ($current_screen->id === 'ls_link' && $current_screen->action !== 'add') {
      return 'edit.php?post_type=ls_link';
    }

    return $submenu_file;
  }
}

// Inicializar la clase
new LS_Settings();
