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
    $result = $db->Execute("SELECT MAX(sort_order) AS sort_order_max FROM " . TABLE_CONFIGURATION . " WHERE configuration_group_id = 15");
    $max_sort_order = $result->fields['sort_order_max'] + 1;

    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
         VALUES
            ('\"Keep Cart\": Keep Visitor\'s Cart?', 'KEEP_CART_ENABLED', 'False', 'Store the visitor\'s cart contents in a cookie.', 15, " . ($max_sort_order + 1) . ", now(), NULL, 'zen_cfg_select_option(array(\'True\', \'False\'),'),

            ('\"Keep Cart\": Days Before Cart Expires', 'KEEP_CART_DURATION', '30', 'Number of days to store cookie in visitors browser.', 15, " . ($max_sort_order + 2) . ",  now(), NULL, NULL),

            ('\"Keep Cart\": Secret Key', 'KEEP_CART_SECRET', 'change me', 'Random characters to use as a secret key in a second cookie. <b>Note:</b> The storefront processing is disabled if this value is left as the default <code>change me</code>.', 15, "
        . ($max_sort_order + 3) . ", now(), NULL, NULL)"
    );
    zen_record_admin_activity('Installed Plugin "Keep Cart"', 'warning');

} elseif (PHP_VERSION_ID < 70300 && KEEP_CART_ENABLED === 'True') {
    $db->Execute(
        "UPDATE " . TABLE_CONFIGURATION . "
                SET configuration_value = 'False',
                    last_modified = now()
              WHERE configuration_key = 'KEEP_CART_ENABLED'
              LIMIT 1"
    );
    $messageStack->add('<em>"Keep Cart"</em> is disabled; it requires PHP minimum version 7.3.0."', 'error');
}
