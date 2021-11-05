Keep Cart (v1.1) Add-on for Zen Cart
=====================================

Created by Christian Pinder.
Copyright (c) C.J.Pinder 2009

(Updated by yaseent December 2016 - all credits to Christian Pinder)

For Zen Cart & eCommerce hints, tips and information visit:
http://www.zen-unlocked.com

ATTENTION:
==========

This module has been successfully tested on Zen Cart v1.3.7 and v1.3.8. You 
may or may not be able to get it to work with other versions - I suggest 
you proceed with caution and always back up your site before you start!


Installation Instructions
=========================

It is strongly recommended that you back up your files and your 
database before making changes to your installation.

1) Unpack the zip file to a temporary directory

2) Using the Admin->Tools->Install SQL Patch, run the included SQL file (keep_cart.sql)

   The keep_cart.sql file has instructions to add new options to the
   Admin->Configuration->Sessions menu.

3) All of the files in this package are stored in the same directory structure
   as the default Zen Cart package. Upload the files into the appropriate directories
   on your server. You do not need to edit any files.

4) In Admin click on the Configuration menu and select Sessions.
   Set the following options:
   Keep Vistors Cart: Set to True to enable Keep Cart, False to turn it off.
   Days Before Cart Expires: Set to the number of days to keep the vistors cart.
   Keep Cart Secret Key: Set to a random string of numbers and letters about 15-20 long.
   
5) Installation complete!

How to Uninstall
================

If you wish to uninstall this module do the following:
1) Delete includes/auto_loaders/config.savecart.php from your server.
2) Delete includes/classes/observers/class.savecart.php from your server.
3) Using the Admin->Tools->Install SQL Patch, run the uninstall.sql file included in this package.
4) Uninstall complete!

Using Keep Cart
===============

Keep Cart stores a copy of the contents of the vistor's cart in a cookie.
The contents of the cart is automatically reloaded when the vistor comes
back to your store or if their session expires and restarts.

Any products that have been sold out, disabled or deleted since they were
added to the cart will be removed when the cart is reloaded.

Product quantities in the cart will automatically be adjusted on reload to
prevent over selling.

Security measures have been put in place to prevent anyone from tampering
with the cookie on the visitor's PC.

VERSION HISTORY
===============

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
