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
// business_id:     The ID of the business the event is attached to.
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
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
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
        'taxtype_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Tax Type'),
        'webflags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Web Flags'),
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
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.priceUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Update the price in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    return ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.events.price', 
        $args['price_id'], $args);
}
?>
