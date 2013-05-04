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
function ciniki_events_web_eventDetails($ciniki, $settings, $business_id, $permalink) {

	$strsql = "SELECT ciniki_events.id, "
		. "ciniki_events.name, "
		. "ciniki_events.permalink, "
		. "ciniki_events.url, "
		. "DATE_FORMAT(start_date, '%a %b %c, %Y') AS start_date, "
		. "DATE_FORMAT(end_date, '%a %b %c, %Y') AS end_date, "
		. "ciniki_events.long_description, "
		. "ciniki_events.primary_image_id, "
		. "ciniki_event_images.image_id, "
		. "ciniki_event_images.name AS image_name, "
		. "ciniki_event_images.permalink AS image_permalink, "
		. "ciniki_event_images.description AS image_description, "
		. "ciniki_event_images.url AS image_url, "
		. "UNIX_TIMESTAMP(ciniki_event_images.last_updated) AS image_last_updated "
		. "FROM ciniki_events "
		. "LEFT JOIN ciniki_event_images ON ("
			. "ciniki_events.id = ciniki_event_images.event_id "
			. "AND (ciniki_event_images.webflags&0x01) = 0 "
			. ") "
		. "WHERE ciniki_events.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_events.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.artclub', array(
		array('container'=>'events', 'fname'=>'id', 
			'fields'=>array('id', 'name', 'permalink', 'image_id'=>'primary_image_id', 
			'start_date', 'end_date', 
			'url', 'description'=>'long_description')),
		array('container'=>'images', 'fname'=>'image_id', 
			'fields'=>array('image_id', 'title'=>'image_name', 'permalink'=>'image_permalink',
				'description'=>'image_description', 'url'=>'image_url',
				'last_updated'=>'image_last_updated')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['events']) || count($rc['events']) < 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1288', 'msg'=>'Unable to find event'));
	}
	$event = array_pop($rc['events']);

	return array('stat'=>'ok', 'event'=>$event);
}
?>
