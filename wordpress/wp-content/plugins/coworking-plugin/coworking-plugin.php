<?php
/*
Plugin Name: Coworking plugin
Plugin URI: http://highseastudio.com/demo/coworking
Description: Coworking premium plugin.
Author: HighSeaStudio
Author URI: http://highseastudio.com/
Version: 1.0.0
Text Domain: coworking-plugin
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.txt
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // don't access directly
};

class Coworking_Plugin {
	public function __construct() {
		$this->coworking_autoload();
		$this->coworking_hooks();
	}

	public static function coworking_plugin_dir() {
		return plugin_dir_path( __file__ );
	}

	public function coworking_autoload() {
		require_once $this->coworking_plugin_dir() . '/include/helper-functions.php';
	}

	public function coworking_hooks() {
        /* Adds the Theme Option page to the admin bar */
        add_action( 'admin_bar_menu', 'ot_register_theme_options_admins_bar_menu', 999 );
	}
}

new Coworking_Plugin();