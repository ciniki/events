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
function ciniki_events_sync_objects($ciniki, &$sync, $business_id, $args) {
	
	$objects = array();
	$objects['event'] = array(
		'name'=>'Events',
		'table'=>'ciniki_events',
		'fields'=>array(
			'name'=>array(),
			'category'=>array(),
			'url'=>array(),
			'description'=>array(),
			),
		'history_table'=>'ciniki_event_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
