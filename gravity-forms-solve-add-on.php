<?php

/**
 * @link              http://duane.co.za/wordress-plugins/gravity-forms-solve-add-on
 * @since             1.0.0
 * @package           GFSolve
 *
 * @wordpress-plugin
 * Plugin Name:       Gravity Forms Solve Add-on
 * Plugin URI:        http://duane.co.za/wordpress-plugins/gravity-forms-solve-add-on
 * Description:       This is the most powerful Solve integration available for WordPress
 * Version:           1.0.0
 * Author:            Duane Cilliers
 * Author URI:        http://duane.co.za
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gf-solve
 * Domain Path:       /languages
 */

// Make sure Gravity Forms is active and loaded.
if ( ! class_exists('GFForms' ) )
	die;

// Load Gravity Forms Add-on Framework.
GFForms::include_addon_framework();

// Require Solve PHP Service Gateway
require dirname( __FILE__ ) . '/includes/solve360Service.php';

class GFSolve extends GFAddOn {

	// The version number is used for example during add-on upgrades.
	protected $_version = '0.1';

	// The Framework will display an appropriate message on the plugins page if necessary.
	protected $_min_gravityforms_version = '1.9.1.2';

	// This will be used for storing options, filters, actions, URLs and text-domain localization.
	protected $_slug = 'gfsolve';

	// Relative path to the plugin from the plugins folder.
	protected $_path = 'gravity-forms-solve-add-on/gravity-forms-solve-add-on.php';

	// Full path to the plugin
	protected $_full_path = __FILE__;

	// Title of the plugin to be used on the settings page, form settings and plugins page.
	protected $_title = 'Graviy Forms Solve Add-on';

	// Short version of the plugin title to be used on menus and other places where a less verbose string is useful.
	protected $_short_title = 'Solve';

}

new GFSolve();
