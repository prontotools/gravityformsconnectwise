<?php

GFForms::include_feed_addon_framework();

class GFConnectWise extends GFFeedAddOn {
	protected $_async_feed_processing    = true;
	protected $_title                    = 'Gravity Forms ConnectWise Add-On';
	protected $_short_title              = 'ConnectWise';
	protected $_version                  = '1.5.1';
	protected $_min_gravityforms_version = '2.0';
	protected $_slug                     = 'connectwise';
	protected $_path                     = 'connectwise-forms-integration/gravityformsconnectwise.php';
	protected $_full_path                = __FILE__;
	private static $_instance            = null;


	public static function get_instance() {
		if ( null == self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	public function setUp() {
		parent::setUp();
		require_once WP_PLUGIN_DIR . '/connectwise-forms-integration/class-cw-connection-version.php';
		$this->connectwise_version = new ConnectWiseVersion();
	}

	public function field_map_title() {
		return esc_html__( 'ConnectWise Field', 'gravityformsconnectwise' );
	}

	public function prepare_company_data( $data_to_prepare ) {
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
			'type'         => array(
				'id' => $data_to_prepare['company_type'],
			),
			'status'       => array(
				'id' => $data_to_prepare['company_status'],
			)
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
			'type'      => array(
				'id' => $data_to_prepare['contact_type'],
			)
		);

		return $contact_data;
	}

	public function process_feed( $feed, $lead, $form ) {
		$can_process_feed = $this->is_valid_settings();
		if ( false === $can_process_feed ) {
			return $lead;
		}
		$this->log_debug( '# ' . __METHOD__ . '(): start sending data to ConnectWise #' );

		$first_name    = $feed['meta']['contact_map_fields_first_name'];
		$last_name     = $feed['meta']['contact_map_fields_last_name'];
		$email         = $feed['meta']['contact_map_fields_email'];
		$department    = $feed['meta']['contact_department'];
		$contact_type  = $feed['meta']['contact_type'];
		$company_id    = NULL;
		$contact_id    = NULL;
		$company       = NULL;
		$address_line1 = NULL;
		$address_line2 = NULL;
		$city          = NULL;
		$state         = NULL;
		$zip           = NULL;
		$phone_number  = NULL;
		$fax_number    = NULL;
		$web_site      = NULL;

		foreach ( $feed['meta']['company_map_fields'] as $custom_map ) {
			if ( 'company' == $custom_map['key'] ) {
				$company = $custom_map['value'];
			} elseif ( 'address_1' == $custom_map['key'] ) {
				$address_line1 = $custom_map['value'];
			} elseif ( 'address_2' == $custom_map['key'] ) {
				$address_line2 = $custom_map['value'];
			} elseif ( 'city' == $custom_map['key'] ) {
				$city = $custom_map['value'];
			} elseif ( 'state' == $custom_map['key'] ) {
				$state = $custom_map['value'];
			} elseif ( 'zip' == $custom_map['key'] ) {
				$zip = $custom_map['value'];
			} elseif ( 'phone_number' == $custom_map['key'] ) {
				$phone_number = $custom_map['value'];
			} elseif ( 'fax_number' == $custom_map['key'] ) {
				$fax_number = $custom_map['value'];
			} elseif ( 'web_site' == $custom_map['key'] ) {
				$web_site = $custom_map['value'];
			}
		}

		$email = strtolower( $lead[ $email ] );
		$contact_data = $this->get_existing_contact( $lead[ $first_name ], $email );

		if ( NULL == $company or '' == $lead[ $company ] ) {
			$identifier = 'Catchall';
		} else {
			$company       = $lead[ $company ];
			$address_line1 = $lead[ $address_line1 ];
			$address_line2 = $lead[ $address_line2 ];
			$city          = $lead[ $city ];
			$state         = $lead[ $state ];
			$zip           = $lead[ $zip ];
			$country       = $lead[ $country ];
			$phone_number  = $lead[ $phone_number ];
			$fax_number    = $lead[ $fax_number ];
			$web_site      = $lead[ $web_site ];

			if ( NULL == $address_line1 or '' == $address_line1 ) {
				$address_line1 = '-';
			}
			if ( NULL == $address_line2 or '' == $address_line2 ) {
				$address_line2 = '-';
			}
			if ( NULL == $city or '' == $city ) {
				$city = '-';
			}
			if ( NULL == $state or '' == $state ) {
				$state = 'CA';
			}
			if ( NULL == $zip or '' == $zip ) {
				$zip = '-';
			}

			$identifier = preg_replace( '/[^\w]/', '', $company );
			$identifier = substr( $identifier, 0, 25 );

			$company_type   = $feed['meta']['company_type'];
			$company_status = $feed['meta']['company_status'];

			$data_to_prepare = array(
				'identifier'     => $identifier,
				'company'        => $company,
				'address_line1'  => $address_line1,
				'address_line2'  => $address_line2,
				'city'           => $city,
				'state'          => $state,
				'zip'            => $zip,
				'phone_number'   => $phone_number,
				'fax_number'     => $fax_number,
				'web_site'       => $web_site,
				'company_type'   => $company_type,
				'company_status' => $company_status,
			);

			$company_data = $this->prepare_company_data( $data_to_prepare );

			if ( '1' == $feed['meta']['company_as_lead'] ) {
				$company_data['leadFlag'] = true;
			}

			$get_company_url = "company/companies?conditions=identifier='{$identifier}'";
			$response        = $this->send_request( $get_company_url, 'GET', NULL );
			$exist_company    = json_decode( $response['body']);

			if ( empty( $exist_company ) and empty( $contact_data ) ) {
				$is_company_created = true;
			} else {
				$is_company_created = false;
			}

			if ( !empty( $contact_data ) ) {
				$identifier = $contact_data->company->identifier;
				if( NULL == $identifier ) {
					$url      = 'company/companies';
					$response = $this->send_request( $url, 'POST', $company_data );
					$company_response = json_decode( $response['body']);
					$company_id       = $company_response->id;
					$identifier       = $company_response->identifier;
				}
			} else {
				$url = 'company/companies';
				$response = $this->send_request( $url, 'POST', $company_data );
			}
		}

		if ( !$contact_data ) {
			$data_to_prepare = array(
				'first_name'   => $lead[ $first_name ],
				'last_name'    => $lead[ $last_name ],
				'identifier'   => $identifier,
				'contact_type' => $contact_type,
			);

			$contact_data = $this->prepare_contact_data( $data_to_prepare );

			if ( '---------------' != $department ) {
				$contact_data['department'] = array(
					'id' => $department
				);
			}

			$url          = 'company/contacts';
			$response     = $this->send_request( $url, 'POST', $contact_data );
			$contact_data = json_decode( $response['body'] );

			$comunication_types = array(
				'value'             => $email,
				'communicationType' => 'Email',
				'type'              => array(
					'id'   => 1,
					'name' => 'Email'
				),
				'defaultFlag' => true,
			);

			$contact_id = $contact_data->id;
			$url        = "company/contacts/{$contact_id}/communications";
			$response   = $this->send_request( $url, 'POST', $comunication_types );

			if ( '' != $feed['meta']['contact_note'] ) {
				$note         = GFCommon::replace_variables( $feed['meta']['contact_note'], $form, $lead, false, false, false, 'html' );
				$contact_note = strip_tags($note);
				$contact_note = html_entity_decode( $contact_note, ENT_QUOTES );

				$contact_note = array(
					'text' => $contact_note
				);

				$url          = "company/contacts/{$contact_id}/notes";
				$response     = $this->send_request( $url, 'POST', $contact_note );
			}
		}

		$get_company_url = "company/companies?conditions=identifier='{$identifier}'";
		$response        = $this->send_request( $get_company_url, 'GET', NULL );
		$company_data    = json_decode( $response['body']);
		$company_id      = $company_data[0]->id;

		if ( '' != $feed['meta']['company_note'] ) {
			$note         = GFCommon::replace_variables( $feed['meta']['company_note'], $form, $lead, false, false, false, 'html' );
			$company_note = strip_tags($note);
			$company_note = html_entity_decode( $company_note, ENT_QUOTES );

			$company_note = array(
				'text' => $company_note
			);

			$url          = "company/companies/{$company_id}/notes";
			$response     = $this->send_request( $url, 'POST', $company_note );
		}

		if ( $is_company_created ) {
			if ( 'Catchall' != $identifier ) {
				$company_url = "company/companies/{$company_id}";
				$company_update_data = array(
					array(
						'op'    => 'replace',
						'path'  => 'defaultContact',
						'value' => $contact_data
					)
				);
				$response     = $this->send_request( $company_url, 'PATCH', $company_update_data, $error_notification = false );
				if ( 400 == $response['response']['code'] ) {
					$company_update_data = array(
						array(
							'op'    => 'replace',
							'path'  => 'defaultContactId',
							'value' => $contact_id
						)
					);
					$response     = $this->send_request( $company_url, 'PATCH', $company_update_data, $error_notification = false );
				}
			}
		}

		if ( '1' == $feed['meta']['create_opportunity'] ) {
			$get_company_site_url  = "company/companies/{$company_id}/sites/";
			$company_site_data     = $this->send_request( $get_company_site_url, 'GET', NULL );
			$company_site_data     = json_decode( $company_site_data['body']);
			$company_site_id       = $company_site_data[0]->id;
			$company_site_name     = $company_site_data[0]->name;
			$opportunity_type      = $feed['meta']['opportunity_type'];
			$opportunity_closedate = $feed['meta']['opportunity_closedate'];

			if ( '' == $opportunity_closedate ) {
				$opportunity_closedate = 30;
			}
			$expectedCloseDate     = mktime( 0, 0, 0, date( 'm' ), date( 'd' ) + $opportunity_closedate, date( 'y' ) );
			$expectedCloseDate     = date( 'Y-m-d', $expectedCloseDate );
			$expectedCloseDate     = $expectedCloseDate . 'T00:00:00Z';
			$opportunity_data = array(
				'name'    => GFCommon::replace_variables( $feed['meta']['opportunity_name'], $form, $lead, false, false, false, 'html' ),
				'company' => array(
					'id'         => $company_id,
					'identifier' => $identifier
				),
				'contact' => array(
					'id'   => $contact_data->id,
					'name' => sprintf( esc_html__( '%s %s' ), $contact_data->firstName, $contact_data->lastName )
				),
				'site' => array(
					'id'   => $company_site_id,
					'name' => $company_site_name
				),
				'primarySalesRep' => array(
					'identifier'  => $feed['meta']['opportunity_owner'],
				),
				'expectedCloseDate' => $expectedCloseDate
			);

			$opportunity_data['name'] = html_entity_decode( $opportunity_data['name'], ENT_QUOTES );

			if ( '---------------' != $opportunity_type ) {
				$opportunity_data['type'] = array(
					'id' => $opportunity_type
				);
			}
			if ( '---------------' != $feed['meta']['marketing_campaign'] ) {
				$opportunity_data['campaign'] = array(
					'id' => $feed['meta']['marketing_campaign'],
				);
			}
			if ( '' != $feed['meta']['opportunity_source'] ) {
				$opportunity_data['source'] = $feed['meta']['opportunity_source'];
			}
			if ( '' != $feed['meta']['opportunity_note'] ) {
				$note                      = GFCommon::replace_variables( $feed['meta']['opportunity_note'], $form, $lead, false, false, false, 'html' );
				$opportunity_note          = strip_tags( $note );
				$opportunity_note          = html_entity_decode( $opportunity_note, ENT_QUOTES );
				$opportunity_note          = preg_replace( '/\s+/S', ' ', $opportunity_note );
				$opportunity_data['notes'] = $opportunity_note;
			}

			$url = 'sales/opportunities';
			$response             = $this->send_request( $url, 'POST', $opportunity_data );
			$opportunity_response = json_decode( $response['body'] );
			$opportunity_id       = $opportunity_response->id;

			if ( '' != $feed['meta']['opportunity_note'] ) {
				$note             = GFCommon::replace_variables( $feed['meta']['opportunity_note'], $form, $lead, false, false, false, 'html' );
				$opportunity_note = strip_tags($note);
				$opportunity_note = html_entity_decode( $opportunity_note, ENT_QUOTES );
				$opportunity_note = array(
					'text' => $opportunity_note
				);

				$url      = "sales/opportunities/{$opportunity_id}/notes";
				$response = $this->send_request( $url, 'POST', $opportunity_note);
			}
		}

		if ( '1' == $feed['meta']['create_activity'] and '1' == $feed['meta']['create_opportunity'] ) {
			$activity_name      = GFCommon::replace_variables( $feed['meta']['activity_name'], $form, $lead, false, false, false, 'html' );
			$activity_name      = html_entity_decode( $activity_name, ENT_QUOTES );
			$assign_activity_to = $feed['meta']['activity_assigned_to'];

			$input_duedate = $feed['meta']['activity_duedate'];

			if ( $input_duedate == '' ) {
				$input_duedate = 7;
			}

			$dueDate       = mktime( 0, 0, 0, date( 'm' ), date( 'd' ) + $input_duedate, date( 'y' ) );
			$dueDate       = date( 'Y-m-d', $dueDate );

			$dateStart = $dueDate . 'T14:00:00Z';
			$dateEnd   = $dueDate . 'T14:15:00Z';

			$activity_data = array(
				'name'  => $activity_name,
				'email' => $email,
				'type'  => array(
					'id'   => $feed['meta']['activity_type']
				),
				'company' => array(
					'id'         => $company_id,
					'identifier' => $identifier
				),
				'contact' => array(
					'id'   => $contact_data->id,
					'name' => sprintf(esc_html__('%s %s'), $contact_data->firstName, $contact_data->lastName)
				),
				'status' => array(
					'name' => 'Open'
				),
				'assignTo' => array(
					'identifier' => $assign_activity_to
				),
				'opportunity' => array(
					'id'   => $opportunity_response->id,
					'name' => $opportunity_response->name
				),
				'dateStart' => $dateStart,
				'dateEnd'   => $dateEnd
			 );

			if ( '' != $feed['meta']['activity_note'] ) {
				$note  = GFCommon::replace_variables( $feed['meta']['activity_note'], $form, $lead, false, false, false, 'html' );
				$activity_data['notes'] = strip_tags($note);
				$activity_data['notes'] = html_entity_decode( $activity_data['notes'], ENT_QUOTES );
			}

			$url = 'sales/activities';
			$response = $this->send_request( $url, 'POST', $activity_data );
		}

		if ( '1' == $feed['meta']['create_service_ticket'] ) {
			$url = 'service/tickets';
			$ticket_data = array(
				'summary'            => GFCommon::replace_variables( $feed['meta']['service_ticket_summary'], $form, $lead, false, false, false, 'html' ),
				'company'            => array(
					'id'         => $company_id,
					'identifier' => $identifier,
				)
			);

			$ticket_data['summary'] = html_entity_decode( $ticket_data['summary'], ENT_QUOTES );

			$initialDescription                = GFCommon::replace_variables( $feed['meta']['service_ticket_initial_description'], $form, $lead, false, false, false, 'html' );
			$ticket_data['initialDescription'] = strip_tags( $initialDescription );
			$ticket_data['initialDescription'] = str_replace( '&nbsp;', '', $ticket_data['initialDescription'] );
			$ticket_data['initialDescription'] = html_entity_decode( $ticket_data['initialDescription'], ENT_QUOTES );

			$ticket_board = $feed['meta']['service_ticket_board'];
			if ( '' !=  $ticket_board ) {
				$ticket_data['board'] = array(
					'id' => $ticket_board
				);
			}
			$ticket_priority = $feed['meta']['service_ticket_priority'];
			if ( '' !=  $ticket_priority ) {
				$ticket_data['priority'] = array(
					'id' => $ticket_priority
				);
			}
			$ticket_type = $feed['meta']['service_ticket_type'];
			if ( '---------------' !=  $ticket_type ) {
				$ticket_data['type'] = array(
					'id' => $ticket_type
				);
			}
			$ticket_subtype = $feed['meta']['service_ticket_subtype'];
			if ( '---------------' !=  $ticket_subtype ) {
				$ticket_data['subtype'] = array(
					'id' => $ticket_subtype
				);
			}
			$ticket_item = $feed['meta']['service_ticket_item'];
			if ( '---------------' !=  $ticket_item ) {
				$ticket_data['item'] = array(
					'id' => $ticket_item
				);
			}

			$response = $this->send_request( $url, 'POST', $ticket_data);
		}
		$this->log_debug( '# ' . __METHOD__ . '(): finish sending data to ConnectWise #' );

		return $lead;
	}

	public function get_existing_contact( $firstname, $email ) {
		$contact_url  = "company/contacts?conditions=firstname='{$firstname}'";
		$response     = $this->send_request( $contact_url, 'GET', NULL, $error_notification = false );
		$contact_list = json_decode( $response['body']);
		$this->log_debug( __METHOD__ . '(): Number of contacts with matching firstname => ' . print_r( count($contact_list), true ) );

		foreach ( $contact_list as $contact ) {
			if ( '' != $contact->communicationItems ) {
				foreach ( $contact->communicationItems as $item ) {
					if ( $email == $item->value and 'Email' == $item->communicationType ) {
						$this->log_debug( __METHOD__ . '(): Found matching email ' . print_r( $item->value, true ) );
						return $contact;
					}
				}
			}
			else {
				$contact_id          = $contact->id;
				$url                 = "company/contacts/{$contact_id}/communications";
				$response            = $this->send_request( $url, 'GET', NULL, $error_notification = false );
				$communication_items = json_decode( $response['body'] );
				foreach ( $communication_items as $item ) {
					if ( $email == $item->value and 'Email' == $item->communicationType ) {
						$this->log_debug( __METHOD__ . '(): Found matching email ' . print_r( $item->value, true ) );
						return $contact;
					}
				}
			}
		}

		return false;
	}

	public function styles() {
		$styles = array(
			array(
				'handle'  => 'gform_connectwise_form_settings_css',
				'src'     => $this->get_base_url() . '/css/form_settings.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( 'admin_page' => array( 'form_settings', 'plugin_settings' ) ),
				)
			)
		);

		return array_merge( parent::styles(), $styles );
	}

	public function scripts() {
		$current_theme = wp_get_theme();
		$theme_name = $current_theme->Name;

		$scripts = array(
			array(
				'handle'  => 'pronto_ads_js',
				'src'     => $this->get_base_url() . '/js/pronto-ads.js',
				'version' => $this->_version,
				'deps'    => array( 'jquery' ),
				'strings' => array(
					'path' => plugins_url( 'images/connectwise-banner.jpg', __FILE__ )
				),
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings', 'plugin_settings' )
					)
				)
			),
		);




		if ( strpos( $theme_name, 'Phoenix' ) === false ) {
			return array_merge( parent::scripts(), $scripts );
		}
		else {
			return parent::scripts();
		}
	}

	public function feed_list_columns() {
		return array(
			'feed_name' => esc_html__( 'Name', 'gravityformsconnectwise' ),
			'action'    => esc_html__( 'Action', 'gravityformsconnectwise' )
		);
	}

	public function get_column_value_action( $feed ) {
		$actions = array();
		if ( '1' == $feed['meta']['create_opportunity'] ) {
			array_push($actions, 'Create New Opportunity');
		}

		if ( '1' == $feed['meta']['create_activity'] ) {
			array_push($actions, 'Create New Activity');
		}

		if ( '1' == $feed['meta']['create_service_ticket'] ) {
			array_push($actions, 'Create New Service Ticket');
		}

		$actions = implode( ', ', $actions );

		return esc_html__( $actions, 'gravityformsconnectwise' );
	}

	public function feed_settings_fields() {
		$base_fields = array(
			'title'  => 'ConnectWise',
			'fields' => array(
				array(
					'label'    => esc_html__( 'Feed name', 'gravityformsconnectwise' ),
					'type'     => 'text',
					'name'     => 'feed_name',
					'class'    => 'small',
					'required' => true,
					'tooltip'  => esc_html__( '<h6>Name</h6>Enter a feed name to uniquely identify this setup.', 'gravityformsconnectwise' )
				),
				array(
					'name'     => 'action',
					'label'    => esc_html__( 'Action', 'gravityformsconnectwise' ),
					'type'     => 'checkbox',
					'onclick'  => 'jQuery(this).parents("form").submit();',
					'tooltip'  => esc_html__( '<h6>Action</h6>When a feed is active, a Contact and Company lookup will happen each time. You can also set for an Opportunity, Activity and/or Service Ticket to be created.</br>An Opportunity must be created in order to create an Activity.', 'gravityformsconnectwise' ),
					'choices'  => array(
						array(
							'name'  => 'create_opportunity',
							'label' => esc_html__( 'Create New Opportunity', 'gravityformsconnectwise' ),
						),
						array(
							'name'  => 'create_activity',
							'label' => esc_html__( 'Create New Activity', 'gravityformsconnectwise' ),
						),
						array(
							'name'  => 'create_service_ticket',
							'label' => esc_html__( 'Create New Service Ticket', 'gravityformsconnectwise' ),
						),
					)
				)
			)
		);

		$contact_fields = array(
			'title'      => esc_html__( 'Contact Details', 'gravityformsconnectwise' ),
			'fields'     => array(
				array(
					'name'      => 'contact_map_fields',
					'label'     => esc_html__( 'Map Fields', 'gravityformsconnectwise' ),
					'type'      => 'field_map',
					'field_map' => $this->standard_fields_mapping(),
					'tooltip'   => esc_html__( '<h6>Contact Map Fields</h6>Select which Gravity Form fields pair with their respective ConnectWise fields.', 'gravityformsconnectwise' )
				),
				array(
					'name'    => 'contact_type',
					'label'   => esc_html__( 'Contact Type', 'gravityformsconnectwise' ),
					'type'    => 'select',
					'choices' => $this->get_contact_types(),
				),
				array(
					'name'    => 'contact_department',
					'label'   => esc_html__( 'Department', 'gravityformsconnectwise' ),
					'type'    => 'select',
					'choices' => $this->get_departments(),
				),
				array(
					'name'  => 'contact_note',
					'label' => esc_html__( 'Notes', 'gravityformsconnectwise' ),
					'type'  => 'textarea',
					'class' => 'medium merge-tag-support'
				),
			)
		);

		$company_fields = array(
			'title'  => esc_html__( 'Company Details', 'gravityformsconnectwise' ),
			'fields' => array(
				array(
					'name'           => 'company_map_fields',
					'label'          => esc_html__( 'Map Fields', 'gravityformsconnectwise' ),
					'type'           => 'dynamic_field_map',
					'field_map'      => $this->custom_fields_mapping(),
					'tooltip'        => esc_html__( '<h6>Company Map Fields</h6>Select which Gravity Form fields pair with their respective ConnectWise fields.', 'gravityformsconnectwise' ),
					'disable_custom' => true,
				),
				array(
					'name'    => 'company_type',
					'label'   => esc_html__( 'Company Type', 'gravityformsconnectwise' ),
					'type'    => 'select',
					'choices' => $this->get_company_types(),
				),
				array(
					'name'    => 'company_status',
					'label'   => esc_html__( 'Status', 'gravityformsconnectwise' ),
					'type'    => 'select',
					'choices' => $this->get_company_statuses(),
				),
				array(
					'name'    => 'company_as_lead',
					'type'    => 'checkbox',
					'choices' => array(
						array(
							'label' => 'Mark this company as a lead',
							'name'  => 'company_as_lead',
							'tooltip' => esc_html__( "<h6>Mark this company as a lead</h6>Checking this will tick the \"Is this company a lead?\" checkbox in the Company's Profile setting", 'gravityformsconnectwise' ),
						)
					),
				),
				array(
					'name'  => 'company_note',
					'label' => esc_html__( 'Notes', 'gravityformsconnectwise' ),
					'type'  => 'textarea',
					'class' => 'medium merge-tag-support'
				),
			)
		);

		$opportunity_fields = array(
			'title'      => esc_html__( 'Opportunity Details', 'gravityformsconnectwise' ),
			'dependency' => array(
				'field'  => 'create_opportunity',
				'values' => ( '1' )
			),
			'fields'     => array(
				array(
					'name'          => 'opportunity_name',
					'label'         => esc_html__( 'Summary', 'gravityformsconnectwise' ),
					'required'      => true,
					'type'          => 'text',
					'default_value' => 'New Opportunity from page: {embed_post:post_title}',
					'class'         => 'medium merge-tag-support'
				),
				array(
					'name'    => 'opportunity_type',
					'label'   => esc_html__( 'Opportunity Type', 'gravityformsconnectwise' ),
					'type'    => 'select',
					'choices' => $this->get_opportunity_types(),
				),
				array(
					'name'    => 'marketing_campaign',
					'label'   => esc_html__( 'Marketing Campaign', 'gravityformsconnectwise' ),
					'type'    => 'select',
					'tooltip' => esc_html__( '<h6>Marketing Campaign</h6>Any Campaign you create in the Marketing section will be available here for you to attach to the Opportunity.', 'gravityformsconnectwise' ),
					'choices' => $this->get_marketing_campaign(),
				),
				array(
					'name'    => 'opportunity_owner',
					'label'   => esc_html__( 'Sales Rep', 'gravityformsconnectwise' ),
					'type'    => 'select',
					'choices' => $this->get_team_members(),
				),
				array(
					'name'          => 'opportunity_closedate',
					'label'         => esc_html__( 'Close Date', 'gravityformsconnectwise' ),
					'type'          => 'text',
					'class'         => 'small',
					'required'      => true,
					'tooltip'       => '<h6>' . esc_html__( 'Close Date', 'gravityformsconnectwise' ) . '</h6>' . esc_html__( 'Enter the number of days the Opportunity should remain open. For example, entering "30" means the Opportunity will close 30 days after it\'s created.', 'gravityformsconnectwise' ),
					'default_value' => '30'
				),
				array(
					'name'  => 'opportunity_source',
					'label' => esc_html__( 'Source', 'gravityformsconnectwise' ),
					'type'  => 'text',
					'class' => 'medium',
				),
				array(
					'name'  => 'opportunity_note',
					'label' => esc_html__( 'Notes', 'gravityformsconnectwise' ),
					'type'  => 'textarea',
					'class' => 'medium merge-tag-support'
				),
			)
		);

		$activity_fields = array(
			'title'      => esc_html__( 'Activity Details', 'gravityformsconnectwise' ),
			'dependency' => array(
				'field'  => 'create_activity',
				'values' => ( '1' )
			),
			'fields'     => array(
				array(
					'name'          => 'activity_name',
					'required'      => true,
					'label'         => esc_html__( 'Subject', 'gravityformsconnectwise' ),
					'type'          => 'text',
					'class'         => 'medium merge-tag-support',
					'default_value' => 'Follow up with web lead'
				),
				array(
					'name'    => 'activity_assigned_to',
					'label'   => esc_html__( 'Assign To', 'gravityformsconnectwise' ),
					'type'    => 'select',
					'choices' => $this->get_team_members(),
				),
				array(
					'name'          => 'activity_duedate',
					'label'         => esc_html__( 'Due Date', 'gravityformsconnectwise' ),
					'type'          => 'text',
					'class'         => 'small',
					'default_value' => '7',
					'required'      => true,
					'tooltip'       => '<h6>' . esc_html__( 'Due Date', 'gravityformsconnectwise' ) . '</h6>' . esc_html__( 'Enter the number of days until the Activity should be due. For example, entering "7" means the Activity will be due 7 days after it\'s created.', 'gravityformsconnectwise' ),
				),
				array(
					'name'    => 'activity_type',
					'label'   => esc_html__( 'Type', 'gravityformsconnectwise' ),
					'type'    => 'select',
					'choices' => $this->get_activity_types(),
				),
				array(
					'name'  => 'activity_note',
					'label' => esc_html__( 'Notes', 'gravityformsconnectwise' ),
					'type'  => 'textarea',
					'class' => 'medium merge-tag-support'
				)
			)
		);

		$service_ticket_fields = array(
			'title'      => esc_html__( 'Service Ticket Details', 'gravityformsconnectwise' ),
			'dependency' => array(
				'field'  => 'create_service_ticket',
				'values' => ( '1' )
			),
			'fields'     => array(
				array(
					'name'     => 'service_ticket_summary',
					'required' => true,
					'label'    => esc_html__( 'Summary', 'gravityformsconnectwise' ),
					'type'     => 'text',
					'class'    => 'medium merge-tag-support',
				),
				array(
					'name'     => 'service_ticket_board',
					'required' => false,
					'label'    => esc_html__( 'Board', 'gravityformsconnectwise' ),
					'type'     => 'select',
					'choices'  => $this->get_service_board(),
				),
				array(
					'name'     => 'service_ticket_priority',
					'required' => false,
					'label'    => esc_html__( 'Priority', 'gravityformsconnectwise' ),
					'type'     => 'select',
					'choices'  => $this->get_service_priority(),
				),
				array(
					'name'     => 'service_ticket_type',
					'required' => false,
					'label'    => esc_html__( 'Type', 'gravityformsconnectwise' ),
					'type'     => 'select',
					'choices'  => $this->get_service_types(),
			   ),
				array(
				   'name'     => 'service_ticket_subtype',
				   'required' => false,
				   'label'    => esc_html__( 'Subtype', 'gravityformsconnectwise' ),
				   'type'     => 'select',
				   'choices'  => $this->get_service_subtypes(),
			   ),
				array(
				   'name'     => 'service_ticket_item',
				   'required' => false,
				   'label'    => esc_html__( 'Item', 'gravityformsconnectwise' ),
				   'type'     => 'select',
				   'choices'  => $this->get_service_item(),
			   ),
				array(
					'name'     => 'service_ticket_initial_description',
					'required' => true,
					'label'    => esc_html__( 'Initial Description', 'gravityformsconnectwise' ),
					'type'     => 'textarea',
					'class'    => 'medium merge-tag-support',
				),
			)
		);

		$conditional_fields = array(
			'title'      => esc_html__( 'Feed Conditional Logic', 'gravityformsconnectwise' ),
			'fields'     => array(
				array(
					'type'           => 'feed_condition',
					'name'           => 'feed_condition',
					'label'          => esc_html__( 'Conditional Logic', 'gravityformsconnectwise' ),
					'checkbox_label' => esc_html__( 'Enable', 'gravityformsconnectwise' ),
					'instructions'   => esc_html__( 'Export to ConnectWise if', 'gravityformsconnectwise' ),
					'tooltip'        => '<h6>' . esc_html__( 'Conditional Logic', 'gravityformsconnectwise' ) . '</h6>' . esc_html__( 'When conditional logic is enabled, form submissions will only be exported to ConnectWise when the condition is met. When disabled, all form submissions will be posted.', 'gravityformsconnectwise' )
				)
			),
			'dependency' => array( $this, 'show_conditional_logic_field' ),
		);

		return array( $base_fields, $contact_fields, $company_fields, $opportunity_fields, $activity_fields, $service_ticket_fields, $conditional_fields );
	}

	public function can_create_feed() {
		return $this->is_valid_settings();
	}

	public function get_team_members(){
		$this->log_debug( __METHOD__ . '(): start getting team members from ConnectWise' );

		$team_members_list = array();

		$get_team_members_url = 'system/members?pageSize=200';
		$cw_team_members = $this->send_request( $get_team_members_url, 'GET', NULL );
		$cw_team_members = json_decode( $cw_team_members['body'] );

		foreach ( $cw_team_members as $each_member ) {
			$member = array(
					'label' => esc_html__( $each_member->name, 'gravityformsconnectwise' ),
					'value' => $each_member->identifier
			);
			array_push( $team_members_list, $member );
		}

		$this->log_debug( __METHOD__ . '(): finish getting team members from ConnectWise' );

		return $team_members_list;
	}

	public function get_departments() {
		$this->log_debug( __METHOD__ . '(): start getting departments from ConnectWise' );

		$department_list = array();

		$get_departments_url     = 'company/contacts/departments?pageSize=200';
		$cw_department           = $this->send_request( $get_departments_url, 'GET', NULL );
		$cw_department           = json_decode( $cw_department['body'] );
		$default_department      = array(
			'label' => esc_html__( '---------------', 'gravityformsconnectwise' ),
			'value' => NULL
		);

		array_push( $department_list, $default_department );

		foreach ( $cw_department as $each_department ) {
			$department = array(
				'label' => esc_html__( $each_department->name, 'gravityformsconnectwise' ),
				'value' => $each_department->id
			);
			array_push( $department_list, $department );
		}

		$this->log_debug( __METHOD__ . '(): finish getting departments from ConnectWise' );

		return $department_list;
	}

	public function get_service_board() {
		$this->log_debug( __METHOD__ . '(): start getting service board from ConnectWise' );

		$board_list = array();

		$get_boards_url = 'service/boards?pageSize=200';
		$cw_board = $this->send_request( $get_boards_url, 'GET', NULL );
		$cw_board = json_decode( $cw_board['body'] );

		foreach ( $cw_board as $each_board ) {
			$board = array(
				'label' => esc_html__( $each_board->name, 'gravityformsconnectwise' ),
				'value' => $each_board->id
			);
			array_push( $board_list, $board );
		}

		$this->log_debug( __METHOD__ . '(): finish getting service board from ConnectWise' );

		return $board_list;
	}

	public function get_service_priority() {
		$this->log_debug( __METHOD__ . '(): start getting service priority from ConnectWise' );

		$priority_list = array();

		$get_prioritys_url = 'service/priorities?pageSize=200';
		$cw_priority = $this->send_request( $get_prioritys_url, 'GET', NULL );
		$cw_priority = json_decode( $cw_priority['body'] );

		foreach ( $cw_priority as $each_priority ) {
			$priority = array(
				'label' => esc_html__( $each_priority->name, 'gravityformsconnectwise' ),
				'value' => $each_priority->id
			);
			array_push( $priority_list, $priority );
		}

		$this->log_debug( __METHOD__ . '(): finish getting service priority from ConnectWise' );

		return $priority_list;
	}

	public function get_company_types() {
		$company_type_list = array();

		$get_company_type_url = 'company/companies/types?pageSize=200';
		$cw_company_type = $this->send_request( $get_company_type_url, 'GET', NULL );
		$cw_company_type = json_decode( $cw_company_type['body'] );

		foreach ( $cw_company_type as $each_company_type ) {
			$company_type = array(
				'label' => esc_html__( $each_company_type->name, 'gravityformsconnectwise' ),
				'value' => $each_company_type->id
			);
			array_push( $company_type_list, $company_type );
		}
		return $company_type_list;
	}

	public function get_contact_types() {
		$contact_type_list = array();

		$get_contact_types_url = 'company/contacts/types?pageSize=200';
		$cw_contact_types = $this->send_request( $get_contact_types_url, 'GET', NULL );
		$cw_contact_types = json_decode( $cw_contact_types['body'] );

		foreach ( $cw_contact_types as $each_contact_type ) {
			$contact_type = array(
				'label' => esc_html__( $each_contact_type->description, 'gravityformsconnectwise' ),
				'value' => $each_contact_type->id
			);
			array_push( $contact_type_list, $contact_type );
		}
		return $contact_type_list;
	}

	public function get_marketing_campaign() {
		$marketing_campaign_list = array();

		$get_campaign_url      = 'marketing/campaigns?pageSize=200';
		$cw_marketing_campaign = $this->send_request( $get_campaign_url, 'GET', NULL );
		$cw_marketing_campaign = json_decode( $cw_marketing_campaign['body'] );

		$default_campaing      = array(
			'label' => esc_html__( '---------------', 'gravityformsconnectwise' ),
			'value' => NULL
		);
		array_push( $marketing_campaign_list, $default_campaing );

		foreach ( $cw_marketing_campaign as $each_marketing_campaign ) {
			$marketing_campaign = array(
				'label' => esc_html__( $each_marketing_campaign->name, 'gravityformsconnectwise' ),
				'value' => $each_marketing_campaign->id
			);
			array_push( $marketing_campaign_list, $marketing_campaign );
		}
		return $marketing_campaign_list;
	}

	public function get_opportunity_types() {
		$opportunity_type_list = array();

		$get_opportunity_type_url = 'sales/opportunities/types?pageSize=200';
		$cw_opportunity_type = $this->send_request( $get_opportunity_type_url, 'GET', NULL );
		$cw_opportunity_type = json_decode( $cw_opportunity_type['body'] );

		$default_opportunity_type      = array(
			'label' => esc_html__( '---------------', 'gravityformsconnectwise' ),
			'value' => NULL
		);
		array_push( $opportunity_type_list, $default_opportunity_type );

		foreach ( $cw_opportunity_type as $each_opportunity_type ) {
			$opportunity_type = array(
				'label' => esc_html__( $each_opportunity_type->description, 'gravityformsconnectwise' ),
				'value' => $each_opportunity_type->id
			);
			array_push( $opportunity_type_list, $opportunity_type );
		}
		return $opportunity_type_list;
	}

	public function get_company_statuses() {
		$company_status_list = array();

		$get_company_status_url = 'company/companies/statuses?pageSize=200';
		$cw_company_status = $this->send_request( $get_company_status_url, 'GET', NULL );
		$cw_company_status = json_decode( $cw_company_status['body'] );

		foreach ( $cw_company_status as $each_company_status ) {
			$company_status = array(
				'label' => esc_html__( $each_company_status->name, 'gravityformsconnectwise' ),
				'value' => $each_company_status->id
			);
			array_push( $company_status_list, $company_status );
		}
		return $company_status_list;
	}

	public function get_activity_types() {
		$activity_type_list = array();

		$get_activity_type_url = 'sales/activities/types?pageSize=200';
		$cw_activity_type = $this->send_request( $get_activity_type_url, 'GET', NULL );
		$cw_activity_type = json_decode( $cw_activity_type['body'] );

		foreach ( $cw_activity_type as $each_activity_type ) {
			$activity_type = array(
				'label' => esc_html__( $each_activity_type->name, 'gravityformsconnectwise' ),
				'value' => $each_activity_type->id
			);
			array_push( $activity_type_list, $activity_type );
		}
		return $activity_type_list;
	}

	public function get_service_types() {
		$this->log_debug( __METHOD__ . '(): start getting service type from ConnectWise' );
		$type_list = array();
		$get_boards_url = 'service/boards?pageSize=200';
		$cw_board = $this->send_request( $get_boards_url, 'GET', NULL );
		$cw_board = json_decode( $cw_board['body'] );

		$default_board      = array(
			'label' => esc_html__( '---------------', 'gravityformsconnectwise' ),
			'value' => NULL
		);
		array_push( $type_list, $default_board );
		foreach ( $cw_board as $each_board ) {
			$get_type_url = 'service/boards/' . $each_board->id . '/types?pageSize=200';
			$cw_service_type = $this->send_request( $get_type_url, 'GET', NULL );
			$cw_service_type = json_decode( $cw_service_type['body'] );

			$choices = array();
			foreach ( $cw_service_type as $each_type ) {
				$type = array(
					'label' => esc_html__( $each_type->name, 'gravityformsconnectwise' ),
					'value' => $each_type->id
				);
				array_push( $choices, $type );
			}
			$board = array(
				'label' => esc_html__( $each_board->name, 'gravityformsconnectwise' ),
				'choices' => $choices
			);
			array_push( $type_list, $board );
		}
		$this->log_debug( __METHOD__ . '(): finish getting service type from ConnectWise' );
		return $type_list;
	}

	public function get_service_subtypes() {
		$this->log_debug( __METHOD__ . '(): start getting service subtype from ConnectWise' );
		$subtype_list = array();
		$get_boards_url = 'service/boards?pageSize=200';
		$cw_board = $this->send_request( $get_boards_url, 'GET', NULL );
		$cw_board = json_decode( $cw_board['body'] );
		$default_board      = array(
			'label' => esc_html__( '---------------', 'gravityformsconnectwise' ),
			'value' => NULL
		);
		array_push( $subtype_list, $default_board );
		foreach ( $cw_board as $each_board ) {
			$get_subtype_url = 'service/boards/' . $each_board->id . '/subtypes?pageSize=200';
			$cw_service_subtype = $this->send_request( $get_subtype_url, 'GET', NULL );
			$cw_service_subtype = json_decode( $cw_service_subtype['body'] );

			$choices = array();
			foreach ( $cw_service_subtype as $each_subtype ) {
				$subtype = array(
					'label' => esc_html__( $each_subtype->name, 'gravityformsconnectwise' ),
					'value' => $each_subtype->id
				);
				array_push( $choices, $subtype );
			}
			$board = array(
				'label' => esc_html__( $each_board->name, 'gravityformsconnectwise' ),
				'choices' => $choices
			);
			array_push( $subtype_list, $board );
		}
		$this->log_debug( __METHOD__ . '(): finish getting service subtype from ConnectWise' );
		return $subtype_list;
   }

	public function get_service_item() {
		$this->log_debug( __METHOD__ . '(): start getting service item from ConnectWise' );
		$item_list = array();
		$get_boards_url = 'service/boards?pageSize=200';
		$cw_board = $this->send_request( $get_boards_url, 'GET', NULL );
		$cw_board = json_decode( $cw_board['body'] );
		$default_board      = array(
			'label' => esc_html__( '---------------', 'gravityformsconnectwise' ),
			'value' => NULL
		);
		array_push( $item_list, $default_board );
		foreach ( $cw_board as $each_board ) {
			$get_item_url = 'service/boards/' . $each_board->id . '/items?pageSize=200';
			$cw_service_item = $this->send_request( $get_item_url, 'GET', NULL );
			$cw_service_item = json_decode( $cw_service_item['body'] );
			$choices = array();
			foreach ( $cw_service_item as $each_item ) {
				$item = array(
					'label' => esc_html__( $each_item->name, 'gravityformsconnectwise' ),
					'value' => $each_item->id
				);
				array_push( $choices, $item );
			}
			$board = array(
				'label' => esc_html__( $each_board->name, 'gravityformsconnectwise' ),
				'choices' => $choices
			);
			array_push( $item_list, $board );
		}
		$this->log_debug( __METHOD__ . '(): finish getting service item from ConnectWise' );
		return $item_list;
	}

	public function standard_fields_mapping() {
		return array(
			array(
				'name'       => 'first_name',
				'label'      => esc_html__( 'First Name', 'gravityformsconnectwise' ),
				'required'   => true,
				'field_type' => array(
					'name',
					'text',
					'hidden'
				),
			),
			array(
				'name'       => 'last_name',
				'label'      => esc_html__( 'Last Name', 'gravityformsconnectwise' ),
				'required'   => true,
				'field_type' => array(
					'name',
					'text',
					'hidden'
				),
			),
			array(
				'name'       => 'email',
				'label'      => esc_html__( 'Email', 'gravityformsconnectwise' ),
				'required'   => true,
				'field_type' => array(
					'email',
					'text',
					'hidden'
				),
			)
		);
	}

	public function custom_fields_mapping() {
		return array(
			array(
				'label' => esc_html__( 'Choose a Field', 'gravityformsconnectwise' ),
				'choices' => array(
					array(
						'label' => esc_html__( 'Company', 'gravityformsconnectwise' ),
						'value' => 'company'
					),
					array(
						'label' => esc_html__( 'Address 1', 'gravityformsconnectwise' ),
						'value' => 'address_1'
					),
					array(
						'label' => esc_html__( 'Address 2', 'gravityformsconnectwise' ),
						'value' => 'address_2'
					),
					array(
						'label' => esc_html__( 'City', 'gravityformsconnectwise' ),
						'value' => 'city'
					),
					array(
						'label' => esc_html__( 'State', 'gravityformsconnectwise' ),
						'value' => 'state'
					),
					array(
						'label' => esc_html__( 'Zip', 'gravityformsconnectwise' ),
						'value' => 'zip'
					),
					array(
						'label' => esc_html__( 'Phone', 'gravityformsconnectwise' ),
						'value' => 'phone_number'
					),
					array(
						'label' => esc_html__( 'Fax', 'gravityformsconnectwise' ),
						'value' => 'fax_number'
					),
					array(
						'label' => esc_html__( 'Web site', 'gravityformsconnectwise' ),
						'value' => 'web_site'
					),
				),
			  ),
		);
	}

	public function plugin_settings_fields() {
		return array(
			array(
				'description' => $this->plugin_settings_description(),
				'fields'      => array(
					array(
						'name'              => 'connectwise_url',
						'label'             => 'ConnectWise URL',
						'type'              => 'text',
						'class'             => 'medium',
						'save_callback'     => array( $this, 'clean_field' ),
						'feedback_callback' => array( $this, 'is_valid_settings' ),
						'tooltip'           => '<h6>' . esc_html__('ConnectWise URL') . '</h6>' . esc_html__( "The URL you use to login to ConnectWise. You don't need to include https:// or anything after .com/.net. For example, just enter \"cw.yourcompany.com\". If you use a hosted version, you can use that URL (na.myconnectwise.net).", 'gravityformsconnectwise' )
					),
					array(
						'name'              => 'company_id',
						'label'             => 'Company ID',
						'type'              => 'text',
						'class'             => 'small',
						'save_callback'     => array( $this, 'clean_field' ),
						'feedback_callback' => array( $this, 'is_valid_settings' )
					),
					array(
						'name'              => 'public_key',
						'label'             => 'Public API Key',
						'type'              => 'text',
						'class'             => 'small',
						'save_callback'     => array( $this, 'clean_field' ),
						'feedback_callback' => array( $this, 'is_valid_settings' )
					),
					array(
						'name'              => 'private_key',
						'label'             => 'Private API Key',
						'type'              => 'text',
						'class'             => 'small',
						'save_callback'     => array( $this, 'clean_field' ),
						'feedback_callback' => array( $this, 'is_valid_settings' )
					),
					array(
						'name'              => 'client_id',
						'label'             => 'Client ID',
						'type'              => 'text',
						'class'             => 'small',
						'save_callback'     => array( $this, 'clean_field' ),
						'feedback_callback' => array( $this, 'is_valid_settings' )
					)
				)
			),
			array(
				'title'       => 'Error Notifications',
				'fields'      => array(
					array(
						'name'              => 'error_notification_emails_to',
						'label'             => 'Email Address',
						'type'              => 'text',
						'class'             => 'small',
						'save_callback'     => array( $this, 'clean_field' ),
						'feedback_callback' => array( $this, 'is_valid_email_settings' )
					),
					array(
						'name'              => 'error_notification_emails_action',
						'label'             => '',
						'type'              => 'checkbox',
						'class'             => 'small',
						'choices'  => array(
							array(
								'name'     => 'enable_error_notification_emails',
								'label'     => esc_html__( 'Enable error notification emails', 'gravityformsconnectwise' ),
							)
						)
					)
				)
			)
		);
	}

	public function plugin_settings_description() {
		$description  = '<p>';
		$description .= sprintf(
			'Complete the settings below to authenticate with your ConnectWise account. %1$sHere\'s how to generate API keys.%2$s',
			'<a href="https://pronto.zendesk.com/hc/en-us/articles/207946586" target="_blank">', '</a>'
		);
		$description .= '</p>';

		return $description;
	}

	public function clean_field( $field, $field_setting ) {
		$field_setting = preg_replace( '/\s+/', '', $field_setting );

		return sanitize_text_field( $field_setting );
	}

	public function is_valid_settings() {
		$valid = False;

		$url = 'system/info';
		$connection = $this->send_request( $url, 'GET', NULL, $error_notification = false );

		if ( ! is_wp_error( $connection ) and 200 == $connection['response']['code'] ) {
			$valid = True;
		} else {
			$this->log_debug( __METHOD__ . '(): response[body] => ' . print_r( $connection, true ) );
		}

		return $valid;
	}

	public function is_valid_email_settings( $value ) {
		if ( !empty( $value ) ) {
			if ( is_email( $value ) ) {
				return true;
			} else {
				return false;
			}
		}
	}

	public function send_error_notification( $response_body, $response_code, $url, $body ) {
		$to = $this->get_plugin_setting( 'error_notification_emails_to' );

		if ( empty( $to ) ) {
			$to = get_option( 'admin_email' );
		}

		$subject = '[Gravity Forms Connectwise] ERROR';

		$headers = 'From: ' . get_bloginfo( $show = 'name' );
		$headers .= ' <noreply@' . $_SERVER['SERVER_NAME'] . '>';

		$message = 'URL => ' . $url . '\n';
		$message .= 'data => ' . json_encode( $body, JSON_PRETTY_PRINT ) . '\n';
		$message .= 'Response[code] => ' . $response_code . '\n';
		$message .= 'Response[body] => ' . $response_body . '\n';
		$message .= '---------\n';
		$message .= 'Configure error notifications: ' . $this->get_plugin_settings_url() . '\n\n';
		$message .= 'Install the Gravity Forms Logging Add-on to view the complete error log: ';
		$message .= 'https://www.gravityhelp.com/documentation/article/logging-add-on/';

		$status =  wp_mail( $to, $subject, $message , $headers );

		return $status;
	}

	public function send_request( $url, $request_method, $body, $error_notification = true ) {
		if ( 'system/info' != $url ) {
			$this->log_debug( '## ' . __METHOD__ . '(): start sending request ##' );
		}

		$url =  $this->connectwise_version->transform_url( $url );

		if ( false == strpos( $url, 'system/info' ) ) {
			$this->log_debug( __METHOD__ . '(): url => ' . print_r( $url, true ) );
			$this->log_debug( __METHOD__ . '(): request => ' . print_r( $request_method, true ) );
			$this->log_debug( __METHOD__ . '(): body => ' . json_encode( $body, JSON_PRETTY_PRINT ) );
		}

		$company_id        = $this->get_plugin_setting( 'company_id' );
		$public_key        = $this->get_plugin_setting( 'public_key' );
		$private_key       = $this->get_plugin_setting( 'private_key' );
		$client_id         = $this->get_plugin_setting( 'client_id' );
		$enable_error_mail = $this->get_plugin_setting( 'enable_error_notification_emails' );

		$args = array(
			'timeout' => 120,
			'method'  => $request_method,
			'body'    => $body,
			'headers' => array(
				'Accept'           => 'application/vnd.connectwise.com+json;',
				'Content-type'     => 'application/json' ,
				'Authorization'    => 'Basic ' . base64_encode( $company_id . '+' . $public_key  . ':' . $private_key ),
				'X-cw-overridessl' => 'True',
                'clientId'         => $client_id
			)
		);
		if ( $body ) {
			$args['body'] = json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( false == strpos( $url, 'system/info' ) ) {
			if ( true == is_array( $response ) ) {
				if ( false == strpos( $url, 'contacts?conditions=firstname' ) ) {
					$this->log_debug( __METHOD__ . '(): response[body] => ' . print_r( $response['body'], true ) );
				}

				$this->log_debug( __METHOD__ . '(): response[response][code] => ' . print_r( $response['response']['code'], true ) );
			} else {
				$this->log_debug( __METHOD__ . '(): response => ' . print_r( $response, true ) );
			}
			$this->log_debug( '## ' . __METHOD__ . '(): finish sending request ##' );
		}

		if ( true == $error_notification && '1' == $enable_error_mail ) {
			$not_error_msg = 'Company ID already in use.';

			if ( 400 <= $response['response']['code'] && strpos($response['body'], $not_error_msg) == false ) {
				$this->send_error_notification( $response['body'], $response['response']['code'], $url, $body );
			}
		}

		return $response;
	}

}
