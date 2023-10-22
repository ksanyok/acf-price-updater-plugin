<?php
/*
Plugin Name: ACF Price Updater
Description: A plugin to update prices in ACF fields across multiple pages. This plugin allows you to search for a specific device model and update the prices of services related to that device model. The changes are reflected across all the pages where these services are listed.
Version: 1.0
Author: BuyReadySite.com
*/

function acf_price_updater_menu() {
    add_menu_page(
        'ACF Price Updater', // page_title
        'ACF Price Updater', // menu_title
        'manage_options', // capability
        'acf-price-updater', // menu_slug
        'acf_price_updater_page_html', // function
        'dashicons-admin-plugins', // icon_url
        2 // position
    );
}
add_action('admin_menu', 'acf_price_updater_menu');

function acf_price_updater_enqueue_scripts() {
    wp_register_script('acf-price-updater', plugin_dir_url(__FILE__) . 'acf-price-updater.js', array('jquery'), '1.0', true);
    wp_enqueue_script('acf-price-updater');
    wp_localize_script('acf-price-updater', 'acf_price_updater', array(
        'nonce' => wp_create_nonce('acf-price-updater-save-nonce'),
    ));
}
add_action('admin_enqueue_scripts', 'acf_price_updater_enqueue_scripts');

function acf_price_updater_page_html() {
    // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    // show the form
    echo '<div class="wrap">';
    echo '<h1>' . get_admin_page_title() . '</h1>';
    echo '<form id="acf-price-updater-search-form" action="" method="post">';
    echo '<input type="text" id="acf-price-updater-search-query" name="search_query" placeholder="Введите модель устройства...">';
    echo '<input type="submit" value="Поиск">';
    echo '</form>';
    echo '<div id="acf-price-updater-search-results"></div>';
    echo '</div>';
}



function acf_price_updater_search() {
    $search_query = $_POST['search_query'];

    $results = [];

    for ($i = 1; $i <= 30; $i++) {
        $meta_key = 'prise_tab_' . $i . '_prise_tab_name';
        $price_key = 'prise_tab_' . $i . '_prise_tab_money';

        $pages = get_posts([
            'post_type' => 'page',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => $meta_key,
                    'value' => $search_query,
                    'compare' => 'LIKE',
                ],
            ],
        ]);

        foreach ($pages as $page) {
            $meta_value = get_post_meta($page->ID, $meta_key, true);
            $price_value = get_post_meta($page->ID, $price_key, true);

            if (!empty($meta_value)) {
                $results[$meta_value][] = [
                    'post_id' => $page->ID,
                    'service_name' => $meta_value,
                    'price' => $price_value,
                    'field_number' => $i
                ];
            }
        }
    }

    echo '<table>';
    foreach ($results as $serviceName => $duplicates) {
        $firstDuplicate = reset($duplicates);
        echo '<tr><td>' . $serviceName . '</td><td><input type="text" data-post-id="' . $firstDuplicate['post_id'] . '" data-field-number="'.$firstDuplicate['field_number'].'" value="' . $firstDuplicate['price'] . '"></td>';
        echo '<td><button class="acf-price-updater-save">Сохранить</button></td></tr>';
        // Дублирующие записи сохраняются в скрытом виде
        array_shift($duplicates); // Удаляем первый дубликат, так как он уже отображается
        foreach ($duplicates as $duplicate) {
            echo '<tr style="display: none;"><td>' . $duplicate['service_name'] . '</td><td><input type="text" data-post-id="' . $duplicate['post_id'] . '" data-field-number="'.$duplicate['field_number'].'" value="' . $duplicate['price'] . '"></td>';
            echo '<td><button class="acf-price-updater-save">Сохранить</button></td></tr>';
        }
    }
    echo '</table>';

    die();
}

add_action('wp_ajax_acf_price_updater_search', 'acf_price_updater_search');






function acf_price_updater_save() {
    // Check for nonce security
    $nonce = $_POST['nonce'];
    if (!wp_verify_nonce($nonce, 'acf-price-updater-save-nonce')) {
        die('Security check');
    }

    // Get the post ID, price and field number
    $post_id = $_POST['post_id'];
    $price = $_POST['price'];
    $field_number = $_POST['field_number'];

    // Debugging information
    error_log('post_id: ' . $post_id);
    error_log('price: ' . $price);
    error_log('field_number: ' . $field_number);

    // Update the price
    $update_result = update_post_meta($post_id, 'prise_tab_'.$field_number.'_prise_tab_money', $price);

    // More debugging information
    error_log('update_result: ' . ($update_result ? 'true' : 'false'));

    die();
}


add_action('wp_ajax_acf_price_updater_save', 'acf_price_updater_save');
?>
