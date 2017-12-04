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

        $strsql = "SELECT ciniki_event_registrations.id, "
            . "ciniki_event_registrations.customer_id, "
            . "IFNULL(ciniki_customers.display_name, '') AS customer_name, "
            . "ciniki_event_registrations.status, "
            . "ciniki_event_registrations.status AS status_text, "
            . "ciniki_event_registrations.num_tickets, "
            . "ciniki_event_registrations.invoice_id, "
            . "ciniki_event_registrations.customer_notes, "
            . "ciniki_sapos_invoices.payment_status AS invoice_status, "
            . "IFNULL(ciniki_sapos_invoices.payment_status, 0) AS invoice_status_text "
            . "FROM ciniki_event_registrations "
            . "LEFT JOIN ciniki_customers ON (ciniki_event_registrations.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_sapos_invoices ON (ciniki_event_registrations.invoice_id = ciniki_sapos_invoices.id "
                . "AND ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_event_registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_event_registrations.event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
            . "ORDER BY ciniki_customers.last, ciniki_customers.first "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
            array('container'=>'registrations', 'fname'=>'id', 'name'=>'registration',
                'fields'=>array('id', 'customer_id', 'customer_name', 'status', 'status_text', 'num_tickets', 
                    'invoice_id', 'invoice_status', 'invoice_status_text', 'customer_notes'),
                'maps'=>array('invoice_status_text'=>$status_maps, 'status_text'=>$maps['registration']['status'])),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.40', 'msg'=>'Unable to get the list of registrations', 'err'=>$rc['err']));
        }
        if( !isset($rc['registrations']) ) {
            return array('stat'=>'ok', 'registrations'=>array());
        }
        $registrations = $rc['registrations'];

    } else {
        $strsql = "SELECT ciniki_event_registrations.id, "
            . "ciniki_event_registrations.customer_id, "
            . "IFNULL(ciniki_customers.display_name, '') AS customer_name, "
            . "ciniki_event_registrations.status, "
            . "ciniki_event_registrations.status AS status_text, "
            . "ciniki_event_registrations.num_tickets, "
            . "ciniki_event_registrations.invoice_id, "
            . "ciniki_event_registrations.customer_notes "
            . "FROM ciniki_event_registrations "
            . "LEFT JOIN ciniki_customers ON (ciniki_event_registrations.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_event_registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_event_registrations.event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
            . "ORDER BY ciniki_customers.last, ciniki_customers.first "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
            array('container'=>'registrations', 'fname'=>'id', 'name'=>'registration',
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
    // FIXME: check for status of invoices
    //
    // if( isset($modules['ciniki.pos']) ) {
    // }

    return array('stat'=>'ok', 'registrations'=>$registrations);
}
?>
