<?php
//
// Description
// ===========
// This function will search the events for the ciniki.sapos module.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_events_sapos_itemSearch($ciniki, $business_id, $start_needle, $limit) {

	if( $start_needle == '' ) {
		return array('stat'=>'ok', 'items'=>array());
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// FIXME: Query for the taxes for events
	//
//	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
//	$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_event_settings', 'business_id', $business_id,
//		'ciniki.artcatalog', 'taxes', 'taxes');
//	if( $rc['stat'] != 'ok' ) {
//		return $rc;
//	}
//	if( isset($rc['taxes']) ) {
//		$tax_settings = $rc['taxes'];
//	} else {
//		$tax_settings = array();
//	}

	//
	// Set the default taxtype for the item
	//
	$taxtype_id = 0;
//	if( isset($tax_settings['taxes-default-taxtype']) ) {
//		$taxtype_id = $tax_settings['taxes-default-taxtype'];
//	}

	//
	// Prepare the query
	//
	$strsql = "SELECT ciniki_events.id, "
		. "ciniki_events.name, "
		. "DATE_FORMAT(ciniki_events.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
		. "ciniki_event_prices.id AS price_id, "
		. "ciniki_event_prices.name AS price_name, "
		. "ciniki_event_prices.unit_amount, "
		. "ciniki_event_prices.unit_discount_amount, "
		. "ciniki_event_prices.unit_discount_percentage, "
		. "ciniki_event_prices.taxtype_id "
		. "FROM ciniki_events "
		. "LEFT JOIN ciniki_event_prices ON (ciniki_events.id = ciniki_event_prices.event_id "
			. "AND ciniki_event_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE ciniki_events.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (ciniki_events.reg_flags&0x03) > 0 "
		. "AND (ciniki_events.end_date >= DATE(NOW()) OR ciniki_events.start_date >= DATE(NOW())) "
		. "AND (ciniki_events.name LIKE '" . ciniki_core_dbQuote($ciniki, $start_needle) . "%' "
			. "OR ciniki_events.name LIKE '% " . ciniki_core_dbQuote($ciniki, $start_needle) . "%' "
			. ") "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.events', array(
		array('container'=>'events', 'fname'=>'id',
			'fields'=>array('id', 'name', 'start_date')),
		array('container'=>'prices', 'fname'=>'price_id',
			'fields'=>array('id'=>'price_id', 'name'=>'price_name', 'unit_amount'=>'unit_amount', 
				'unit_discount_amount', 'unit_discount_percentage',
				'taxtype_id')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['events']) ) {
		$events = $rc['events'];
	} else {
		return array('stat'=>'ok', 'items'=>array());
	}

	$items = array();
	foreach($events as $eid => $event) {
		if( isset($event['prices']) && count($event['prices']) > 1 ) {
			foreach($event['prices'] as $pid => $price) {
				$details = array(
					'status'=>0,
					'object'=>'ciniki.events.event',
					'object_id'=>$event['id'],
					'description'=>$event['name'],
					'quantity'=>1,
					'unit_amount'=>$price['unit_amount'],
					'unit_discount_amount'=>$price['unit_discount_amount'],
					'unit_discount_percentage'=>$price['unit_discount_percentage'],
					'taxtype_id'=>$price['taxtype_id'], 
					'notes'=>'',
					);
				if( $price['name'] != '' ) {
					$details['description'] .= ' - ' . $price['name'];
				}
				$items[] = array('item'=>$details);
			}
		} else {
			$details = array(
				'status'=>0,
				'object'=>'ciniki.events.event',
				'object_id'=>$event['id'],
				'description'=>$event['name'],
				'quantity'=>1,
				'unit_amount'=>0,
				'unit_discount_amount'=>0,
				'unit_discount_percentage'=>0,
				'taxtype_id'=>0, 
				'notes'=>'',
				);
			if( isset($event['prices']) && count($event['prices']) == 1 ) {
				$price = array_pop($event['prices']);
				if( isset($price['name']) && $price['name'] != '' ) {
					$details['description'] .= ' - ' . $price['name'];
				}
				if( isset($price['unit_amount']) && $price['unit_amount'] != '' ) {
					$details['unit_amount'] = $price['unit_amount'];
				}
				if( isset($price['unit_discount_amount']) && $price['unit_discount_amount'] != '' ) {
					$details['unit_discount_amount'] = $price['unit_discount_amount'];
				}
				if( isset($price['unit_discount_percentage']) && $price['unit_discount_percentage'] != '' ) {
					$details['unit_discount_percentage'] = $price['unit_discount_percentage'];
				}
				if( isset($price['taxtype_id']) && $price['taxtype_id'] != '' ) {
					$details['taxtype_id'] = $price['taxtype_id'];
				}
			}
			$items[] = array('item'=>$details);
		}
	}

	return array('stat'=>'ok', 'items'=>$items);		
}
?>