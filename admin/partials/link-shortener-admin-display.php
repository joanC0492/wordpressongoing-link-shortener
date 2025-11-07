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
  <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

  <div class="notice notice-info">
    <p><?php echo esc_html__('Welcome to the main Link Shortener configuration panel.', 'link-shortener-wordpressongoing'); ?></p>
  </div>

  <div class="card">
    <h2><?php echo esc_html__('Plugin Summary', 'link-shortener-wordpressongoing'); ?></h2>
    <p><?php echo esc_html__('This plugin allows you to create custom short links', 'link-shortener-wordpressongoing'); ?></p>
    <!-- <p>Este plugin te permite crear enlaces cortos personalizados con las siguientes características:</p> -->
    <!-- <ul>
      <li>Gestión completa de enlaces cortos</li>
      <li>Organización por categorías</li>
      <li>Estadísticas de clics</li>
      <li>Configuraciones personalizables</li>
    </ul> -->
  </div>

  <div class="card">
    <h2><?php echo esc_html__('Quick Access', 'link-shortener-wordpressongoing'); ?></h2>
    <p>
      <a href="<?php echo esc_url(admin_url('admin.php?page=ff-link-shortener-all-links')); ?>" class="button button-primary">
        <?php echo esc_html__('View All Links', 'link-shortener-wordpressongoing'); ?>
      </a>
      <a href="<?php echo esc_url(admin_url('admin.php?page=ff-link-shortener-add-new')); ?>" class="button">
        <?php echo esc_html__('Add New Link', 'link-shortener-wordpressongoing'); ?>
      </a>
      <a href="<?php echo esc_url(admin_url('admin.php?page=ff-link-shortener-categories')); ?>" class="button">
        <?php echo esc_html__('Manage Categories', 'link-shortener-wordpressongoing'); ?>
      </a>
    </p>
  </div>
</div>