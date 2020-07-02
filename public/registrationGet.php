<?php
//
// Description
// ===========
// This method will return all the information about an event registration.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the event is attached to.
// registration_id:     The ID of the registration to get the details for.
// 
// Returns
// -------
//
function ciniki_events_registrationGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'), 
        'customer'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
        'invoice'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice'),
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
    $rc = ciniki_events_checkAccess($ciniki, $args['tnid'], 'ciniki.events.registrationGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    $strsql = "SELECT ciniki_event_registrations.id, "
        . "ciniki_event_registrations.event_id, "
        . "ciniki_event_registrations.price_id, "
        . "ciniki_event_registrations.customer_id, "
        . "ciniki_event_registrations.invoice_id, "
        . "ciniki_event_registrations.status, "
        . "ciniki_event_registrations.num_tickets, "
        . "ciniki_event_registrations.customer_notes, "
        . "ciniki_event_registrations.notes "
        . "FROM ciniki_event_registrations "
        . "WHERE ciniki_event_registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_event_registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'registrations', 'fname'=>'id', 'name'=>'registration',
            'fields'=>array('id', 'event_id', 'price_id', 'customer_id', 'invoice_id', 'status', 'num_tickets', 'customer_notes', 'notes')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['registrations']) || !isset($rc['registrations'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.39', 'msg'=>'Unable to find registration'));
    }
    $registration = $rc['registrations'][0]['registration'];

    //
    // If include customer information
    //
    if( isset($args['customer']) && $args['customer'] == 'yes' && $registration['customer_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerDetails');
        $rc = ciniki_customers__customerDetails($ciniki, $args['tnid'], $registration['customer_id'], 
            array('phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes', 'subscriptions'=>'no'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $registration['customer_details'] = $rc['details'];
    }

    //
    // Get the available prices for this event
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x01) ) {
        //
        // Get the price list for the event
        //
        $strsql = "SELECT prices.id, "
            . "IFNULL(events.name, '') AS event_name, "
            . "prices.name, "
            . "prices.available_to, "
            . "prices.available_to AS available_to_text, "
            . "prices.unit_amount, "
            . "prices.webflags, "
            . "prices.num_tickets "
            . "FROM ciniki_event_prices AS prices "
            . "LEFT JOIN ciniki_events AS events ON ("
                . "prices.event_id = events.id "
                . "AND events.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE prices.event_id = '" . ciniki_core_dbQuote($ciniki, $registration['event_id']) . "' "
            . "AND (prices.webflags&0x08) = 0 "   // Skip mapped ticket prices
            . "ORDER BY prices.name COLLATE latin1_general_cs "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.events', array(
            array('container'=>'prices', 'fname'=>'id',
                'fields'=>array('id', 'event_name', 'name', 'available_to', 'available_to_text', 'unit_amount', 
                    'webflags', 'num_tickets'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['prices']) ) {
            $registration['prices'] = $rc['prices'];
            foreach($registration['prices'] as $pid => $price) {
                $registration['prices'][$pid]['unit_amount_display'] = '$' . number_format($price['unit_amount'], 2);
            }
        } else {
            $registration['prices'] = array();
        }
    }

    //
    // Add invoice information
    //
    if( isset($args['invoice']) && $args['invoice'] == 'yes' && $registration['invoice_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
        $rc = ciniki_sapos_invoiceLoad($ciniki, $args['tnid'], $registration['invoice_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $registration['invoice'] = $rc['invoice'];
    }

    return array('stat'=>'ok', 'registration'=>$registration);
}
?>
