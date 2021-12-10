Keep Cart (v2.0.1) Plugin for Zen Cart
=====================================

Created by Christian Pinder.
Copyright (c) C.J.Pinder 2009

(Updated by yaseent December 2016 - all credits to Christian Pinder)

Updated 20211107-lat9: Copyright (C) 2021, Vinos de Frutas Tropicales
Updated 20211210-lat9: Copyright (C) 2021, Vinos de Frutas Tropicales

ATTENTION:
==========

This module requires that the site be run on a PHP version of 7.3.0 or later!  It's been
validated on Zen Cart versions 1.5.7 (fully patched) and later.


Installation Instructions
=========================

It is strongly recommended that you back up your files and your 
database before making changes to your installation.

1) Unpack the zip file to a temporary directory and rename the 'YOUR_ADMIN' sub-directory
   to match the name of your Zen Cart admin folder.

2) All of the files in this package are stored in the same directory structure
   as the default Zen Cart package. Upload the files into the appropriate directories
   on your server. You do not need to edit any files.

3) In Admin click on the Configuration menu and select Sessions and set the following options:
   - Keep Visitor's Cart: Set to True to enable Keep Cart, False to turn it off.  The admin processing
     will force this value to 'False' if the site's PHP version is less than 7.3.0!

   - Days Before Cart Expires: Set to the number of days to keep the visitor's cart.

   - Keep Cart Secret Key: Set to a random string of numbers and letters about 15-20 long.  The storefront
     processing will disable its processing (with a warning logged) if the plugin has been enabled, but this
     value is unchanged from its default ('change me') value.
   
4) Installation complete!

How to Uninstall
================

If you wish to uninstall this module, delete the following files from your server:
    - /includes/auto_loaders/config.savecart.php
    - /includes/classes/observers/class.savecart.php
    - /YOUR_ADMIN/includes/auto_loaders/config.savecart_admin.php
    - /YOUR_ADMIN/includes/init_includes/init_savecart_admin.php

Then, using your Admin->Tools->Install SQL Patch, run the uninstall.sql file included in this package.

Using Keep Cart
===============

Keep Cart stores a copy of the contents of the visitor's cart in a cookie.
The contents of the cart is automatically reloaded when the visitor comes
back to your store or if their session expires and restarts.

Any products that have been sold out, disabled or deleted since they were
added to the cart will be removed when the cart is reloaded.

Product quantities in the cart will automatically be adjusted on reload to
prevent over selling.

Security measures have been put in place to prevent anyone from tampering
with the cookie on the visitor's PC.

VERSION HISTORY
===============
v2.0.1, 20211210, lat9/marco-pm
    - Correct timeout after cart restored.

v2.0.0, 20211109, lat9
    - The minimum PHP version supported is 7.3.0, enables the 'samesite' attribute for the savecart cookies.
      "Keep Cart" cookies are stored with 'samesite=lax'.
    - Cookies are kept **only** for non-logged-in customers.  Otherwise, there would be duplication of products
      restored to the customer's cart upon re-login.
    - Admin installation now included, no more SQL-install script!

v1.1: Release by C.J. Pinder, update by ayseent. December 2016

v1.0: Release by C.J.Pinder. October 2009

LICENSE
=======
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
