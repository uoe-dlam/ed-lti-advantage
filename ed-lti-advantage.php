<?php

/*
Plugin Name: UoE LTI Advantage
Description: Allows LMSs to create blogs in a WordPress multisite installation via an LTI 1.3 connection
Author: DLAM Applications Development Team
Version: 1.0
Copyright: University of Edinburgh
License: GPL-3.0+
*/

/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

namespace EdLTIAdvantage;

// Include the autoloader so we can dynamically include the rest of the classes.
require_once trailingslashit( dirname( __FILE__ ) ) . 'inc/autoloader.php';

use EdLTIAdvantage\classes\Ed_LTI;

new Ed_LTI();

register_activation_hook( __FILE__, [ 'EdLTIAdvantage\classes\Ed_LTI', 'activate' ] );
