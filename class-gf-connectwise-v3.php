<?php

require_once WP_PLUGIN_DIR . "/gravityformsconnectwise/class-gf-connectwise.php";
GFForms::include_feed_addon_framework();

class GFConnectWiseV3 extends GFConnectWise {
    private static $_instance = null;

    public static function get_instance() {
       if ( self::$_instance == null ) {
           self::$_instance = new self;
       }

       return self::$_instance;
    }

    public function process_feed( $feed, $lead, $form ) {
        $this->log_debug( "# " . __METHOD__ . "(): start sending data to ConnectWise #" );
        $first_name    = $feed["meta"]["contact_map_fields_first_name"];
        $last_name     = $feed["meta"]["contact_map_fields_last_name"];
        $email         = $feed["meta"]["contact_map_fields_email"];
        $department    = $feed["meta"]["contact_department"];
        $contact_type  = $feed["meta"]["contact_type"];
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
        foreach ( $feed["meta"]["company_map_fields"] as $custom_map ) {
            if ( "company" == $custom_map["key"] ) {
                $company = $custom_map["value"];
            } elseif ( "address_1" == $custom_map["key"] ) {
                $address_line1 = $custom_map["value"];
            } elseif ( "address_2" == $custom_map["key"] ) {
                $address_line2 = $custom_map["value"];
            } elseif ( "city" == $custom_map["key"] ) {
                $city = $custom_map["value"];
            } elseif ( "state" == $custom_map["key"] ) {
                $state = $custom_map["value"];
            } elseif ( "zip" == $custom_map["key"] ) {
                $zip = $custom_map["value"];
            } elseif ( "phone_number" == $custom_map["key"] ) {
                $phone_number = $custom_map["value"];
            } elseif ( "fax_number" == $custom_map["key"] ) {
                $fax_number = $custom_map["value"];
            } elseif ( "web_site" == $custom_map["key"] ) {
                $web_site = $custom_map["value"];
            }
        }
        if ( NULL == $company or "" == $lead[ $company ] ) {
            $identifier = "Catchall";
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
            if ( NULL == $address_line1 or "" == $address_line1 ) {
                $address_line1 = "-";
            }
            if ( NULL == $address_line2 or "" == $address_line2 ) {
                $address_line2 = "-";
            }
            if ( NULL == $city or "" == $city ) {
                $city = "-";
            }
            if ( NULL == $state or "" == $state ) {
                $state = "-";
            }
            if ( NULL == $zip or "" == $zip ) {
                $zip = "-";
            }
            $identifier = preg_replace( "/[^\w]/", "", $company );
            $identifier = substr( $identifier, 0, 25 );
            $company_type   = $feed["meta"]["company_type"];
            $company_status = $feed["meta"]["company_status"];
            $company_data = array(
                "id"           => 0,
                "identifier"   => $identifier,
                "name"         => $company,
                "addressLine1" => $address_line1,
                "addressLine2" => $address_line2,
                "city"         => $city,
                "state"        => $state,
                "zip"          => $zip,
                "phoneNumber"  => $phone_number,
                "faxNumber"    => $fax_number,
                "website"      => $web_site,
                "type"         => array(
                    "id" => $company_type,
                ),
                "status"       => array(
                    "id" => $company_status,
                )
            );
            if ( "1" == $feed["meta"]["company_as_lead"] ) {
                $company_data["leadFlag"] = true;
            }
            if ( "" != $feed["meta"]["company_note"] ) {
                $note                 = GFCommon::replace_variables( $feed["meta"]["company_note"], $form, $lead, false, false, false, "html" );
                $company_data["note"] = $note;
            }
            $url = "company/companies";
            $response = $this->send_request( $url, "POST", $company_data );
        }
        $contact_data = $this->get_existing_contact( $lead[ $first_name ], $lead[ $email ] );
        if ( !$contact_data ) {
            $contact_data = array(
                "firstName" => $lead[ $first_name ],
                "lastName"  => $lead[ $last_name ],
                "company"   => array(
                    "identifier" => $identifier,
                ),
                "type" => array(
                    "id" => $contact_type
                )
            );
            if ( "---------------" != $department ) {
                $contact_data["department"] = array(
                    "id" => $department
                );
            }
            if ( "" != $feed["meta"]["contact_note"] ) {
                $note                 = GFCommon::replace_variables( $feed["meta"]["contact_note"], $form, $lead, false, false, false, "html" );
                $contact_data["note"] = $note;
            }
            $url          = "company/contacts";
            $response     = $this->send_request( $url, "POST", $contact_data );
            $contact_data = json_decode( $response["body"] );
            $comunication_types = array(
                "value"             => $lead[ $email ],
                "communicationType" => "Email",
                "type"              => array(
                    "id"   => 1,
                    "name" => "Email"
                ),
                "defaultFlag" => true,
            );
            $contact_id = $contact_data->id;
            $url        = "company/contacts/{$contact_id}/communications";
            $response   = $this->send_request( $url, "POST", $comunication_types );
        }
        $get_company_url = "company/companies?conditions=identifier='{$identifier}'";
        $response        = $this->send_request( $get_company_url, "GET", NULL );
        $company_data    = json_decode( $response["body"]);
        $company_id      = $company_data[0]->id;
        if ( "Catchall" != $identifier ){
            $company_url = "company/companies/{$company_id}";
            $company_update_data = array(
                array(
                    "op"    => "replace",
                    "path"  => "defaultContact",
                    "value" => $contact_data
                )
            );
            $response     = $this->send_request( $company_url, "PATCH", $company_update_data, $error_notification = false );
        }
        if ( "1" == $feed["meta"]["create_opportunity"] ) {
            $get_company_site_url  = "company/companies/{$company_id}/sites/";
            $company_site_data     = $this->send_request( $get_company_site_url, "GET", NULL );
            $company_site_data     = json_decode( $company_site_data["body"]);
            $company_site_id       = $company_site_data[0]->id;
            $company_site_name     = $company_site_data[0]->name;
            $opportunity_type      = $feed["meta"]["opportunity_type"];
            $opportunity_closedate = $feed["meta"]["opportunity_closedate"];
            if ( $opportunity_closedate == "" ) {
                $opportunity_closedate = 30;
            }
            $expectedCloseDate     = mktime( 0, 0, 0, date( "m" ), date( "d" ) + $opportunity_closedate, date( "y" ) );
            $expectedCloseDate     = date( "Y-m-d", $expectedCloseDate );
            $expectedCloseDate     = $expectedCloseDate . "T00:00:00Z";
            $opportunity_data = array(
                "name"    => GFCommon::replace_variables( $feed["meta"]["opportunity_name"], $form, $lead, false, false, false, "html" ),
                "company" => array(
                    "identifier" => $identifier
                ),
                "contact" => array(
                    "id"   => $contact_data->id,
                    "name" => sprintf( esc_html__( "%s %s" ), $contact_data->firstName, $contact_data->lastName )
                ),
                "site" => array(
                    "id"   => $company_site_id,
                    "name" => $company_site_name
                ),
                "primarySalesRep" => array(
                    "identifier"  => $feed["meta"]["opportunity_owner"],
                ),
                "expectedCloseDate" => $expectedCloseDate
            );
            if ( "---------------" != $opportunity_type ) {
                $opportunity_data["type"] = array(
                    "id" => $opportunity_type
                );
            }
            if ( "---------------" != $feed["meta"]["marketing_campaign"] ) {
                $opportunity_data["campaign"] = array(
                    "id" => $feed["meta"]["marketing_campaign"],
                );
            }
            if ( "" != $feed["meta"]["opportunity_source"] ) {
                $opportunity_data["source"] = $feed["meta"]["opportunity_source"];
            }
            if ( "" != $feed["meta"]["opportunity_note"] ) {
                $note  = GFCommon::replace_variables( $feed["meta"]["opportunity_note"], $form, $lead, false, false, false, "html" );
                $opportunity_data["notes"] = $note;
            }
            $url = "sales/opportunities";
            $response = $this->send_request( $url, "POST", $opportunity_data );
            $opportunity_response = json_decode( $response["body"] );
        }
        if ( "1" == $feed["meta"]["create_activity"] and "1" == $feed["meta"]["create_opportunity"] ) {
            $activity_name      = GFCommon::replace_variables( $feed["meta"]["activity_name"], $form, $lead, false, false, false, "html" );
            $assign_activity_to = $feed["meta"]["activity_assigned_to"];
            $input_duedate = $feed["meta"]["activity_duedate"];
            if ( $input_duedate == "" ) {
                $input_duedate = 7;
            }
            $dueDate       = mktime( 0, 0, 0, date( "m" ), date( "d" ) + $input_duedate, date( "y" ) );
            $dueDate       = date( "Y-m-d", $dueDate );
            $dateStart = $dueDate . "T14:00:00Z";
            $dateEnd   = $dueDate . "T14:15:00Z";
            $activity_data = array(
                "name"  => $activity_name,
                "email" => $lead[ $email ],
                "type"  => array(
                    "id"   => $feed["meta"]["activity_type"]
                ),
                "company" => array(
                    "identifier" => $identifier
                ),
                "contact" => array(
                    "id"   => $contact_data->id,
                    "name" => sprintf(esc_html__("%s %s"), $contact_data->firstName, $contact_data->lastName)
                ),
                "status" => array(
                    "name" => "Open"
                ),
                "assignTo" => array(
                    "identifier" => $assign_activity_to
                ),
                "opportunity" => array(
                    "id"   => $opportunity_response->id,
                    "name" => $opportunity_response->name
                ),
                "dateStart" => $dateStart,
                "dateEnd"   => $dateEnd
             );
            if ( "" != $feed["meta"]["activity_note"] ) {
                $note  = GFCommon::replace_variables( $feed["meta"]["activity_note"], $form, $lead, false, false, false, "html" );
                $activity_data["notes"] = $note;
            }
            $url = "sales/activities";
            $response = $this->send_request( $url, "POST", $activity_data );
        }
        if ( "1" == $feed["meta"]["create_service_ticket"] ) {
            $url = "service/tickets";
            $ticket_data = array(
                "summary"            => GFCommon::replace_variables( $feed["meta"]["service_ticket_summary"], $form, $lead, false, false, false, "html" ),
                "initialDescription" => GFCommon::replace_variables( $feed["meta"]["service_ticket_initial_description"], $form, $lead, false, false, false, "html" ),
                "company"            => array(
                    "identifier" => $identifier,
                )
            );
            $ticket_board = $feed["meta"]["service_ticket_board"];
            if ( "" !=  $ticket_board ) {
                $ticket_data["board"] = array(
                    "id" => $ticket_board
                );
            }
            $ticket_priority = $feed["meta"]["service_ticket_priority"];
            if ( "" !=  $ticket_priority ) {
                $ticket_data["priority"] = array(
                    "id" => $ticket_priority
                );
            }
            $response = $this->send_request( $url, "POST", $ticket_data);
        }
        $this->log_debug( "# " . __METHOD__ . "(): finish sending data to ConnectWise #" );
        return $lead;
    }
}
