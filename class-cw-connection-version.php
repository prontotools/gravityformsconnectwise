<?php

GFForms::include_feed_addon_framework();

class ConnectWiseVersion extends GFAddOn {
    protected $_title                    = "Gravity Forms ConnectWise Add-On";
    protected $_short_title              = "ConnectWise";
    protected $_version                  = "1.5.0";
    protected $_min_gravityforms_version = "2.0";
    protected $_slug                     = "connectwise";
    protected $_path                     = "connectwise-forms-integration/gravityformsconnectwise.php";
    protected $_full_path                = __FILE__;
    private static $_instance            = null;

    public function transform_url( $url ) {
        $wp_connectwise_url = $this->get_plugin_setting( "connectwise_url" );

        $prefix = array( "na.", "eu.", "aus.", "staging." );
        $first_dot_pos = strpos( $wp_connectwise_url, "." );

        if ( true == in_array( substr( $wp_connectwise_url, 0, $first_dot_pos + 1 ), $prefix ) ) {
            $url = "https://api-" . $wp_connectwise_url . "/v4_6_release/apis/3.0/" . $url;
        } else {
            $url = "https://" . $wp_connectwise_url . "/v4_6_release/apis/3.0/" . $url;
        }

        return $url;
    }

    public function get(){
        $connectwise_version_url = "system/info";

        $url =  $this->transform_url( $connectwise_version_url );

        $company_id  = $this->get_plugin_setting( "company_id" );
        $public_key  = $this->get_plugin_setting( "public_key" );
        $private_key = $this->get_plugin_setting( "private_key" );
        $client_id   = $this->get_plugin_setting( "client_id" );

        $args = array(
            "method"  => "GET",
            "body"    => NULL,
            "headers" => array(
                "Accept"           => "application/vnd.connectwise.com+json; version=v2015_3",
                "Content-type"     => "application/json" ,
                "Authorization"    => "Basic " . base64_encode( $company_id . "+" . $public_key  . ":" . $private_key ),
                "X-cw-overridessl" => "True",
                "clientId"         => $client_id,
            )
        );
        $response = wp_remote_request( $url, $args );

        if ( ! is_wp_error( $response ) and 200 == $response["response"]["code"] ) {
            $response = json_decode( $response["body"] );
            $version = $response -> version;
            $version = substr( $version, 1 );
        } else {
            $version = null;
        }
        return $version;
    }
}
