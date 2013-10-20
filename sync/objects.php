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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'objects');
	return ciniki_events_objects($ciniki);
}
?>
