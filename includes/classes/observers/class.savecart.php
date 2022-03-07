<?php
/*
Keep-Cart v2.0.2

Copyright C.J.Pinder 2009 http://www.zen-unlocked.com
Copyright 2021-2022, lat9, https://vinosdefrutastropicales.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
// -----
// Keeps the contents of a **not logged-in** customer's cart in a cookie (so they don't lose
// their selections if their session times out).  Once a customer has logged in, their selections
// are saved in the database and we don't want to replicate those choices in a cookie.
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

class save_cart extends base
{
    public function __construct()
    {
        global $db;

        if (defined('KEEP_CART_ENABLED') && KEEP_CART_ENABLED === 'True' && defined('KEEP_CART_DURATION') && defined('KEEP_CART_SECRET')) {
            if (version_compare(PHP_VERSION, '7.3.0', '<')) {
                trigger_error('Keep Cart requires PHP 7.3.0 or later; currently using PHP ' . PHP_VERSION . '.  Keep Cart has been disabled.', E_USER_WARNING);
                return;
            }
            if (KEEP_CART_SECRET === 'change me') {
                trigger_error('Keep Cart "Secret" setting requires change prior to use.  Keep Cart has been disabled.', E_USER_WARNING);
                return;
            }
            $this->attach($this, [
                'NOTIFIER_CART_ADD_CART_END',
                'NOTIFIER_CART_UPDATE_QUANTITY_END',
                'NOTIFIER_CART_CLEANUP_END',
                'NOTIFIER_CART_REMOVE_END',
                'NOTIFIER_CART_RESET_END',
                'NOTIFIER_CART_RESTORE_CONTENTS_END',
                'NOTIFY_HEADER_START_CHECKOUT_SUCCESS',
                'NOTIFY_HEADER_START_LOGOFF',
            ]);

            if (isset($_COOKIE['cart']) && isset($_COOKIE['cartkey']) && empty($_SESSION['cart']->contents)) {
                $cookie_value = $_COOKIE['cart'];
                $hash_key = md5(KEEP_CART_SECRET . $cookie_value);
                if ($hash_key === $_COOKIE['cartkey']) {
                    $cart_contents = base64_decode($cookie_value);
                    $cart_contents = gzuncompress($cart_contents);
                    $_SESSION['cart']->contents = unserialize($cart_contents);

                    // -----
                    // Loop through each of the now-restored cart products, checking that there is sufficient
                    // quantity still available and that the product (and associated attributes) is still available (i.e. not disabled).
                    //
                    foreach ($_SESSION['cart']->contents as $products_id => $details) {
                        // -----
                        // First, check to see that the base product is still present and not disabled.  If so, the
                        // product will be removed from the customer's shopping-cart.
                        //
                        $prid = zen_get_prid($products_id);
                        $status_info = $db->Execute(
                            "SELECT products_status
                               FROM " . TABLE_PRODUCTS . "
                              WHERE products_id = " . (int)$prid . "
                                AND products_status != 0
                              LIMIT 1"
                        );
                        if ($status_info->EOF) {
                            unset($_SESSION['cart']->contents[$products_id]);
                            continue;
                        }

                        // -----
                        // Next, if attributes are present, make sure that each option-combination is
                        // still present and available for the associated product.  If not, the associated
                        // product is removed from the customer's cart.
                        //
                        // Note: The (int) cast for the options_values_id is needed, since checkbox attributes
                        // use an id similar to 13chk_34 to 'bind' checkbox option 13 to checkbox value 34.
                        //
                        if (isset($details['attributes'])) {
                            $attributes_ok = true;
                            $language_id = (isset($_SESSION['languages_id'])) ? $_SESSION['languages_id'] : 1;
                            foreach ($details['attributes'] as $options_id => $options_values_id) {
                                $attr_check = $db->Execute(
                                    "SELECT pa.products_attributes_id
                                       FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                            INNER JOIN " . TABLE_PRODUCTS_OPTIONS . " po
                                                ON po.products_options_id = pa.options_id
                                               AND po.language_id = $language_id
                                            INNER JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov
                                                ON pov.products_options_values_id = pa.options_values_id
                                               AND pov.language_id = $language_id
                                      WHERE pa.products_id = $prid
                                        AND pa.options_id = " . (int)$options_id . "
                                        AND pa.options_values_id = " . (int)$options_values_id . "
                                      LIMIT 1"
                                );
                                if ($attr_check->EOF) {
                                    $attributes_ok = false;
                                    break;
                                }
                            }
                            if ($attributes_ok === false) {
                                unset($_SESSION['cart']->contents[$products_id]);
                                continue;
                            }
                        }

                        // -----
                        // Finally, make sure that there's stock available for purchase (if the store's so configured).  If there's no
                        // stock, the associated product is removed from the customer's cart; if there's not
                        // sufficient stock, the product's cart-quantity is reduced to what's available.
                        //
                        if (STOCK_ALLOW_CHECKOUT === 'false') {
                            $stock_qty = zen_get_products_stock($products_id);
                            if ($stock_qty < 1) {
                                unset($_SESSION['cart']->contents[$products_id]);
                            } elseif ($stock_qty < $_SESSION['cart']->contents[$products_id]['qty']) {
                                $_SESSION['cart']->contents[$products_id]['qty'] = $stock_qty;
                            }
                        }
                    }

                    // -----
                    // Now, recalculate the 'cartID' based on the just-added items; otherwise, the cartID isn't set
                    // and the customer would be redirected to the time_out page when they attempt to checkout.
                    //
                    $_SESSION['cart']->cartID = $_SESSION['cart']->generate_cart_id();
                }
            }
        }
    }

    public function update(&$class, $eventID)
    {
        $domain = str_replace(
            [
                'https://',
                'http://',
                '//'
            ],
            '',
            strtolower(HTTP_SERVER)
        );
        $secure = (stripos(HTTP_SERVER, 'https://') === 0);
        $cookie_options = [
            'expires' => strtotime('+' . ((ctype_digit(KEEP_CART_DURATION)) ? KEEP_CART_DURATION : '30') . ' days'),
            'path' => DIR_WS_CATALOG,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'lax'
        ];
        switch ($eventID)
        {
            case 'NOTIFIER_CART_ADD_CART_END':
            case 'NOTIFIER_CART_UPDATE_QUANTITY_END':
            case 'NOTIFIER_CART_CLEANUP_END':
            case 'NOTIFIER_CART_REMOVE_END':
                if (!zen_is_logged_in() || zen_in_guest_checkout()) {
                    $cookie_value = serialize($_SESSION['cart']->contents);
                    $cookie_value = gzcompress($cookie_value, 9);
                    $cookie_value = base64_encode($cookie_value);
                    $hash_key = md5(KEEP_CART_SECRET . $cookie_value);
                    setcookie('cart', $cookie_value, $cookie_options);
                    setcookie('cartkey', $hash_key, $cookie_options);
                }
                break;

            case 'NOTIFIER_CART_RESET_END':
            case 'NOTIFY_HEADER_START_LOGOFF':
            case 'NOTIFY_HEADER_START_CHECKOUT_SUCCESS':
            case 'NOTIFIER_CART_RESTORE_CONTENTS_END':
                $cookie_options['expires'] = time() - 3600;
                setcookie('cart', '', $cookie_options);
                setcookie('cartkey', '', $cookie_options);
                break;
        }
    }
}
