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
function ciniki_events_maps($ciniki, $modules) {
	$maps = array();
	$maps['prices'] = array('available_to'=>array(
		0x01=>'Customers',
		0x02=>'Private',
		0x10=>'Members',
		0x20=>'Dealers',
		0x40=>'Distributors',
		));

	return array('stat'=>'ok', 'maps'=>$maps);
}
?>
