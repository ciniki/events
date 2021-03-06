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
function ciniki_events_web_webCollectionList($ciniki, $settings, $tnid, $args) {

    $strsql = "SELECT ciniki_events.id, "
        . "ciniki_events.name, "
        . "ciniki_events.permalink, "
        . "ciniki_events.url, "
        . "IF(ciniki_events.long_description='', 'no', 'yes') AS isdetails, "
        . "DATE_FORMAT(ciniki_events.start_date, '%M') AS start_month, "
        . "DATE_FORMAT(ciniki_events.start_date, '%D') AS start_day, "
        . "DATE_FORMAT(ciniki_events.start_date, '%Y') AS start_year, "
        . "IF(ciniki_events.end_date = '0000-00-00', '', DATE_FORMAT(ciniki_events.end_date, '%M')) AS end_month, "
        . "IF(ciniki_events.end_date = '0000-00-00', '', DATE_FORMAT(ciniki_events.end_date, '%D')) AS end_day, "
        . "IF(ciniki_events.end_date = '0000-00-00', '', DATE_FORMAT(ciniki_events.end_date, '%Y')) AS end_year, "
        . "DATE_FORMAT(ciniki_events.start_date, '%a %b %e, %Y') AS start_date, "
        . "DATE_FORMAT(ciniki_events.end_date, '%a %b %e, %Y') AS end_date, "
        . "ciniki_events.times, "
        . "ciniki_events.description, "
        . "ciniki_events.primary_image_id, "
        . "COUNT(ciniki_event_images.id) AS num_images, "
        . "COUNT(ciniki_event_files.id) AS num_files "
        . "FROM ciniki_web_collection_objrefs "
        . "INNER JOIN ciniki_events ON ("
            . "ciniki_web_collection_objrefs.object_id = ciniki_events.id "
            . "AND ciniki_events.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (ciniki_events.flags&0x01) = 0x01 "
            . "";
            if( isset($args['type']) && $args['type'] == 'past' ) {
                $strsql .= "AND ((ciniki_events.end_date > ciniki_events.start_date AND ciniki_events.end_date < DATE(NOW())) "
                        . "OR (ciniki_events.end_date <= ciniki_events.start_date AND ciniki_events.start_date < DATE(NOW())) "
                        . ") ";
            } else {
                $strsql .= "AND (ciniki_events.end_date >= DATE(NOW()) OR ciniki_events.start_date >= DATE(NOW())) ";
            }
    $strsql .= ") "
        . "LEFT JOIN ciniki_event_images ON (ciniki_events.id = ciniki_event_images.event_id "
            . "AND ciniki_event_images.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (ciniki_event_images.webflags&0x01) = 0 " // public images
            . ") "
        . "LEFT JOIN ciniki_event_files ON (ciniki_events.id = ciniki_event_files.event_id "
            . "AND ciniki_event_files.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (ciniki_event_files.webflags&0x01) = 0 " // public files
            . ") "
        . "WHERE ciniki_web_collection_objrefs.collection_id = '" . ciniki_core_dbQuote($ciniki, $args['collection_id']) . "' "
        . "AND ciniki_web_collection_objrefs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_web_collection_objrefs.object = 'ciniki.events.event' "
        . "";
    if( isset($args['type']) && $args['type'] == 'past' ) {
        $strsql .= "GROUP BY ciniki_events.id ";
        $strsql .= "ORDER BY ciniki_events.start_date DESC ";
    } else {
        $strsql .= "GROUP BY ciniki_events.id ";
        $strsql .= "ORDER BY ciniki_events.start_date ASC ";
    }
    if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 && is_int($args['limit']) ) {
        $strsql .= "LIMIT " . intval($args['limit']) . " ";
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'events', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'image_id'=>'primary_image_id', 'isdetails', 
                'start_month', 'start_day', 'start_year', 'end_month', 'end_day', 'end_year', 
                'start_date', 'end_date', 'times',
                'permalink', 'description', 'url', 'num_images', 'num_files')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['events']) ) {
        return array('stat'=>'ok', 'events'=>array());
    }
    return array('stat'=>'ok', 'events'=>$rc['events']);
}
?>
