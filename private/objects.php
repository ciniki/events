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
function ciniki_events_objects($ciniki) {
	
	$objects = array();
	$objects['event'] = array(
		'name'=>'Events',
		'sync'=>'yes',
		'table'=>'ciniki_events',
		'fields'=>array(
			'name'=>array(),
			'permalink'=>array(),
			'url'=>array(),
			'description'=>array(),
			'reg_flags'=>array(),
			'num_tickets'=>array(),
			'start_date'=>array(),
			'end_date'=>array(),
			'primary_image_id'=>array('ref'=>'ciniki.images.image'),
			'long_description'=>array(),
			),
		'history_table'=>'ciniki_event_history',
		);
	$objects['image'] = array(
		'name'=>'Image',
		'sync'=>'yes',
		'table'=>'ciniki_event_images',
		'fields'=>array(
			'event_id'=>array('ref'=>'ciniki.events.event'),
			'name'=>array(),
			'permalink'=>array(),
			'webflags'=>array(),
			'image_id'=>array('ref'=>'ciniki.images.image'),
			'description'=>array(),
			'url'=>array(),
			),
		'history_table'=>'ciniki_event_history',
		);
	$objects['file'] = array(
		'name'=>'File',
		'sync'=>'yes',
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
			'binary_content'=>array('history'=>'no'),
			),
		'history_table'=>'ciniki_event_history',
		);
	$objects['registration'] = array(
		'name'=>'Registration',
		'sync'=>'yes',
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
