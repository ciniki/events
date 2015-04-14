<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_events_web_eventList($ciniki, $settings, $business_id, $args) {

	$type_strsql = '';
	if( isset($args['type']) && $args['type'] == 'past' ) {
		$type_strsql .= "AND ((ciniki_events.end_date > ciniki_events.start_date AND ciniki_events.end_date < DATE(NOW())) "
				. "OR (ciniki_events.end_date <= ciniki_events.start_date AND ciniki_events.start_date < DATE(NOW())) "
				. ") ";
	} else {
		$type_strsql .= "AND (ciniki_events.end_date >= DATE(NOW()) OR ciniki_events.start_date >= DATE(NOW())) ";
	}
	$strsql = "SELECT ciniki_events.id, "
		. "ciniki_events.name, "
		. "ciniki_events.permalink, "
		. "ciniki_events.url, "
		. "IF(ciniki_events.long_description='', 'no', 'yes') AS isdetails, "
		. "DATE_FORMAT(ciniki_events.start_date, '%a %b %e, %Y') AS start_date, "
		. "DATE_FORMAT(ciniki_events.end_date, '%a %b %e, %Y') AS end_date, "
		. "DATE_FORMAT(ciniki_events.start_date, '%M') AS start_month, "
		. "DATE_FORMAT(ciniki_events.start_date, '%D') AS start_day, "
		. "DATE_FORMAT(ciniki_events.start_date, '%Y') AS start_year, "
		. "IF(ciniki_events.end_date = '0000-00-00', '', DATE_FORMAT(ciniki_events.end_date, '%M')) AS end_month, "
		. "IF(ciniki_events.end_date = '0000-00-00', '', DATE_FORMAT(ciniki_events.end_date, '%D')) AS end_day, "
		. "IF(ciniki_events.end_date = '0000-00-00', '', DATE_FORMAT(ciniki_events.end_date, '%Y')) AS end_year, "
		. "ciniki_events.times, "
		. "ciniki_events.description, "
		. "ciniki_events.primary_image_id, "
		. "COUNT(ciniki_event_images.id) AS num_images, "
		. "COUNT(ciniki_event_files.id) AS num_files "
		. "";
	if( isset($args['tag_type']) && $args['tag_type'] != '' 
		&& isset($args['tag_permalink']) && $args['tag_permalink'] != '' 
		) {
		$strsql .= "FROM ciniki_event_tags "
			. "LEFT JOIN ciniki_events ON ("
				. "ciniki_event_tags.event_id = ciniki_events.id "
				. "AND ciniki_events.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. $type_strsql
				. ") "
			. "LEFT JOIN ciniki_event_images ON ("
				. "ciniki_events.id = ciniki_event_images.event_id "
				. "AND ciniki_event_images.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND (ciniki_event_images.webflags&0x01) = 0 " // public images
				. ") "
			. "LEFT JOIN ciniki_event_files ON ("
				. "ciniki_events.id = ciniki_event_files.event_id "
				. "AND ciniki_event_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND (ciniki_event_files.webflags&0x01) = 0 " // public files
				. ") "
			. "WHERE ciniki_event_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_event_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $args['tag_type']) . "' "
			. "AND ciniki_event_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['tag_permalink']) . "' "
			. "";
	} else {
		$strsql .= "FROM ciniki_events "
			. "LEFT JOIN ciniki_event_images ON ("
				. "ciniki_events.id = ciniki_event_images.event_id "
				. "AND ciniki_event_images.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND (ciniki_event_images.webflags&0x01) = 0 " // public images
				. ") "
			. "LEFT JOIN ciniki_event_files ON ("
				. "ciniki_events.id = ciniki_event_files.event_id "
				. "AND ciniki_event_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND (ciniki_event_files.webflags&0x01) = 0 " // public files
				. ") "
			. "WHERE ciniki_events.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. $type_strsql
			. "";
	}
	if( isset($args['type']) && $args['type'] == 'past' ) {
		$strsql .= "GROUP BY ciniki_events.id "
			. "ORDER BY ciniki_events.start_date DESC "
			. "";
	} else {
		$strsql .= "GROUP BY ciniki_events.id "
			. "ORDER BY ciniki_events.start_date ASC "
			. "";
	}
	if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 && is_int($args['limit']) ) {
		$strsql .= "LIMIT " . $args['limit'] . " ";
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.events', array(
		array('container'=>'events', 'fname'=>'id', 
			'fields'=>array('id', 'name', 'image_id'=>'primary_image_id', 'isdetails', 
				'start_month', 'start_day', 'start_year', 'end_month', 'end_day', 'end_year', 
				'start_date', 'end_date', 'times',
				'permalink', 'description', 'url', 'num_images', 'num_files')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['events']) ) {
		return array('stat'=>'ok', 'events'=>array());
	}
	return array('stat'=>'ok', 'events'=>$rc['events']);
}
?>
