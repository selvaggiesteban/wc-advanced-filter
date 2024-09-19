<?php
/**
 * Plugin Name: Filtro Avanzado para WooCommerce
 * Description: Agrega un filtro de búsqueda avanzado para productos de WooCommerce usando un shortcode
 * Version: 2.2
 * Author: Esteban Selvaggi
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

class WC_Advanced_Filter {
    public function __construct() {
        // Registrar scripts y estilos
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Registrar acciones AJAX para el filtrado de productos
        add_action('wp_ajax_filter_products', array($this, 'filter_products'));
        add_action('wp_ajax_nopriv_filter_products', array($this, 'filter_products'));
        add_action('wp_ajax_filter_products_ajax', array($this, 'filter_products_ajax'));
        add_action('wp_ajax_nopriv_filter_products_ajax', array($this, 'filter_products_ajax'));

        // Registrar el shortcode para el filtro
        add_shortcode('wc_advanced_filter', array($this, 'filter_shortcode'));
    }

    /**
     * Encola los scripts y estilos necesarios para el funcionamiento del filtro
     */
    public function enqueue_scripts() {
        // Encolar jQuery UI Slider
        wp_enqueue_script('jquery-ui-slider');
        wp_enqueue_style('jquery-ui-style', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

        // Encolar fuentes de Google
        wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap');

        // JavaScript inline para la funcionalidad del filtro
        $script = "
        jQuery(document).ready(function($) {
            // Inicializar el deslizador de precio
            var minPrice = parseFloat($('#min_price').data('min'));
            var maxPrice = parseFloat($('#max_price').data('max'));

            $('#price-range').slider({
                range: true,
                min: minPrice,
                max: maxPrice,
                values: [minPrice, maxPrice],
                slide: function(event, ui) {
                    $('#min_price').val(ui.values[0]);
                    $('#max_price').val(ui.values[1]);
                    $('#price-range-labels').html('$' + ui.values[0] + ' - $' + ui.values[1]);
                },
                change: function(event, ui) {
                    filterProducts();
                }
            });

            // Inicializar etiquetas de precio
            $('#price-range-labels').html('$' + minPrice + ' - $' + maxPrice);

            // Manejar cambios en los checkboxes y el rango de precios
            $('#wc-advanced-filter-form input[type=\"checkbox\"], #price-range').on('change', function() {
                // Actualizar etiquetas de precio
                var currentMin = $('#min_price').val();
                var currentMax = $('#max_price').val();
                $('#price-range-labels').html('$' + currentMin + ' - $' + currentMax);
            });

            // Función para filtrar productos mediante AJAX
            function filterProducts() {
                var formData = $('#wc-advanced-filter-form').serialize();

                $.ajax({
                    url: '" . admin_url('admin-ajax.php') . "',
                    type: 'POST',
                    data: formData + '&action=filter_products_ajax',
                    success: function(response) {
                        if(response.success) {
                            $('.products').html(response.data.html);
                        }
                    }
                });
            }

            // Manejar el envío del formulario al hacer clic en Aplicar Filtros
            $('#wc-advanced-filter-form').on('submit', function(e) {
                e.preventDefault();
                filterProducts();
            });

            // Manejar el clic en Borrar Filtros
            $('#reset-filters').on('click', function(e) {
                e.preventDefault();
                // Restablecer todos los checkboxes
                $('#wc-advanced-filter-form')[0].reset();
                // Restablecer el deslizador de precio
                $('#price-range').slider('values', 0, minPrice);
                $('#price-range').slider('values', 1, maxPrice);
                $('#price-range-labels').html('$' + minPrice + ' - $' + maxPrice);
                // Filtrar productos nuevamente con los filtros restablecidos
                filterProducts();
            });
        });
        ";

        wp_add_inline_script('jquery-ui-slider', $script);

        // CSS inline para el estilo del filtro
        $styles = "
        /* Estilos generales del formulario de filtro */
        #wc-advanced-filter-form {
            font-family: 'Times New Roman', Times, serif;
            color: #666;
        }
        #wc-advanced-filter-form h3 {
            text-transform: uppercase;
            font-weight: bold;
            font-size: 16px;
            font-family: 'Times New Roman', Times, serif;
        }
        #wc-advanced-filter-form h4 {
            text-transform: uppercase;
            font-weight: bold;
            font-size: 16px;
            font-family: 'Times New Roman', Times, serif;
            margin-bottom: 5px;
        }
        #wc-advanced-filter-form label {
            display: block;
            margin-bottom: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: normal;
        }
        #wc-advanced-filter-form label:first-letter {
            text-transform: uppercase;
        }
        #wc-advanced-filter-form input[type='checkbox'] {
            margin-right: 5px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            width: 16px;
            height: 16px;
            border: none;
            border-radius: 4px;
            background-color: #c2c2c2;
            cursor: pointer;
            vertical-align: middle;
            position: relative;
        }
        #wc-advanced-filter-form input[type='checkbox']:checked::before {
            content: '\\2713';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
        }
        .filter-section {
            margin-bottom: 20px;
        }
        #price-range {
            margin-bottom: 10px;
        }
        #price-range-labels {
            text-align: center;
            margin-bottom: 10px;
        }
        .color-filter-item {
            display: inline-block;
            margin-right: 10px;
            text-align: center;
        }
        .color-filter-item img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-bottom: 5px;
        }
        .taxonomy-divider {
            height: 4px;
            width: 100%;
            margin-bottom: 10px;
            position: relative;
        }
        .taxonomy-divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 50%;
            height: 3px;
            background-color: #146CCC;
        }
        .taxonomy-divider::after {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            width: 50%;
            height: 1px;
            background-color: #333;
        }

        /* Estilos personalizados para el botón Aplicar Filtros */
        #wc-advanced-filter-form .apply-filters-button {
            color: #FFFFFF;
            background-color: #146CCC;
            font-family: 'Times New Roman', Sans-serif;
            font-size: 14px;
            font-weight: 400;
            border-radius: 0px;
            padding: 6px 20px;
            border: none;
            cursor: pointer;
        }

        /* Estilos personalizados para el botón Borrar Filtros */
        #wc-advanced-filter-form .reset-filters-button {
            color: #FFFFFF;
            background-color: #FF0000; /* Puedes cambiar el color de fondo según tu preferencia */
            font-family: 'Times New Roman', Sans-serif;
            font-size: 14px;
            font-weight: 400;
            border-radius: 0px;
            padding: 6px 20px;
            border: none;
            cursor: pointer;
            margin-left: 10px;
        }

        /* Estilos para ajustar el diseño de los botones */
        #wc-advanced-filter-form .buttons-container {
            margin-top: 20px;
            text-align: center;
        }

        /* Opcional: Estilos para agregar un espacio entre los botones */
        #wc-advanced-filter-form .buttons-container button {
            margin: 5px;
        }

        /* Personalización del deslizador de precios */
        /* Cambiar el color del rango */
        .ui-slider-range {
            background: #146CCC;
        }

        /* Personalizar los manejadores del deslizador */
        .ui-slider-handle {
            background: #146CCC;
            border: none;
            border-radius: 50%; /* Hace los manejadores redondos */
            width: 20px;
            height: 20px;
            top: -8px; /* Ajusta la posición vertical */
            cursor: pointer;
        }

        /* Opcional: Cambiar el color al pasar el ratón sobre el manejador */
        .ui-slider-handle:hover {
            background: #0F5BAA;
        }
        ";

        wp_add_inline_style('jquery-ui-style', $styles);
    }

    /**
     * Callback para el shortcode del filtro
     */
    public function filter_shortcode() {
        ob_start();
        $this->display_filter();
        return ob_get_clean();
    }

    /**
     * Muestra el formulario del filtro
     */
    public function display_filter() {
        // Obtener rango de precios
        $price_range = $this->get_price_range();

        // Obtener categorías de producto
        $product_categories = get_terms('product_cat', array('hide_empty' => true));

        // Obtener atributos específicos
        $color_terms = get_terms('pa_color', array('hide_empty' => true));
        $chain_length_terms = get_terms('pa_largo_de_cadena', array('hide_empty' => true)); // Taxonomía corregida
        $bracelet_length_terms = get_terms('pa_largo_de_pulsera', array('hide_empty' => true)); // Taxonomía corregida
        $stones_terms = get_terms('pa_piedras', array('hide_empty' => true));
        $size_terms = get_terms('pa_talle', array('hide_empty' => true));
        $crystal_tone_terms = get_terms('pa_tonos_de_cristal', array('hide_empty' => true));

        // Mostrar el formulario de filtro
        ?>
        <form id="wc-advanced-filter-form">
            <!-- Filtro de precio -->
            <div class="filter-section">
                <h4>Filtrar por Precio</h4>
                <div class="taxonomy-divider"></div>
                <div id="price-range"></div>
                <div id="price-range-labels"></div>
                <input type="hidden" id="min_price" name="min_price" value="<?php echo esc_attr($price_range->min_price); ?>" data-min="<?php echo esc_attr($price_range->min_price); ?>">
                <input type="hidden" id="max_price" name="max_price" value="<?php echo esc_attr($price_range->max_price); ?>" data-max="<?php echo esc_attr($price_range->max_price); ?>">
            </div>

            <!-- Filtro de categorías (Tipo de producto) -->
            <div class="filter-section">
                <h4>Filtrar por Tipo de producto</h4>
                <div class="taxonomy-divider"></div>
                <?php $this->display_category_hierarchy($product_categories); ?>
            </div>

            <!-- Filtro de color -->
            <?php $this->display_attribute_filter('Filtrar por Color', 'pa_color', $color_terms); ?>

            <!-- Filtro de largo de cadena -->
            <?php $this->display_attribute_filter('Filtrar por Largo de cadena', 'pa_largo_de_cadena', $chain_length_terms); ?> <!-- Taxonomía corregida -->

            <!-- Filtro de largo de pulsera -->
            <?php $this->display_attribute_filter('Filtrar por Largo de pulsera', 'pa_largo_de_pulsera', $bracelet_length_terms); ?> <!-- Taxonomía corregida -->

            <!-- Filtro de piedras -->
            <?php $this->display_attribute_filter('Filtrar por Piedras', 'pa_piedras', $stones_terms); ?>

            <!-- Filtro de talle -->
            <?php $this->display_attribute_filter('Filtrar por Talle', 'pa_talle', $size_terms); ?>

            <!-- Filtro de tonos de cristal -->
            <?php $this->display_attribute_filter('Filtrar por Tonos de cristal', 'pa_tonos_de_cristal', $crystal_tone_terms); ?> <!-- Taxonomía corregida -->

            <!-- Contenedor de botones "Aplicar Filtros" y "Borrar Filtros" -->
            <div class="buttons-container">
                <button type="submit" class="apply-filters-button">Aplicar Filtros</button>
                <button type="button" id="reset-filters" class="reset-filters-button">Borrar Filtros</button>
            </div>
        </form>
        <?php
    }

    /**
     * Muestra la jerarquía de categorías
     */
    private function display_category_hierarchy($categories, $parent = 0, $depth = 0) {
        foreach ($categories as $category) {
            if ($category->parent == $parent) {
                $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
                ?>
                <label>
                    <?php echo $indent; ?>
                    <input type="checkbox" name="product_cat[]" value="<?php echo esc_attr($category->term_id); ?>" class="category-filter">
                    <?php echo esc_html($category->name); ?>
                </label>
                <?php
                // Llamada recursiva para mostrar subcategorías
                $this->display_category_hierarchy($categories, $category->term_id, $depth + 1);
            }
        }
    }

    /**
     * Muestra un filtro de atributo
     */
    private function display_attribute_filter($title, $taxonomy, $terms) {
        if (!empty($terms)) : ?>
            <div class="filter-section">
                <h4><?php echo esc_html($title); ?></h4>
                <div class="taxonomy-divider"></div>
                <?php foreach ($terms as $term) : ?>
                    <label>
                        <input type="checkbox" name="attributes[<?php echo esc_attr($taxonomy); ?>][]" value="<?php echo esc_attr($term->term_id); ?>">
                        <?php echo esc_html($term->name); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif;
    }

    /**
     * Obtiene el rango de precios de los productos
     */
    private function get_price_range() {
        global $wpdb;
        $sql = "
            SELECT MIN(CAST(meta_value AS DECIMAL)) AS min_price, MAX(CAST(meta_value AS DECIMAL)) AS max_price
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_price'
        ";
        return $wpdb->get_row($sql);
    }

    /**
     * Maneja la solicitud AJAX para filtrar productos
     */
    public function filter_products() {
        $args = $this->get_filter_args();

        $query = new WP_Query($args);

        ob_start();
        if ($query->have_posts()) {
            woocommerce_product_loop_start();
            while ($query->have_posts()) {
                $query->the_post();
                wc_get_template_part('content', 'product');
            }
            woocommerce_product_loop_end();
        } else {
            echo '<p>No se encontraron productos.</p>';
        }
        $products_html = ob_get_clean();

        wp_reset_postdata();

        wp_send_json_success(array('html' => $products_html));
    }

    /**
     * Maneja la solicitud AJAX para filtrar productos (versión alternativa)
     */
    public function filter_products_ajax() {
        $args = $this->get_filter_args();

        $query = new WP_Query($args);

        ob_start();
        if ($query->have_posts()) {
            woocommerce_product_loop_start();
            while ($query->have_posts()) {
                $query->the_post();
                wc_get_template_part('content', 'product');
            }
            woocommerce_product_loop_end();
        } else {
            echo '<p>No se encontraron productos.</p>';
        }
        $products_html = ob_get_clean();

        wp_reset_postdata();

        wp_send_json_success(array('html' => $products_html));
    }

    /**
     * Construye los argumentos para la consulta de productos basados en los filtros seleccionados
     */
    private function get_filter_args() {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'tax_query' => array(),
            'meta_query' => array(),
        );

        // Filtrar por precio
        if (isset($_POST['min_price']) && isset($_POST['max_price'])) {
            $args['meta_query'][] = array(
                'key' => '_price',
                'value' => array(floatval($_POST['min_price']), floatval($_POST['max_price'])),
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN',
            );
        }

        // Filtrar por atributos (color, largo de cadena, largo de pulsera, piedras, talle, tonos de cristal)
        if (isset($_POST['attributes']) && !empty($_POST['attributes'])) {
            foreach ($_POST['attributes'] as $taxonomy => $terms) {
                if (!empty($terms)) {
                    $args['tax_query'][] = array(
                        'taxonomy' => sanitize_text_field($taxonomy),
                        'field' => 'term_id',
                        'terms' => array_map('intval', $terms),
                        'operator' => 'IN',
                    );
                }
            }
        }

        // Filtrar por categorías (Tipo de producto)
        if (isset($_POST['product_cat']) && !empty($_POST['product_cat'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => array_map('intval', $_POST['product_cat']),
                'operator' => 'IN',
            );
        }

        // Asegurarse de que 'tax_query' funcione correctamente
        if (count($args['tax_query']) > 1) {
            $args['tax_query']['relation'] = 'AND';
        }

        return $args;
    }

    /**
     * Inicializa el plugin
     */
    public function init_plugin() {
        new WC_Advanced_Filter();
    }
}

// Inicializar el plugin
new WC_Advanced_Filter();
