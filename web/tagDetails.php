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
function ciniki_events_web_tagDetails($ciniki, $settings, $tnid, $tag_type, $tag_permalink) {

    $tag = array('title'=>$tag_permalink);

    //
    // Get the full name for the tag
    //
    $strsql = "SELECT tag_name "
        . "FROM ciniki_event_tags "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND tag_type = '" . ciniki_core_dbQuote($ciniki, $tag_type) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $tag_permalink) . "' "
        . "LIMIT 1"
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'tag');
    if( isset($rc['tag']) ) {
        $tag['title'] = $rc['tag']['tag_name'];
    }

    //
    // Get the settings for the tag, synopsis, image, description, etc
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_event_settings', 'tnid', $tnid,
        'ciniki.events', 'settings', 'tag-' . $tag_type . '-' . $tag_permalink);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'ok', 'tag'=>array());
    }
    if( isset($rc['settings']) ) {
        foreach($rc['settings'] as $setting => $value) {
            $setting = str_replace("tag-$tag_type-$tag_permalink-", '', $setting);
            $tag[$setting] = $value;
        }
    } else {
        $tag = array();
    }

    return array('stat'=>'ok', 'tag'=>$tag);
}
?>
