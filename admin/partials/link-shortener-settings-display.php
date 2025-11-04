<?php
/**
 * Vista para la página de ajustes del plugin
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
    
    <form method="post" action="options.php">
        <?php
        settings_fields('link_shortener_settings');
        do_settings_sections('link_shortener_settings');
        ?>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="base_url">URL Base</label>
                    </th>
                    <td>
                        <input type="url" id="base_url" name="link_shortener_base_url" class="regular-text" 
                               value="<?php echo esc_attr(get_option('link_shortener_base_url', home_url())); ?>">
                        <p class="description">URL base para tus enlaces cortos (ej: https://tudominio.com)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="slug_length">Longitud del Slug</label>
                    </th>
                    <td>
                        <input type="number" id="slug_length" name="link_shortener_slug_length" min="3" max="10" 
                               value="<?php echo esc_attr(get_option('link_shortener_slug_length', 6)); ?>">
                        <p class="description">Número de caracteres para los slugs generados automáticamente</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="tracking_enabled">Habilitar Seguimiento</label>
                    </th>
                    <td>
                        <input type="checkbox" id="tracking_enabled" name="link_shortener_tracking_enabled" value="1" 
                               <?php checked(get_option('link_shortener_tracking_enabled', 1)); ?>>
                        <p class="description">Registrar estadísticas de clics en los enlaces</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="redirect_type">Tipo de Redirección</label>
                    </th>
                    <td>
                        <select id="redirect_type" name="link_shortener_redirect_type">
                            <option value="301" <?php selected(get_option('link_shortener_redirect_type', '301'), '301'); ?>>
                                301 - Redirección Permanente
                            </option>
                            <option value="302" <?php selected(get_option('link_shortener_redirect_type', '301'), '302'); ?>>
                                302 - Redirección Temporal
                            </option>
                        </select>
                        <p class="description">Tipo de redirección HTTP a utilizar</p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>