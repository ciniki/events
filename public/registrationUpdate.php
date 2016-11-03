<?php
//
// Description
// ===========
// This method will update an event registration in the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business the event is attached to.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_events_registrationUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'), 
        'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Customer'),
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'),
        'num_tickets'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Number of Tickets'),
        'customer_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer Notes'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.registrationUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Get the existing details for the registration
    //
    $strsql = "SELECT id, num_tickets, invoice_id "
        . "FROM ciniki_event_registrations "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'registration');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.42', 'msg'=>'Registration does not exist'));
    }
    $registration = $rc['registration'];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.events');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Update the registration in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.events.registration', $args['registration_id'], $args);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
        return $rc;
    }

    //
    // Check if there is an invoice for this event, and update the invoice.
    //
    if( isset($args['num_tickets']) && $args['num_tickets'] != $registration['num_tickets'] && $registration['invoice_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateItem');
        $rc = ciniki_sapos_invoiceUpdateItem($ciniki, $args['business_id'], $registration['invoice_id'],
            array('object'=>'ciniki.events.registration',
                'object_id'=>$registration['id'],
                'quantity'=>$args['num_tickets'],
                ));
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
            return $rc;
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.events');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'events');

    return array('stat'=>'ok');
}
?>
