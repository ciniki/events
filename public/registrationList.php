<?php
//
// Description
// -----------
// This method will return the list of customers who have registered for an event.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_events_registrationList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'event_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Event'), 
        'price_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Price'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
    $rc = ciniki_events_checkAccess($ciniki, $args['tnid'], 'ciniki.events.registrationList');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'maps');
    $rc = ciniki_events_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Build the query string to get the list of registrations
    //
    if( isset($modules['ciniki.sapos']) ) {
        //
        // Load the status maps for the text description of each status
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
        $rc = ciniki_sapos_maps($ciniki);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $status_maps = $rc['maps']['invoice']['payment_status'];
        $status_maps[0] = 'No Invoice';

        $strsql = "SELECT registrations.id, "
            . "registrations.customer_id, "
            . "IFNULL(ciniki_customers.display_name, '') AS customer_name, "
            . "registrations.status, "
            . "registrations.status AS status_text, "
            . "registrations.num_tickets, "
            . "registrations.invoice_id, "
            . "registrations.customer_notes, "
            . "ciniki_sapos_invoices.payment_status AS invoice_status, "
            . "IFNULL(ciniki_sapos_invoices.payment_status, 0) AS invoice_status_text "
            . "FROM ciniki_event_registrations AS registrations "
            . "LEFT JOIN ciniki_customers ON ("
                . "registrations.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_sapos_invoices ON ("
                . "registrations.invoice_id = ciniki_sapos_invoices.id "
                . "AND ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND registrations.event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' ";
        if( isset($args['price_id']) && $args['price_id'] > 0 ) {
            $strsql .= "AND registrations.price_id = '" . ciniki_core_dbQuote($ciniki, $args['price_id']) . "' ";
        }
        $strsql .= "ORDER BY ciniki_customers.last, ciniki_customers.first "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.events', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'customer_id', 'customer_name', 'status', 'status_text', 'num_tickets', 
                    'invoice_id', 'invoice_status', 'invoice_status_text', 'customer_notes'),
                'maps'=>array('invoice_status_text'=>$status_maps, 
                    'status_text'=>$maps['registration']['status'],
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.40', 'msg'=>'Unable to get the list of registrations', 'err'=>$rc['err']));
        }
        if( !isset($rc['registrations']) ) {
            return array('stat'=>'ok', 'registrations'=>array());
        }
        $registrations = $rc['registrations'];

    } else {
        $strsql = "SELECT registrations.id, "
            . "registrations.customer_id, "
            . "IFNULL(ciniki_customers.display_name, '') AS customer_name, "
            . "registrations.status, "
            . "registrations.status AS status_text, "
            . "registrations.num_tickets, "
            . "registrations.invoice_id, "
            . "registrations.customer_notes "
            . "FROM ciniki_event_registrations AS registrations "
            . "LEFT JOIN ciniki_customers ON ("
                . "registrations.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND registrations.event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' ";
        if( isset($args['price_id']) && $args['price_id'] > 0 ) {
            $strsql .= "AND registrations.price_id = '" . ciniki_core_dbQuote($ciniki, $args['price_id']) . "' ";
        }
        $strsql .= "ORDER BY ciniki_customers.last, ciniki_customers.first "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.events', array(
            array('container'=>'registrations', 'fname'=>'id',
                'fields'=>array('id', 'customer_id', 'customer_name', 'status', 'status_text', 'num_tickets', 'invoice_id', 'customer_notes'),
                'maps'=>array('status_text'=>$maps['registration']['status'])),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.41', 'msg'=>'Unable to get the list of registrations', 'err'=>$rc['err']));
        }
        if( !isset($rc['registrations']) ) {
            return array('stat'=>'ok', 'registrations'=>array());
        }
        $registrations = $rc['registrations'];
    }

    //
    // Get the list of prices and number of registrations for each
    //
    $strsql = "SELECT prices.id, "
        . "prices.name AS label, "
        . "SUM(registrations.num_tickets) AS num_tickets "
        . "FROM ciniki_event_prices AS prices "
        . "LEFT JOIN ciniki_event_registrations AS registrations ON ("
            . "prices.id = registrations.price_id "
            . "AND prices.event_id = registrations.event_id "
            . ") "
        . "WHERE prices.event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
        . "AND prices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "GROUP BY prices.id "
        . "ORDER BY prices.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'prices', 'fname'=>'id', 'fields'=>array('id', 'label', 'num_tickets')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.115', 'msg'=>'Unable to load prices', 'err'=>$rc['err']));
    }
    $prices = isset($rc['prices']) ? $rc['prices'] : array();

    //
    // Get total of all tickets
    //
    $strsql = "SELECT SUM(num_tickets) AS num_tickets "
        . "FROM ciniki_event_registrations "
        . "WHERE event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'num');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.116', 'msg'=>'Unable to load num', 'err'=>$rc['err']));
    }
    $total_tickets = isset($rc['num']['num_tickets']) ? $rc['num']['num_tickets'] : 0;

    array_unshift($prices, array(
        'id' => 0,
        'label' => 'All Tickets',
        'num_tickets' => $total_tickets,
        ));

    //
    // FIXME: check for status of invoices
    //
    // if( isset($modules['ciniki.pos']) ) {
    // }

    return array('stat'=>'ok', 'registrations'=>$registrations, 'prices'=>$prices);
}
?>
