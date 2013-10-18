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
			'url'=>array(),
			'description'=>array(),
			'start_date'=>array(),
			'end_date'=>array(),
			),
		'history_table'=>'ciniki_event_history',
		);
	$objects['file'] = array(
		'name'=>'File',
		'table'=>'ciniki_event_files',
		'fields'=>array(
			'event_id'=>array('ref'=>'ciniki.events.event'),
			'extension'=>array(),
			'name'=>array(),
			'permalink'=>array(),
			'webflags'=>array(),
			'description'=>array(),
			'org_filename'=>array(),
			'publish_date'=>array(),
			'binary_content'=>array(),
			),
		'history_table'=>'ciniki_event_history',
		);
	$objects['registration'] = array(
		'ref'=>'ciniki.events.registration',
		'name'=>'Registration',
		'table'=>'ciniki_event_registrations',
		'fields'=>array(
			'event_id'=>array('ref'=>'ciniki.events.event'),
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'invoice_id'=>array('ref'=>'ciniki.pos.invoice'),
			'num_tickets'=>array(),
			'customer_notes'=>array(),
			'notes'=>array(),
			),
		'history_table'=>'ciniki_event_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
