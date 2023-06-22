<?php
//
// Description
// -----------
// This function will return the history for an element of the event price.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the history for.
// price_id:        The ID of the price to get the history for.
// field:               The field to get the history for.
//
// Returns
// -------
//  <history>
//      <action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//      ...
//  </history>
//  <users>
//      <user id="1" name="users.display_name" />
//      ...
//  </users>
//
function ciniki_events_priceHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'price_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
    $rc = ciniki_events_checkAccess($ciniki, $args['tnid'], 'ciniki.events.priceHistory', 0);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( preg_match("/^webflags([0-9]+)/", $args['field'], $m) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryFlagBit');
        return ciniki_core_dbGetModuleHistoryFlagBit($ciniki, 'ciniki.events', 'ciniki_event_history', 
            $args['tnid'], 'ciniki_event_prices', $args['price_id'], 'webflags', pow(2, ($m[1]-1)), 'No', 'Yes');
            

    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.events', 
        'ciniki_event_history', $args['tnid'], 
        'ciniki_event_prices', $args['price_id'], $args['field']);
}
?>
