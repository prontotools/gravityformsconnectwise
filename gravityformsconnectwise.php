<?php
/*
 * Plugin Name: Gravity Forms ConnectWise Add-On
 * Plugin URI: http://www.prontotools.io
 * Description: Integrates Gravity Forms with ConnectWise, allowing form submissions to be automatically sent to your ConnectWise account.
 * Version: 1.0
 * Author: Pronto Tools
 * Author URI: http://www.prontotools.io
 */

add_action( 'gform_loaded', array( 'GF_ConnectWise_Bootstrap', 'load' ), 5 );

class GF_ConnectWise_Bootstrap {

    public static function load(){
        require_once( 'class-gf-connectwise.php' );
        GFAddOn::register( 'GFConnectWise' );
    }

}
