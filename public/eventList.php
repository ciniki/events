<?php
//
// Description
// -----------
// This method will return the list of events for a business.  It is restricted
// to business owners and sysadmins.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get events for.
//
// Returns
// -------
// <upcoming>
//		<event id="41" name="Event name" url="http://www.ciniki.org/" description="Event description" start_date="Jul 18, 2012" end_date="Jul 20, 2012" />
// </upcoming>
// <past />
//
function ciniki_events_eventList($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'tag_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'), 
		'tag_permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'), 
		'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.eventList');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	$rsp = array('stat'=>'ok');

	//
	// If categories are also to be returned
	//
	if( isset($args['categories']) && $args['categories'] == 'yes' ) {
		$rsp['tag_name'] = 'Uncategorized';

		//
		// Get the distinct list of tags
		//
		$strsql = "SELECT ciniki_event_tags.tag_name, "
			. "ciniki_event_tags.permalink, "
			. "COUNT(ciniki_events.id) AS num_upcoming_events "
			. "FROM ciniki_event_tags "
			. "LEFT JOIN ciniki_events ON ("
				. "ciniki_event_tags.event_id = ciniki_events.id "
				. "AND ciniki_events.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND (ciniki_events.end_date >= DATE(NOW()) OR ciniki_events.start_date >= DATE(NOW())) "
				. ") "
			. "WHERE ciniki_event_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_event_tags.tag_type = '10' "
			. "GROUP BY ciniki_event_tags.permalink "
			. "ORDER BY ciniki_event_tags.tag_name COLLATE latin1_general_cs "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
			array('container'=>'tags', 'fname'=>'tag_name', 'name'=>'tag',
				'fields'=>array('tag_name', 'permalink', 'num_upcoming_events')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['tags']) ) {
			$rsp['categories'] = $rc['tags'];
		}
		if( isset($args['tag_permalink']) && $args['tag_permalink'] != '' ) {
			foreach($rsp['categories'] as $cid => $cat) {
				if( $cat['tag']['permalink'] == $args['tag_permalink'] ) {
					$rsp['tag_name'] = $cat['tag']['tag_name'];
				}
			}
		}

		//
		// Check for any uncategorized events
		//
		$strsql = "SELECT COUNT(ciniki_events.id) AS num_events, ciniki_event_tags.tag_name "
			. "FROM ciniki_events "
			. "LEFT JOIN ciniki_event_tags ON ("
				. "ciniki_events.id = ciniki_event_tags.event_id "
				. "AND ciniki_event_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND ciniki_event_tags.tag_type = '10' "
				. ") "
			. "WHERE ciniki_events.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
//			. "AND (ciniki_events.end_date >= DATE(NOW()) OR ciniki_events.start_date >= DATE(NOW())) "
			. "AND ISNULL(tag_name) "
			. "GROUP BY tag_name "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'uncategorized');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['uncategorized']) ) {
			$rsp['categories'][] = array('tag'=>array('tag_name'=>'Uncategorized', 'permalink'=>'', 'num_events'=>$rc['uncategorized']['num_events']));
		}
		// No uncategorized events, show the first category
		if( (!isset($rc['uncategorized']['num_events']) || $rc['uncategorized']['num_events'] == 0) 
			&& count($rsp['categories']) > 0 
			&& (!isset($args['tag_permalink']) || $args['tag_permalink'] == '') 
			) {
			$args['tag_permalink'] = $rsp['categories'][0]['tag']['permalink'];
			$rsp['tag_name'] = $rsp['categories'][0]['tag']['tag_name'];
		}

		$rsp['tag_permalink'] = $args['tag_permalink'];
	}
	
	//
	// Load the upcoming events
	//
	if( isset($args['tag_type']) && $args['tag_type'] != '' 
		&& isset($args['tag_permalink']) && $args['tag_permalink'] != '' 
		) {
		$strsql = "SELECT ciniki_events.id, ciniki_events.name, ciniki_events.url, ciniki_events.description, "
			. "DATE_FORMAT(ciniki_events.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
			. "DATE_FORMAT(ciniki_events.end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date "
			. "FROM ciniki_event_tags, ciniki_events "
			. "WHERE ciniki_event_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_event_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $args['tag_type']) . "' "
			. "AND ciniki_event_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['tag_permalink']) . "' "
			. "AND ciniki_event_tags.event_id = ciniki_events.id "
			. "AND ciniki_events.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND (ciniki_events.end_date >= DATE(NOW()) OR ciniki_events.start_date >= DATE(NOW())) "
			. "ORDER BY ciniki_events.start_date ASC "
			. "";
	} elseif( isset($args['tag_type']) && $args['tag_type'] != '' 
		&& isset($args['tag_permalink']) && $args['tag_permalink'] == '' 
		) {
		$strsql = "SELECT ciniki_events.id, ciniki_events.name, ciniki_events.url, ciniki_events.description, "
			. "DATE_FORMAT(ciniki_events.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
			. "DATE_FORMAT(ciniki_events.end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date, "
			. "ciniki_event_tags.tag_name "
			. "FROM ciniki_events "
			. "LEFT JOIN ciniki_event_tags ON ("
				. "ciniki_events.id = ciniki_event_tags.event_id "
				. "AND ciniki_event_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $args['tag_type']) . "' "
				. "AND ciniki_event_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_events.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND (ciniki_events.end_date >= DATE(NOW()) OR ciniki_events.start_date >= DATE(NOW())) "
			. "AND ISNULL(tag_name) "
			. "ORDER BY ciniki_events.start_date ASC "
			. "";
	} else {
		$strsql = "SELECT id, name, url, description, "
			. "DATE_FORMAT(start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
			. "DATE_FORMAT(end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date "
			. "FROM ciniki_events "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND (end_date >= DATE(NOW()) OR start_date >= DATE(NOW())) "
			. "ORDER BY ciniki_events.start_date ASC "
			. "";
	}

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
	$rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.events', 'events', 'event', array('stat'=>'ok', 'events'=>array()));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	$rsp['upcoming'] = $rc['events'];

	//
	// Load the past events
	//
	if( isset($args['tag_type']) && $args['tag_type'] != '' 
		&& isset($args['tag_permalink']) && $args['tag_permalink'] != '' 
		) {
		$strsql = "SELECT ciniki_events.id, ciniki_events.name, ciniki_events.url, ciniki_events.description, "
			. "DATE_FORMAT(ciniki_events.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
			. "DATE_FORMAT(ciniki_events.end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date "
			. "FROM ciniki_event_tags, ciniki_events "
			. "WHERE ciniki_event_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_event_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $args['tag_type']) . "' "
			. "AND ciniki_event_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['tag_permalink']) . "' "
			. "AND ciniki_event_tags.event_id = ciniki_events.id "
			. "AND ciniki_events.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ((ciniki_events.end_date > ciniki_events.start_date AND ciniki_events.end_date < DATE(NOW())) "
				. "OR (ciniki_events.end_date <= ciniki_events.start_date AND ciniki_events.start_date <= DATE(NOW())) "
				. ") "
			. "ORDER BY ciniki_events.start_date DESC "
			. "";
	} elseif( isset($args['tag_type']) && $args['tag_type'] != '' 
		&& isset($args['tag_permalink']) && $args['tag_permalink'] == '' 
		) {
		$strsql = "SELECT ciniki_events.id, ciniki_events.name, ciniki_events.url, ciniki_events.description, "
			. "DATE_FORMAT(ciniki_events.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
			. "DATE_FORMAT(ciniki_events.end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date, "
			. "ciniki_event_tags.tag_name "
			. "FROM ciniki_events "
			. "LEFT JOIN ciniki_event_tags ON ("
				. "ciniki_events.id = ciniki_event_tags.event_id "
				. "AND ciniki_event_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $args['tag_type']) . "' "
				. "AND ciniki_event_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_events.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ((ciniki_events.end_date > ciniki_events.start_date AND ciniki_events.end_date < DATE(NOW())) "
				. "OR (ciniki_events.end_date <= ciniki_events.start_date AND ciniki_events.start_date <= DATE(NOW())) "
				. ") "
			. "AND ISNULL(tag_name) "
			. "ORDER BY ciniki_events.start_date DESC "
			. "";
	} else {
		$strsql = "SELECT id, name, url, description, "
			. "DATE_FORMAT(start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
			. "DATE_FORMAT(end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date "
			. "FROM ciniki_events "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ((ciniki_events.end_date > ciniki_events.start_date AND ciniki_events.end_date < DATE(NOW())) "
				. "OR (ciniki_events.end_date <= ciniki_events.start_date AND ciniki_events.start_date <= DATE(NOW())) "
				. ") "
			. "ORDER BY ciniki_events.start_date DESC "
			. "";
	}

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
	$rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.events', 'events', 'event', array('stat'=>'ok', 'events'=>array()));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$rsp['past'] = $rc['events'];

	return $rsp;
}
?>
