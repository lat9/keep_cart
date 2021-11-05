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
		global $zco_notifier, $session_started, $db;

		if ((defined('KEEP_CART_ENABLED') && KEEP_CART_ENABLED == 'True') && $session_started && isset($_SESSION['cart']))
		{
			$_SESSION['cart']->attach($this, array(
                'NOTIFIER_CART_ADD_CART_END',
                'NOTIFIER_CART_UPDATE_QUANTITY_END',
                'NOTIFIER_CART_CLEANUP_END',
                'NOTIFIER_CART_REMOVE_END',
                'NOTIFIER_CART_REMOVE_ALL_END',
                'NOTIFIER_CART_RESTORE_CONTENTS_END',
            ));
			$zco_notifier->attach($this, array('NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION','NOTIFY_HEADER_START_LOGOFF'));

			if (isset($_COOKIE['cart']) && isset($_COOKIE['cartkey']) && (empty($_SESSION['cart']->contents)))
			{
				$cookie_value = $_COOKIE['cart'];
				$hash_key = md5(KEEP_CART_SECRET . $cookie_value);
				if ($hash_key === $_COOKIE['cartkey'])
				{
					$cart_contents = base64_decode ($cookie_value);
					$cart_contents = gzuncompress ($cart_contents);
					$_SESSION['cart']->contents = unserialize($cart_contents);

					foreach($_SESSION['cart']->contents as $products_id => $details)
					{
						$prid = zen_get_prid($products_id);
						$stock_query = "select products_quantity, products_status
										from " . TABLE_PRODUCTS . "
										where products_id = '" . (int)$prid . "'";

						$stock_info = $db->Execute($stock_query);
						if ($stock_info->EOF)
							unset($_SESSION['cart']->contents[$products_id]);
						else
						{
							$stock_qty = $stock_info->fields['products_quantity'];
							$stock_status = $stock_info->fields['products_status'];
							if (($stock_qty < 1) || ($stock_status == 0)) 
								unset($_SESSION['cart']->contents[$products_id]);
							else
							{
								if ($stock_qty < $_SESSION['cart']->contents[$products_id]['qty'])
								$_SESSION['cart']->contents[$products_id] = array('qty' => (float)$stock_qty);
							}
						}
					}
				}
			}
		}
	}

	function update(&$class, $eventID)
	{
		if (!defined('KEEP_CART_ENABLED') || KEEP_CART_ENABLED != 'True' || !defined('KEEP_CART_DURATION') || !defined('KEEP_CART_SECRET')) {
			return;
		}

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
				$cookie_value = gzcompress($cookie_value,9);
				$cookie_value = base64_encode($cookie_value);
				$hash_key = md5(KEEP_CART_SECRET . $cookie_value);
				setcookie("cart", $cookie_value, $cookie_expires, "/", (zen_not_null($current_domain) ? $current_domain : ''));
				setcookie("cartkey", $hash_key, $cookie_expires, "/", (zen_not_null($current_domain) ? $current_domain : ''));
				break;

			case 'NOTIFY_HEADER_START_LOGOFF':
			case 'NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION':
				setcookie('cart', '', time()-1000,"/",(zen_not_null($current_domain) ? $current_domain : ''));
				setcookie('cartkey', '', time()-1000,"/",(zen_not_null($current_domain) ? $current_domain : ''));
				break;
		}

	}
}
