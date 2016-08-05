<?php
require_once WP_PLUGIN_DIR . "/gravityforms/gravityforms.php";
require_once WP_PLUGIN_DIR . "/gravityformsconnectwise/class-gf-connectwise-v3.php";
require_once WP_PLUGIN_DIR . "/gravityformsconnectwise/class-cw-connection-version.php";
require_once 'vendor/autoload.php';

class GravityFormsConnectWiseAddOnTest extends WP_UnitTestCase {
    function setUp() {
        parent::setUp();

        $this->connectwise_plugin = new GFConnectWiseV3();
        $this->connectwise_api = new ConnectWiseApi();
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

    function test_create_company_and_contact_should_set_new_contact_to_primary() {
        $feed = array(
            "id" => "1",
            "form_id" => "1",
            "is_active" => "1",
            "meta" => array(
                "contact_map_fields_first_name"  => "2.3",
                "contact_map_fields_last_name"   => "2.6",
                "contact_map_fields_email"       => "3",
                "contact_type"                   => "1",
                "contact_department"             => "2",
                "company_type"                   => "1",
                "company_status"                 => "1",
                "company_note"                   => "Here is a company note",
                "company_map_fields"             => array(
                    array(
                        "key"        => "company",
                        "value"      => "2",
                        "custom_key" => ""
                    )
                )
            )
        );

        $lead = array(
            "2.3" => "Test Firstname",
            "2.6" => "Test Lastname",
            "3"   => "test@test.com",
            "2"   => "Test Company",
            "2.2" =>"",
            "2.4" => "",
            "2.8" => ""
        );

        $company_data = array(
            "id"           => 0,
            "identifier"   => "TestCompany",
            "name"         => "Test Company",
            "addressLine1" => "-",
            "addressLine2" => "-",
            "city"         => "-",
            "state"        => "-",
            "zip"          => "-",
            "phoneNumber"  => NULL,
            "faxNumber"    => NULL,
            "website"      => NULL,
            "note"         => "Here is a company note",
            "type"         => array(
                "id" => "1"
            ),
            "status"       => array(
                "id" => "1"
            )
        );

        $contact_data = array(
            "firstName"          => "Test Firstname",
            "lastName"           => "Test Lastname",
            "company"            => array(
                "identifier" => "TestCompany",
            ),
            "type"               => array(
                "id" => "1"
            ),
            "department"         => array(
                "id" => "2"
            )
        );

        $comunication_types = array(
            "value"             => "test@test.com",
            "communicationType" => "Email",
            "type"              => array(
                "id"   => 1,
                "name" => "Email"
            ),
            "defaultFlag" => true
        );

        $GF_ConnectWise = $this->getMockBuilder( "GFConnectWiseV3" )
                               ->setMethods( array( "send_request", "get_existing_contact" ) )
                               ->getMock();

        $GF_ConnectWise->expects( $this->at( 0 ) )
                       ->method( "send_request" )
                       ->with(
                            "company/companies",
                            "POST",
                            $company_data
                       );

        $GF_ConnectWise->expects( $this->at( 1 ) )
            ->method( "get_existing_contact" )
            ->will( $this->returnValue( false ) );

        $mock_contact_data = '{"id":20}';
        $mock_contact_response = array(
            "body" => $mock_contact_data
        );

        $company_update_data   = array(
            array(
                "op"    => "replace",
                "path"  => "defaultContact",
                "value" => json_decode( $mock_contact_response["body"] )
            )
        );

        $GF_ConnectWise->expects( $this->at( 2 ) )
                       ->method( "send_request" )
                       ->with(
                            "company/contacts",
                            "POST",
                            $contact_data
                       )
                       ->will( $this->returnValue( $mock_contact_response ) );

        $GF_ConnectWise->expects( $this->at( 3 ) )
                       ->method( "send_request" )
                       ->with(
                            "company/contacts/20/communications",
                            "POST",
                            $comunication_types
                       );

        $mock_company_response = array(
            "body" => '[{"id": 1}]'
        );

        $GF_ConnectWise->expects( $this->at( 4 ) )
                       ->method( "send_request" )
                       ->with(
                            "company/companies?conditions=identifier='TestCompany'",
                            "GET",
                            NULL
                       )
                       ->will($this->returnValue( $mock_company_response ));

        $GF_ConnectWise->expects( $this->at(5) )
                       ->method( "send_request" )
                       ->with(
                            "company/companies/1",
                            "PATCH",
                            $company_update_data
                       );

        $GF_ConnectWise->process_feed( $feed, $lead, array() );
    }

    function test_primary_default_contact_data_not_work_should_use_contact_id() {
        $feed = array(
            "id" => "1",
            "form_id" => "1",
            "is_active" => "1",
            "meta" => array(
                "contact_map_fields_first_name"  => "2.3",
                "contact_map_fields_last_name"   => "2.6",
                "contact_map_fields_email"       => "3",
                "contact_type"                   => "1",
                "contact_department"             => "2",
                "company_type"                   => "1",
                "company_status"                 => "1",
                "company_note"                   => "Here is a company note",
                "company_map_fields"             => array(
                    array(
                        "key"        => "company",
                        "value"      => "2",
                        "custom_key" => ""
                    )
                )
            )
        );

        $lead = array(
            "2.3" => "Test Firstname",
            "2.6" => "Test Lastname",
            "3"   => "test@test.com",
            "2"   => "Test Company",
            "2.2" =>"",
            "2.4" => "",
            "2.8" => ""
        );

        $company_data = array(
            "id"           => 0,
            "identifier"   => "TestCompany",
            "name"         => "Test Company",
            "addressLine1" => "-",
            "addressLine2" => "-",
            "city"         => "-",
            "state"        => "-",
            "zip"          => "-",
            "phoneNumber"  => NULL,
            "faxNumber"    => NULL,
            "website"      => NULL,
            "note"         => "Here is a company note",
            "type"         => array(
                "id" => "1"
            ),
            "status"       => array(
                "id" => "1"
            )
        );

        $contact_data = array(
            "firstName"          => "Test Firstname",
            "lastName"           => "Test Lastname",
            "company"            => array(
                "identifier" => "TestCompany",
            ),
            "type"               => array(
                "id" => "1"
            ),
            "department"         => array(
                "id" => "2"
            )
        );

        $comunication_types = array(
            "value"             => "test@test.com",
            "communicationType" => "Email",
            "type"              => array(
                "id"   => 1,
                "name" => "Email"
            ),
            "defaultFlag" => true
        );

        $GF_ConnectWise = $this->getMockBuilder( "GFConnectWiseV3" )
                               ->setMethods( array( "send_request", "get_existing_contact" ) )
                               ->getMock();

        $GF_ConnectWise->expects( $this->at( 0 ) )
                       ->method( "send_request" )
                       ->with(
                            "company/companies",
                            "POST",
                            $company_data
                       );

        $GF_ConnectWise->expects( $this->at( 1 ) )
            ->method( "get_existing_contact" )
            ->will( $this->returnValue( false ) );

        $mock_contact_data = '{"id":20}';
        $mock_contact_response = array(
            "body" => $mock_contact_data
        );

        $company_update_data   = array(
            array(
                "op"    => "replace",
                "path"  => "defaultContact",
                "value" => json_decode( $mock_contact_response["body"] )
            )
        );

        $GF_ConnectWise->expects( $this->at( 2 ) )
                       ->method( "send_request" )
                       ->with(
                            "company/contacts",
                            "POST",
                            $contact_data
                       )
                       ->will( $this->returnValue( $mock_contact_response ) );

        $GF_ConnectWise->expects( $this->at( 3 ) )
                       ->method( "send_request" )
                       ->with(
                            "company/contacts/20/communications",
                            "POST",
                            $comunication_types
                       );

        $mock_company_response = array(
            "body" => '[{"id": 1}]'
        );

        $GF_ConnectWise->expects( $this->at( 4 ) )
                       ->method( "send_request" )
                       ->with(
                            "company/companies?conditions=identifier='TestCompany'",
                            "GET",
                            NULL
                       )
                       ->will($this->returnValue( $mock_company_response ));

        $GF_ConnectWise->expects( $this->at(5) )
                       ->method( "send_request" )
                       ->with(
                            "company/companies/1",
                            "PATCH",
                            $company_update_data
                       );

        $GF_ConnectWise->process_feed( $feed, $lead, array() );
    }

}
