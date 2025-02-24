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
function ciniki_events_wng_pastYearsProcess(&$ciniki, $tnid, $request, $section) {

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
    // Get the years we have events for
    //
    $strsql = "SELECT events.id, "
        . "events.name, "
        . "events.permalink, "
        . "events.flags, "
        . "events.description AS synopsis, "
        . "DATE_FORMAT(events.start_date, '%Y') AS year, "
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
    $strsql .= "ORDER BY events.start_date DESC, events.name ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'years', 'fname'=>'year', 
            'fields'=>array('year'),
            ),
        array('container'=>'events', 'fname'=>'permalink', 
            'fields'=>array('id', 'title'=>'name', 'permalink', 'flags', 'synopsis', 'start_date', 'end_date', 'times', 
                'image-id'=>'primary_image_id'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.108', 'msg'=>'Unable to load events', 'err'=>$rc['err']));
    }
    $years = isset($rc['years']) ? $rc['years'] : array();

    foreach($years as $yid => $year) {
        $years[$yid]['image-id'] = 0;
        foreach($year['events'] as $event) {
            if( $years[$yid]['image-id'] == 0 && $event['image-id'] > 0 ) {
                $years[$yid]['image-id'] = $event['image-id'];
            }
            $years[$yid]['url'] = $request['page']['path'] . '/' . $yid;
            $years[$yid]['button-1-url'] = $request['page']['path'] . '/' . $yid;
            $years[$yid]['button-1-text'] = 'More Info';
            $years[$yid]['title'] = $yid;


            //
            // Check for event request
            //
            if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
                && $request['uri_split'][($request['cur_uri_pos']+1)] == $yid 
                ) {
                $request['cur_uri_pos']++;
                $request['page']['path'] .= '/' . $yid;
                $section['settings']['year'] = $yid;
                ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'wng', 'pastProcess');
                $rc = ciniki_events_wng_pastProcess($ciniki, $tnid, $request, $section);
                return array('stat'=>'ok', 'blocks'=>$rc['blocks'], 'stop'=>'yes', 'clear'=>'yes');
            }
            
        }
    }

    //
    // Display list of years
    //
    if( isset($s['title']) && $s['title'] != '' ) {
        $blocks[] = array(
            'type' => 'title',
            'title' => $s['title'],
            );
    }
    if( count($years) <= 0 ) {
        $blocks[] = array(
            'type' => 'text',
            'content' => 'No past events',
            );
    } else {
        $blocks[] = array(
            'type' => 'buttons',
            'class' => 'aligncenter',
            'image-ratio' => '1-1',
            'items' => $years,
            );
/*        $blocks[] = array(
            'type' => 'tradingcards',
            'image-ratio' => '1-1',
            'items' => $years,
            ); */
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
