<?php
require_once WP_PLUGIN_DIR . "/gravityforms/gravityforms.php";
require_once WP_PLUGIN_DIR . "/connectwise-forms-integration/class-gf-connectwise-v4.php";
require_once 'vendor/autoload.php';

class GravityFormsConnectWiseAddOnTestV4 extends WP_UnitTestCase {
    function setUp() {
        parent::setUp();

        $this->connectwise_plugin = new GFConnectWiseV4();
        $this->slug = "gravityformsaddon_connectwise_settings";
    }

    function tearDown() {
        $this->reset_phpmailer_instance();

        parent::tearDown();
    }

    function tests_retrieve_phpmailer_instance() {
        $mailer = false;

        if ( isset( $GLOBALS['phpmailer'] ) ) {
            $mailer = $GLOBALS['phpmailer'];
        }

        return $mailer;
    }

    function reset_phpmailer_instance() {
        $mailer = $this->tests_retrieve_phpmailer_instance();

        if ( $mailer && isset( $mailer->mock_sent ) ) {
            unset( $mailer->mock_sent );
            return true;
        }

        return false;
    }


    function test_member_api_should_return_correct_member_list() {
        $GF_ConnectWise = $this->getMockBuilder( "GFConnectWiseV4" )
            ->setMethods( array( "send_request" ) )
            ->getMock();

        $mock_members_response = array(
            "body" => '[{"identifier": "Admin1", "firstName": "Training", "lastName": "Admin1"},{"identifier": "Admin2", "firstName": "Training", "lastName": "Admin2"}]'
        );
        $GF_ConnectWise->expects( $this->at( 0 ) )
            ->method( "send_request" )
            ->with(
                "system/members?pageSize=200",
                "GET",
                NULL
            )
            ->will( $this->returnValue( $mock_members_response ) );

        $actual_member_list = $GF_ConnectWise->get_team_members();
        $expected_member_list = array(
            array(
                "value" => "Admin1",
                "label" => "Training Admin1",
            ),
            array(
                "value" => "Admin2",
                "label" => "Training Admin2",
            )
        );

        $this->assertEquals( $actual_member_list, $expected_member_list);
    }
}
