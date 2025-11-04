<?php
/**
 * Vista para mostrar todos los enlaces cortos
 *
 * @package    Fulltimeforce_Link_Shortener
 * @subpackage Fulltimeforce_Link_Shortener/admin/partials
 */

// Prevenir acceso directo al archivo
if (!defined('ABSPATH')) {
  exit;
}

// Obtener los enlaces cortos
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
// Parámetros de búsqueda y filtro
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
// Filtro por categoría
$category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';

$args = array(
  'post_type' => 'short_link',
  'posts_per_page' => 20,
  'paged' => $paged,
  'post_status' => 'publish'
);

// Agregar búsqueda si existe
if (!empty($search)) {
  $args['s'] = $search;
}

// Agregar filtro por categoría si existe
if (!empty($category)) {
  $args['tax_query'] = array(
    array(
      'taxonomy' => 'link_category',
      'field' => 'slug',
      'terms' => $category,
    ),
  );
}

$links_query = new WP_Query($args);

// Obtener estadísticas
$total_links = wp_count_posts('short_link');
$active_links = $total_links->publish;
$inactive_links = wp_count_posts('short_link', 'trash')->trash;

// Obtener categorías para el filtro
$categories = get_terms(array(
  'taxonomy' => 'link_category',
  'hide_empty' => false,
));
?>

<div class="wrap">
  <h1>
    <?= esc_html(get_admin_page_title()); ?>
    <a href="<?= admin_url('admin.php?page=ff-link-shortener-add-new'); ?>" class="page-title-action">
      Agregar nuevo
    </a>
  </h1>

  <!-- Filtros superiores -->
  <ul class="subsubsub">
    <li class="all">
      <a href="<?= admin_url('admin.php?page=ff-link-shortener-all-links'); ?>" class="current">
        Todos <span class="count">(<?php echo $active_links; ?>)</span>
      </a> |
    </li>
    <li class="published">
      <a href="<?= admin_url('admin.php?page=ff-link-shortener-all-links&status=active'); ?>">
        Activos <span class="count">(<?php echo $active_links; ?>)</span>
      </a> |
    </li>
    <li class="trash">
      <a href="<?= admin_url('admin.php?page=ff-link-shortener-all-links&status=inactive'); ?>">
        Inactivos <span class="count">(<?php echo $inactive_links; ?>)</span>
      </a>
    </li>
  </ul>

  <form id="links-filter" method="get">
    <input type="hidden" name="page" value="ff-link-shortener-all-links" />

    <!-- Barra de herramientas superior -->
    <div class="tablenav top">
      <div class="alignleft actions bulkactions">
        <label for="bulk-action-selector-top" class="screen-reader-text">Seleccionar acción en lote</label>
        <select name="action" id="bulk-action-selector-top">
          <option value="-1">Acciones en lote</option>
          <option value="activate">Activar</option>
          <option value="deactivate">Desactivar</option>
          <option value="delete">Eliminar</option>
        </select>
        <input type="submit" id="doaction" class="button action" value="Aplicar">
      </div>

      <div class="alignleft actions">
        <label for="filter-by-category" class="screen-reader-text">Filtrar por categoría</label>
        <select name="category" id="filter-by-category">
          <option value="">Todas las categorías</option>
          <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($category, $cat->slug); ?>>
                <?php echo esc_html($cat->name); ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filtrar">
      </div>

      <div class="alignright actions">
        <label class="screen-reader-text" for="link-search-input">Buscar enlaces:</label>
        <input type="search" id="link-search-input" name="s" value="" placeholder="Buscar enlaces...">
        <input type="submit" id="search-submit" class="button" value="Buscar enlaces">
      </div>

      <div class="tablenav-pages">
        <span class="displaying-num"><?php echo $links_query->found_posts; ?> elementos</span>
        <?php
        // Generar paginación
        $total_pages = $links_query->max_num_pages;
        if ($total_pages > 1):
          $page_links = paginate_links(array(
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'prev_text' => '‹',
            'next_text' => '›',
            'total' => $total_pages,
            'current' => $paged,
            'type' => 'array'
          ));

          if ($page_links) {
            echo '<span class="pagination-links">';
            echo implode('', $page_links);
            echo '</span>';
          }
        endif;
        ?>
      </div>

      <br class="clear">
    </div>

    <table class="wp-list-table widefat fixed striped table-view-list">
      <thead>
        <tr>
          <td id="cb" class="manage-column column-cb check-column">
            <label class="screen-reader-text" for="cb-select-all-1">Seleccionar todo</label>
            <input id="cb-select-all-1" type="checkbox">
          </td>
          <th scope="col" id="title" class="manage-column column-title column-primary sortable desc">
            <a href="<?= admin_url('admin.php?page=ff-link-shortener-all-links&orderby=title&order=asc'); ?>">
              <span>URL Original</span>
              <span class="sorting-indicator"></span>
            </a>
          </th>
          <th scope="col" id="short_url" class="manage-column column-short-url">Enlace Corto</th>
          <th scope="col" id="category" class="manage-column column-category">Categoría</th>
          <th scope="col" id="date" class="manage-column column-date sortable asc">
            <a href="<?= admin_url('admin.php?page=ff-link-shortener-all-links&orderby=date&order=desc'); ?>">
              <span>Fecha</span>
              <span class="sorting-indicator"></span>
            </a>
          </th>
          <th scope="col" id="category" class="manage-column column-category">Acciones</th>
        </tr>
      </thead>
      <tbody id="the-list">
        <?php if ($links_query->have_posts()): ?>
          <?php while ($links_query->have_posts()):
            $links_query->the_post(); ?>
            <?php
            $post_id = get_the_ID();
            $original_url = get_post_meta($post_id, '_original_url', true);
            $short_code = get_post_meta($post_id, '_short_code', true);
            $short_url = home_url('/go/' . $short_code);
            $categories = wp_get_post_terms($post_id, 'link_category');
            ?>
            <tr>
              <th scope="row" class="check-column">
                <input type="checkbox" name="post[]" value="<?php echo $post_id; ?>">
              </th>
              <td class="title column-title has-row-actions column-primary" data-colname="URL Original">
                <strong>
                  <a href="<?php echo get_edit_post_link($post_id); ?>" class="row-title">
                    <?php echo esc_html($original_url ?: get_the_title()); ?>
                  </a>
                </strong>
                <div class="row-actions">
                  <span class="edit">
                    <a href="<?php echo get_edit_post_link($post_id); ?>" aria-label="Editar enlace">Editar</a> |
                  </span>
                  <span class="copy">
                    <a href="#" class="copy-link" data-url="<?php echo esc_attr($short_url); ?>"
                      aria-label="Copiar enlace">Copiar</a> |
                  </span>
                  <span class="view">
                    <a href="<?php echo esc_url($short_url); ?>" target="_blank" aria-label="Probar enlace">Probar</a> |
                  </span>
                  <span class="trash">
                    <a href="<?php echo get_delete_post_link($post_id); ?>" class="submitdelete"
                      aria-label="Mover a papelera">Papelera</a>
                  </span>
                </div>
              </td>
              <td class="short_url column-short_url" data-colname="Enlace Corto">
                <a href="<?php echo esc_url($short_url); ?>" target="_blank">
                  <?php echo esc_html($short_url); ?>
                </a>
                <br>
                <small>
                  <a href="#" class="copy-link" data-url="<?php echo esc_attr($short_url); ?>">
                    📋 Copiar enlace
                  </a>
                </small>
              </td>
              <td class="category column-category" data-colname="Categoría">
                <?php if (!empty($categories)): ?>
                  <?php echo esc_html($categories[0]->name); ?>
                <?php else: ?>
                  <span class="na">—</span>
                <?php endif; ?>
              </td>
              <td class="date column-date" data-colname="Fecha">
                <?php echo get_the_date(); ?>
              </td>
              <td class="actions column-actions" data-colname="Acciones">
                <a href="<?php echo get_edit_post_link($post_id); ?>" class="button button-small">Editar</a>
                <a href="#" class="button button-small copy-link" data-url="<?php echo esc_attr($short_url); ?>">Copiar</a>
                <a href="<?php echo esc_url($short_url); ?>" target="_blank" class="button button-small">Probar</a>
              </td>
            </tr>
          <?php endwhile; ?>
          <?php wp_reset_postdata(); ?>
        <?php else: ?>
          <tr class="no-items">
            <td class="colspanchange" colspan="6">
              <div style="text-align: center; padding: 40px 20px;">
                <p style="font-size: 18px; margin-bottom: 15px;">No se han creado enlaces cortos aún.</p>
                <p style="margin-bottom: 20px; color: #666;">Comienza creando tu primer enlace corto para comenzar a
                  trackear tus URLs.</p>
                <a href="<?= admin_url('admin.php?page=ff-link-shortener-add-new'); ?>"
                  class="button button-primary button-large">
                  <span class="dashicons dashicons-plus-alt" style="vertical-align: middle; margin-right: 5px;"></span>
                  Crear tu primer enlace corto
                </a>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr>
          <td class="manage-column column-cb check-column">
            <label class="screen-reader-text" for="cb-select-all-2">Seleccionar todo</label>
            <input id="cb-select-all-2" type="checkbox">
          </td>
          <th scope="col" class="manage-column column-title column-primary sortable desc">
            <a href="<?= admin_url('admin.php?page=ff-link-shortener-all-links&orderby=title&order=asc'); ?>">
              <span>URL Original</span>
              <span class="sorting-indicator"></span>
            </a>
          </th>
          <th scope="col" class="manage-column column-short-url">Enlace Corto</th>
          <th scope="col" class="manage-column column-category">Categoría</th>
          <th scope="col" class="manage-column column-date sortable asc">
            <a href="<?= admin_url('admin.php?page=ff-link-shortener-all-links&orderby=date&order=desc'); ?>">
              <span>Fecha</span>
              <span class="sorting-indicator"></span>
            </a>
          </th>
          <th scope="col" class="manage-column column-category">Acciones</th>
        </tr>
      </tfoot>
    </table>

    <!-- Barra de herramientas inferior -->
    <div class="tablenav bottom">
      <div class="alignleft actions bulkactions">
        <label for="bulk-action-selector-bottom" class="screen-reader-text">Seleccionar acción en lote</label>
        <select name="action2" id="bulk-action-selector-bottom">
          <option value="-1">Acciones en lote</option>
          <option value="activate">Activar</option>
          <option value="deactivate">Desactivar</option>
          <option value="delete">Eliminar</option>
        </select>
        <input type="submit" id="doaction2" class="button action" value="Aplicar">
      </div>

      <div class="tablenav-pages">
        <span class="displaying-num">0 elementos</span>
        <span class="pagination-links">
          <span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
          <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
          <span class="paging-input">
            <label for="current-page-selector-bottom" class="screen-reader-text">Página actual</label>
            <input class="current-page" id="current-page-selector-bottom" type="text" name="paged" value="1" size="1">
            <span class="tablenav-paging-text"> de <span class="total-pages">1</span></span>
          </span>
          <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
          <span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
        </span>
      </div>

      <br class="clear">
    </div>

  </form>
</div>