<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_events_flags($ciniki, $modules) {
	$flags = array(
		array('flag'=>array('bit'=>'1', 'name'=>'Registrations')),
		array('flag'=>array('bit'=>'2', 'name'=>'Online Registrations')),
		);

	return array('stat'=>'ok', 'flags'=>$flags);
}
?>
