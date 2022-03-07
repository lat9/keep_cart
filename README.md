# Keep Cart: For Zen Cart v157 and later
This drop-in plugin keeps a *guest* customer's cart in a browser-based cookie.  Once the customer has logged into your store, their selections are maintained in your site's database.

It's been tested on Zen Cart 1.5.7c (but should work on any fully-patched Zen Cart 157+ installation). 
The plugin requires a minimum PHP version of 7.3.0, since that PHP version introduced the support for `samesite` cookie controls.

Zen Cart Download: https://www.zen-cart.com/downloads.php?do=file&id=992

## Installation
1. Copy the files into your development installation.
This will install two new options in Admin->Configuration->Sessions:

* "Keep Visitor's Cart": enable the plugin.
* "Days Before Cart Expires": how long the plugin cookies persist in the users browser.
* "Keep Cart: Secret Key" - can be anything, but not the default text or the cookies will not be set.
2. Test.
Using the browser Developer Tools you may view the cookies being created and manually delete them to review the plugin functionality.  
Note the default `zenid` cookie also stores cart data, but quickly expires depending on your server settings. You may delete this cookie to simulate a returning customer.
3. Copy files to production site.
4. Test again.

## How it Works

When any addition/modification to the cart are made, two cookies are created.  

* `cartkey`: holds the MD5-encrypted secret key.
* `cart`: holds the cart contents (base64 encoded and compressed)

On a page load, if these cookies are present and `cartkey`=the MD5-encoded Secret Key, the contents of `cart` are loaded into the shopping cart.

Note that the storefront observer is manually loaded at breakpoint 90 to catch all the possible notifiers. It is not possible to make this an auto-loading observer (which uses breakpoint 175). 

## Uninstall
1. Remove files.
2. Run uninstall.sql in the Admin->Tools->Install SQL Patches to remove the three constants from the Admin->Configuration->Sessions menu.