<?php
//
// Description
// ===========
// This method will add a new price for an event.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to add the file to.
// event_id:            The ID of the event the file is attached to.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_events_priceAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'event_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Event'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'available_to'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'name'=>'Available To'),
        'valid_from'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'type'=>'datetimetoutc',
            'name'=>'Valid From'),
        'valid_to'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'type'=>'datetimetoutc',
            'name'=>'Valid To'),
        'unit_amount'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'currency', 'name'=>'Unit Amount'),
        'unit_discount_amount'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'type'=>'currency', 
            'name'=>'Unit Discount Amount'),
        'unit_discount_percentage'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 
            'name'=>'Unit Discount Percentage'),
        'taxtype_id'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Tax Type'),
        'webflags'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Web Flags'),
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
    $rc = ciniki_events_checkAccess($ciniki, $args['tnid'], 'ciniki.events.priceAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Add the price to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    return ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.events.price', $args);
}
?>
