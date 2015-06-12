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
function ciniki_events_maps($ciniki) {
	$maps = array();
	$maps['prices'] = array('available_to'=>array(
		0x01=>'Public',
		0x02=>'Private',
		0x10=>'Customers',
		0x20=>'Members',
		0x40=>'Dealers',
		0x80=>'Distributors',
		));
	$maps['registration'] = array('status'=>array(
		'0'=>'Unknown',
		'10'=>'Reserved',
		'20'=>'Confirmed',
		'30'=>'Paid',
		));

	return array('stat'=>'ok', 'maps'=>$maps);
}
?>
