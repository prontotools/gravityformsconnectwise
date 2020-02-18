<?php
require_once WP_PLUGIN_DIR . '/gravityforms/gravityforms.php';
require_once WP_PLUGIN_DIR . '/connectwise-forms-integration/class-gf-connectwise-2020v1.php';
require_once 'vendor/autoload.php';

class GravityFormsConnectWiseAddOnTest2020V1 extends WP_UnitTestCase {
	function setUp() {
		parent::setUp();

		$this->connectwise_plugin = new GFConnectWise2020v1();
		$this->slug = 'gravityformsaddon_connectwise_settings';
		$settings = array(
			'connectwise_url'                   => 'company_url',
			'company_id'                        => 'company_id',
			'public_key'                        => 'public_key',
			'private_key'                       => 'private_key',
			'client_id'							=> 'client_id',
			'enable_error_notification_emails'  => '1',
			'error_notification_emails_to'      => 'test@mail.com'
		);
		update_option( 'gravityformsaddon_connectwise_settings', $settings );
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
		$GF_ConnectWise = $this->getMockBuilder( 'GFConnectWise2020v1' )
			->setMethods( array( 'send_request' ) )
			->getMock();

		$mock_members_response = array(
			'body' => '[{"identifier": "Admin1", "firstName": "Training", "lastName": "Admin1"},{"identifier": "Admin2", "firstName": "Training", "lastName": "Admin2"}]'
		);
		$GF_ConnectWise->expects( $this->at( 0 ) )
			->method( 'send_request' )
			->with(
				'system/members?pageSize=200',
				'GET',
				NULL
			)
			->will( $this->returnValue( $mock_members_response ) );

		$actual_member_list = $GF_ConnectWise->get_team_members();
		$expected_member_list = array(
			array(
				'value' => 'Admin1',
				'label' => 'Training Admin1',
			),
			array(
				'value' => 'Admin2',
				'label' => 'Training Admin2',
			)
		);

		$this->assertEquals( $actual_member_list, $expected_member_list);
	}

	function tests_get_prepare_company_data() {
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

		$GF_ConnectWise = $this->getMockBuilder( 'GFConnectWise2020v1' )
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
			'types'        => array(
				array(
					'id' => array(
						'1'
					),
				)
			),
			'status'       => array(
				'id' => array(
					'1'
				)
			),
			'site'         => array(
				'name' => 'company_id',
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

		$GF_ConnectWise = $this->getMockBuilder( 'GFConnectWise2020v1' )
			->setMethods( array( 'send_request' ) )
			->getMock();


		$actual = $GF_ConnectWise->prepare_contact_data( $data_to_prepare );

		$expect = array(
			'firstName' => 'Test Firstname',
			'lastName'  => 'Test Lastname',
			'company'   => array(
				'identifier' => 'TestCompany'
			),
			'types'     => array(
				array(
					'id' => '1'
				)
			)
		);

		$this->assertEquals( $actual, $expect );
	}
}