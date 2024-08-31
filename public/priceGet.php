<?php
//
// Description
// ===========
// This method will return all the information about an event price.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the event is attached to.
// price_id:        The ID of the price to get the details for.
// 
// Returns
// -------
//
function ciniki_events_priceGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'event_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Event'), 
        'price_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'), 
        'customer'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
        'invoice'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice'),
        'ticketmap'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Ticket Map Number'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
    $rc = ciniki_events_checkAccess($ciniki, $args['tnid'], 'ciniki.events.priceGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Check if event is specified and check for ticketmap
    //
    if( isset($args['event_id']) ) {
        $strsql = "SELECT ciniki_events.id, "
            . "ciniki_events.name, "
            . "ciniki_events.permalink, "
            . "ciniki_events.flags, "
            . "ciniki_events.url, "
            . "ciniki_events.description, "
            . "ciniki_events.num_tickets, "
            . "ciniki_events.reg_flags, "
            . "DATE_FORMAT(ciniki_events.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
            . "DATE_FORMAT(ciniki_events.end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date, "
            . "ciniki_events.times, "
            . "ciniki_events.primary_image_id, "
            . "ciniki_events.long_description, "
            . "CONCAT_WS(':', ciniki_events.object, ciniki_events.object_id) AS oidref, "
            . "ciniki_events.object, "
            . "ciniki_events.object_id, "
            . "ciniki_events.ticketmap1_image_id, "
            . "ciniki_events.ticketmap1_ptext, "
            . "ciniki_events.ticketmap1_btext "
            . "FROM ciniki_events "
            . "WHERE ciniki_events.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_events.id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'event');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.78', 'msg'=>'Unable to load event', 'err'=>$rc['err']));
        }
        if( !isset($rc['event']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.82', 'msg'=>'Unable to find requested event'));
        }
        $event = $rc['event'];
    }

    if( $args['price_id'] == 0 ) {
        $price = array(
            'id' => 0,
            'event_id' => $args['event_id'],
            'name' => '',
            'available_to' => 0,
            'valid_from' => '',
            'valid_to' => '',
            'unit_amount' => '',
            'unit_discount_amount' => '',
            'unit_discount_percentage' => '',
            'unit_donation_amount' => '',
            'taxtype_id' => '',
            'webflags' => 0,
            'num_tickets' => 0,
            'position_num' => 1,
            'position_x' => '',
            'position_y' => '',
            'diameter' => 15,
            'ticket_format' => 'default',
            'ticket_image_id' => '0',
            'ticket_event_name' => '',
            'ticket_timedate' => '',
            'ticket_location' => '',
            'ticket_notes' => '',
            );
        //
        // Get the last ticket price
        //
        if( isset($args['ticketmap']) && $args['ticketmap'] > 0 ) {
            $strsql = "SELECT name, available_to, "
                . "unit_amount, unit_discount_amount, unit_discount_percentage, unit_donation_amount, "
                . "taxtype_id, webflags, position_num, position_x, position_y, diameter, "
                . "ticket_format, ticket_image_id, ticket_event_name, "
                . "ticket_timedate, ticket_location, ticket_notes "
                . "FROM ciniki_event_prices "
                . "WHERE event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND (webflags&0x08) = 0x08 "
                . "ORDER BY last_updated "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'lastprice');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.80', 'msg'=>'Unable to load last price', 'err'=>$rc['err']));
            }
            $last_num = 0;
            if( isset($rc['rows']) ) {
                foreach($rc['rows'] as $row) {
                    if( preg_match("/([^0-9])([0-9]+)([^0-9]|$)/", $row['name'], $m) ) {
                        if( $m[2] > $last_num ) {
                            $price['name'] = preg_replace("/[^0-9]([0-9]+)([^0-9]|$)/", $m[1] . ($m[2]+1) . $m[3], $row['name']);
                            $price['position_num'] = $row['position_num'] + 1;
                            $rc['lastprice'] = $row;
                            $last_num = $m[2];
                        }
                    }
                }
            }
            if( isset($rc['lastprice']) ) {
                $price['diameter'] = $rc['lastprice']['diameter'];
                $price['available_to'] = $rc['lastprice']['available_to'];
                $price['unit_amount'] = '$' . number_format($rc['lastprice']['unit_amount'], 2);
                if( $rc['lastprice']['unit_discount_amount'] > 0 ) {
                    $price['unit_discount_amount'] = '$' . number_format($rc['lastprice']['unit_discount_amount'], 2);
                }
                if( $rc['lastprice']['unit_discount_percentage'] > 0 ) {
                    $price['unit_discount_percentage'] = number_format($rc['lastprice']['unit_discount_percentage'], 2) . '%';
                }
                if( $rc['lastprice']['unit_donation_amount'] > 0 ) {
                    $price['unit_donation_amount'] = '$' . number_format($rc['lastprice']['unit_donation_amount'], 2);
                }
                $price['taxtype_id'] = $rc['lastprice']['taxtype_id'];
                $price['webflags'] = $rc['lastprice']['webflags'];
            } else {
                $price['webflags'] = 0x08;
            }
        }
    } else {
        $strsql = "SELECT ciniki_event_prices.id, "
            . "ciniki_event_prices.event_id, "
            . "ciniki_event_prices.name, "
            . "ciniki_event_prices.available_to, "
            . "ciniki_event_prices.valid_from, "
            . "ciniki_event_prices.valid_to, "
            . "ciniki_event_prices.unit_amount, "
            . "ciniki_event_prices.unit_discount_amount, "
            . "ciniki_event_prices.unit_discount_percentage, "
            . "ciniki_event_prices.unit_donation_amount, "
            . "ciniki_event_prices.taxtype_id, "
            . "ciniki_event_prices.webflags, "
            . "ciniki_event_prices.num_tickets, "
            . "ciniki_event_prices.position_num, "
            . "ciniki_event_prices.position_x, "
            . "ciniki_event_prices.position_y, "
            . "ciniki_event_prices.diameter, "
            . "ciniki_event_prices.ticket_format, "
            . "ciniki_event_prices.ticket_image_id, "
            . "ciniki_event_prices.ticket_event_name, "
            . "ciniki_event_prices.ticket_timedate, "
            . "ciniki_event_prices.ticket_location, "
            . "ciniki_event_prices.ticket_notes "
            . "FROM ciniki_event_prices "
            . "WHERE ciniki_event_prices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_event_prices.id = '" . ciniki_core_dbQuote($ciniki, $args['price_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
            array('container'=>'prices', 'fname'=>'id', 'name'=>'price',
                'fields'=>array('id', 'event_id', 'name', 'available_to', 'valid_from', 'valid_to', 
                    'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 'unit_donation_amount',
                    'taxtype_id', 'webflags', 'num_tickets', 'position_num', 'position_x', 'position_y', 'diameter',
                    'ticket_format', 'ticket_image_id', 'ticket_event_name', 
                    'ticket_timedate', 'ticket_location', 'ticket_notes',
                    ),
                'utctotz'=>array('valid_from'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                    'valid_to'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['prices']) || !isset($rc['prices'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.36', 'msg'=>'Unable to find price'));
        }
        $price = $rc['prices'][0]['price'];

        $price['unit_discount_percentage'] = (float)$price['unit_discount_percentage'];
        $price['unit_amount'] = numfmt_format_currency($intl_currency_fmt,
            $price['unit_amount'], $intl_currency);
        $price['unit_discount_amount'] = numfmt_format_currency($intl_currency_fmt,
            $price['unit_discount_amount'], $intl_currency);
        $price['unit_donation_amount'] = numfmt_format_currency($intl_currency_fmt,
            $price['unit_donation_amount'], $intl_currency);
    }

    if( isset($event['ticketmap1_image_id']) ) {
        $price['ticketmap1_image_id'] = $event['ticketmap1_image_id'];
    }

    $rsp = array('stat'=>'ok', 'price'=>$price);

    //
    // Load tickets for this event to draw on image
    //
    if( isset($args['ticketmap']) && $args['ticketmap'] > 0 ) {
        $strsql = "SELECT id, name, "
            . "webflags, "
            . "position_num, position_x, position_y, diameter, "
            . "ticket_format, ticket_image_id, ticket_event_name, "
            . "ticket_timedate, ticket_location, ticket_notes "
            . "FROM ciniki_event_prices "
            . "WHERE event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
            . "AND (webflags&0x08) = 0x08 "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.events', array(
            array('container'=>'tickets', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'webflags', 'position_num', 'position_x', 'position_y', 'diameter',
                    'ticket_format', 'ticket_image_id', 'ticket_event_name', 
                    'ticket_timedate', 'ticket_location', 'ticket_notes',
                    )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.81', 'msg'=>'Unable to load tickets', 'err'=>$rc['err']));
        }
        $rsp['tickets'] = isset($rc['tickets']) ? $rc['tickets'] : array();
    }

    return $rsp;
}
?>
