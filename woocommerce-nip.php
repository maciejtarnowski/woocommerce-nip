<?php
/*
Plugin Name: WooCommerce NIP
Description: Dodaje pole na NIP do szczegółów płatności w WooCommerce
Version:     1.0
Author:      Maciej Tarnowski
Author URI:  http://maxik.me
License:     GPL3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('ABSPATH') or die('Direct access not allowed');

add_filter('woocommerce_checkout_fields', 'add_nip_billing_field');

function add_nip_billing_field($fields)
{
    $fields['billing']['nip'] = [
        'label'       => __('NIP', 'woocommerce'),
        'placeholder' => __('Numer Identyfikacji Podatkowej', 'placeholder', 'woocommerce'),
        'required'    => false,
        'class'       => ['form-row-wide'],
        'clear'       => true
    ];

    return $fields;
}

add_action('woocommerce_checkout_process', 'validate_nip_field', 10, 1);

function validate_nip($nip)
{
    $nip = str_replace(['-', ' '], '', $nip);

    if (strlen($nip) < 10 || !is_numeric($nip)) {
        return false;
    }

    $nip = explode('', $nip);
    $digits = array_map('intval', $nip);
    $checksum = ((6 * $digits[0]) + (5 * $digits[1]) + (7 * $digits[2]) + (2 * $digits[3]) + (3 * $digits[4]) + (4 * $digits[5]) + (5 * $digits[6]) + (6 * $digits[7]) + (7 * $digits[8])) % 11;

    if ($digits[9] == $checksum) {
        return true;
    }

    return false;
}

function validate_nip_field()
{
    $nip = $_POST['nip'];

    if (isset($nip) && !empty($nip) && !validate_nip($nip)) {
        wc_add_notice(__('Proszę podać poprawny NIP'), 'error');
    }
}

add_action('woocommerce_checkout_update_order_meta', 'save_nip_field', 10, 1);

function save_nip_field($orderId)
{
    $nip = $_POST['nip'];

    if (!isset($nip) || !validate_nip($nip)) {
        return;
    }

    update_post_meta($orderId, 'nip', sanitize_text_field($nip));
}

add_action('woocommerce_admin_order_data_after_billing_address', 'add_nip_to_billing_info', 10, 1);

function add_nip_to_billing_info($order)
{
    echo '<p><strong>' . __('NIP') . ':</strong> ' . (get_post_meta($order->id, 'nip', true) ? : 'brak') . '</p>';
}

add_filter('woocommerce_email_order_meta_keys', 'add_nip_to_order_email', 10, 1);

function add_nip_to_order_email($keys)
{
    $keys['NIP'] = 'nip';

    return $keys;
}
