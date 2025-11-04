<?php
/**
 * Proporciona una vista del área de administración para el plugin
 *
 * @package    Fulltimeforce_Link_Shortener
 * @subpackage Fulltimeforce_Link_Shortener/admin/partials
 */

// Prevenir acceso directo al archivo
if (!defined('ABSPATH')) {
  exit;
}
?>

<div class="wrap">
  <h1><?= esc_html(get_admin_page_title()); ?></h1>

  <div class="notice notice-info">
    <p><?php _e('Welcome to the main Link Shortener configuration panel.', 'fulltimeforce-link-shortener'); ?></p>
  </div>

  <div class="card">
    <h2><?php _e('Plugin Summary', 'fulltimeforce-link-shortener'); ?></h2>
    <p><?php _e('This plugin allows you to create custom short links', 'fulltimeforce-link-shortener'); ?></p>
    <!-- <p>Este plugin te permite crear enlaces cortos personalizados con las siguientes características:</p> -->
    <!-- <ul>
      <li>Gestión completa de enlaces cortos</li>
      <li>Organización por categorías</li>
      <li>Estadísticas de clics</li>
      <li>Configuraciones personalizables</li>
    </ul> -->
  </div>

  <div class="card">
    <h2><?php _e('Quick Access', 'fulltimeforce-link-shortener'); ?></h2>
    <p>
      <a href="<?= admin_url('admin.php?page=ff-link-shortener-all-links'); ?>" class="button button-primary">
        <?php _e('View All Links', 'fulltimeforce-link-shortener'); ?>
      </a>
      <a href="<?= admin_url('admin.php?page=ff-link-shortener-add-new'); ?>" class="button">
        <?php _e('Add New Link', 'fulltimeforce-link-shortener'); ?>
      </a>
      <a href="<?= admin_url('admin.php?page=ff-link-shortener-categories'); ?>" class="button">
        <?php _e('Manage Categories', 'fulltimeforce-link-shortener'); ?>
      </a>
    </p>
  </div>
</div>