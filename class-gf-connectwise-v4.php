<?php

require_once WP_PLUGIN_DIR . "/connectwise-forms-integration/class-gf-connectwise.php";
GFForms::include_feed_addon_framework();

class GFConnectWiseV4 extends GFConnectWise {
    private static $_instance = null;

    public static function get_instance() {
       if ( self::$_instance == null ) {
           self::$_instance = new self;
       }

       return self::$_instance;
    }

    public function get_team_members(){
        $this->log_debug( __METHOD__ . "(): start getting team members from ConnectWise" );

        $team_members_list = array();

        $get_team_members_url = "system/members?pageSize=200";
        $cw_team_members = $this->send_request( $get_team_members_url, "GET", NULL );
        $cw_team_members = json_decode( $cw_team_members["body"] );

        foreach ( $cw_team_members as $each_member ) {
            $member = array(
                    "label" => esc_html__( $each_member->firstName . " " . $each_member->lastName, "gravityformsconnectwise" ),
                    "value" => $each_member->identifier
            );
            array_push( $team_members_list, $member );
        }

        $this->log_debug( __METHOD__ . "(): finish getting team members from ConnectWise" );

        return $team_members_list;
    }
}
