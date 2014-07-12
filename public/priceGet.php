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
// business_id:			The ID of the business the event is attached to.
// price_id:		The ID of the price to get the details for.
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
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'price_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'), 
		'customer'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
		'invoice'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice'),
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
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.priceGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki, 'php');

	$strsql = "SELECT ciniki_event_prices.id, "
		. "ciniki_event_prices.event_id, "
		. "ciniki_event_prices.name, "
		. "ciniki_event_prices.available_to, "
		. "ciniki_event_prices.valid_from, "
		. "ciniki_event_prices.valid_to, "
		. "ciniki_event_prices.unit_amount, "
		. "ciniki_event_prices.unit_discount_amount, "
		. "ciniki_event_prices.unit_discount_percentage, "
		. "ciniki_event_prices.taxtype_id, "
		. "ciniki_event_prices.webflags "
		. "FROM ciniki_event_prices "
		. "WHERE ciniki_event_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_event_prices.id = '" . ciniki_core_dbQuote($ciniki, $args['price_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
		array('container'=>'prices', 'fname'=>'id', 'name'=>'price',
			'fields'=>array('id', 'event_id', 'name', 'available_to', 'valid_from', 'valid_to', 
				'unit_amount', 'unit_discount_amount', 'unit_discount_percentage',
				'taxtype_id', 'webflags'),
			'utctotz'=>array('valid_from'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
				'valid_to'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
				),
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['prices']) || !isset($rc['prices'][0]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1329', 'msg'=>'Unable to find price'));
	}
	$price = $rc['prices'][0]['price'];

	$price['unit_discount_percentage'] = (float)$price['unit_discount_percentage'];
	$price['unit_amount'] = numfmt_format_currency($intl_currency_fmt,
		$price['unit_amount'], $intl_currency);
	$price['unit_discount_amount'] = numfmt_format_currency($intl_currency_fmt,
		$price['unit_discount_amount'], $intl_currency);

	return array('stat'=>'ok', 'price'=>$price);
}
?>
