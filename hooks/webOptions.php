<?php
//
// Description
// -----------
// This function will return the list of options for the module that can be set for the website.
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure.
// business_id:		The ID of the business to get events for.
//
// args:			The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_events_hooks_webOptions(&$ciniki, $business_id, $args) {

	//
	// Check to make sure the module is enabled
	//
	if( !isset($ciniki['business']['modules']['ciniki.events']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2580', 'msg'=>"I'm sorry, the page you requested does not exist."));
	}

	//
	// Get the settings from the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
	$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_settings', 'business_id', $business_id, 'ciniki.web', 'settings', 'page-events');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['settings']) ) {
		$settings = array();
	} else {
		$settings = $rc['settings'];
	}


	$options = array();
	$options[] = array('option'=>array(
		'label'=>'Include Past Events',
		'setting'=>'page-events-past', 
		'type'=>'toggle',
		'value'=>(isset($settings['page-events-past'])?$settings['page-events-past']:'no'),
		'toggles'=>array(
			array('toggle'=>array('value'=>'no', 'label'=>'No')),
			array('toggle'=>array('value'=>'yes', 'label'=>'Yes')),
			),
		));

	$options[] = array('option'=>array(
		'label'=>'Hide empty upcoming',
		'setting'=>'page-events-upcoming-empty-hide', 
		'type'=>'toggle',
		'value'=>(isset($settings['page-events-upcoming-empty-hide'])?$settings['page-events-upcoming-empty-hide']:'no'),
		'dependency'=>'page-events-past',
		'dependency_value'=>'yes',
		'toggles'=>array(
			array('toggle'=>array('value'=>'no', 'label'=>'No')),
			array('toggle'=>array('value'=>'yes', 'label'=>'Yes')),
			),
		));
	
	//
	// Categories enabled
	//
	if( ($ciniki['business']['modules']['ciniki.events']['flags']&0x10) > 0 ) {
		$options[] = array('option'=>array(
			'label'=>'Display Categories',
			'setting'=>'page-events-categories-display', 
			'type'=>'toggle',
			'value'=>(isset($settings['page-events-categories-display'])?$settings['page-events-categories-display']:'off'),
			'toggles'=>array(
				array('toggle'=>array('value'=>'off', 'label'=>'Off')),
				array('toggle'=>array('value'=>'submenu', 'label'=>'Menu')),
				),
			));
	}

	return array('stat'=>'ok', 'options'=>$options);
}
?>
