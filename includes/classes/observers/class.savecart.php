<?php
/*
Copyright C.J.Pinder 2009
http://www.zen-unlocked.com

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

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

class save_cart extends base
{
    function __construct()
    {
        global $session_started, $db;

        if ((defined('KEEP_CART_ENABLED') && KEEP_CART_ENABLED === 'True' && defined('KEEP_CART_DURATION') && defined('KEEP_CART_SECRET')) && $session_started) {
            $this->attach($this, [
                'NOTIFIER_CART_ADD_CART_END',
                'NOTIFIER_CART_UPDATE_QUANTITY_END',
                'NOTIFIER_CART_CLEANUP_END',
                'NOTIFIER_CART_REMOVE_END',
                'NOTIFIER_CART_REMOVE_ALL_END',
                'NOTIFIER_CART_RESTORE_CONTENTS_END',
                'NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION',
                'NOTIFY_HEADER_START_LOGOFF',
            ]);

            if (isset($_COOKIE['cart']) && isset($_COOKIE['cartkey']) && empty($_SESSION['cart']->contents)) {
                $cookie_value = $_COOKIE['cart'];
                $hash_key = md5(KEEP_CART_SECRET . $cookie_value);
                if ($hash_key === $_COOKIE['cartkey']) {
                    $cart_contents = base64_decode($cookie_value);
                    $cart_contents = gzuncompress($cart_contents);
                    $_SESSION['cart']->contents = unserialize($cart_contents);

                    foreach ($_SESSION['cart']->contents as $products_id => $details) {
                        $prid = zen_get_prid($products_id);
                        $stock_info = $db->Execute(
                            "SELECT products_quantity, products_status
                               FROM " . TABLE_PRODUCTS . "
                              WHERE products_id = " . (int)$prid . "
                              LIMIT 1"
                        );
                        if ($stock_info->EOF) {
                            unset($_SESSION['cart']->contents[$products_id]);
                        } else {
                            $stock_qty = $stock_info->fields['products_quantity'];
                            $stock_status = $stock_info->fields['products_status'];
                            if ($stock_qty < 1 || $stock_status === '0') {
                                unset($_SESSION['cart']->contents[$products_id]);
                            } elseif ($stock_qty < $_SESSION['cart']->contents[$products_id]['qty']) {
                                $_SESSION['cart']->contents[$products_id] = ['qty' => $stock_qty];
                            }
                        }
                    }
                }
            }
        }
    }

    function update(&$class, $eventID)
    {
        global $current_domain; //- Set by /includes/init_includes/init_tlds.php

        switch ($eventID)
        {
            case 'NOTIFIER_CART_ADD_CART_END':
            case 'NOTIFIER_CART_UPDATE_QUANTITY_END':
            case 'NOTIFIER_CART_CLEANUP_END':
            case 'NOTIFIER_CART_REMOVE_END':
            case 'NOTIFIER_CART_REMOVE_ALL_END':
            case 'NOTIFIER_CART_RESTORE_CONTENTS_END':
                $cookie_expires = time() + (((int)KEEP_CART_DURATION) * 24 * 60 * 60);
                $cookie_value = serialize($_SESSION['cart']->contents);
                $cookie_value = gzcompress($cookie_value, 9);
                $cookie_value = base64_encode($cookie_value);
                $hash_key = md5(KEEP_CART_SECRET . $cookie_value);
                setcookie('cart', $cookie_value, $cookie_expires, '/', $current_domain);
                setcookie('cartkey', $hash_key, $cookie_expires, '/', $current_domain);
                break;

            case 'NOTIFY_HEADER_START_LOGOFF':
            case 'NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION':
                setcookie('cart', '', time()-1000, '/', $current_domain);
                setcookie('cartkey', '', time()-1000, '/', $current_domain);
                break;
        }
    }
}
