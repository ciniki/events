<?php
//
// Description
// ===========
// This function will be a callback when an item is added to ciniki.sapos.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_events_sapos_itemLookup($ciniki, $business_id, $args) {

	if( !isset($args['object']) || $args['object'] == ''
		|| !isset($args['object_id']) || $args['object_id'] == '' 
		|| !isset($args['price_id']) || $args['price_id'] == '' 
		) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1818', 'msg'=>'No event specified.'));
	}

	//
	// An event was added to an invoice item, get the details and see if we need to 
	// create a registration for this event
	//
	if( $args['object'] == 'ciniki.events.event' ) {
		$strsql = "SELECT id, name, reg_flags, num_tickets "
			. "FROM ciniki_events "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'event');
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( !isset($rc['event']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1819', 'msg'=>'Unable to find event'));
		}
		$event = $rc['event'];
		$item = array(
			'id'=>$event['id'],
			'name'=>$event['name'],
			'flags'=>0x08, 			// Registration item
			);

		//
		// If registrations online enabled, check the available tickets
		//
		if( ($event['reg_flags']&0x02) > 0 ) {
			$event['tickets_sold'] = 0;
			$strsql = "SELECT 'num_tickets', SUM(num_tickets) AS num_tickets "
				. "FROM ciniki_event_registrations "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND ciniki_event_registrations.event_id = '" . ciniki_core_dbQuote($ciniki, $event['id']) . "' "
				. "";
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
			$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.events', 'num');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['num']['num_tickets']) ) {
				$event['tickets_sold'] = $rc['num']['num_tickets'];
				$event['tickets_available'] = $event['num_tickets'] - $event['tickets_sold'];
				$item['limited_units'] = 'yes';
				$item['units_available'] = $event['tickets_available'];
			}
		}

		return array('stat'=>'ok', 'item'=>$item);
	}

	return array('stat'=>'ok');
}
?>
