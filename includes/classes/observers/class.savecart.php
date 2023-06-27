<?php
/*
Keep-Cart v2.0.3

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
    private array $cookie_options = [];

    public function __construct()
    {
        global $db;

        if (defined('KEEP_CART_ENABLED') && KEEP_CART_ENABLED === 'True' && defined('KEEP_CART_DURATION') && defined('KEEP_CART_SECRET')) {
            if (version_compare(PHP_VERSION, '7.3.0', '<')) {
                if (!isset($_SESSION['kc_disabled_logged'])) {
                    trigger_error('Keep Cart requires PHP 7.3.0 or later; currently using PHP ' . PHP_VERSION . '.  Keep Cart has been disabled.', E_USER_WARNING);
                }
                $_SESSION['kc_disabled_logged'] = true;
                return;
            }
            if (KEEP_CART_SECRET === 'change me') {
                if (!isset($_SESSION['kc_disabled_logged'])) {
                    trigger_error('Keep Cart "Secret" setting requires change prior to use.  Keep Cart has been disabled.', E_USER_WARNING);
                }
                $_SESSION['kc_disabled_logged'] = true;
                return;
            }

            $domain = str_replace(['https://', 'http://', '//'], '', strtolower(HTTP_SERVER));
            $secure = (stripos(HTTP_SERVER, 'https://') === 0);
            $this->cookie_options = [
                'expires' => strtotime('+' . ((ctype_digit(KEEP_CART_DURATION)) ? KEEP_CART_DURATION : '30') . ' days'),
                'path' => DIR_WS_CATALOG,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'lax'
            ];

            $this->attach($this, [
                'NOTIFIER_CART_ADD_CART_END',           //on completion of function add_cart: when a product is added to the cart
                'NOTIFIER_CART_UPDATE_QUANTITY_END',    //on completion of function add_cart: when a cart quantity is modified
                'NOTIFIER_CART_CLEANUP_END',            //on completion of function cleanup: removal of zero quantity items from the cart:
                // after add_cart / after restore_contents and 'NOTIFIER_CART_RESTORE_CONTENTS_END'
                'NOTIFIER_CART_REMOVE_END',             //on completion of function remove: removal of a product from the cart
                'NOTIFIER_CART_RESET_END',              //on completion of function reset: clears all products from the cart: _construct / remove_all / restore_contents (prior to 'NOTIFIER_CART_RESTORE_CONTENTS_END')
                'NOTIFIER_CART_RESTORE_CONTENTS_END',   //on completion of function restore_contents: restore of cart contents as stored in the database, when customer logs in
                'NOTIFY_HEADER_START_CHECKOUT_SUCCESS', //on pageload/header of checkout_success/order completion
                'NOTIFY_HEADER_START_LOGOFF',           //on pageload/header of logoff
            ]);

            if (isset($_COOKIE['cart'], $_COOKIE['cartkey']) && isset($_SESSION['cart']) && empty($_SESSION['cart']->contents)) {
                $cookie_value = $_COOKIE['cart'];
                $hash_key = md5(KEEP_CART_SECRET . $cookie_value);
                if ($hash_key === $_COOKIE['cartkey']) {
                    $cart_contents = base64_decode($cookie_value);
                    $cart_contents = gzuncompress($cart_contents);

                    // -----
                    // If the uncompressed cookie contents' can't be uncompressed, the cookie's somehow
                    // gotten to an invalid format; simply expire the cookie so that we'll start over.
                    //
                    if ($cart_contents === false) {
                        $this->expireKeepCartCookie();
                        return;
                    }

                    // -----
                    // Otherwise, continue with the cart's restoration from the valid cookie.
                    //
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
                        $prid = (int)$products_id;//$products_id will be an integer if no atttributes (avoid zen_get_prid which is hinted as string)
                        $status_info = $db->Execute(
                            'SELECT products_status
                               FROM ' . TABLE_PRODUCTS . '
                              WHERE products_id = ' . $prid . '
                                AND products_status != 0
                              LIMIT 1'
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
                            $language_id = (isset($_SESSION['languages_id'])) ? (int)$_SESSION['languages_id'] : 1;
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
                        // stock, the associated product is removed from the customer's cart; if there is not
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

    /**
     * @param $class
     * @param $eventID
     * @return void
     */
    public function update(&$class, $eventID): void
    {
        switch ($eventID) {
            case 'NOTIFIER_CART_ADD_CART_END':        // on adding a product to the cart
            case 'NOTIFIER_CART_UPDATE_QUANTITY_END': // on change quantity in cart
            case 'NOTIFIER_CART_CLEANUP_END':         // at the end of add to cart and restore stored basket to cart post-login
            case 'NOTIFIER_CART_REMOVE_END':          // on removal/delete product from cart
                if (!empty($_SESSION['cart']->contents)) {
                    if (!zen_is_logged_in() || zen_in_guest_checkout()) {
                        $cookie_value = serialize($_SESSION['cart']->contents);
                        $cookie_value = gzcompress($cookie_value, 9);
                        $cookie_value = base64_encode($cookie_value);
                        $hash_key = md5(KEEP_CART_SECRET . $cookie_value);
                        setcookie('cart', $cookie_value, $this->cookie_options);
                        setcookie('cartkey', $hash_key, $this->cookie_options);
                    }
                    break;
                }
//- If cart is empty, fall through to expire the "Keep Cart" cookies
            case 'NOTIFIER_CART_RESET_END':
            case 'NOTIFY_HEADER_START_LOGOFF':
            case 'NOTIFY_HEADER_START_CHECKOUT_SUCCESS':
            case 'NOTIFIER_CART_RESTORE_CONTENTS_END':
                $this->expireKeepCartCookie();
                break;
        }
    }

    /**
     * @return void
     */
    protected function expireKeepCartCookie(): void
    {
        $this->cookie_options['expires'] = time() - 3600;
        setcookie('cart', '', $this->cookie_options);
        setcookie('cartkey', '', $this->cookie_options);
    }
}
