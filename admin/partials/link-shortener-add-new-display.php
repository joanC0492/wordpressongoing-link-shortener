<?php
/**
 * Vista para agregar un nuevo enlace corto
 *
 * @package    Fulltimeforce_Link_Shortener
 * @subpackage Fulltimeforce_Link_Shortener/admin/partials
 */

// Prevenir acceso directo al archivo
if (!defined('ABSPATH')) {
  exit;
}

// Procesar el formulario si se envió
if (isset($_POST['submit']) && isset($_POST['link_shortener_nonce']) && wp_verify_nonce(wp_unslash($_POST['link_shortener_nonce']), 'link_shortener_add_new')) {
  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized with esc_url_raw() below
  $original_url = isset($_POST['original_url']) ? esc_url_raw(wp_unslash($_POST['original_url'])) : '';
  $short_code = isset($_POST['short_code']) ? sanitize_text_field(wp_unslash($_POST['short_code'])) : '';
  $category = isset($_POST['category']) ? absint($_POST['category']) : 0;
  $description = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';
  
  // Validar que la URL original no esté vacía
  if (empty($original_url)) {
    $error_message = 'La URL original es requerida.';
  } else {
    // Crear el post
    $post_data = array(
      'post_title' => $original_url,
      'post_type' => 'short_link',
      'post_status' => 'publish',
      'post_content' => $description
    );
  
    $post_id = wp_insert_post($post_data);
  
    if ($post_id && !is_wp_error($post_id)) {
      // Guardar metadatos
      update_post_meta($post_id, '_original_url', $original_url);
      update_post_meta($post_id, '_description', $description);
    
    // Generar o usar código corto personalizado
    if (!empty($short_code)) {
      // Verificar que no exista
      // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary for uniqueness validation
      $existing = new WP_Query(array(
        'post_type' => 'short_link',
        'meta_query' => array(
          array(
            'key' => '_short_code',
            'value' => $short_code,
            'compare' => '='
          )
        )
      ));
      
      if ($existing->have_posts()) {
        $error_message = 'El código corto ya existe. Se generó uno automáticamente.';
        $short_code = '';
      }
    }
    
    if (empty($short_code)) {
      // Generar código automático
      $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
      do {
        $short_code = '';
        for ($i = 0; $i < 6; $i++) {
          $short_code .= $characters[wp_rand(0, strlen($characters) - 1)];
        }
        $exists = new WP_Query(array(
          'post_type' => 'short_link',
          'meta_query' => array(
            array(
              'key' => '_short_code',
              'value' => $short_code,
              'compare' => '='
            )
          )
        ));
      } while ($exists->have_posts());
    }
    
    update_post_meta($post_id, '_short_code', $short_code);
    
    // Asignar categoría si se seleccionó
    if ($category > 0) {
      wp_set_post_terms($post_id, array($category), 'link_category');
    }
    
    $success_message = 'Enlace corto creado exitosamente. Código: ' . $short_code;
    $short_url = home_url('/go/' . $short_code);
    } else {
      $error_message = 'Error al crear el enlace corto. Por favor intenta nuevamente.';
    }
  }
}

// Obtener categorías para el dropdown
$categories = get_terms(array(
  'taxonomy' => 'link_category',
  'hide_empty' => false,
));
?>

<div class="wrap">
  <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
  
  <?php if (isset($success_message)) : ?>
    <div class="notice notice-success is-dismissible">
      <p><?php echo esc_html($success_message); ?></p>
      <p>
        <strong>Enlace corto:</strong> 
        <a href="<?php echo esc_url($short_url); ?>" target="_blank"><?php echo esc_html($short_url); ?></a>
        <button type="button" class="button button-small copy-link" data-url="<?php echo esc_attr($short_url); ?>">Copiar</button>
      </p>
    </div>
  <?php endif; ?>
  
  <?php if (isset($error_message)) : ?>
    <div class="notice notice-error is-dismissible">
      <p><?php echo esc_html($error_message); ?></p>
    </div>
  <?php endif; ?>

  <form method="post" action="" id="link_shortener_form">
    <?php wp_nonce_field('link_shortener_add_new', 'link_shortener_nonce'); ?>
    <!-- Campo oculto para el nonce de seguridad -->
    <!-- <input type="hidden" id="link_shortener_nonce" name="link_shortener_nonce" value="a3f9b61a2c" /> -->
    <!-- <input type="hidden" name="_wp_http_referer" value="/wp-admin/admin.php?page=ff-link-shortener-add-new" /> -->
    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row">
            <label for="original_url">URL Original</label>
          </th>
          <td>
            <input type="url" id="original_url" name="original_url" class="regular-text" required>
            <p class="description">Ingresa la URL completa que deseas acortar (ej: https://example.com)</p>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="short_code">Código Corto (Opcional)</label>
          </th>
          <td>
            <input type="text" id="short_code" name="short_code" class="regular-text">
            <p class="description">Deja vacío para generar uno automáticamente</p>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="category">Categoría</label>
          </th>
          <td>
            <select id="category" name="category">
              <option value="">Sin categoría</option>
              <?php if (!empty($categories)) : ?>
                <?php foreach ($categories as $cat) : ?>
                  <option value="<?php echo esc_attr($cat->term_id); ?>">
                    <?php echo esc_html($cat->name); ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
            <p class="description">Selecciona una categoría para organizar tu enlace</p>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="description">Descripción (Opcional)</label>
          </th>
          <td>
            <textarea id="description" name="description" rows="3" class="large-text"></textarea>
            <p class="description">Breve descripción del enlace para tu referencia</p>
          </td>
        </tr>
      </tbody>
    </table>

    <?php submit_button('Crear Enlace Corto'); ?>
  </form>
</div>