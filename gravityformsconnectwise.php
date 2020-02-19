<?php
/*
 * Plugin Name: Gravity Forms ConnectWise Add-On
 * Plugin URI: http://www.prontotools.io
 * Description: Integrates Gravity Forms with ConnectWise, allowing form submissions to be automatically sent to your ConnectWise account.
 * Version: 1.6.0
 * Author: Pronto Tools
 * Author URI: http://www.prontotools.io
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( class_exists( 'GFForms' ) ) {
	add_action( 'gform_loaded', array( 'GFConnectWiseBootstrap', 'load' ), 5 );
	require_once WP_PLUGIN_DIR . '/connectwise-forms-integration/class-cw-connection-version.php';

	class GFConnectWiseBootstrap {
		public static function load() {
			$cw_api  = new ConnectWiseVersion();
			$version = $cw_api->get();

			if ( '2020.1' <= $version ) {
				require_once( 'class-gf-connectwise-2020v1.php' );
				GFAddOn::register( 'GFConnectWise2020v1' );
			} elseif ( '2018.6' < $version ) {
				require_once( 'class-gf-connectwise-2018v6.php' );
				GFAddOn::register( 'GFConnectWise2018v6' );
			} elseif ( '2016.4' <= $version ) {
				require_once( 'class-gf-connectwise-2016v4.php' );
				GFAddOn::register( 'GFConnectWiseV4' );
			} else {
				require_once( 'class-gf-connectwise.php' );
				GFAddOn::register( 'GFConnectWise' );
			}
		}
	}

	function gf_connectwise() {
		$cw_api  = new ConnectWiseApi();
		$version = $cw_api->get_connectwise_version();
		if ( '2020.1' <= $version ) {
			require_once( 'class-gf-connectwise-2020v1.php' );
			return GFConnectWise2020v1::get_instance();
		} elseif ( '2018.6' < $version ) {
			require_once( 'class-gf-connectwise-2018v6.php' );
			return GFConnectWise2018v6::get_instance();
		} elseif ( '2016.4' <= $version ) {
			require_once( 'class-gf-connectwise-2016v4.php' );
			return GFConnectWiseV4::get_instance();
		} else {
			require_once( 'class-gf-connectwise.php' );
			return GFConnectWise::get_instance();
		}
	}
}
