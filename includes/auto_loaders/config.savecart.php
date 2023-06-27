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

$autoLoadConfig[90][] = [
    'autoType' => 'class',
    'loadFile' => 'observers/class.savecart.php'
];
$autoLoadConfig[90][] = [
    'autoType' => 'classInstantiate',
    'className' => 'save_cart',
    'objectName' => 'save_cart'
];
