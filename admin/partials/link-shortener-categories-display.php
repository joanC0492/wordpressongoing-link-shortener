<?php
/**
 * Vista para gestionar las categorías
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

  <div class="row">
    <div class="col-sm-6">
      <div class="card">
        <h2>Agregar Nueva Categoría</h2>
        <form method="post" action="">
          <?php wp_nonce_field('link_shortener_add_category', 'category_nonce'); ?>

          <table class="form-table">
            <tbody>
              <tr>
                <th scope="row">
                  <label for="category_name">Nombre de la Categoría</label>
                </th>
                <td>
                  <input type="text" id="category_name" name="category_name" class="regular-text" required>
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="category_description">Descripción</label>
                </th>
                <td>
                  <textarea id="category_description" name="category_description" rows="3"
                    class="large-text"></textarea>
                </td>
              </tr>
            </tbody>
          </table>

          <?php submit_button('Agregar Categoría'); ?>
        </form>
      </div>
    </div>

    <div class="col-sm-6">
      <div class="card">
        <h2>Categorías Existentes</h2>
        <table class="wp-list-table widefat fixed striped">
          <thead>
            <tr>
              <th scope="col">Nombre</th>
              <th scope="col">Descripción</th>
              <th scope="col">Enlaces</th>
              <th scope="col">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="4" style="text-align: center; padding: 20px;">
                No hay categorías creadas aún.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<style>
  .row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -10px;
  }

  .col-sm-6 {
    flex: 0 0 50%;
    padding: 0 10px;
  }

  .card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
  }
</style>