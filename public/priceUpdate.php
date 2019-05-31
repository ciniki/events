<?php
//
// Description
// ===========
// This method will update an event price in the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the event is attached to.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_events_priceUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'price_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'), 
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'available_to'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Available To'),
        'valid_from'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc',
            'name'=>'Valid From'),
        'valid_to'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc',
            'name'=>'Valid To'),
        'unit_amount'=>array('required'=>'no', 'blank'=>'no', 'type'=>'currency', 'name'=>'Unit Amount'),
        'unit_discount_amount'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 
            'name'=>'Unit Discount Amount'),
        'unit_discount_percentage'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Unit Discount Percentage'),
        'unit_donation_amount'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Donation Portion'),
        'taxtype_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Tax Type'),
        'webflags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Web Flags'),
        'position_num'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Ticket Map Position Number'),
        'position_x'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Ticket Map Position X'),
        'position_y'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Ticket Map Position Y'),
        'diameter'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Ticket Map Diameter'),
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
    $rc = ciniki_events_checkAccess($ciniki, $args['tnid'], 'ciniki.events.priceUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Update the price in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    return ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.events.price', 
        $args['price_id'], $args);
}
?>
