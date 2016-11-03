<?php
//
// Description
// ===========
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_events_registrationDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'),
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
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.registrationDelete'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the existing registration information
    //
    $strsql = "SELECT id, uuid, invoice_id "
        . "FROM ciniki_event_registrations "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.37', 'msg'=>'Event registration does not exist'));
    }
    $item = $rc['item'];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.events');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Remove from the invoice
    //
    if( $item['invoice_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceItemDelete');
        $rc = ciniki_sapos_hooks_invoiceItemDelete($ciniki, $args['business_id'], array(
            'invoice_id'=>$item['invoice_id'],
            'object'=>'ciniki.events.registration',
            'object_id'=>$item['id'],
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.38', 'msg'=>'Unable to remove registration.', 'err'=>$rc['err']));
        }
    }

    //
    // Delete the object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'registrationDelete');
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.events.registration', $item['id'], $item['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
        return $rc;
    }

    //
    // Commit the changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.events');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
