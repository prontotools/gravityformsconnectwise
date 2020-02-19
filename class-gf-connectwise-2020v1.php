<?php

require_once WP_PLUGIN_DIR . '/connectwise-forms-integration/class-gf-connectwise.php';
GFForms::include_feed_addon_framework();

class GFConnectWise2020v1 extends GFConnectWise {
	private static $_instance = null;

	public static function get_instance() {
		if ( null == self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	public function get_team_members() {
		$this->log_debug( __METHOD__ . '(): start getting team members from ConnectWise' );

		$team_members_list    = array();
		$get_team_members_url = 'system/members?pageSize=200';
		$cw_team_members      = $this->send_request( $get_team_members_url, 'GET', NULL );
		$cw_team_members      = json_decode( $cw_team_members['body'] );

		foreach ( $cw_team_members as $each_member ) {
			$member = array(
				'label' => esc_html__( $each_member->firstName . ' ' . $each_member->lastName, 'gravityformsconnectwise' ),
				'value' => $each_member->identifier
			);
			array_push( $team_members_list, $member );
		}

		$this->log_debug( __METHOD__ . '(): finish getting team members from ConnectWise' );

		return $team_members_list;
	}

	public function prepare_company_data( $data_to_prepare ) {
		$company_id   = $this->get_plugin_setting( 'company_id' );
		$company_data = array(
			'id'           => 0,
			'identifier'   => $data_to_prepare['identifier'],
			'name'         => $data_to_prepare['company'],
			'addressLine1' => $data_to_prepare['address_line1'],
			'addressLine2' => $data_to_prepare['address_line2'],
			'city'         => $data_to_prepare['city'],
			'state'        => $data_to_prepare['state'],
			'zip'          => $data_to_prepare['zip'],
			'phoneNumber'  => $data_to_prepare['phone_number'],
			'faxNumber'    => $data_to_prepare['fax_number'],
			'website'      => $data_to_prepare['web_site'],
			'types'        => array(
				array(
					'id' => $data_to_prepare['company_type'],
				),
			),
			'status'       => array(
				'id' => $data_to_prepare['company_status'],
			),
			'site'         => array(
				'name' => $company_id,
			),
		);

		return $company_data;
	}

	public function prepare_contact_data( $data_to_prepare ) {
		$contact_data = array(
			'firstName' => $data_to_prepare['first_name'],
			'lastName'  => $data_to_prepare['last_name'],
			'company'   => array(
				'identifier' => $data_to_prepare['identifier'],
			),
			'types'     => array(
				array(
					'id' => $data_to_prepare['contact_type'],
				),
			),
		);

		return $contact_data;
	}
}