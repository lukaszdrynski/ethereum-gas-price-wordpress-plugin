<?php
/*
Plugin Name: Ethereum Gas Price
Description: Tracks Ethereum Gas price
Version: 1.0
Author: Lukasz Drynski
*/

// Register a settings page for the plugin
function ethereum_gas_tracker_settings_page() {
    add_options_page('Ethereum Gas Tracker Settings', 'Gas Tracker Settings', 'manage_options', 'ethereum-gas-tracker-settings', 'ethereum_gas_tracker_settings_page_callback');
}
add_action('admin_menu', 'ethereum_gas_tracker_settings_page');

// Add a settings section and field for the API URL
function ethereum_gas_tracker_settings_init() {
    add_settings_section('ethereum_gas_tracker_settings_section', 'API Settings', 'ethereum_gas_tracker_settings_section_callback', 'ethereum_gas_tracker_settings');
    add_settings_field('ethereum_gas_tracker_api_key', 'API Key', 'ethereum_gas_tracker_api_key_callback', 'ethereum_gas_tracker_settings', 'ethereum_gas_tracker_settings_section');
    register_setting('ethereum_gas_tracker_settings', 'ethereum_gas_tracker_api_key');
}
add_action('admin_init', 'ethereum_gas_tracker_settings_init');

// Callback function for the settings page
function ethereum_gas_tracker_settings_page_callback() {
    ?>
    <div class="wrap">
        <h1>Ethereum Gas Tracker Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('ethereum_gas_tracker_settings');
            do_settings_sections('ethereum_gas_tracker_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Callback function for the settings section
function ethereum_gas_tracker_settings_section_callback() {
    echo 'Configure the API key for Ethereum Gas Tracker';

    // Echo the shortcode names and short descriptions as a list
    echo '<ul>';
    echo '<li>Shortcode: <strong>[ethereum_gas_price_last_block]</strong> - Displays the last block number</li>';
    echo '<li>Shortcode: <strong>[ethereum_gas_price_safe_gas]</strong> - Displays the safe gas price</li>';
    echo '<li>Shortcode: <strong>[ethereum_gas_price_propose_gas]</strong> - Displays the proposed gas price</li>';
    echo '<li>Shortcode: <strong>[ethereum_gas_price_fast_gas]</strong> - Displays the fast gas price</li>';
    echo '</ul>';
}

// Callback function for the API Key field
function ethereum_gas_tracker_api_key_callback() {
    $api_key = get_option('ethereum_gas_tracker_api_key');
    echo '<input type="text" name="ethereum_gas_tracker_api_key" value="' . esc_attr($api_key) . '" />';
}

// Main plugin function
function call_api() {
    // Check if the data is already cached
    $cached_data = get_transient('ethereum_gas_price_data');

    if (false === $cached_data) {
        // Data is not cached, make the API call
        $api_key = get_option('ethereum_gas_tracker_api_key');
        $api_url = 'https://api.etherscan.io/api?module=gastracker&action=gasoracle&apikey=' . $api_key;

        $response = wp_remote_get($api_url);

        if (is_wp_error($response)) {
            error_log('API call failed: ' . $response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        // Cache the data for 1 minute
        set_transient('ethereum_gas_price_data', $data, 60);
    } else {
        // Data is already cached, use the cached data
        $data = $cached_data;
    }
}

// Assign values to shortcodes using the data
function ethereum_gas_price_last_block_shortcode($atts) {
    $data = get_transient('ethereum_gas_price_data');
    $last_block = $data->result->LastBlock;
    return $last_block;
}

function ethereum_gas_price_safe_gas_shortcode($atts) {
    $data = get_transient('ethereum_gas_price_data');
    $safe_gas_price = $data->result->SafeGasPrice;
    return $safe_gas_price;
}

function ethereum_gas_price_propose_gas_shortcode($atts) {
    $data = get_transient('ethereum_gas_price_data');
    $propose_gas_price = $data->result->ProposeGasPrice;
    return  $propose_gas_price;
}

function ethereum_gas_price_fast_gas_shortcode($atts) {
    $fast_gas_price = $atts['fast_gas_price'];
    return  $fast_gas_price;
}

// Register the shortcodes
add_shortcode('ethereum_gas_price_last_block', 'ethereum_gas_price_last_block_shortcode');
add_shortcode('ethereum_gas_price_safe_gas', 'ethereum_gas_price_safe_gas_shortcode');
add_shortcode('ethereum_gas_price_propose_gas', 'ethereum_gas_price_propose_gas_shortcode');
add_shortcode('ethereum_gas_price_fast_gas', 'ethereum_gas_price_fast_gas_shortcode');


add_action('init', 'call_api');

// Add settings link to the plugin's action links
function ethereum_gas_tracker_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=ethereum-gas-tracker-settings">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ethereum_gas_tracker_settings_link');
