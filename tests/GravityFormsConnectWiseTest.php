<?php
require_once WP_PLUGIN_DIR . "/gravityforms/gravityforms.php";
require_once WP_PLUGIN_DIR . "/connectwise-forms-integration/class-gf-connectwise.php";
require_once WP_PLUGIN_DIR . "/connectwise-forms-integration/class-cw-connection-version.php";
require_once 'vendor/autoload.php';

class GravityFormsConnectWiseAddOnTest extends WP_UnitTestCase {
	function setUp() {
		parent::setUp();

		$this->connectwise_plugin = new GFConnectWise();
		$this->connectwise_api = new ConnectWiseVersion();
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

	function test_addon_settings_should_have_fields() {
		$actual = $this->connectwise_plugin->plugin_settings_fields();

		$this->assertEquals( count( $actual[0]["fields"] ), 5 );

		$expected_description  = "<p>Complete the settings below to authenticate with your ConnectWise account. ";
		$expected_description .= '<a href="https://pronto.zendesk.com/hc/en-us/articles/207946586" target="_blank">';
		$expected_description .= "Here's how to generate API keys.</a></p>";

		$this->assertEquals( $actual[0]["description"], $expected_description );

		$this->assertEquals( $actual[0]["fields"][0]["name"], "connectwise_url" );
		$this->assertEquals( $actual[0]["fields"][0]["label"], "ConnectWise URL" );
		$this->assertEquals( $actual[0]["fields"][0]["type"], "text" );
		$this->assertEquals( $actual[0]["fields"][0]["class"], "medium" );
		$this->assertEquals( $actual[0]["fields"][0]["tooltip"], "<h6>ConnectWise URL</h6>The URL you use to login to ConnectWise. You don&#039;t need to include https:// or anything after .com/.net. For example, just enter &quot;cw.yourcompany.com&quot;. If you use a hosted version, you can use that URL (na.myconnectwise.net)." );
		$this->assertTrue( array_key_exists( "save_callback", $actual[0]["fields"][0] ) );
		$this->assertTrue( array_key_exists( "feedback_callback", $actual[0]["fields"][0] ) );

		$this->assertEquals( $actual[0]["fields"][1]["name"], "company_id" );
		$this->assertEquals( $actual[0]["fields"][1]["label"], "Company ID" );
		$this->assertEquals( $actual[0]["fields"][1]["type"], "text" );
		$this->assertEquals( $actual[0]["fields"][1]["class"], "small" );
		$this->assertTrue( array_key_exists( "save_callback", $actual[0]["fields"][1] ) );
		$this->assertTrue( array_key_exists( "feedback_callback", $actual[0]["fields"][1] ) );

		$this->assertEquals( $actual[0]["fields"][2]["name"], "public_key" );
		$this->assertEquals( $actual[0]["fields"][2]["label"], "Public API Key" );
		$this->assertEquals( $actual[0]["fields"][2]["type"], "text" );
		$this->assertEquals( $actual[0]["fields"][2]["class"], "small" );
		$this->assertTrue( array_key_exists( "save_callback", $actual[0]["fields"][2] ) );
		$this->assertTrue( array_key_exists( "feedback_callback", $actual[0]["fields"][2] ) );

		$this->assertEquals( $actual[0]["fields"][3]["name"], "private_key" );
		$this->assertEquals( $actual[0]["fields"][3]["label"], "Private API Key" );
		$this->assertEquals( $actual[0]["fields"][3]["type"], "text" );
		$this->assertEquals( $actual[0]["fields"][3]["class"], "small" );
		$this->assertTrue( array_key_exists( "save_callback", $actual[0]["fields"][3] ) );
		$this->assertTrue( array_key_exists( "feedback_callback", $actual[0]["fields"][3] ) );

		$this->assertEquals( $actual[0]["fields"][4]["name"], "client_id" );
		$this->assertEquals( $actual[0]["fields"][4]["label"], "Client ID" );
		$this->assertEquals( $actual[0]["fields"][4]["type"], "text" );
		$this->assertEquals( $actual[0]["fields"][4]["class"], "small" );
		$this->assertTrue( array_key_exists( "save_callback", $actual[0]["fields"][4] ) );
		$this->assertTrue( array_key_exists( "feedback_callback", $actual[0]["fields"][4] ) );

		$this->assertEquals( count( $actual[1]["fields"] ), 2 );

		$this->assertEquals( $actual[1]["title"], "Error Notifications" );

		$this->assertEquals( $actual[1]["fields"][0]["name"], "error_notification_emails_to" );
		$this->assertEquals( $actual[1]["fields"][0]["label"], "Email Address" );
		$this->assertEquals( $actual[1]["fields"][0]["type"], "text" );
		$this->assertEquals( $actual[1]["fields"][0]["class"], "small" );
		$this->assertTrue( array_key_exists( "save_callback", $actual[1]["fields"][0] ) );
		$this->assertTrue( array_key_exists( "feedback_callback", $actual[1]["fields"][0] ) );

		$this->assertEquals( $actual[1]["fields"][1]["name"], "error_notification_emails_action" );
		$this->assertEquals( $actual[1]["fields"][1]["label"], "" );
		$this->assertEquals( $actual[1]["fields"][1]["type"], "checkbox" );
		$this->assertEquals( $actual[1]["fields"][1]["class"], "small" );
		$this->assertEquals( $actual[1]["fields"][1]["choices"][0]["name"], "enable_error_notification_emails" );
		$this->assertEquals( $actual[1]["fields"][1]["choices"][0]["label"], "Enable error notification emails" );
	}

	function test_settings_should_has_override_form_settings_style() {
		$actual = $this->connectwise_plugin->styles();

		$this->assertEquals( $actual[2]["handle"], "gform_connectwise_form_settings_css" );
		$this->assertEquals( $actual[2]["src"], "http://example.org/wp-content/plugins/connectwise-forms-integration/css/form_settings.css" );
		$this->assertEquals( $actual[2]["enqueue"][0]["admin_page"][0], "form_settings" );
	}

	function test_setting_field_should_clean_before_save() {
		$username = "<h2> auth_key</h2>";

		$actual = $this->connectwise_plugin->clean_field( "username", $username );

		$this->assertEquals( $actual, "auth_key" );
	}

	function test_transform_url_should_add_api_infront_of_staging_connectwise_url() {
		$ConnectWiseVersion = $this->getMockBuilder( "ConnectWiseVersion" )
							   ->setMethods( array( "get_plugin_setting" ) )
							   ->getMock();

		$ConnectWiseVersion->method( "get_plugin_setting" )
					   ->willReturn( "staging.connectwisetest.com" );

		$ConnectWiseVersion->expects( $this->once() )
					   ->method( "get_plugin_setting" )
					   ->with( "connectwise_url" );

		$input_url    = "system/members";
		$expected_url = "https://api-staging.connectwisetest.com/v4_6_release/apis/3.0/system/members";
		$actual_url   = $ConnectWiseVersion->transform_url( $input_url );

		$this->assertEquals( $actual_url, $expected_url );
	}

	function test_transform_url_should_add_api_infront_of_na_connectwise_url() {
		$ConnectWiseVersion = $this->getMockBuilder( "ConnectWiseVersion" )
			->setMethods( array( "get_plugin_setting" ) )
			->getMock();

		$ConnectWiseVersion->method( "get_plugin_setting" )
			->willReturn( "na.connectwisetest.com" );

		$ConnectWiseVersion->expects( $this->once() )
			->method( "get_plugin_setting" )
			->with( "connectwise_url" );

		$input_url    = "system/members";
		$expected_url = "https://api-na.connectwisetest.com/v4_6_release/apis/3.0/system/members";
		$actual_url   = $ConnectWiseVersion->transform_url( $input_url );

		$this->assertEquals( $expected_url, $actual_url );
	}

	function test_transform_url_should_add_api_infront_of_eu_connectwise_url() {
		$ConnectWiseVersion = $this->getMockBuilder( "ConnectWiseVersion" )
			->setMethods( array( "get_plugin_setting" ) )
			->getMock();

		$ConnectWiseVersion->method( "get_plugin_setting" )
			->willReturn( "eu.connectwisetest.com" );

		$ConnectWiseVersion->expects( $this->once() )
			->method( "get_plugin_setting" )
			->with( "connectwise_url" );

		$input_url    = "system/members";
		$expected_url = "https://api-eu.connectwisetest.com/v4_6_release/apis/3.0/system/members";
		$actual_url   = $ConnectWiseVersion->transform_url( $input_url );

		$this->assertEquals( $expected_url, $actual_url );
	}

	function test_transform_url_should_add_api_infront_of_aus_connectwise_url() {
		$ConnectWiseVersion = $this->getMockBuilder( "ConnectWiseVersion" )
			->setMethods( array( "get_plugin_setting" ) )
			->getMock();

		$ConnectWiseVersion->method( "get_plugin_setting" )
			->willReturn( "aus.connectwisetest.com" );

		$ConnectWiseVersion->expects( $this->once() )
			->method( "get_plugin_setting" )
			->with( "connectwise_url" );

		$input_url    = "system/members";
		$expected_url = "https://api-aus.connectwisetest.com/v4_6_release/apis/3.0/system/members";
		$actual_url   = $ConnectWiseVersion->transform_url( $input_url );

		$this->assertEquals( $expected_url, $actual_url );
	}

	function test_send_request_should_call_transform_url() {
		$ConnectWiseVersion = $this->getMockBuilder( "ConnectWiseVersion" )
			->setMethods( array( "transform_url" ) )
			->getMock();

		$ConnectWiseVersion->expects( $this->once() )
			->method( "transform_url" )
			->with( "system/info" );

		$GF_ConnectWise = new GFConnectWise();
		$GF_ConnectWise->connectwise_version = $ConnectWiseVersion;

		$GF_ConnectWise->send_request(
			"system/info",
			"GET",
			NULL
		);
	}

	function test_input_valid_email_should_return_true() {
		$email = "admin@mail.com";

		$actual = $this->connectwise_plugin->is_valid_email_settings( $email );

		$this->assertTrue( $actual );
	}

	function test_input_invalid_email_should_return_false() {
		$email = "admin@";

		$actual = $this->connectwise_plugin->is_valid_email_settings( $email );

		$this->assertFalse( $actual );
	}

	function test_save_valid_form_settings_should_return_true() {
		$mock_request = array(
			"response" => array(
				"code" => 200
			)
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request" ) )
			->getMock();

		$GF_ConnectWise->method( "send_request" )
			->willReturn( $mock_request );

		$GF_ConnectWise->expects( $this->once() )
			->method( "send_request" )
			->with(
				"system/info",
				"GET",
				NULL
			);

		$actual = $GF_ConnectWise->is_valid_settings();

		$this->assertTrue( $actual );
	}

	function test_save_invalid_username_password_should_return_false() {
		$mock_request = array(
			"response" => array(
				"code" => 404,
			)
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request" ) )
			->getMock();

		$GF_ConnectWise->method( "send_request" )
			->willReturn( $mock_request );

		$GF_ConnectWise->expects( $this->once() )
			->method( "send_request" )
			->with(
				"system/info",
				"GET",
				NULL
			);

		$actual = $GF_ConnectWise->is_valid_settings();

		$this->assertFalse( $actual );
	}

	function test_save_invalid_url_should_return_false() {
		$mock_request = new WP_Error(
			"Error", __( "Error to connect", "error_text" )
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request" ) )
			->getMock();

		$GF_ConnectWise->method( "send_request" )
			->willReturn( $mock_request );

		$GF_ConnectWise->expects( $this->once() )
			->method( "send_request" )
			->with(
				"system/info",
				"GET",
				NULL
			);

		$actual = $GF_ConnectWise->is_valid_settings();

		$this->assertFalse( $actual );
	}

	function test_field_map_title_should_return_correct_title() {
		$actual = $this->connectwise_plugin->field_map_title();

		$this->assertEquals( $actual, "ConnectWise Field" );
	}

	function test_can_create_feed_must_return_false_if_setting_is_invalid() {
		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( false );

		$actual = $GF_ConnectWise->can_create_feed();
		$this->assertFalse( $actual );
	}

	function test_can_create_feed_must_return_true_if_setting_is_valid() {
		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$actual = $GF_ConnectWise->can_create_feed();
		$this->assertTrue( $actual );
	}

	function test_feed_settings_fields_should_return_array_of_settings_field() {
		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array(
				"get_team_members",
				"get_departments",
				"get_service_board",
				"get_service_priority",
				"get_service_types",
				"get_service_subtypes",
				"get_service_item",
				"get_contact_types",
				"get_company_types",
				"get_company_statuses",
				"get_activity_types",
				"get_opportunity_types",
				"get_marketing_campaign",
			) )
			->getMock();

		$mock_members_response = array(
			array(
				"value" => "Admin1",
				"label" => "Admin Training 1",
			),
			array(
				"value" => "Admin2",
				"label" => "Admin Training 2",
			)
		);

		$mock_departments_response = array(
			array(
				"value" => NULL,
				"label" => "---------------",
			),
			array(
				"value" => "1",
				"label" => "Accounting",
			),
			array(
				"value" => "2",
				"label" => "Sales",
			)
		);

		$mock_boards_response = array(
			array(
				"value" => "1",
				"label" => "Normal",
			),
			array(
				"value" => "2",
				"label" => "Special",
			)
		);

		$mock_service_types_response = array(
			array(
				"value" => "1",
				"label" => "Break-fix",
			),
			array(
				"value" => "2",
				"label" => "Proactive",
			)
		);

		$mock_service_subtypes_response = array(
			array(
				"value" => "1",
				"label" => "CRM",
			),
			array(
				"value" => "2",
				"label" => "RMM",
			)
		);

		$mock_service_item_response = array(
			array(
				"value" => "1",
				"label" => "Service Board",
			),
			array(
				"value" => "2",
				"label" => "Workflow Rules",
			)
		);

		$mock_contact_types_response = array(
			array(
				"value" => "1",
				"label" => "Approver",
			),
			array(
				"value" => "2",
				"label" => "End User",
			)
		);

		$mock_company_types_response = array(
			array(
				"value" => "1",
				"label" => "Prospect",
			),
			array(
				"value" => "2",
				"label" => "Owner",
			)
		);

		$mock_company_statuses_response = array(
			array(
				"value" => "1",
				"label" => "Active",
			),
			array(
				"value" => "2",
				"label" => "Imported",
			)
		);

		$mock_opportunity_types_response = array(
			array(
				"value" => NULL,
				"label" => "---------------",
			),
			array(
				"value" => "1",
				"label" => "Training",
			),
			array(
				"value" => "2",
				"label" => "Cabling",
			)
		);

		$mock_priorities_response = array(
			array(
				"value" => "1",
				"label" => "Urgent",
			),
			array(
				"value" => "2",
				"label" => "High",
			)
		);

		$mock_activity_type_response = array(
			array(
				"value" => "1",
				"label" => "Call",
			),
			array(
				"value" => "2",
				"label" => "Quote",
			)
		);

		$mock_marketing_campaign_response = array(
			array(
				"value" => NULL,
				"label" => "---------------",
			),
			array(
				"value" => "1",
				"label" => "Test Campaign",
			)
		);

		$GF_ConnectWise->expects( $this->exactly( 2 ) )
			->method( "get_team_members" )
			->with()
			->will( $this->returnValue( $mock_members_response ) );

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "get_departments" )
			->with()
			->will( $this->returnValue( $mock_departments_response ) );

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "get_service_board" )
			->with()
			->will( $this->returnValue( $mock_boards_response ) );

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "get_service_priority" )
			->with()
			->will( $this->returnValue( $mock_priorities_response ) );

		$GF_ConnectWise->expects( $this->exactly(1) )
			->method( "get_service_types" )
			->with()
			->will($this->returnValue( $mock_service_types_response ));

		$GF_ConnectWise->expects( $this->exactly(1) )
			->method( "get_service_subtypes" )
			->with()
			->will($this->returnValue( $mock_service_subtypes_response ));

		$GF_ConnectWise->expects( $this->exactly(1) )
			->method( "get_service_item" )
			->with()
			->will($this->returnValue( $mock_service_item_response ));

		$GF_ConnectWise->expects( $this->exactly(1) )
			->method( "get_company_types" )
			->with()
			->will($this->returnValue( $mock_company_types_response ));

		$GF_ConnectWise->expects( $this->exactly(1) )
			->method( "get_opportunity_types" )
			->with()
			->will($this->returnValue( $mock_opportunity_types_response ));

		$GF_ConnectWise->expects( $this->exactly(1) )
			->method( "get_contact_types" )
			->with()
			->will($this->returnValue( $mock_contact_types_response ));

		$GF_ConnectWise->expects( $this->exactly(1) )
			->method( "get_opportunity_types" )
			->with()
			->will($this->returnValue( $mock_opportunity_types_response ));

		$GF_ConnectWise->expects( $this->exactly(1) )
			->method( "get_company_statuses" )
			->with()
			->will($this->returnValue( $mock_company_statuses_response ));

		$GF_ConnectWise->expects( $this->exactly(1) )
			->method( "get_activity_types" )
			->with()
			->will($this->returnValue( $mock_activity_type_response ));

		$GF_ConnectWise->expects( $this->exactly(1) )
			->method( "get_marketing_campaign" )
			->with()
			->will($this->returnValue( $mock_marketing_campaign_response ));

		$actual = $GF_ConnectWise->feed_settings_fields( NULL );
		$base_fields           = $actual[0];
		$contact_fields        = $actual[1];
		$company_fields        = $actual[2];
		$opportunity_fields    = $actual[3];
		$activity_fields       = $actual[4];
		$service_ticket_fields = $actual[5];
		$conditional_fields    = $actual[6];

		$opportunity_type_choices      = $opportunity_fields["fields"][1]["choices"];
		$marketing_campaign_choices    = $opportunity_fields["fields"][2]["choices"];
		$sales_rep_member_choices      = $opportunity_fields["fields"][3]["choices"];
		$assign_to_member_choices      = $activity_fields["fields"][1]["choices"];
		$contact_type_choices          = $contact_fields["fields"][1]["choices"];
		$contact_department_choices    = $contact_fields["fields"][2]["choices"];
		$service_board_choices         = $service_ticket_fields["fields"][1]["choices"];
		$service_priority_choices      = $service_ticket_fields["fields"][2]["choices"];

		$service_type_choices          = $service_ticket_fields["fields"][3]["choices"];
		$service_subtype_choices       = $service_ticket_fields["fields"][4]["choices"];
		$service_item_choices          = $service_ticket_fields["fields"][5]["choices"];

		$company_type_choices          = $company_fields["fields"][1]["choices"];
		$company_statuses_choices      = $company_fields["fields"][2]["choices"];
		$activity_type_choices         = $activity_fields["fields"][3]["choices"];

		$this->assertEquals( $base_fields["title"], "ConnectWise" );
		$this->assertEquals( $base_fields["fields"][0]["label"], "Feed name" );
		$this->assertEquals( $base_fields["fields"][0]["type"], "text" );
		$this->assertEquals( $base_fields["fields"][0]["name"], "feed_name" );
		$this->assertEquals( $base_fields["fields"][0]["class"], "small" );
		$this->assertEquals( $base_fields["fields"][0]["required"], true );
		$this->assertEquals( $base_fields["fields"][0]["tooltip"], "&lt;h6&gt;Name&lt;/h6&gt;Enter a feed name to uniquely identify this setup." );
		$this->assertEquals( $base_fields["fields"][1]["name"], "action" );
		$this->assertEquals( $base_fields["fields"][1]["label"], "Action" );
		$this->assertEquals( $base_fields["fields"][1]["type"], "checkbox" );
		$this->assertEquals( $base_fields["fields"][1]["tooltip"], "&lt;h6&gt;Action&lt;/h6&gt;When a feed is active, a Contact and Company lookup will happen each time. You can also set for an Opportunity, Activity and/or Service Ticket to be created.&lt;/br&gt;An Opportunity must be created in order to create an Activity." );
		$this->assertEquals( $base_fields["fields"][1]["onclick"], "jQuery(this).parents(\"form\").submit();" );
		$this->assertEquals( $base_fields["fields"][1]["choices"][0]["name"], "create_opportunity" );
		$this->assertEquals( $base_fields["fields"][1]["choices"][0]["label"], "Create New Opportunity" );
		$this->assertEquals( $base_fields["fields"][1]["choices"][1]["name"], "create_activity" );
		$this->assertEquals( $base_fields["fields"][1]["choices"][1]["label"], "Create New Activity" );
		$this->assertEquals( $base_fields["fields"][1]["choices"][2]["name"], "create_service_ticket" );
		$this->assertEquals( $base_fields["fields"][1]["choices"][2]["label"], "Create New Service Ticket" );

		$this->assertEquals( $contact_fields["title"], "Contact Details" );
		$this->assertEquals( $contact_fields["fields"][0]["name"], "contact_map_fields" );
		$this->assertEquals( $contact_fields["fields"][0]["label"], "Map Fields" );
		$this->assertEquals( $contact_fields["fields"][0]["type"], "field_map" );
		$this->assertTrue( array_key_exists( "field_map", $contact_fields["fields"][0] ) );

		$expected  = "&lt;h6&gt;Contact Map Fields&lt;/h6&gt;Select which Gravity Form fields pair with their respective ConnectWise fields.";
		$this->assertEquals( $contact_fields["fields"][0]["tooltip"], $expected );

		$this->assertEquals( $contact_fields["fields"][1]["name"], "contact_type" );
		$this->assertEquals( $contact_fields["fields"][1]["label"], "Contact Type" );
		$this->assertEquals( $contact_fields["fields"][1]["type"], "select" );
		$this->assertEquals( count( $contact_type_choices ), 2 );
		$this->assertEquals( $contact_type_choices[0]["value"], "1" );
		$this->assertEquals( $contact_type_choices[0]["label"], "Approver" );
		$this->assertEquals( $contact_type_choices[1]["value"], "2" );
		$this->assertEquals( $contact_type_choices[1]["label"], "End User" );

		$this->assertEquals( $contact_fields["fields"][2]["name"], "contact_department" );
		$this->assertEquals( $contact_fields["fields"][2]["label"], "Department" );
		$this->assertEquals( $contact_fields["fields"][2]["type"], "select" );
		$this->assertEquals( count( $contact_department_choices ), 3 );
		$this->assertEquals( $contact_department_choices[0]["value"], NULL );
		$this->assertEquals( $contact_department_choices[0]["label"], "---------------" );
		$this->assertEquals( $contact_department_choices[1]["value"], "1" );
		$this->assertEquals( $contact_department_choices[1]["label"], "Accounting" );
		$this->assertEquals( $contact_department_choices[2]["value"], "2" );
		$this->assertEquals( $contact_department_choices[2]["label"], "Sales" );

		$this->assertEquals( $contact_fields["fields"][3]["name"], "contact_note" );
		$this->assertEquals( $contact_fields["fields"][3]["label"], "Notes" );
		$this->assertEquals( $contact_fields["fields"][3]["type"], "textarea" );
		$this->assertEquals( $contact_fields["fields"][3]["class"], "medium merge-tag-support" );

		$this->assertEquals( $company_fields["title"], "Company Details" );
		$this->assertEquals( $company_fields["fields"][0]["name"], "company_map_fields" );
		$this->assertEquals( $company_fields["fields"][0]["label"], "Map Fields" );
		$this->assertEquals( $company_fields["fields"][0]["type"], "dynamic_field_map" );
		$this->assertEquals( $company_fields["fields"][0]["disable_custom"], true );
		$this->assertTrue( array_key_exists( "field_map", $company_fields["fields"][0] ) );

		$this->assertEquals( $company_fields["fields"][3]["name"], "company_as_lead" );
		$this->assertEquals( $company_fields["fields"][3]["label"], "" );
		$this->assertEquals( $company_fields["fields"][3]["type"], "checkbox" );
		$this->assertEquals( $company_fields["fields"][3]["choices"][0]["label"], "Mark this company as a lead" );
		$this->assertEquals( $company_fields["fields"][3]["choices"][0]["name"], "company_as_lead" );
		$this->assertEquals( $company_fields["fields"][3]["choices"][0]["tooltip"], "&lt;h6&gt;Mark this company as a lead&lt;/h6&gt;Checking this will tick the &quot;Is this company a lead?&quot; checkbox in the Company&#039;s Profile setting" );

		$this->assertEquals( $company_fields["fields"][4]["name"], "company_note" );
		$this->assertEquals( $company_fields["fields"][4]["label"], "Notes" );
		$this->assertEquals( $company_fields["fields"][4]["type"], "textarea" );
		$this->assertEquals( $company_fields["fields"][4]["class"], "medium merge-tag-support" );

		$this->assertEquals( count( $company_type_choices ), 2 );
		$this->assertEquals( $company_type_choices[0]["value"], "1" );
		$this->assertEquals( $company_type_choices[0]["label"], "Prospect" );
		$this->assertEquals( $company_type_choices[1]["value"], "2" );
		$this->assertEquals( $company_type_choices[1]["label"], "Owner" );

		$this->assertEquals( count( $company_statuses_choices ), 2 );
		$this->assertEquals( $company_statuses_choices[0]["value"], "1" );
		$this->assertEquals( $company_statuses_choices[0]["label"], "Active" );
		$this->assertEquals( $company_statuses_choices[1]["value"], "2" );
		$this->assertEquals( $company_statuses_choices[1]["label"], "Imported" );


		$expected  = "&lt;h6&gt;Company Map Fields&lt;/h6&gt;Select which Gravity Form fields pair with their respective ConnectWise fields.";
		$this->assertEquals( $company_fields["fields"][0]["tooltip"], $expected );

		$this->assertEquals( $service_ticket_fields["title"], "Service Ticket Details" );
		$this->assertEquals( $service_ticket_fields["dependency"]["field"], "create_service_ticket" );
		$this->assertEquals( $service_ticket_fields["dependency"]["values"], "1" );

		$this->assertEquals( $service_ticket_fields["fields"][0]["name"], "service_ticket_summary" );
		$this->assertEquals( $service_ticket_fields["fields"][0]["required"], true );
		$this->assertEquals( $service_ticket_fields["fields"][0]["label"], "Summary" );
		$this->assertEquals( $service_ticket_fields["fields"][0]["type"], "text" );
		$this->assertEquals( $service_ticket_fields["fields"][0]["class"], "medium merge-tag-support" );

		$this->assertEquals( $service_ticket_fields["fields"][1]["name"], "service_ticket_board" );
		$this->assertEquals( $service_ticket_fields["fields"][1]["required"], false );
		$this->assertEquals( $service_ticket_fields["fields"][1]["label"], "Board" );
		$this->assertEquals( $service_ticket_fields["fields"][1]["type"], "select" );

		$this->assertEquals( $service_ticket_fields["fields"][2]["name"], "service_ticket_priority" );
		$this->assertEquals( $service_ticket_fields["fields"][2]["required"], false );
		$this->assertEquals( $service_ticket_fields["fields"][2]["label"], "Priority" );
		$this->assertEquals( $service_ticket_fields["fields"][2]["type"], "select" );

		$this->assertEquals( $service_ticket_fields["fields"][3]["name"], "service_ticket_type" );
		$this->assertEquals( $service_ticket_fields["fields"][3]["required"], false );
		$this->assertEquals( $service_ticket_fields["fields"][3]["label"], "Type" );
		$this->assertEquals( $service_ticket_fields["fields"][3]["type"], "select" );

		$this->assertEquals( $service_ticket_fields["fields"][4]["name"], "service_ticket_subtype" );
		$this->assertEquals( $service_ticket_fields["fields"][4]["required"], false );
		$this->assertEquals( $service_ticket_fields["fields"][4]["label"], "Subtype" );
		$this->assertEquals( $service_ticket_fields["fields"][4]["type"], "select" );

		$this->assertEquals( $service_ticket_fields["fields"][5]["name"], "service_ticket_item" );
		$this->assertEquals( $service_ticket_fields["fields"][5]["required"], false );
		$this->assertEquals( $service_ticket_fields["fields"][5]["label"], "Item" );
		$this->assertEquals( $service_ticket_fields["fields"][5]["type"], "select" );

		$this->assertEquals( $service_ticket_fields["fields"][6]["name"], "service_ticket_initial_description" );
		$this->assertEquals( $service_ticket_fields["fields"][6]["required"], true );
		$this->assertEquals( $service_ticket_fields["fields"][6]["label"], "Initial Description" );
		$this->assertEquals( $service_ticket_fields["fields"][6]["type"], "textarea" );
		$this->assertEquals( $service_ticket_fields["fields"][6]["class"], "medium merge-tag-support" );

		$this->assertEquals( count( $service_board_choices ), 2 );
		$this->assertEquals( $service_board_choices[0]["value"], "1" );
		$this->assertEquals( $service_board_choices[0]["label"], "Normal" );
		$this->assertEquals( $service_board_choices[1]["value"], "2" );
		$this->assertEquals( $service_board_choices[1]["label"], "Special" );
		$this->assertEquals( count( $service_priority_choices ), 2 );
		$this->assertEquals( $service_priority_choices[0]["value"], "1" );
		$this->assertEquals( $service_priority_choices[0]["label"], "Urgent" );
		$this->assertEquals( $service_priority_choices[1]["value"], "2" );
		$this->assertEquals( $service_priority_choices[1]["label"], "High" );

		$this->assertEquals( count( $service_type_choices ), 2 );
		$this->assertEquals( $service_type_choices[0]["value"], "1" );
		$this->assertEquals( $service_type_choices[0]["label"], "Break-fix" );
		$this->assertEquals( $service_type_choices[1]["value"], "2" );
		$this->assertEquals( $service_type_choices[1]["label"], "Proactive" );

		$this->assertEquals( count( $service_subtype_choices ), 2 );
		$this->assertEquals( $service_subtype_choices[0]["value"], "1" );
		$this->assertEquals( $service_subtype_choices[0]["label"], "CRM" );
		$this->assertEquals( $service_subtype_choices[1]["value"], "2" );
		$this->assertEquals( $service_subtype_choices[1]["label"], "RMM" );

		$this->assertEquals( count( $service_item_choices ), 2 );
		$this->assertEquals( $service_item_choices[0]["value"], "1" );
		$this->assertEquals( $service_item_choices[0]["label"], "Service Board" );
		$this->assertEquals( $service_item_choices[1]["value"], "2" );
		$this->assertEquals( $service_item_choices[1]["label"], "Workflow Rules" );

		$this->assertEquals( $opportunity_fields["title"], "Opportunity Details" );
		$this->assertEquals( $opportunity_fields["dependency"]["field"], "create_opportunity" );
		$this->assertEquals( $opportunity_fields["dependency"]["values"], "1" );
		$this->assertEquals( $opportunity_fields["fields"][0]["name"], "opportunity_name" );
		$this->assertEquals( $opportunity_fields["fields"][0]["label"], "Summary" );
		$this->assertEquals( $opportunity_fields["fields"][0]["required"], true );
		$this->assertEquals( $opportunity_fields["fields"][0]["type"], "text" );
		$this->assertEquals( $opportunity_fields["fields"][0]["class"], "medium merge-tag-support" );
		$this->assertEquals( $opportunity_fields["fields"][0]["default_value"], "New Opportunity from page: {embed_post:post_title}" );
		$this->assertEquals( $opportunity_fields["fields"][2]["name"], "marketing_campaign" );
		$this->assertEquals( $opportunity_fields["fields"][2]["label"], "Marketing Campaign" );
		$this->assertEquals( $opportunity_fields["fields"][2]["type"], "select" );
		$this->assertEquals( $opportunity_fields["fields"][2]["tooltip"], "&lt;h6&gt;Marketing Campaign&lt;/h6&gt;Any Campaign you create in the Marketing section will be available here for you to attach to the Opportunity." );
		$this->assertEquals( $opportunity_fields["fields"][3]["name"], "opportunity_owner" );
		$this->assertEquals( $opportunity_fields["fields"][3]["label"], "Sales Rep" );
		$this->assertEquals( $opportunity_fields["fields"][3]["type"], "select" );
		$this->assertEquals( $opportunity_fields["fields"][4]["name"], "opportunity_closedate" );
		$this->assertEquals( $opportunity_fields["fields"][4]["required"], true );
		$this->assertEquals( $opportunity_fields["fields"][4]["label"], "Close Date" );
		$this->assertEquals( $opportunity_fields["fields"][4]["type"], "text" );
		$this->assertEquals( $opportunity_fields["fields"][4]["class"], "small" );
		$this->assertEquals( $opportunity_fields["fields"][4]["tooltip"], "<h6>Close Date</h6>Enter the number of days the Opportunity should remain open. For example, entering &quot;30&quot; means the Opportunity will close 30 days after it&#039;s created." );
		$this->assertEquals( $opportunity_fields["fields"][4]["default_value"], "30" );
		$this->assertEquals( $opportunity_fields["fields"][5]["name"], "opportunity_source" );
		$this->assertEquals( $opportunity_fields["fields"][5]["label"], "Source" );
		$this->assertEquals( $opportunity_fields["fields"][5]["type"], "text" );
		$this->assertEquals( $opportunity_fields["fields"][5]["class"], "medium" );
		$this->assertEquals( $opportunity_fields["fields"][6]["name"], "opportunity_note" );
		$this->assertEquals( $opportunity_fields["fields"][6]["label"], "Notes" );
		$this->assertEquals( $opportunity_fields["fields"][6]["type"], "textarea" );
		$this->assertEquals( $opportunity_fields["fields"][6]["class"], "medium merge-tag-support" );
		$this->assertEquals( count( $opportunity_type_choices ), 3 );
		$this->assertEquals( $opportunity_type_choices[0]["value"], NULL );
		$this->assertEquals( $opportunity_type_choices[0]["label"], "---------------" );
		$this->assertEquals( $opportunity_type_choices[1]["value"], "1" );
		$this->assertEquals( $opportunity_type_choices[1]["label"], "Training" );
		$this->assertEquals( $opportunity_type_choices[2]["value"], "2" );
		$this->assertEquals( $opportunity_type_choices[2]["label"], "Cabling" );

		$this->assertEquals( count( $marketing_campaign_choices ), 2 );
		$this->assertEquals( $marketing_campaign_choices[0]["value"], NULL );
		$this->assertEquals( $marketing_campaign_choices[0]["label"], "---------------" );
		$this->assertEquals( $marketing_campaign_choices[1]["value"], 1 );
		$this->assertEquals( $marketing_campaign_choices[1]["label"], "Test Campaign" );

		$this->assertEquals( count( $sales_rep_member_choices ), 2 );
		$this->assertEquals( $sales_rep_member_choices[0]["value"], "Admin1" );
		$this->assertEquals( $sales_rep_member_choices[0]["label"], "Admin Training 1" );
		$this->assertEquals( $sales_rep_member_choices[1]["value"], "Admin2" );
		$this->assertEquals( $sales_rep_member_choices[1]["label"], "Admin Training 2" );

		$this->assertEquals( $activity_fields["title"], "Activity Details" );
		$this->assertEquals( $activity_fields["dependency"]["field"], "create_activity" );
		$this->assertEquals( $activity_fields["dependency"]["values"], "1" );
		$this->assertEquals( $activity_fields["fields"][0]["name"], "activity_name" );
		$this->assertEquals( $activity_fields["fields"][0]["label"], "Subject" );
		$this->assertEquals( $activity_fields["fields"][0]["type"], "text" );
		$this->assertEquals( $activity_fields["fields"][0]["default_value"], "Follow up with web lead" );
		$this->assertEquals( $activity_fields["fields"][0]["class"], "medium merge-tag-support" );
		$this->assertEquals( $activity_fields["fields"][1]["name"], "activity_assigned_to" );
		$this->assertEquals( $activity_fields["fields"][1]["label"], "Assign To" );
		$this->assertEquals( $activity_fields["fields"][1]["type"], "select" );

		$this->assertEquals( $activity_fields["fields"][2]["name"], "activity_duedate" );
		$this->assertEquals( $activity_fields["fields"][2]["label"], "Due Date" );
		$this->assertEquals( $activity_fields["fields"][2]["type"], "text" );
		$this->assertEquals( $activity_fields["fields"][2]["class"], "small" );
		$this->assertEquals( $activity_fields["fields"][2]["required"], True );
		$this->assertEquals( $activity_fields["fields"][2]["tooltip"], "<h6>Due Date</h6>Enter the number of days until the Activity should be due. For example, entering &quot;7&quot; means the Activity will be due 7 days after it&#039;s created." );

		$this->assertEquals( $activity_fields["fields"][4]["name"], "activity_note" );
		$this->assertEquals( $activity_fields["fields"][4]["label"], "Notes" );
		$this->assertEquals( $activity_fields["fields"][4]["type"], "textarea" );
		$this->assertEquals( $activity_fields["fields"][4]["class"], "medium merge-tag-support" );

		$this->assertEquals( count( $assign_to_member_choices), 2 );
		$this->assertEquals( $assign_to_member_choices[0]["value"], "Admin1" );
		$this->assertEquals( $assign_to_member_choices[0]["label"], "Admin Training 1" );
		$this->assertEquals( $assign_to_member_choices[1]["value"], "Admin2" );
		$this->assertEquals( $assign_to_member_choices[1]["label"], "Admin Training 2" );

		$this->assertEquals( count( $activity_type_choices), 2 );
		$this->assertEquals( $activity_type_choices[0]["value"], "1" );
		$this->assertEquals( $activity_type_choices[0]["label"], "Call" );
		$this->assertEquals( $activity_type_choices[1]["value"], "2" );
		$this->assertEquals( $activity_type_choices[1]["label"], "Quote" );

		$this->assertEquals( $conditional_fields["dependency"][1], "show_conditional_logic_field" );
		$this->assertEquals( $conditional_fields["title"], "Feed Conditional Logic");
		$this->assertEquals( $conditional_fields["fields"][0]["type"], "feed_condition");
		$this->assertEquals( $conditional_fields["fields"][0]["name"], "feed_condition");
		$this->assertEquals( $conditional_fields["fields"][0]["label"], "Conditional Logic");
		$this->assertEquals( $conditional_fields["fields"][0]["checkbox_label"], "Enable");
		$this->assertEquals( $conditional_fields["fields"][0]["instructions"], "Export to ConnectWise if");
		$this->assertEquals( $conditional_fields["fields"][0]["tooltip"], "<h6>Conditional Logic</h6>When conditional logic is enabled, form submissions will only be exported to ConnectWise when the condition is met. When disabled, all form submissions will be posted.");
	}

	function test_standard_fields_mapping_should_return_array_of_fields() {
		$actual = $this->connectwise_plugin->standard_fields_mapping();

		$this->assertEquals( $actual[0]["name"], "first_name" );
		$this->assertEquals( $actual[0]["label"], "First Name" );
		$this->assertEquals( $actual[0]["required"], true );
		$this->assertEquals( $actual[0]["field_type"], array( "name", "text", "hidden" ) );

		$this->assertEquals( $actual[1]["name"], "last_name" );
		$this->assertEquals( $actual[1]["label"], "Last Name" );
		$this->assertEquals( $actual[1]["required"], true );
		$this->assertEquals( $actual[1]["field_type"], array( "name", "text", "hidden" ) );

		$this->assertEquals( $actual[2]["name"], "email" );
		$this->assertEquals( $actual[2]["label"], "Email" );
		$this->assertEquals( $actual[2]["required"], true );
		$this->assertEquals( $actual[2]["field_type"], array( "email", "text", "hidden" ) );
	}

	function test_custom_fields_mapping_should_return_array_of_fields() {
		$actual = $this->connectwise_plugin->custom_fields_mapping();

		$this->assertEquals( $actual[0]["label"], "Choose a Field" );
		$this->assertEquals( $actual[0]["choices"][0]["label"], "Company" );
		$this->assertEquals( $actual[0]["choices"][0]["value"], "company" );
		$this->assertEquals( $actual[0]["choices"][1]["label"], "Address 1" );
		$this->assertEquals( $actual[0]["choices"][1]["value"], "address_1" );
		$this->assertEquals( $actual[0]["choices"][2]["label"], "Address 2" );
		$this->assertEquals( $actual[0]["choices"][2]["value"], "address_2" );
		$this->assertEquals( $actual[0]["choices"][3]["label"], "City" );
		$this->assertEquals( $actual[0]["choices"][3]["value"], "city" );
		$this->assertEquals( $actual[0]["choices"][4]["label"], "State" );
		$this->assertEquals( $actual[0]["choices"][4]["value"], "state" );
		$this->assertEquals( $actual[0]["choices"][5]["label"], "Zip" );
		$this->assertEquals( $actual[0]["choices"][5]["value"], "zip" );
		$this->assertEquals( $actual[0]["choices"][6]["label"], "Phone" );
		$this->assertEquals( $actual[0]["choices"][6]["value"], "phone_number" );
		$this->assertEquals( $actual[0]["choices"][7]["label"], "Fax" );
		$this->assertEquals( $actual[0]["choices"][7]["value"], "fax_number" );
		$this->assertEquals( $actual[0]["choices"][8]["label"], "Web site" );
		$this->assertEquals( $actual[0]["choices"][8]["value"], "web_site" );
	}

	function test_process_feed_with_company_should_create_new_company_and_contact() {
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
			"state"        => "CA",
			"zip"          => "-",
			"phoneNumber"  => NULL,
			"faxNumber"    => NULL,
			"website"      => NULL,
			"type"         => array(
				"id" => "1"
			),
			"status"       => array(
				"id" => "1"
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

		$contact_data = array(
			"firstName"          => "Test Firstname",
			"lastName"           => "Test Lastname",
			"company"            => array(
				"identifier" => "TestCompany",
			),
			"type"               => array(
				"id"         => "1"
			),
			"department"         => array(
				"id"         => "2"
			)
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='TestCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( array() ));

		$GF_ConnectWise->expects( $this->at( 3 ) )
			->method( "send_request" )
			->with(
				"company/companies",
				"POST",
				$company_data
			);

		$GF_ConnectWise->expects( $this->at( 3 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$mock_contact_data = '{"id":1}';
		$mock_contact_response = array(
			"body" => $mock_contact_data
		);

		$GF_ConnectWise->expects( $this->at( 4 ) )
			->method( "send_request" )
			->with(
				"company/contacts",
				"POST",
				$contact_data
			)
			->will( $this->returnValue( $mock_contact_response ) );

		$GF_ConnectWise->expects( $this->at( 5 ) )
			->method( "send_request" )
			->with(
				"company/contacts/1/communications",
				"POST",
				$comunication_types
			);

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_process_feed_with_company_with_all_other_data_should_create_correct_value() {
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
				"company_map_fields"            => array(
					array(
						"key"        => "company",
						"value"      => "2",
						"custom_key" => ""
					),
					array(
						"key"        => "address_1",
						"value"      => "4",
						"custom_key" => ""
					),
					array(
						"key"        => "address_2",
						"value"      => "5",
						"custom_key" => ""
					),
					array(
						"key"        => "city",
						"value"      => "6",
						"custom_key" => ""
					),
					array(
						"key"        => "state",
						"value"      => "7",
						"custom_key" => ""
					),
					array(
						"key"        => "zip",
						"value"      => "8",
						"custom_key" => ""
					),
					array(
						"key"        => "phone_number",
						"value"      => "9",
						"custom_key" => ""
					),
					array(
						"key"        => "fax_number",
						"value"      => "10",
						"custom_key" => ""
					),
					array(
						"key"        => "web_site",
						"value"      => "11",
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
			"2.2" => "",
			"2.4" => "",
			"2.8" => "",
			"4"   => "22/25 Jatujak",
			"5"   => "Jatujak",
			"6"   => "CA",
			"7"   => "KA",
			"8"   => "65000",
			"9"   => "023456789",
			"10"  => "1234",
			"11"  => "www.google.com",
		);

		$company_data = array(
			"id"           => 0,
			"identifier"   => "TestCompany",
			"name"         => "Test Company",
			"addressLine1" => "22/25 Jatujak",
			"addressLine2" => "Jatujak",
			"city"         => "CA",
			"state"        => "KA",
			"zip"          => "65000",
			"phoneNumber"  => "023456789",
			"faxNumber"    => "1234",
			"website"      => "www.google.com",
			"type"         => array(
				"id" => "1"
			),
			"status"       => array(
				"id" => "1"
			)
		);

		$comunication_types = array(
			"value"             => "test@test.com",
			"communicationType" => "Email",
			"type"              => array(
				"id"   => 1,
				"name" => "Email"
			),
			"defaultFlag"       => true
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

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='TestCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( array() ));

		$GF_ConnectWise->expects( $this->at( 3 ) )
			->method( "send_request" )
			->with(
				"company/companies",
				"POST",
				$company_data
			);

		$mock_contact_data = '{"id":1}';
		$mock_contact_response = array(
			"body" => $mock_contact_data
		);

		$GF_ConnectWise->expects( $this->at( 4 ) )
			->method( "send_request" )
			->with(
				"company/contacts",
				"POST",
				$contact_data
			)
			->will( $this->returnValue( $mock_contact_response ) );

		$GF_ConnectWise->expects( $this->at( 5 ) )
			->method( "send_request" )
			->with(
				"company/contacts/1/communications",
				"POST",
				$comunication_types
			);

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_process_feed_with_company_with_lead_flag_should_mark_as_lead() {
		$feed = array(
			"id" => "1",
			"form_id" => "1",
			"is_active" => "1",
			"meta" => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"contact_type"                  => "1",
				"contact_department"            => "2",
				"company_type"                  => "1",
				"company_status"                => "1",
				"company_as_lead"               => "1",
				"company_map_fields"            => array(
					array(
						"key"        => "company",
						"value"      => "2",
						"custom_key" => ""
					),
					array(
						"key"        => "address_1",
						"value"      => "4",
						"custom_key" => ""
					),
					array(
						"key"        => "address_2",
						"value"      => "5",
						"custom_key" => ""
					),
					array(
						"key"        => "city",
						"value"      => "6",
						"custom_key" => ""
					),
					array(
						"key"        => "state",
						"value"      => "7",
						"custom_key" => ""
					),
					array(
						"key"        => "zip",
						"value"      => "8",
						"custom_key" => ""
					),
					array(
						"key"        => "phone_number",
						"value"      => "9",
						"custom_key" => ""
					),
					array(
						"key"        => "fax_number",
						"value"      => "10",
						"custom_key" => ""
					),
					array(
						"key"        => "web_site",
						"value"      => "11",
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
			"2.2" => "",
			"2.4" => "",
			"2.8" => "",
			"4"   => "22/25 Jatujak",
			"5"   => "Jatujak",
			"6"   => "CA",
			"7"   => "KA",
			"8"   => "65000",
			"9"   => "023456789",
			"10"  => "1234",
			"11"  => "www.google.com",
		);

		$company_data = array(
			"id"           => 0,
			"identifier"   => "TestCompany",
			"name"         => "Test Company",
			"addressLine1" => "22/25 Jatujak",
			"addressLine2" => "Jatujak",
			"city"         => "CA",
			"state"        => "KA",
			"zip"          => "65000",
			"phoneNumber"  => "023456789",
			"faxNumber"    => "1234",
			"website"      => "www.google.com",
			"type"         => array(
				"id" => "1"
			),
			"status"       => array(
				"id" => "1"
			),
			"leadFlag"     => true,
		);

		$comunication_types = array(
			"value"             => "test@test.com",
			"communicationType" => "Email",
			"type"              => array(
				"id"   => 1,
				"name" => "Email"
			),
			"defaultFlag"       => true
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

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='TestCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( array() ));

		$GF_ConnectWise->expects( $this->at( 3 ) )
			->method( "send_request" )
			->with(
				"company/companies",
				"POST",
				$company_data
			);

		$mock_contact_data = '{"id":1}';
		$mock_contact_response = array(
			"body" => $mock_contact_data
		);

		$GF_ConnectWise->expects( $this->at( 4 ) )
			->method( "send_request" )
			->with(
				"company/contacts",
				"POST",
				$contact_data
			)
			->will( $this->returnValue( $mock_contact_response ) );

		$GF_ConnectWise->expects( $this->at( 5 ) )
			->method( "send_request" )
			->with(
				"company/contacts/1/communications",
				"POST",
				$comunication_types
			);

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_company_identifier_should_remove_special_char_and_max_length_not_more_than_25() {
		$feed = array(
			"id" => "1",
			"form_id" => "1",
			"is_active" => "1",
			"meta" => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"contact_type"                  => "1",
				"contact_department"            => "2",
				"company_type"                  => "1",
				"company_status"                => "1",
				"company_map_fields"            => array(
					array(
						"key"        => "company",
						"value"      => "2",
						"custom_key" => ""
					),
				)
			)
		);

		$lead = array(
			"2.3" => "Test Firstname",
			"2.6" => "Test Lastname",
			"3"   => "test@test.com",
			"2"   => "Test@Company ,,%$ AAAA BBBB CCCC DDDD",
		);

		$company_data = array(
			"id"           => 0,
			"identifier"   => "TestCompanyAAAABBBBCCCCDD",
			"name"         => "Test@Company ,,%$ AAAA BBBB CCCC DDDD",
			"addressLine1" => "-",
			"addressLine2" => "-",
			"city"         => "-",
			"state"        => "CA",
			"zip"          => "-",
			"phoneNumber"  => NULL,
			"faxNumber"    => NULL,
			"website"      => NULL,
			"type"         => array(
				"id" => "1"
			),
			"status"       => array(
				"id" => "1"
			)
		);

		$comunication_types = array(
			array(
				"value"             => "test@test.com",
				"communicationType" => "Email",
				"type"              => array(
					"id"   => 1,
					"name" => "Email"
				),
				"defaultFlag"       => true
			)
		);

		$contact_data = array(
			"firstName"          => "Test Firstname",
			"lastName"           => "Test Lastname",
			"company"            => array(
				"identifier" => "TestCompanyAAAABBBBCCCCDD",
			),
			"type"               => array(
				"id" => "1"
			),
			"department"         => array(
				"id" => "2"
			)
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='TestCompanyAAAABBBBCCCCDD'",
				"GET",
				NULL
			)
			->will( $this->returnValue( array() ));

		$GF_ConnectWise->expects( $this->at( 3 ) )
			->method( "send_request" )
			->with(
				"company/companies",
				"POST",
				$company_data
			);

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function get_cw_system_info() {
		$mock_connectwise_version_response = array(
			"response" => array(
				"code" => 200
			),
			"body"     => '{"version": "v2016.5.41842" }'
		);

		return $mock_connectwise_version_response;
	}

	function test_get_connectwise_version_should_return_version() {
		add_filter( "pre_http_request", array( $this, "get_cw_system_info" ), 10, 3 );

		$ConnectWiseVersion = new ConnectWiseVersion();
		$existing_version = $ConnectWiseVersion->get();

		$expect_version = "2016.5.41842";
		$this->assertEquals( $existing_version, $expect_version);
	}

	function test_get_existing_contact_should_return_contact_if_email_is_exsiting() {
		$firstname      = "FirstName";
		$email          = "test@test.com";

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request" ) )
			->getMock();

		$mock_contact_data = '[{"id": "1", "firstName": "FirstName", "communicationItems": [{"communicationType": "Email", "value": "test@test.com"}]}]';

		$mock_contact_response = array(
			'body' => $mock_contact_data
		);

		$GF_ConnectWise->expects( $this->at( 0 ) )
			->method( "send_request" )
			->with(
				"company/contacts?conditions=firstname='FirstName'",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_contact_response ) );

		$existing_contact = $GF_ConnectWise->get_existing_contact( $firstname, $email );
		$expect_contact = json_decode( $mock_contact_data )[0];

		$this->assertEquals( $existing_contact, $expect_contact);
	}

	function test_get_existing_contact_should_return_contact_if_email_is_exsiting_with_no_communication_items() {
		$firstname      = "FirstName";
		$email          = "test@test.com";

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request" ) )
			->getMock();

		$mock_contact_data = '[{"id": "1", "firstName": "FirstName"}]';

		$mock_contact_response = array(
			"body" => $mock_contact_data
		);

		$GF_ConnectWise->expects( $this->at( 0 ) )
			->method( "send_request" )
			->with(
				"company/contacts?conditions=firstname='FirstName'",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_contact_response ) );

		$communication_items = '[{"communicationType": "Email", "value": "test@test.com"}]';
		$mock_communication_item_response = array(
			"body" => $communication_items
		);

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "send_request" )
			->with(
				"company/contacts/1/communications",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_communication_item_response ) );

		$existing_contact = $GF_ConnectWise->get_existing_contact( $firstname, $email );
		$expect_contact = json_decode( $mock_contact_data )[0];

		$this->assertEquals( $existing_contact, $expect_contact);
	}

	function test_get_existing_contact_should_return_false_if_email_isnot_exsiting() {
		$firstname      = "FirstName";
		$email          = "test1@test.com";

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request" ) )
			->getMock();

		$mock_contact_data = '[{"id": "1", "firstName": "FirstName", "communicationItems": [{"communicationType": "Email", "value": "test@test.com"}]}]';

		$mock_contact_response = array(
			'body' => $mock_contact_data
		);

		$GF_ConnectWise->expects( $this->at( 0 ) )
			->method( "send_request" )
			->with(
				"company/contacts?conditions=firstname='FirstName'",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_contact_response ) );

		$existing_contact = $GF_ConnectWise->get_existing_contact( $firstname, $email );

		$this->assertEquals( $existing_contact, false);
	}

	function test_create_company_with_existing_email_in_contact_should_not_create_new_contact() {
		$feed = array(
			"id" => "1",
			"form_id" => "1",
			"is_active" => "1",
			"meta" => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"contact_type"                  => "1",
				"contact_department"            => "2",
				"company_type"                  => "1",
				"company_status"                => "1",
				"company_map_fields"            => array(
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
			"2"   => "New Company",
			"2.2" => "",
			"2.4" => "",
			"2.8" => ""
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

		$contact_data = array(
			"firstName"          => "Test Firstname",
			"lastName"           => "Test Lastname",
			"company"            => array(
				"identifier" => "NewCompany",
			),
			"department"         => array(
				"id"         => "1"
			),
			"communicationItems" => $communication_types
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$mock_contact_data = '[{"id": "1", "firstName": "FirstName", "communicationItems": [{"communicationType": "Email", "value": "test@test.com"}]}]';
		$mock_contact_response = array(
			'body' => $mock_contact_data
		);

		$company_data = array(
			"id"           => 0,
			"identifier"   => "NewCompany",
			"name"         => "New Company",
			"addressLine1" => "-",
			"addressLine2" => "-",
			"city"         => "-",
			"state"        => "CA",
			"zip"          => "-",
			"phoneNumber"  => NULL,
			"faxNumber"    => NULL,
			"website"      => NULL,
			"type"         => array(
				"id" => "1"
			),
			"status"       => array(
				"id" => "1"
			)
		);

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( $mock_contact_response ) );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='NewCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( array() ));

		$GF_ConnectWise->expects( $this->at( 3 ) )
			->method( "send_request" )
			->with(
				"company/companies",
				"POST",
				$company_data
			);

		$GF_ConnectWise->process_feed( $feed, $lead, NULL );
	}

	function test_create_company_with_inexisting_email_in_contact_should_create_new_contact() {
		$feed = array(
			"id" => "1",
			"form_id" => "1",
			"is_active" => "1",
			"meta" => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"contact_type"                  => "1",
				"contact_department"            => "2",
				"company_type"                  => "1",
				"company_status"                => "1",
				"company_map_fields"            => array(
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
			"2"   => "New Company",
			"2.2" => "",
			"2.4" => "",
			"2.8" => ""
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

		$contact_data = array(
			"firstName"          => "Test Firstname",
			"lastName"           => "Test Lastname",
			"company"            => array(
				"identifier" => "NewCompany",
			),
			"type"               => array(
				"id" => "1"
			),
			"department"         => array(
				"id" => "2"
			)
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$company_data = array(
			"id"           => 0,
			"identifier"   => "NewCompany",
			"name"         => "New Company",
			"addressLine1" => "-",
			"addressLine2" => "-",
			"city"         => "-",
			"state"        => "CA",
			"zip"          => "-",
			"phoneNumber"  => null,
			"faxNumber"    => null,
			"website"      => null,
			"type"         => array(
				"id" => "1"
			),
			"status"       => array(
				"id" => "1"
			)
		);

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='NewCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( array() ));

		$GF_ConnectWise->expects( $this->at( 3 ) )
			->method( "send_request" )
			->with(
				"company/companies",
				"POST",
				$company_data
			);

		$mock_contact_data = '{"id":1}';
		$mock_contact_response = array(
			"body" => $mock_contact_data
		);

		$GF_ConnectWise->expects( $this->at( 4 ) )
			->method( "send_request" )
			->with(
				"company/contacts",
				"POST",
				$contact_data
			)
			->will( $this->returnValue( $mock_contact_response ) );


		$GF_ConnectWise->expects( $this->at( 5 ) )
			->method( "send_request" )
			->with(
				"company/contacts/1/communications",
				"POST",
				$comunication_types
			);

		$GF_ConnectWise->expects( $this->at( 6 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='NewCompany'",
				"GET",
				NULL
			);

		$GF_ConnectWise->process_feed( $feed, $lead, NULL );
	}

	function test_submit_existing_contact_with_new_company_should_not_update_to_be_primary_contact() {
		$feed = array(
			"id"        => "1",
			"form_id"   => "1",
			"is_active" => "1",
			"meta"      => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"contact_type"                  => "1",
				"contact_department"            => "2",
				"company_type"                  => "1",
				"company_status"                => "1",
				"company_map_fields"            => array(
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
			"2"   => "New Company",
			"2.2" => "",
			"2.4" => "",
			"2.8" => ""
		);

		$comunication_types = array(
			"value"             => "test@test.com",
			"communicationType" => "Email",
			"type"              => array(
				"id"   => 1,
				"name" => "Email"
			),
			"defaultFlag"       => true
		);

		$contact_data = array(
			"firstName"          => "Test Firstname",
			"lastName"           => "Test Lastname",
			"company"            => array(
				"identifier" => "TestCompany",
			),
			"department"         => array(
				"id"         => "1"
			),
			"communicationItems" => $communication_types
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$mock_contact_data = '{"id": "1", "firstName": "FirstName", "communicationItems": [{"communicationType": "Email", "value": "test@test.com"}], "company": {"identifier": "TestCompany"}}';
		$mock_contact_response = json_decode( $mock_contact_data );

		$mock_company_response = array(
			"body" => '[{"id": "1"}]'
		);

		$company_data = array(
			"id"           => 0,
			"identifier"   => "NewCompany",
			"name"         => "New Company",
			"addressLine1" => "-",
			"addressLine2" => "-",
			"city"         => "-",
			"state"        => "CA",
			"zip"          => "-",
			"phoneNumber"  => NULL,
			"faxNumber"    => NULL,
			"website"      => NULL,
			"type"         => array(
				"id" => "1"
			),
			"status"       => array(
				"id" => "1"
			)
		);

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( $mock_contact_response ) );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='NewCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( array() ) );

		$GF_ConnectWise->expects( $this->at( 3 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='TestCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_company_response ) );

		$GF_ConnectWise->expects( $this->exactly( 2 ) )
			->method( "send_request" );

		$GF_ConnectWise->process_feed( $feed, $lead, NULL );
	}

	function test_process_feed_without_company_data_should_create_contact_to_catchall() {
		$feed = array(
			"id" => "1",
			"form_id" => "1",
			"is_active" => "1",
			"meta" => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"contact_type"                  => "1",
				"contact_department"            => "2",
				"company_type"                  => "1",
				"company_status"                => "1",
				"company_map_fields"            => array(
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
			"2"   => "",
			"2.2" => "",
			"2.4" => "",
			"2.8" => ""
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

		$contact_data = array(
			"firstName"          => "Test Firstname",
			"lastName"           => "Test Lastname",
			"company"            => array(
				"identifier" => "Catchall",
			),
			"type"               => array(
				"id" => "1"
			),
			"department"         => array(
				"id" => "2"
			)
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$mock_contact_data = '{"id":1}';
		$mock_contact_response = array(
			"body" => $mock_contact_data
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
				"company/contacts/1/communications",
				"POST",
				$comunication_types
			);

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_create_contact_with_note_should_send_note_to_connectwise() {
		$feed = array(
			"id" => "1",
			"form_id" => "1",
			"is_active" => "1",
			"meta" => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"contact_type"                  => "1",
				"contact_department"            => "2",
				"company_type"                  => "1",
				"company_status"                => "1",
				"contact_note"                  => "Please call this contact",
				"company_map_fields"            => array(
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
			"2"   => "",
			"2.2" => "",
			"2.4" => "",
			"2.8" => ""
		);

		$contact_data = array(
			"firstName"          => "Test Firstname",
			"lastName"           => "Test Lastname",
			"company"            => array(
				"identifier" => "Catchall",
			),
			"type"               => array(
				"id" => "1"
			),
			"department"         => array(
				"id" => "2"
			)
		);

		$comunication_data = array(
				"value"             => "test@test.com",
				"communicationType" => "Email",
				"type"              => array(
					"id"   => 1,
					"name" => "Email"
				),
				"defaultFlag" => true,
			);

		$mock_contact_data = '{"id":1}';
		$mock_contact_response = array(
			"body" => $mock_contact_data
		);

		$note_data = array(
			"text" => "Please call this contact"
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

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
							"company/contacts/1/communications",
							"POST",
							$comunication_data
					   );

		$GF_ConnectWise->expects( $this->at( 4 ) )
					   ->method( "send_request" )
					   ->with(
							"company/contacts/1/notes",
							"POST",
							$note_data
						);

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_create_contact_with_default_value_should_create_contact() {
		$feed = array(
			"id" => "1",
			"form_id" => "1",
			"is_active" => "1",
			"meta" => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"contact_type"                  => "1",
				"contact_department"            => "---------------",
				"company_type"                  => "1",
				"company_status"                => "1",
				"company_map_fields"            => array(
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
			"2"   => "",
			"2.2" => "",
			"2.4" => "",
			"2.8" => ""
		);

		$contact_data = array(
			"firstName"          => "Test Firstname",
			"lastName"           => "Test Lastname",
			"company"            => array(
				"identifier" => "Catchall",
			),
			"type"               => array(
				"id" => "1"
			)
		);

		$note_data = array(
			"text" => "Please call this contact"
		);

		$mock_contact_data = '{"id":1}';
		$mock_contact_response = array(
			"body" => $mock_contact_data
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/contacts",
				"POST",
				$contact_data
			)
			->will( $this->returnValue( $mock_contact_response ) );

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_create_company_with_note_should_send_note_to_connectwise() {
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
				"company_note"                   => "Company Note",
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
			"state"        => "CA",
			"zip"          => "-",
			"phoneNumber"  => NULL,
			"faxNumber"    => NULL,
			"website"      => NULL,
			"type"         => array(
				"id" => "1"
			),
			"status"       => array(
				"id" => "1"
			)
		);

		$company_note = array(
			"text" => "Company Note"
		);

		$mock_company_response = array(
			"body" => '[{"id": 1}]'
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='TestCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( array() ));

		$GF_ConnectWise->expects( $this->at( 3 ) )
					   ->method( "send_request" )
					   ->with(
							"company/companies",
							"POST",
							$company_data
					   );

		$GF_ConnectWise->expects( $this->at( 6 ) )
					   ->method( "send_request" )
					   ->with(
							"company/companies?conditions=identifier='TestCompany'"
						)
					   ->will( $this->returnValue( $mock_company_response ) );

		$GF_ConnectWise->expects( $this->at( 7 ) )
					   ->method( "send_request" )
					   ->with(
							"company/companies/1/notes",
							"POST",
							$company_note
					   );

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
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
			"state"        => "CA",
			"zip"          => "-",
			"phoneNumber"  => NULL,
			"faxNumber"    => NULL,
			"website"      => NULL,
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

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='TestCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( array() ));

		$GF_ConnectWise->expects( $this->at( 3 ) )
					   ->method( "send_request" )
					   ->with(
							"company/companies",
							"POST",
							$company_data
					   );

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

		$GF_ConnectWise->expects( $this->at( 4 ) )
					   ->method( "send_request" )
					   ->with(
							"company/contacts",
							"POST",
							$contact_data
					   )
					   ->will( $this->returnValue( $mock_contact_response ) );

		$GF_ConnectWise->expects( $this->at( 5 ) )
					   ->method( "send_request" )
					   ->with(
							"company/contacts/20/communications",
							"POST",
							$comunication_types
					   );

		$mock_company_response = array(
			"body" => '[{"id": 1}]'
		);

		$GF_ConnectWise->expects( $this->at( 6 ) )
					   ->method( "send_request" )
					   ->with(
							"company/companies?conditions=identifier='TestCompany'",
							"GET",
							NULL
					   )
					   ->will($this->returnValue( $mock_company_response ));

		$GF_ConnectWise->expects( $this->at( 7 ) )
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
			"state"        => "CA",
			"zip"          => "-",
			"phoneNumber"  => NULL,
			"faxNumber"    => NULL,
			"website"      => NULL,
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

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='TestCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( array() ));

		$GF_ConnectWise->expects( $this->at( 3 ) )
					   ->method( "send_request" )
					   ->with(
							"company/companies",
							"POST",
							$company_data
					   );

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

		$GF_ConnectWise->expects( $this->at( 4 ) )
					   ->method( "send_request" )
					   ->with(
							"company/contacts",
							"POST",
							$contact_data
					   )
					   ->will( $this->returnValue( $mock_contact_response ) );

		$GF_ConnectWise->expects( $this->at( 5 ) )
					   ->method( "send_request" )
					   ->with(
							"company/contacts/20/communications",
							"POST",
							$comunication_types
					   );

		$mock_company_response = array(
			"body" => '[{"id": 1}]'
		);

		$GF_ConnectWise->expects( $this->at( 6 ) )
					   ->method( "send_request" )
					   ->with(
							"company/companies?conditions=identifier='TestCompany'",
							"GET",
							NULL
					   )
					   ->will($this->returnValue( $mock_company_response ));

		$GF_ConnectWise->expects( $this->at( 7 ) )
					   ->method( "send_request" )
					   ->with(
							"company/companies/1",
							"PATCH",
							$company_update_data
					   );

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_process_feed_without_company_setting_should_create_contact_to_catchall() {
		$feed = array(
			"id" => "1",
			"form_id" => "1",
			"is_active" => "1",
			"meta" => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"contact_type"                  => "1",
				"contact_department"            => "2",
				"company_type"                  => "1",
				"company_status"                => "1",
				"company_map_fields"            => array()
			)
		);

		$lead = array(
			"2.3" => "Test Firstname",
			"2.6" => "Test Lastname",
			"3"   => "test@test.com",
			"2"   => "Test Company",
			"2.2" => "",
			"2.4" => "",
			"2.8" => ""
		);

		$comunication_types = array(
			"value"             => "test@test.com",
			"communicationType" => "Email",
			"type"              => array(
				"id"   => 1,
				"name" => "Email"
			),
			"defaultFlag"       => true
		);

		$contact_data = array(
			"firstName"          => "Test Firstname",
			"lastName"           => "Test Lastname",
			"company"            => array(
				"identifier" => "Catchall",
			),
			"type"               => array(
				"id" => "1"
			),
			"department"         => array(
				"id" => "2"
			)
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$mock_contact_data = '{"id":1}';
		$mock_contact_response = array(
			"body" => $mock_contact_data
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
							"company/contacts/1/communications",
							"POST",
							$comunication_types
					   );

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_create_service_ticket_without_company_should_create_ticket_with_default_company() {
		$feed = array(
			"id" => "1",
			"form_id" => "1",
			"is_active" => "1",
			"meta" => array(
				"create_service_ticket"              => "1",
				"service_ticket_summary"             => "Test Ticket Name",
				"service_ticket_initial_description" => "Test Ticket Description",
				"company_map_fields"                 => array(),
				"service_ticket_type"                => "---------------",
				"service_ticket_subtype"             => "---------------",
				"service_ticket_item"                => "---------------"
			)
		);

		$ticket_data = array(
			"summary"            => "Test Ticket Name",
			"initialDescription" => "Test Ticket Description",
			"company"            => array(
				"id"         => null,
				"identifier" => "Catchall",
			)
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$GF_ConnectWise->expects( $this->at( 5 ) )
			->method( "send_request" )
			->with(
				"service/tickets",
				"POST",
				$ticket_data
			);

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_create_service_ticket_with_board_setting_should_set_ticket_to_board() {
		$feed = array(
			"id" => "1",
			"form_id" => "1",
			"is_active" => "1",
			"meta" => array(
				"create_service_ticket"              => "1",
				"service_ticket_summary"             => "Test Ticket Name",
				"service_ticket_initial_description" => "Test Ticket Description",
				"service_ticket_board"               => "1",
				"company_map_fields"                 => array(),
				"service_ticket_type"                => "---------------",
				"service_ticket_subtype"             => "---------------",
				"service_ticket_item"                => "---------------"
			)
		);
		$ticket_data = array(
			"summary"            => "Test Ticket Name",
			"initialDescription" => "Test Ticket Description",
			"company"            => array(
				"id"         => null,
				"identifier" => "Catchall",
			),
			"board"              => array(
				"id"         => "1",
			),
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$GF_ConnectWise->expects( $this->at( 5 ) )
			->method( "send_request" )
			->with(
				"service/tickets",
				"POST",
				$ticket_data
			);

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_create_service_ticket_with_priority_setting_should_set_ticket_priority() {
		$feed = array(
			"id" => "1",
			"form_id" => "1",
			"is_active" => "1",
			"meta" => array(
				"create_service_ticket"              => "1",
				"service_ticket_summary"             => "Test Ticket Name",
				"service_ticket_initial_description" => "Test Ticket Description",
				"service_ticket_priority"            => "1",
				"company_map_fields"                 => array(),
				"service_ticket_type"                => "---------------",
				"service_ticket_subtype"             => "---------------",
				"service_ticket_item"                => "---------------"
			)
		);

		$ticket_data = array(
			"summary"            => "Test Ticket Name",
			"initialDescription" => "Test Ticket Description",
			"company"            => array(
				"id"         => null,
				"identifier" => "Catchall",
			),
			"priority"           => array(
				"id"         => "1",
			),
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$GF_ConnectWise->expects( $this->at( 5 ) )
			->method( "send_request" )
			->with(
				"service/tickets",
				"POST",
				$ticket_data
			);

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_create_service_ticket_with_company_data_should_create_ticket_with_company_data() {
		$feed = array(
			"id" => "1",
			"form_id" => "1",
			"is_active" => "1",
			"meta" => array(
				"contact_map_fields_first_name"      => "2.3",
				"contact_map_fields_last_name"       => "2.6",
				"contact_map_fields_email"           => "3",
				"contact_type"                       => "1",
				"contact_department"                 => "2",
				"company_type"                       => "1",
				"company_status"                     => "1",
				"create_service_ticket"              => "1",
				"service_ticket_summary"             => "Test Ticket Name",
				"service_ticket_initial_description" => "Test Ticket Description",
				"service_ticket_type"                => "---------------",
				"service_ticket_subtype"             => "---------------",
				"service_ticket_item"                => "---------------",
				"company_map_fields"                 => array(
					array(
						"key"        => "company",
						"value"      => "2",
						"custom_key" => ""
					),
					array(
						"key"        => "address_1",
						"value"      => "4",
						"custom_key" => ""
					),
					array(
						"key"        => "address_2",
						"value"      => "5",
						"custom_key" => ""
					),
					array(
						"key"        => "city",
						"value"      => "6",
						"custom_key" => ""
					),
					array(
						"key"        => "state",
						"value"      => "7",
						"custom_key" => ""
					),
					array(
						"key"        => "zip",
						"value"      => "8",
						"custom_key" => ""
					),
					array(
						"key"        => "phone_number",
						"value"      => "9",
						"custom_key" => ""
					),
					array(
						"key"        => "fax_number",
						"value"      => "10",
						"custom_key" => ""
					),
					array(
						"key"        => "web_site",
						"value"      => "11",
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
			"2.2" => "",
			"2.4" => "",
			"2.8" => "",
			"4"   => "22/25 Jatujak",
			"5"   => "Jatujak",
			"6"   => "CA",
			"7"   => "KA",
			"8"   => "65000",
			"9"   => "023456789",
			"10"  => "1234",
			"11"  => "www.google.com",
		);

		$company_data = array(
			"id"           => 0,
			"identifier"   => "TestCompany",
			"name"         => "Test Company",
			"addressLine1" => "22/25 Jatujak",
			"addressLine2" => "Jatujak",
			"city"         => "CA",
			"state"        => "KA",
			"zip"          => "65000",
			"phoneNumber"  => "023456789",
			"faxNumber"    => "1234",
			"website"      => "www.google.com",
			"type"         => array(
				"id" => "1"
			),
			"status"       => array(
				"id" => "1"
			)
		);

		$ticket_data = array(
			"summary"            => "Test Ticket Name",
			"initialDescription" => "Test Ticket Description",
			"company"            => array(
				"id"         => 0,
				"identifier" => "TestCompany",
			)
		);

		$plugin_settings = array(
			"username" => "valid_username",
			"password" => "valid_password",
			"connectwise_url" => "http://connectwise_url.com/api/"
		);

		update_option( $this->slug, $plugin_settings );

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='TestCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( array() ));

		$GF_ConnectWise->expects( $this->at( 3 ) )
			->method( "send_request" )
			->with(
				"company/companies",
				"POST",
				$company_data
			);

		$GF_ConnectWise->expects( $this->at( 8 ) )
			->method( "send_request" )
			->with(
				"service/tickets",
				"POST",
				$ticket_data
			);

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_create_opportunity_with_no_campaign_shouldnot_add_campaign_to_opportunity() {
		$feed = array(
			"id" => "1",
			"form_id" => "1",
			"is_active" => "1",
			"meta" => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"contact_type"                  => "1",
				"contact_department"            => "2",
				"company_type"                  => "Prospect",
				"company_status"                => "Active",
				"create_opportunity"            => "1",
				"opportunity_name"              => "Test OP from form",
				"opportunity_closedate"         => "0",
				"opportunity_owner"             => "Admin1",
				"company_map_fields"            => array(),
				"opportunity_type"              => "1",
				"opportunity_note"              => "test note",
				"marketing_campaign"            => "---------------",
			)
		);

		$lead = array(
			"2.3" => "Test Firstname",
			"2.6" => "Test Lastname",
			"3"   => "test@test.com",
			"2"   => "",
			"2.2" => "",
			"2.4" => "",
			"2.8" => ""
		);

		$expectedCloseDate = mktime( 0, 0, 0, date( "m" ), date( "d" ), date( "y" ) );
		$expectedCloseDate = date( "Y-m-d", $expectedCloseDate );

		$opportunity_data = array(
			"name"              => $feed["meta"]["opportunity_name"],
			"type"              => array(
				"id" => "1"
			),
			"company"           => array(
				"id"            => "1",
				"identifier"    => "Catchall"
			),
			"contact"           => array(
				"id"   => 1,
				"name" => "FirstName LastName"
			),
			"site"              => array(
				"id"   => 1,
				"name" => "Main",
			),
			"primarySalesRep"   => array(
				"identifier" => "Admin1",
			),
			"expectedCloseDate" => $expectedCloseDate . "T00:00:00Z",
			"notes"             => "test note",
		);

		$contact_data = array(
			"firstName"          => "Test Firstname",
			"lastName"           => "Test Lastname",
			"company"            => array(
				"identifier" => "Catchall",
			),
			"type"               => array(
				"id" => "1"
			),
			"department"         => array(
				"id" => "2"
			)
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$mock_contact_data = '{"id":1, "firstName": "FirstName", "lastName": "LastName"}';
		$mock_contact_response = array(
			"body" => $mock_contact_data
		);

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/contacts",
				"POST",
				$contact_data
			)
			->will( $this->returnValue( $mock_contact_response ) );

		$mock_company_response = array(
			"body" => '[{"id":1}]'
		);

		$GF_ConnectWise->expects( $this->at( 4 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='Catchall'",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_company_response ) );

		$mock_company_site_response = array(
			"body" => '[{"id":1, "name": "Main"}]'
		);
		$GF_ConnectWise->expects( $this->at( 5 ) )
			->method( "send_request" )
			->with(
				"company/companies/1/sites/",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_company_site_response ) );

		$GF_ConnectWise->expects( $this->at( 6 ) )
			->method( "send_request" )
			->with(
				"sales/opportunities",
				"POST",
				$opportunity_data
			);

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_create_opportunity_without_company_field_should_pass() {
		$feed = array(
			"id" => "1",
			"form_id" => "1",
			"is_active" => "1",
			"meta" => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"contact_type"                  => "1",
				"contact_department"            => "2",
				"company_type"                  => "Prospect",
				"company_status"                => "Active",
				"create_opportunity"            => "1",
				"opportunity_name"              => "Test OP from form",
				"opportunity_owner"             => "Admin1",
				"opportunity_closedate"         => "2",
				"company_map_fields"            => array(),
				"opportunity_type"              => "1",
				"opportunity_note"              => "test note",
				"marketing_campaign"            => "1"
			)
		);

		$lead = array(
			"2.3" => "Test Firstname",
			"2.6" => "Test Lastname",
			"3"   => "test@test.com",
			"2"   => "",
			"2.2" => "",
			"2.4" => "",
			"2.8" => ""
		);

		$expectedCloseDate = mktime( 0, 0, 0, date( "m" ), date( "d" ) + 2, date( "y" ) );
		$expectedCloseDate = date( "Y-m-d", $expectedCloseDate );

		$opportunity_data = array(
			"name"              => $feed["meta"]["opportunity_name"],
			"company"           => array(
				"id"            => "1",
				"identifier"    => "Catchall"
			),
			"contact"           => array(
				"id"   => 1,
				"name" => "FirstName LastName"
			),
			"site"              => array(
				"id"   => 1,
				"name" => "Main",
			),
			"primarySalesRep"   => array(
				"identifier"    => "Admin1",
			),
			"expectedCloseDate" => $expectedCloseDate . "T00:00:00Z",
			"campaign"          => array(
				"id"            => "1",
			),
			"type"              => array(
				"id" => "1"
			),
			"notes"             => "test note",
		);

		$contact_data = array(
			"firstName"          => "Test Firstname",
			"lastName"           => "Test Lastname",
			"company"            => array(
				"identifier" => "Catchall",
			),
			"type"               => array(
				"id" => "1"
			),
			"department"         => array(
				"id" => "2"
			)
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$mock_contact_data = '{"id":1, "firstName": "FirstName", "lastName": "LastName"}';
		$mock_contact_response = array(
			"body" => $mock_contact_data
		);

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/contacts",
				"POST",
				$contact_data
			)
			->will( $this->returnValue( $mock_contact_response ) );

		$mock_company_response = array(
			"body" => '[{"id":1}]'
		);

		$GF_ConnectWise->expects( $this->at( 4 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='Catchall'",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_company_response ) );

		$mock_company_site_response = array(
			"body" => '[{"id":1, "name": "Main"}]'
		);
		$GF_ConnectWise->expects( $this->at( 5 ) )
			->method( "send_request" )
			->with(
				"company/companies/1/sites/",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_company_site_response ) );

		$GF_ConnectWise->expects( $this->at( 6 ) )
			->method( "send_request" )
			->with(
				"sales/opportunities",
				"POST",
				$opportunity_data
			);

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_create_opportunity_with_company_should_pass() {
		$feed = array(
			"id" => "1",
			"form_id" => "1",
			"is_active" => "1",
			"meta" => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"contact_type"                  => "1",
				"contact_department"            => "2",
				"create_opportunity"            => "1",
				"opportunity_name"              => "Test OP from form",
				"opportunity_owner"             => "Admin1",
				"opportunity_source"            => "FORM 01",
				"opportunity_type"              => "1",
				"company_type"                  => "1",
				"company_status"                => "1",
				"company_map_fields"            => array(
					array(
					   "key"        => "company",
					   "value"      => "2",
					   "custom_key" => ""
					)
				),
				"marketing_campaign"            => "1"
			)
		);

		$lead = array(
			"2.3" => "Firstname",
			"2.6" => "Lastname",
			"3"   => "test@test.com",
			"2"   => "Test Company",
			"2.2" => "",
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
			"state"        => "CA",
			"zip"          => "-",
			"phoneNumber"  => NULL,
			"faxNumber"    => NULL,
			"website"      => NULL,
			"type"         => array(
				"id" => "1"
			),
			"status"       => array(
				"id" => "1"
			)
		);

		$contact_data = array(
			"firstName"  => "Firstname",
			"lastName"   => "Lastname",
			"company"    => array(
				"identifier" => "TestCompany",
			),
			"type"       => array(
				"id" => "1"
			),
			"department" => array(
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
			"defaultFlag"       => true
		);

		$expectedCloseDate = mktime( 0, 0, 0, date( "m" ), date( "d" ) + 30, date( "y" ) );
		$expectedCloseDate = date( "Y-m-d", $expectedCloseDate );
		$opportunity_data = array(
			"name"   => $feed["meta"]["opportunity_name"],
			"source" => "FORM 01",
			"type"   => array(
				"id" => "1"
			),
			"company" => array(
				"id"         => "1",
				"identifier" => "TestCompany"
			),
			"contact" => array(
				"id"   => 1,
				"name" => "Firstname Lastname"
			),
			"site" => array(
				"id"   => 10,
				"name" => "Main",
			),
			"primarySalesRep" => array(
				"identifier"  => "Admin1",
			),
			"expectedCloseDate" => $expectedCloseDate . "T00:00:00Z",
			"campaign"          => array(
				"id" => "1",
			),
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='TestCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( array() ));

		$GF_ConnectWise->expects( $this->at( 3 ) )
			->method( "send_request" )
			->with(
				"company/companies",
				"POST",
				$company_data
			);

		$mock_contact_data = '{"id":1, "firstName": "Firstname", "lastName": "Lastname"}';
		$mock_contact_response = array(
			"body" => $mock_contact_data
		);

		$GF_ConnectWise->expects( $this->at( 4 ) )
			->method( "send_request" )
			->with(
				"company/contacts",
				"POST",
				$contact_data
			)
			->will( $this->returnValue( $mock_contact_response ) );

		$GF_ConnectWise->expects( $this->at( 5 ) )
			->method( "send_request" )
			->with(
				"company/contacts/1/communications",
				"POST",
				$comunication_types
			);

		$mock_company_response = array(
			"body" => '[{"id":1}]'
		);
		$GF_ConnectWise->expects( $this->at( 6 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='TestCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_company_response ) );

		$mock_company_site_response = array(
			"body" => '[{"id":10, "name": "Main"}]'
		);
		$GF_ConnectWise->expects( $this->at( 8 ) )
			->method( "send_request" )
			->with(
				"company/companies/1/sites/",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_company_site_response ) );

		$GF_ConnectWise->expects( $this->at( 9 ) )
			->method( "send_request" )
			->with(
				"sales/opportunities",
				"POST",
				$opportunity_data
			);

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_create_opportunity_note_should_send_note_correctly() {
		$feed = array(
			"id" => "1",
			"form_id" => "1",
			"is_active" => "1",
			"meta" => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"contact_type"                  => "1",
				"contact_department"            => "2",
				"create_opportunity"            => "1",
				"opportunity_name"              => "Test OP from form",
				"opportunity_owner"             => "Admin1",
				"opportunity_source"            => "FORM 01",
				"opportunity_type"              => "1",
				"opportunity_note"              => "Opportunity Note",
				"company_type"                  => "1",
				"company_status"                => "1",
				"company_map_fields"            => array(
					array(
					   "key"        => "company",
					   "value"      => "2",
					   "custom_key" => ""
					)
				),
				"marketing_campaign"            => "1"
			)
		);

		$lead = array(
			"2.3" => "Firstname",
			"2.6" => "Lastname",
			"3"   => "test@test.com",
			"2"   => "Test Company",
			"2.2" => "",
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
			"state"        => "CA",
			"zip"          => "-",
			"phoneNumber"  => NULL,
			"faxNumber"    => NULL,
			"website"      => NULL,
			"type"         => array(
				"id" => "1"
			),
			"status"       => array(
				"id" => "1"
			)
		);

		$contact_data = array(
			"firstName"  => "Firstname",
			"lastName"   => "Lastname",
			"company"    => array(
				"identifier" => "TestCompany",
			),
			"type"       => array(
				"id" => "1"
			),
			"department" => array(
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
			"defaultFlag"       => true
		);

		$expectedCloseDate = mktime( 0, 0, 0, date( "m" ), date( "d" ) + 30, date( "y" ) );
		$expectedCloseDate = date( "Y-m-d", $expectedCloseDate );
		$opportunity_data = array(
			"name"   => $feed["meta"]["opportunity_name"],
			"source" => "FORM 01",
			"type"   => array(
				"id" => "1"
			),
			"company" => array(
				"id"         => "1",
				"identifier" => "TestCompany"
			),
			"contact" => array(
				"id"   => 1,
				"name" => "Firstname Lastname"
			),
			"site" => array(
				"id"   => 10,
				"name" => "Main",
			),
			"primarySalesRep" => array(
				"identifier"  => "Admin1",
			),
			"expectedCloseDate" => $expectedCloseDate . "T00:00:00Z",
			"campaign"          => array(
				"id" => "1",
			),
			"notes"             => "Opportunity Note",
		);

		$note_data = array(
			"text" => "Opportunity Note"
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='TestCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( array() ));

		$GF_ConnectWise->expects( $this->at( 3 ) )
			->method( "send_request" )
			->with(
				"company/companies",
				"POST",
				$company_data
			);

		$mock_contact_data = '{"id":1, "firstName": "Firstname", "lastName": "Lastname"}';
		$mock_contact_response = array(
			"body" => $mock_contact_data
		);

		$GF_ConnectWise->expects( $this->at( 4 ) )
			->method( "send_request" )
			->with(
				"company/contacts",
				"POST",
				$contact_data
			)
			->will( $this->returnValue( $mock_contact_response ) );

		$GF_ConnectWise->expects( $this->at( 5 ) )
			->method( "send_request" )
			->with(
				"company/contacts/1/communications",
				"POST",
				$comunication_types
			);

		$mock_company_response = array(
			"body" => '[{"id":1}]'
		);
		$GF_ConnectWise->expects( $this->at( 6 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='TestCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_company_response ) );

		$mock_company_site_response = array(
			"body" => '[{"id":10, "name": "Main"}]'
		);
		$GF_ConnectWise->expects( $this->at( 8 ) )
			->method( "send_request" )
			->with(
				"company/companies/1/sites/",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_company_site_response ) );

		$mock_opportunity_data = '{"id":1}';
		$mock_opportunity_response = array(
			"body" => $mock_opportunity_data
		);

		$GF_ConnectWise->expects( $this->at( 9 ) )
			->method( "send_request" )
			->with(
				"sales/opportunities",
				"POST",
				$opportunity_data
			)
			->will( $this->returnValue( $mock_opportunity_response ) );

		$GF_ConnectWise->expects( $this->at( 10 ) )
					   ->method( "send_request" )
					   ->with(
							"sales/opportunities/1/notes",
							"POST",
							$note_data
						);

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_create_activity_should_pass() {
		$feed = array(
			"id" => "1",
			"form_id" => "1",
			"is_active" => "1",
			"meta" => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"contact_type"                  => "1",
				"contact_department"            => "2",
				"create_opportunity"            => "1",
				"opportunity_name"              => "Test OP from form",
				"opportunity_owner"             => "Admin1",
				"opportunity_type"              => "1",
				"opportunity_closedate"         => "31",
				"create_activity"               => "1",
				"activity_name"                 => "Follow up the client",
				"activity_assigned_to"          => "Admin1",
				"activity_type"                 => "1",
				"activity_duedate"              => "5",
				"activity_note"                 => "test activity note",
				"company_type"                  => "1",
				"company_status"                => "1",
				"company_map_fields"            => array(
					array(
					   "key"        => "company",
					   "value"      => "2",
					   "custom_key" => ""
					)
				),
				"marketing_campaign"            => "1"
			)
		);

		$lead = array(
			"2.3" => "Firstname",
			"2.6" => "Lastname",
			"3"   => "test@test.com",
			"2"   => "Test Company",
			"2.2" => "",
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
			"state"        => "CA",
			"zip"          => "-",
			"phoneNumber"  => NULL,
			"faxNumber"    => NULL,
			"website"      => NULL,
			"type"         => array(
				"id" => "1"
			),
			"status"       => array(
				"id" => "1"
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

		$contact_data = array(
			"firstName" => "Firstname",
			"lastName"  => "Lastname",
			"company"   => array(
				"identifier" => "TestCompany",
			),
			"type" => array(
				"id" => "1"
			),
			"department" => array(
				"id" => "2"
			)
		);

		$expectedCloseDate = mktime( 0, 0, 0, date( "m" ), date( "d" ) + 31, date( "y" ) );
		$expectedCloseDate = date( "Y-m-d", $expectedCloseDate );
		$opportunity_data = array(
			"name" => $feed["meta"]["opportunity_name"],
			"type" => array(
				"id" => "1"
			),
			"company" => array(
				"id"         => "1",
				"identifier" => "TestCompany"
			),
			"contact" => array(
				"id"   => 1,
				"name" => "Firstname Lastname"
			),
			"site" => array(
				"id"   => 10,
				"name" => "Main",
			),
			"primarySalesRep"   => array(
				"identifier" => "Admin1",
			),
			"expectedCloseDate" => $expectedCloseDate . "T00:00:00Z",
			"campaign"          => array(
				"id" => "1",
			),
		);

		$dueDate = mktime( 0, 0, 0, date( "m" ), date( "d" ) + 5, date( "y" ) );
		$dueDate = date( "Y-m-d", $dueDate );
		$activity_data = array(
			"name"  => "Follow up the client",
			"email" => "test@test.com",
			"type"  => array(
				"id" => "1",
			),
			"company" => array(
				"id"         => "1",
				"identifier" => "TestCompany"
			),
			"contact" => array(
				"id"   => 1,
				"name" => "Firstname Lastname"
			),
			"status" => array(
				"name" => "Open"
			),
			"assignTo" => array(
				"identifier" => "Admin1"
			),
			"opportunity" => array(
				"id"   => 1,
				"name" => "Test OP from form"
			),
			"notes"     => "test activity note",
			"dateStart" => $dueDate . "T14:00:00Z",
			"dateEnd"   => $dueDate . "T14:15:00Z"
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='TestCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( array() ));

		$GF_ConnectWise->expects( $this->at( 3 ) )
			->method( "send_request" )
			->with(
				"company/companies",
				"POST",
				$company_data
			);

		$mock_contact_data = '{"id":1, "firstName": "Firstname", "lastName": "Lastname"}';
		$mock_contact_response = array(
			"body" => $mock_contact_data
		);
		$GF_ConnectWise->expects( $this->at( 4 ) )
			->method( "send_request" )
			->with(
				"company/contacts",
				"POST",
				$contact_data
			)
			->will( $this->returnValue( $mock_contact_response ) );

		$GF_ConnectWise->expects( $this->at( 5 ) )
			->method( "send_request" )
			->with(
				"company/contacts/1/communications",
				"POST",
				$comunication_types
			);

		$mock_company_response = array(
			"body" => '[{"id":1}]'
		);
		$GF_ConnectWise->expects( $this->at( 6 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='TestCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_company_response ) );

		$mock_company_site_response = array(
			"body" => '[{"id":10, "name": "Main"}]'
		);
		$GF_ConnectWise->expects( $this->at( 8 ) )
			->method( "send_request" )
			->with(
				"company/companies/1/sites/",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_company_site_response ) );

		$mock_opportunity_data = '{"id":1, "name": "Test OP from form"}';
		$mock_opportunity_response = array(
			"body" => $mock_opportunity_data
		);
		$GF_ConnectWise->expects( $this->at( 9 ) )
			->method( "send_request" )
			->with(
				"sales/opportunities",
				"POST",
				$opportunity_data
			)
			->will( $this->returnValue( $mock_opportunity_response ) );

		$GF_ConnectWise->expects( $this->at( 10 ) )
			->method( "send_request" )
			->with(
				"sales/activities",
				"POST",
				$activity_data
			);

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_create_activity_without_notes_should_pass() {
		$feed = array(
			"id"        => "1",
			"form_id"   => "1",
			"is_active" => "1",
			"meta"      => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"contact_type"                  => "1",
				"contact_department"            => "2",
				"create_opportunity"            => "1",
				"opportunity_name"              => "Test OP from form",
				"opportunity_owner"             => "Admin1",
				"opportunity_type"              => "1",
				"create_activity"               => "1",
				"activity_name"                 => "Follow up the client",
				"activity_assigned_to"          => "Admin1",
				"activity_type"                 => "1",
				"company_type"                  => "1",
				"company_status"                => "1",
				"company_map_fields"            => array(
					array(
					   "key"        => "company",
					   "value"      => "2",
					   "custom_key" => ""
					)
				),
				"marketing_campaign"            => "1"
			)
		);

		$lead = array(
			"2.3" => "Firstname",
			"2.6" => "Lastname",
			"3"   => "test@test.com",
			"2"   => "Test Company",
			"2.2" => "",
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
			"state"        => "CA",
			"zip"          => "-",
			"phoneNumber"  => NULL,
			"faxNumber"    => NULL,
			"website"      => NULL,
			"type"         => array(
				"id" => "1"
			),
			"status"       => array(
				"id" => "1"
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

		$contact_data = array(
			"firstName" => "Firstname",
			"lastName"  => "Lastname",
			"company"   => array(
				"identifier" => "TestCompany",
			),
			"type" => array(
				"id" => "1"
			),
			"department" => array(
				"id" => "2"
			)
		);

		$expectedCloseDate = mktime( 0, 0, 0, date( "m" ), date( "d" ) + 30, date( "y" ) );
		$expectedCloseDate = date( "Y-m-d", $expectedCloseDate );
		$opportunity_data = array(
			"name" => $feed["meta"]["opportunity_name"],
			"type" => array(
				"id" => "1"
			),
			"company" => array(
				"id"            => "1",
				"identifier"    => "TestCompany"
			),
			"contact" => array(
				"id"   => 1,
				"name" => "Firstname Lastname"
			),
			"site" => array(
				"id"   => 10,
				"name" => "Main",
			),
			"primarySalesRep" => array(
				"identifier" => "Admin1",
			),
			"expectedCloseDate" => $expectedCloseDate . "T00:00:00Z",
			"campaign"          => array(
				"id"            => "1",
			),
		);

		$dueDate = mktime( 0, 0, 0, date( "m" ), date( "d" ) + 7, date( "y" ) );
		$dueDate = date( "Y-m-d", $dueDate );
		$activity_data = array(
			"name"  => "Follow up the client",
			"email" => "test@test.com",
			"type"  => array(
				"id" => "1",
			),
			"company" => array(
				"id"         => "1",
				"identifier" => "TestCompany"
			),
			"contact" => array(
				"id"   => 1,
				"name" => "Firstname Lastname"
			),
			"status" => array(
				"name" => "Open"
			),
			"assignTo" => array(
				"identifier" => "Admin1"
			),
			"opportunity" => array(
				"id"   => 1,
				"name" => "Test OP from form"
			),
			"dateStart" => $dueDate . "T14:00:00Z",
			"dateEnd"   => $dueDate . "T14:15:00Z"
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->exactly( 1 ) )
			->method( "is_valid_settings" )
			->willReturn( true );

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "get_existing_contact" )
			->will( $this->returnValue( false ) );

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='TestCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( array() ));

		$GF_ConnectWise->expects( $this->at( 3 ) )
			->method( "send_request" )
			->with(
				"company/companies",
				"POST",
				$company_data
			);

		$mock_contact_data = '{"id":1, "firstName": "Firstname", "lastName": "Lastname"}';
		$mock_contact_response = array(
			"body" => $mock_contact_data
		);
		$GF_ConnectWise->expects( $this->at( 4 ) )
			->method( "send_request" )
			->with(
				"company/contacts",
				"POST",
				$contact_data
			)
			->will( $this->returnValue( $mock_contact_response ) );

		$GF_ConnectWise->expects( $this->at( 5 ) )
			->method( "send_request" )
			->with(
				"company/contacts/1/communications",
				"POST",
				$comunication_types
			);

		$mock_company_response = array(
			"body" => '[{"id":1}]'
		);
		$GF_ConnectWise->expects( $this->at( 6 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='TestCompany'",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_company_response ) );

		$mock_company_site_response = array(
			"body" => '[{"id":10, "name": "Main"}]'
		);
		$GF_ConnectWise->expects( $this->at( 8 ) )
			->method( "send_request" )
			->with(
				"company/companies/1/sites/",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_company_site_response ) );

		$mock_opportunity_data = '{"id":1, "name": "Test OP from form"}';
		$mock_opportunity_response = array(
			"body" => $mock_opportunity_data
		);
		$GF_ConnectWise->expects( $this->at( 9 ) )
			->method( "send_request" )
			->with(
				"sales/opportunities",
				"POST",
				$opportunity_data
			)
			->will( $this->returnValue( $mock_opportunity_response ) );

		$GF_ConnectWise->expects( $this->at( 10 ) )
			->method( "send_request" )
			->with(
				"sales/activities",
				"POST",
				$activity_data
			);

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_member_api_should_return_correct_member_list() {
		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request" ) )
			->getMock();

		$mock_members_response = array(
			"body" => '[{"identifier": "Admin1", "name": "Admin Training 1"},{"identifier": "Admin2", "name": "Admin Training 2"}]'
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
				"label" => "Admin Training 1",
			),
			array(
				"value" => "Admin2",
				"label" => "Admin Training 2",
			)
		);

		$this->assertEquals( $actual_member_list, $expected_member_list);
	}

	function test_campaign_api_should_return_correct_campaign_list() {
		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request" ) )
			->getMock();

		$mock_campaign_response = array(
			"body" => '[{"id": "1", "name": "Test Campaign"}]'
		);
		$GF_ConnectWise->expects( $this->at( 0 ) )
			->method( "send_request" )
			->with(
				"marketing/campaigns?pageSize=200",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_campaign_response ) );

		$actual_campaign_list = $GF_ConnectWise->get_marketing_campaign();
		$expected_campaign_list = array(
			array(
				"value" => NULL,
				"label" => "---------------",
			),
			array(
				"value" => "1",
				"label" => "Test Campaign",
			)
		);

		$this->assertEquals( $actual_campaign_list, $expected_campaign_list);
	}

	function test_service_type_api_should_return_correct_type_list() {
		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request" ) )
			->getMock();

		$mock_board_data = '[{"name":"Administration", "id":1}]';
		$mock_board_response = array(
			"body" => $mock_board_data
		);

		$GF_ConnectWise->expects( $this->at( 0 ) )
			->method( "send_request" )
			->with(
				"service/boards?pageSize=200",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_board_response ) );

		$board_url = "service/boards/1/types?pageSize=200";

		$mock_service_types_response = array(
			"body" => '[{"id": "1", "name": "Break-fix"},{"id": "2", "name": "Proactive"}]'
		);

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "send_request" )
			->with(
				$board_url,
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_service_types_response ) );

		$actual_type_list = $GF_ConnectWise->get_service_types();
		$expected_type_list = array(
			array(
				"label" => "---------------",
				"value" => NULL
			),
			array(
				"label" => "Administration",
				"choices" => array(
						array(
							"value" => "1",
							"label" => "Break-fix",
						),
						array(
							"value" => "2",
							"label" => "Proactive",
						)
				 )
		  )
		);
		$this->assertEquals( $actual_type_list, $expected_type_list);
	}

	function test_service_subtype_api_should_return_correct_subtype_list() {
		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request" ) )
			->getMock();

		$mock_board_data = '[{"name":"Administration", "id":1}]';
		$mock_board_response = array(
			"body" => $mock_board_data
		);

		$GF_ConnectWise->expects( $this->at( 0 ) )
			->method( "send_request" )
			->with(
				"service/boards?pageSize=200",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_board_response ) );

		$board_url = "service/boards/1/subtypes?pageSize=200";

		$mock_service_subtypes_response = array(
			"body" => '[{"id": "1", "name": "CRM"},{"id": "2", "name": "RMM"}]'
		);

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "send_request" )
			->with(
				$board_url,
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_service_subtypes_response ) );

		$actual_subtype_list = $GF_ConnectWise->get_service_subtypes();
		$expected_subtype_list = array(
			array(
				"label" => "---------------",
				"value" => NULL
			),
			array(
				"label" => "Administration",
				"choices" => array(
						array(
							"value" => "1",
							"label" => "CRM",
						),
						array(
							"value" => "2",
							"label" => "RMM",
						)
				)
			)
		);
		$this->assertEquals( $actual_subtype_list, $expected_subtype_list);
	}

	function test_service_item_api_should_return_correct_item_list() {
		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request" ) )
			->getMock();

		$mock_board_data = '[{"name":"Administration", "id":1}]';
		$mock_board_response = array(
			"body" => $mock_board_data
		);

		$GF_ConnectWise->expects( $this->at( 0 ) )
			->method( "send_request" )
			->with(
				"service/boards?pageSize=200",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_board_response ) );

		$board_url = "service/boards/1/items?pageSize=200";

		$mock_service_item_response = array(
			"body" => '[{"id": "1", "name": "Service Board"},{"id": "2", "name": "Workflow Rules"}]'
		);

		$GF_ConnectWise->expects( $this->at( 1 ) )
			->method( "send_request" )
			->with(
				$board_url,
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_service_item_response ) );

		$actual_item_list = $GF_ConnectWise->get_service_item();
		$expected_item_list = array(
			array(
				"label" => "---------------",
				"value" => NULL
			),
			array(
				"label" => "Administration",
				"choices" => array(
						array(
							"value" => "1",
							"label" => "Service Board",
						),
						array(
							"value" => "2",
							"label" => "Workflow Rules",
						)
			   )
			)
		);
		$this->assertEquals( $actual_item_list, $expected_item_list);
	}

	function test_feed_list_column_should_return_correct_column() {
		$actual = $this->connectwise_plugin->feed_list_columns();
		$expected = array(
			"feed_name" => "Name",
			"action"    => "Action"
		);

		$this->assertEquals( $actual, $expected);
	}

	function test_get_column_action_value_should_return_correctly() {
		$feed = array(
			"meta"  => array(
				"create_opportunity"    => "1",
				"create_activity"       => "1",
				"create_service_ticket" => "1"
			)
		);
		$actual = $this->connectwise_plugin->get_column_value_action( $feed );
		$expected = "Create New Opportunity, Create New Activity, Create New Service Ticket";

		$this->assertEquals( $actual, $expected);
	}

	function test_connectwise_shouldnot_add_ads_js_for_phoenix_theme() {
		switch_theme("Phoenix Child Theme 1");

		$pronto_ads_js = array(
			"handle"    => "pronto_ads_js",
			"src"       => "http://example.org/wp-content/plugins/connectwise-forms-integration/js/pronto-ads.js",
			"version"   => "1.3.0",
			"deps"      => array( "jquery" ),
			"enqueue"   =>
				array(
					array(
						"admin_page"=> array( "form_settings", "plugin_settings" )
					)
				)
			);

		$this->assertNotContains( $pronto_ads_js, $this->connectwise_plugin->scripts() );
	}

	function test_connectwise_should_add_ads_js_for_other_theme() {
		switch_theme("Twenty Sixteen");

		$pronto_ads_js = array(
			"handle"    => "pronto_ads_js",
			"src"       => "http://example.org/wp-content/plugins/connectwise-forms-integration/js/pronto-ads.js",
			"version"   => "1.5.1",
			"deps"      => array( "jquery" ),
			"strings"   => array(
				"path" => 'http://example.org/wp-content/plugins/connectwise-forms-integration/images/connectwise-banner.jpg'
			),
			"enqueue"   =>
				array(
					array(
						"admin_page"=> array( "form_settings", "plugin_settings" )
					)
				)
			);

		$this->assertContains( $pronto_ads_js, $this->connectwise_plugin->scripts() );
	}

	function test_error_response_from_connectwise_should_send_to_specific_email() {
		$_SERVER["SERVER_NAME"] = 'example.org';

		$this->reset_phpmailer_instance();

		$settings = array(
			"connectwise_url"                   => "",
			"company_id"                        => "",
			"public_key"                        => "",
			"private_key"                       => "",
			"client_id"							=> "",
			"enable_error_notification_emails"  => "1",
			"error_notification_emails_to"      => "test@mail.com"
		);

		update_option( "gravityformsaddon_connectwise_settings", $settings );

		$response_body = "{\"message\": \"contact object is invalid\"}";
		$response_code = 400;
		$url = "/apis/3.0/company/contacts";
		$body = array("data" => "contacts");

		$actual = $this->connectwise_plugin->send_error_notification( $response_body, $response_code, $url, $body );

		$mailer = $this->tests_retrieve_phpmailer_instance();

		$this->assertTrue( $actual );

		$this->assertEquals( "Test Blog", $mailer->FromName );
		$this->assertEquals( "noreply@example.org", $mailer->From );
		$this->assertEquals( "[Gravity Forms Connectwise] ERROR", $mailer->Subject );
		$this->assertEquals( "test@mail.com", $mailer->mock_sent[0]["to"][0][0] );

		$expected = "URL => /apis/3.0/company/contacts";
		$this->assertContains( $expected, $mailer->mock_sent[0]["body"] );
		$expected = "data => {";
		$this->assertContains( $expected, $mailer->mock_sent[0]["body"] );
		$expected = "\"data\": \"contacts\"";
		$this->assertContains( $expected, $mailer->mock_sent[0]["body"] );
		$expected = "Response[code] => 400";
		$this->assertContains( $expected, $mailer->mock_sent[0]["body"] );
		$expected = "Response[body] => {\"message\": \"contact object is invalid\"}";
		$this->assertContains( $expected, $mailer->mock_sent[0]["body"] );
		$expected = "---------";
		$this->assertContains( $expected, $mailer->mock_sent[0]["body"] );
		$expected = "Configure error notifications: ";
		$expected .= "http://example.org/wp-admin/admin.php";
		$expected .= "?page=gf_settings&subview=connectwise";
		$this->assertContains( $expected, $mailer->mock_sent[0]["body"] );
		$expected = "Install the Gravity Forms Logging Add-on to view the complete error log: ";
		$expected .= "https://www.gravityhelp.com/documentation/article/logging-add-on/";
		$this->assertContains( $expected, $mailer->mock_sent[0]["body"] );
	}

	function test_error_response_from_connectwise_should_send_to_admin_email() {
		$_SERVER["SERVER_NAME"] = 'example.org';

		$this->reset_phpmailer_instance();

		$response_body = "{\"message\": \"contact object is invalid\"}";
		$response_code = 400;
		$url = "/apis/3.0/company/contacts";
		$body = array("data" => "contacts");

		$actual = $this->connectwise_plugin->send_error_notification( $response_body, $response_code, $url, $body );

		$mailer = $this->tests_retrieve_phpmailer_instance();

		$this->assertTrue( $actual );

		$this->assertEquals( "Test Blog", $mailer->FromName );
		$this->assertEquals( "noreply@example.org", $mailer->From );
		$this->assertEquals( "[Gravity Forms Connectwise] ERROR", $mailer->Subject );
		$this->assertEquals( "admin@example.org", $mailer->mock_sent[0]["to"][0][0] );

		$expected = "URL => /apis/3.0/company/contacts";
		$this->assertContains( $expected, $mailer->mock_sent[0]["body"] );
		$expected = "data => {";
		$this->assertContains( $expected, $mailer->mock_sent[0]["body"] );
		$expected = "\"data\": \"contacts\"";
		$this->assertContains( $expected, $mailer->mock_sent[0]["body"] );
		$expected = "Response[code] => 400";
		$this->assertContains( $expected, $mailer->mock_sent[0]["body"] );
		$expected = "Response[body] => {\"message\": \"contact object is invalid\"}";
		$this->assertContains( $expected, $mailer->mock_sent[0]["body"] );
		$expected = "---------";
		$this->assertContains( $expected, $mailer->mock_sent[0]["body"] );
		$expected = "Configure error notifications: ";
		$expected .= "http://example.org/wp-admin/admin.php";
		$expected .= "?page=gf_settings&subview=connectwise";
		$this->assertContains( $expected, $mailer->mock_sent[0]["body"] );
		$expected = "Install the Gravity Forms Logging Add-on to view the complete error log: ";
		$expected .= "https://www.gravityhelp.com/documentation/article/logging-add-on/";
		$this->assertContains( $expected, $mailer->mock_sent[0]["body"] );
	}

	function test_get_prepare_company_data() {
		$data_to_prepare = array(
			'identifier'     => 'TestCompany',
			'company'        => 'Test Company',
			'address_line1'  => '-',
			'address_line2'  => '-',
			'city'           => '-',
			'state'          => 'CA',
			'zip'            => '-',
			'phone_number'   => NULL,
			'fax_number'     => NULL,
			'web_site'       => NULL,
			'company_type'   => array(
				'1'
			),
			'company_status' => array(
				'1'
			),
		);

		$GF_ConnectWise = $this->getMockBuilder( 'GFConnectWise' )
			->setMethods( array( 'send_request' ) )
			->getMock();


		$actual = $GF_ConnectWise->prepare_company_data( $data_to_prepare );

		$expect = array(
			'id'           => 0,
			'identifier'   => 'TestCompany',
			'name'         => 'Test Company',
			'addressLine1' => '-',
			'addressLine2' => '-',
			'city'         => '-',
			'state'        => 'CA',
			'zip'          => '-',
			'phoneNumber'  => NULL,
			'faxNumber'    => NULL,
			'website'      => NULL,
			'type'        => array(
				'id' => array(
					'1'
				)
			),
			'status'       => array(
				'id' => array(
					'1'
				)
			),
		);

		$this->assertEquals( $actual, $expect );
	}

	function tests_get_prepare_contact_data() {
		$data_to_prepare = array(
			'first_name'   => 'Test Firstname',
			'last_name'    => 'Test Lastname',
			'identifier'   => 'TestCompany',
			'contact_type' => '1',
		);

		$GF_ConnectWise = $this->getMockBuilder( 'GFConnectWise' )
			->setMethods( array( 'send_request' ) )
			->getMock();


		$actual = $GF_ConnectWise->prepare_contact_data( $data_to_prepare );

		$expect = array(
			'firstName' => 'Test Firstname',
			'lastName'  => 'Test Lastname',
			'company'   => array(
				'identifier' => 'TestCompany'
			),
			'type'      => array(
				'id' => '1'
			)
		);

		$this->assertEquals( $actual, $expect );
	}

	function test_sent_opportunity_summary_field_should_escape_special_character() {
		$feed = array (
			"id"        => "1",
			"form_id"   => "1",
			"is_active" => "1",
			"meta"      => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"create_opportunity"            => "1",
				"opportunity_name"              => "Can&#039;t translate",
				"company_map_fields"            => array(),
			)
		);

		$lead = array(
			"2.3" => "Alita",
			"2.6" => "Fobs",
			"3"   => "alita@gmail.com",
		);

		$expectedCloseDate = mktime( 0, 0, 0, date( "m" ), date( "d" ) + 30, date( "y" ) );
		$expectedCloseDate = date( "Y-m-d", $expectedCloseDate );

		$opportunity_data = array(
			"name"              => "Can't translate",
			"primarySalesRep"   => array(
				"identifier"    => "",
			),
			"expectedCloseDate" => $expectedCloseDate . "T00:00:00Z",
			"company"           => array(
				"id"         => "",
				"identifier" => "Catchall"
			),
			"contact"           => array(
				"id"   => "",
				"name" => " "
			),
			"site"              => array(
				"id"   => "",
				"name" => ""
			),
			"type"              => array( "id" => "" ),
			"campaign"          => array( "id" => "" )
		);

		$mock_opportunity_data = '{"id":1,"name":"Can\'t translate"}';
		$mock_opportunity_response = array(
			"body" => $mock_opportunity_data
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->at( 6 ) )
			->method( "send_request" )
			->with(
				"sales/opportunities",
				"POST",
				$opportunity_data
			)
			->will( $this->returnValue( $mock_opportunity_response ) );

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_sent_opportunity_note_field_should_escape_special_character() {
		$feed = array (
			"id"        => "1",
			"form_id"   => "1",
			"is_active" => "1",
			"meta"      => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"create_opportunity"            => "1",
				"opportunity_name"              => "Can translate",
				"opportunity_note"              => "Opportunity Note Can&#039;t have special",
				"company_map_fields"            => array(),

			)
		);

		$lead = array(
			"2.3" => "Alita",
			"2.6" => "Fobs",
			"3"   => "alita@gmail.com",
		);

		$expectedCloseDate = mktime( 0, 0, 0, date( "m" ), date( "d" ) + 30, date( "y" ) );
		$expectedCloseDate = date( "Y-m-d", $expectedCloseDate );

		$opportunity_data = array(
			"name"              => "Can translate",
			"primarySalesRep"   => array(
				"identifier" => "",
			),
			"expectedCloseDate" => $expectedCloseDate . "T00:00:00Z",
			"company"           => array(
				"id"         => "",
				"identifier" => "Catchall"
			),
			"contact"           => array(
				"id"   => "",
				"name" => " "
			),
			"site"              => array(
				"id"   => "",
				"name" => ""
			),
			"type"              => array( "id" => "" ),
			"campaign"          => array( "id" => "" ),
			"notes"             => "Opportunity Note Can't have special"
		);

		$note_data = array(
			"text" => "Opportunity Note Can't have special"
		);


		$mock_opportunity_data     = '{
			"id":1,
			"name":"Can translate",
			"notes":"Opportunity Note Can\'t have special"
		}';
		$mock_opportunity_response = array(
			"body" => $mock_opportunity_data
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->at( 6 ) )
			->method( "send_request" )
			->with(
				"sales/opportunities",
				"POST",
				$opportunity_data
			)
			->will( $this->returnValue( $mock_opportunity_response ) );

		$GF_ConnectWise->expects( $this->at( 7 ) )
			->method( "send_request" )
			->with(
				"sales/opportunities/1/notes",
				"POST",
				$note_data
			)
			->will( $this->returnValue( $mock_opportunity_response ) );

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_sent_activity_subject_field_should_escape_special_character() {
		$feed = array (
				"id"        => "1",
				"form_id"   => "1",
				"is_active" => "1",
				"meta"      => array(
					"contact_map_fields_first_name" => "2.3",
					"contact_map_fields_last_name"  => "2.6",
					"contact_map_fields_email"      => "3",
					"create_opportunity"            => "1",
					"opportunity_name"              => "Can&#039;t translate",
					"create_activity"               => "1",
					"activity_name"                 => "Let&#039;s Follow up the client",
					"company_map_fields"            => array(),
				)
			);

			$lead = array(
				"2.3" => "Alita",
				"2.6" => "Fobs",
				"3"   => "alita@gmail.com",
			);

			$dueDate = mktime( 0, 0, 0, date( "m" ), date( "d" ) + 7, date( "y" ) );
			$dueDate = date( "Y-m-d", $dueDate );

			$activity_data = array(
				"name"        => "Let's Follow up the client",
				"email"       => "alita@gmail.com",
				"type"        => array( "id" => "" ),
				"company"     => array(
					"id"         => "",
					"identifier" => "Catchall"
				),
				"contact"     => array(
					"id"   => "",
					"name" => " "
				),
				"status"      => array(
					"name" => "Open"
				),
				"assignTo"    => array(
					"identifier" => ""
				),
				"opportunity" => array(
					"id"   => "",
					"name" => ""
				),
				"dateStart"   => $dueDate . "T14:00:00Z",
				"dateEnd"     => $dueDate . "T14:15:00Z"
			);

			$mock_activity_data     = '{
				"id":1,
				"name":"Let\'s Follow up the client",
			}';
			$mock_activity_response = array(
				"body" => $mock_activity_data
			);


			$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
				->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
				->getMock();

			$GF_ConnectWise->expects( $this->at( 7 ) )
				->method( "send_request" )
				->with(
					"sales/activities",
					"POST",
					$activity_data
				)
				->will( $this->returnValue( $mock_activity_response ) );

			$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_sent_activity_note_field_should_escape_special_character() {
				$feed = array (
				"id"        => "1",
				"form_id"   => "1",
				"is_active" => "1",
				"meta"      => array(
					"contact_map_fields_first_name" => "2.3",
					"contact_map_fields_last_name"  => "2.6",
					"contact_map_fields_email"      => "3",
					"create_opportunity"            => "1",
					"opportunity_name"              => "Can&#039;t translate",
					"create_activity"               => "1",
					"activity_name"                 => "Let&#039;s Follow up the client",
					"activity_note"	                => "It&#039;s a note",
					"company_map_fields"            => array(),
				)
			);

			$lead = array(
				"2.3" => "Alita",
				"2.6" => "Fobs",
				"3"   => "alita@gmail.com",
			);

			$dueDate = mktime( 0, 0, 0, date( "m" ), date( "d" ) + 7, date( "y" ) );
			$dueDate = date( "Y-m-d", $dueDate );

			$activity_data = array(
				"name"        => "Let's Follow up the client",
				"email"       => "alita@gmail.com",
				"type"        => array( "id" => "" ),
				"company"     => array(
					"id"         => "",
					"identifier" => "Catchall"
				),
				"contact"     => array(
					"id"   => "",
					"name" => " "
				),
				"status"      => array(
					"name" => "Open"
				),
				"assignTo"    => array(
					"identifier" => ""
				),
				"opportunity" => array(
					"id"   => "",
					"name" => ""
				),
				"notes"       => "It's a note",
				"dateStart"   => $dueDate . "T14:00:00Z",
				"dateEnd"     => $dueDate . "T14:15:00Z"
			);

			$mock_activity_data     = '{
				"id":1,
				"name":"Let\'s Follow up the client",
				"notes":"It\'s a note"
			}';
			$mock_activity_response = array(
				"body" => $mock_activity_data
			);

			$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
				->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
				->getMock();

			$GF_ConnectWise->expects( $this->at( 7 ) )
				->method( "send_request" )
				->with(
					"sales/activities",
					"POST",
					$activity_data
				)
				->will( $this->returnValue( $mock_activity_response ) );

			$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_sent_service_ticket_summary_field_should_escape_special_character() {
		$feed = array (
				"id"        => "1",
				"form_id"   => "1",
				"is_active" => "1",
				"meta"      => array(
					"create_service_ticket"         => "1",
					"service_ticket_summary"        => "Service&#039;s Ticket Name",
					"contact_map_fields_first_name" => "2.3",
					"contact_map_fields_last_name"  => "2.6",
					"contact_map_fields_email"      => "3",
					"company_map_fields"            => array(),
				)
		);

		$lead = array(
			"2.3" => "Alita",
			"2.6" => "Fobs",
			"3"   => "alita@gmail.com",
		);

		$ticket_data = array(
			"summary"            => "Service's Ticket Name",
			"initialDescription" => "",
			"company"            => array(
				"id"         =>  "",
				"identifier" => "Catchall",
			),
			"type"               => array( "id" => "" ),
			"subtype"            => array( "id" => "" ),
			"item"               => array( "id" => "" ),
		);

		$mock_ticket_data     = '{
			"id":1,
			"summary":"Service\'s Ticket Name",
		}';
		$mock_ticket_response = array(
			"body" => $mock_ticket_data
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->at( 5 ) )
			->method( "send_request" )
			->with(
				"service/tickets",
				"POST",
				$ticket_data
			)
			->will( $this->returnValue( $mock_ticket_response ) );

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_sent_service_ticket_initial_description_should_escape_special_character() {
		$feed = array (
			"id"        => "1",
			"form_id"   => "1",
			"is_active" => "1",
			"meta"      => array(
				"create_service_ticket"              => "1",
				"service_ticket_summary"             => "Service's Ticket Name",
				"service_ticket_initial_description" => "It&#039;s Just an init",
				"contact_map_fields_first_name"      => "2.3",
				"contact_map_fields_last_name"       => "2.6",
				"contact_map_fields_email"           => "3",
				"company_map_fields"                 => array(),
			)
		);

		$lead = array(
			"2.3" => "Alita",
			"2.6" => "Fobs",
			"3"   => "alita@gmail.com",
		);

		$ticket_data = array(
			"summary"        => "Service's Ticket Name",
			"initialDescription" => "It's Just an init",
			"company"        => array(
				"id"         =>  "",
				"identifier" => "Catchall",
			),
			"type"           => array( "id" => "" ),
			"subtype"        => array( "id" => "" ),
			"item"           => array( "id" => "" ),
		);

		$mock_ticket_data     = '{
			"id":1,
			"summary":"Service\'s Ticket Name",
		}';
		$mock_ticket_response = array(
			"body" => $mock_ticket_data
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->at( 5 ) )
			->method( "send_request" )
			->with(
				"service/tickets",
				"POST",
				$ticket_data
			)
			->will( $this->returnValue( $mock_ticket_response ) );

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_sent_company_note_should_escape_special_charcter() {
		$feed = array (
			"id"        => "1",
			"form_id"   => "1",
			"is_active" => "1",
			"meta"      => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"company_note"                  => "It&#039;s Company Note",
				"company_map_fields"            => array(),
			)
		);

		$lead = array(
			"2.3" => "Alita",
			"2.6" => "Fobs",
			"3"   => "alita@gmail.com",
		);

		$mock_company_response = array(
			"body" => '[{"id":1}]'
		);

		$mock_company_note_data = '{
			"id":1,
			"text":"It\'s Company Note"
		}';

		$mock_company_note_response = array(
			"body" => $mock_company_note_data
		);

		$company_note = array(
			"text" => "It's Company Note"
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->at( 4 ) )
			->method( "send_request" )
			->with(
				"company/companies?conditions=identifier='Catchall'",
				"GET",
				NULL
			)
			->will( $this->returnValue( $mock_company_response ) );


		$GF_ConnectWise->expects( $this->at( 5 ) )
			->method( "send_request" )
			->with(
				"company/companies/1/notes",
				"POST",
				$company_note
			)
			->will( $this->returnValue( $mock_company_note_response ) );

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}

	function test_sent_contact_note_should_escape_special_character() {
		$feed = array(
			"id"        => "1",
			"form_id"   => "1",
			"is_active" => "1",
			"meta"      => array(
				"contact_map_fields_first_name" => "2.3",
				"contact_map_fields_last_name"  => "2.6",
				"contact_map_fields_email"      => "3",
				"contact_type"                  => "1",
				"contact_department"            => "2",
				"company_type"                  => "1",
				"company_status"                => "1",
				"contact_note"                  => "Can&#039;t help",
				"company_map_fields"            => array(
					array(
						"key"        => "company",
						"value"      => "2",
						"custom_key" => ""
					)
				)
			)
		);

		$lead = array(
			"2.3" => "Alita",
			"2.6" => "Fobs",
			"3"   => "alita@gmail.com",
		);

		$contact_data = array(
			"firstName"          => "Alita",
			"lastName"           => "Fobs",
			"company"            => array(
				"identifier" => "Catchall",
			),
			"type"               => array( "id" => "1" ),
			"department"         => array( "id" => "2" )
		);

		$mock_contact_data = '{"id":1}';
		$mock_contact_response = array(
			"body" => $mock_contact_data
		);

		$mock_contact_note_data = '{
			"id":1,
			"text": "Can\'t help"
		}';
		$mock_contact_note_response = array(
			"body" => $mock_contact_note_data
		);

		$note_data = array(
			"text" => "Can't help"
		);

		$GF_ConnectWise = $this->getMockBuilder( "GFConnectWise" )
			->setMethods( array( "send_request", "get_existing_contact", "is_valid_settings" ) )
			->getMock();

		$GF_ConnectWise->expects( $this->at( 2 ) )
			->method( "send_request" )
			->with(
				"company/contacts",
				"POST",
				$contact_data
			)
			->will( $this->returnValue( $mock_contact_response ) );


		$GF_ConnectWise->expects( $this->at( 4 ) )
			->method( "send_request" )
			->with(
				"company/contacts/1/notes",
				"POST",
				$note_data
			)
			->will( $this->returnValue( $mock_contact_note_response ) );

		$GF_ConnectWise->process_feed( $feed, $lead, array() );
	}
}
