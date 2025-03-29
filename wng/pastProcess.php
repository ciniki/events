<?php
//
// Description
// -----------
// This function will process a wng request for the events module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_events_wng_pastProcess(&$ciniki, $tnid, $request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.events']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.events.109', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.110', 'msg'=>"No event specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Check for image format
    //
    $thumbnail_format = 'square-cropped';
    $thumbnail_padding_color = '#ffffff';
    if( isset($s['thumbnail-format']) && $s['thumbnail-format'] == 'square-padded' ) {
        $thumbnail_format = $s['thumbnail-format'];
        if( isset($s['thumbnail-padding-color']) && $s['thumbnail-padding-color'] != '' ) {
            $thumbnail_padding_color = $s['thumbnail-padding-color'];
        } 
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    $dt = new DateTime('now', new DateTimezone($intl_timezone));

    //
    // Get the upcoming and current (optional) events
    //
    $strsql = "SELECT events.id, "
        . "events.name, "
        . "events.permalink, "
        . "events.flags, "
        . "events.description AS synopsis, "
        . "DATE_FORMAT(events.start_date, '%a %b %e, %Y') AS start_date, "
        . "DATE_FORMAT(events.end_date, '%a %b %e, %Y') AS end_date, "
        . "events.times, "
        . "events.primary_image_id "
        . "FROM ciniki_events AS events "
        . "WHERE events.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (events.flags&0x01) = 0x01 " // Visible
        . "";
    if( isset($s['include-current']) && $s['include-current'] == 'yes' ) {
        $strsql .= "AND events.start_date < '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' ";
    } else {
        $strsql .= "AND events.start_date < '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' ";
        $strsql .= "AND events.end_date < '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' ";
    }
    if( isset($s['year']) && $s['year'] != '' ) {
        $strsql .= "AND YEAR(events.start_date) = '" . ciniki_core_dbQuote($ciniki, $s['year']) . "' ";
    }
    elseif( isset($s['limit-years']) && is_numeric($s['limit-years']) && $s['limit-years'] > 0 ) {
        $year = clone $dt;
        $year->sub(new DateInterval('P' . intval($s['limit-years']) . 'Y'));
        $strsql .= "AND YEAR(events.start_date) > '" . ciniki_core_dbQuote($ciniki, $year->format('Y')) . "' ";
    }
    $strsql .= "ORDER BY events.start_date DESC, events.name ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'events', 'fname'=>'permalink', 
            'fields'=>array('id', 'title'=>'name', 'permalink', 'flags', 'synopsis', 'start_date', 'end_date', 'times', 
                'image-id'=>'primary_image_id'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.108', 'msg'=>'Unable to load events', 'err'=>$rc['err']));
    }
    $events = isset($rc['events']) ? $rc['events'] : array();

    //
    // Check for event request
    //
    if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
        && $request['uri_split'][($request['cur_uri_pos']+1)] != '' 
        && isset($events[$request['uri_split'][($request['cur_uri_pos']+1)]])
        ) {
        $request['cur_uri_pos']++;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'wng', 'eventProcess');
        return ciniki_events_wng_eventProcess($ciniki, $tnid, $request, $section);
    }

    //
    // Display list of events
    //
    if( isset($s['title']) && $s['title'] != '' ) {
        if( isset($s['year']) && $s['year'] != '' ) {
            $blocks[] = array(
                'type' => 'title',
                'title' => $s['title'] . ' - ' . $s['year'],
                );
        } else {
            $blocks[] = array(
                'type' => 'title',
                'title' => $s['title'],
                );
        }
    }
    if( count($events) <= 0 ) {
        $blocks[] = array(
            'type' => 'text',
            'content' => 'No upcoming events',
            );
    } elseif( isset($s['layout']) && $s['layout'] == 'tradingcards' ) {
        foreach($events as $eid => $event) {
            $events[$eid]['button-class'] = 'button';
            $events[$eid]['button-1-text'] = 'More Info';
            $events[$eid]['button-1-url'] = ($request['page']['path'] != '/' ? $request['page']['path'] : '') . '/' . $event['permalink'];
            $events[$eid]['url'] = ($request['page']['path'] != '/' ? $request['page']['path'] : '') . '/' . $event['permalink'];
        }
        $blocks[] = array(
            'type' => 'tradingcards',
            'image-ratio' => '1-1',
            'items' => $events,
            );
    } else {
        foreach($events as $event) {
            $blocks[] = array(
                'type' => 'contentphoto',
                'title' => $event['title'],
                'subtitle' => $event['start_date'],
                'content' => $event['synopsis'],
                'image-id' => $event['image-id'],
                'image-position' => isset($s['image-position']) && $s['image-position'] != '' ? $s['image-position'] : '',
                'image-size' => isset($s['image-size']) && $s['image-size'] != '' ? $s['image-size'] : '',
                'button-1-text' => isset($s['button-text']) && $s['button-text'] != '' ? $s['button-text'] : 'More info',
                'button-1-url' => $request['page']['path'] . '/' . $event['permalink'],
                );
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
