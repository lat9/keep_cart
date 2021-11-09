<?php
// -----
// Part of the "Keep Cart" plugin, v2.0.0 and later.  Performs the plugin's database
// initialization and/or update.
//
// Last updated 20211106-lat9 for v2.0.0
//
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

// -----
// Only update configuration when an admin is logged in.
//
if (!isset($_SESSION['admin_id'])) {
    return;
}

// -----
// If the plugin's configuration settings haven't yet been recorded in the database, do that
// now.
//
if (!defined('KEEP_CART_ENABLED')) {
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
         VALUES
            ('Keep Visitor\'s Cart?', 'KEEP_CART_ENABLED', 'False', 'Keep a copy of the visitor\'s cart in a cookie.', 15, 50, now(), NULL, 'zen_cfg_select_option(array(\'True\', \'False\'),'),

            ('Days Before Cart Expires', 'KEEP_CART_DURATION', '30', 'Number of days to keep a visitor\'s cart.', 15, 51,  now(), NULL, NULL),

            ('Keep Cart: Secret Key', 'KEEP_CART_SECRET', 'change me', 'Random characters to use as Keep Cart secret key.  <b>Note:</b> The storefront processing is disabled if this value is left as the default <code>change me</code>.', 15, 52, now(), NULL, NULL)"
    );
} else {
    if (version_compare(PHP_VERSION, '7.3.0', '<') && KEEP_CART_ENABLED === 'True') {
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET configuration_value = 'False',
                    last_modified = now()
              WHERE configuration_key = 'KEEP_CART_ENABLED'
              LIMIT 1"
        );
        $messageStack->add("<em>Keep Cart</em> has been disabled; it requires a minimum PHP version of 7.3.0.", 'error');
    }
}
