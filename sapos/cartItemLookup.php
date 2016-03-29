<?php
//
// Description
// ===========
// This function will lookup an item that is being added to a shopping cart online.  This function
// has extra checks to make sure the requested item is available to the customer.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_events_sapos_cartItemLookup($ciniki, $business_id, $customer, $args) {

	if( !isset($args['object']) || $args['object'] == '' 
		|| !isset($args['object_id']) || $args['object_id'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3160', 'msg'=>'No event specified.'));
	}

	//
	// Lookup the requested event if specified along with a price_id
	//
	if( $args['object'] == 'ciniki.events.event' && isset($args['price_id']) && $args['price_id'] > 0 ) {
        $strsql = "SELECT ciniki_events.id AS event_id, "
            . "ciniki_events.name AS description, "
            . "ciniki_events.reg_flags, "
            . "ciniki_events.num_tickets, "
			. "ciniki_event_prices.id AS price_id, "
			. "ciniki_event_prices.name AS price_name, "
			. "ciniki_event_prices.available_to, "
			. "ciniki_event_prices.unit_amount, "
			. "ciniki_event_prices.unit_discount_amount, "
			. "ciniki_event_prices.unit_discount_percentage, "
			. "ciniki_event_prices.taxtype_id "
			. "FROM ciniki_event_prices "
			. "LEFT JOIN ciniki_events ON ("
				. "ciniki_event_prices.event_id = ciniki_events.id "
				. "AND ciniki_events.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND ciniki_events.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
				. ") "
            . "WHERE ciniki_event_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_event_prices.id = '" . ciniki_core_dbQuote($ciniki, $args['price_id']) . "' "
            . "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'events', 'fname'=>'event_id',
				'fields'=>array('event_id', 'price_id', 'price_name', 'description', 'reg_flags', 'num_tickets', 
					'available_to', 'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 'taxtype_id',
                    )),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['events']) || count($rc['events']) < 1 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3162', 'msg'=>'No event found.'));		
		}
		$item = array_pop($rc['events']);
        if( isset($item['price_name']) && $item['price_name'] != '' ) {
            $item['description'] .= ' - ' . $item['price_name'];
        }

		//
		// Check the available_to is correct for the specified customer
		//
		if( ($item['available_to']|0xF0) > 0 ) {
			if( ($item['available_to']&$customer['price_flags']) == 0 ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3163', 'msg'=>"I'm sorry, but this product is not available to you."));
			}
		}

        $item['flags'] = 0x20;
    
        //
        // Check the number of seats remaining
        //
        $item['tickets_sold'] = 0;
        $strsql = "SELECT 'num_tickets', SUM(num_tickets) AS num_tickets "
            . "FROM ciniki_event_registrations "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_event_registrations.event_id = '" . ciniki_core_dbQuote($ciniki, $item['event_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.events', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['num_tickets']) ) {
            $item['tickets_sold'] = $rc['num']['num_tickets'];
        }
        $item['units_available'] = $item['num_tickets'] - $item['tickets_sold'];
        $item['limited_units'] = 'yes';

		return array('stat'=>'ok', 'item'=>$item);
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3161', 'msg'=>'No event specified.'));
}
?>
