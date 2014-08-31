<?php
$json = file_get_contents('http://www.crestinortodox.ro/calendar/j');
$obj = json_decode($json, true);

require_once 'iCalcreator.class.php';

$v = new vcalendar();                          // initiate new CALENDAR
$v->setConfig( "nl", "\n" );
//$v->setConfig( "format", "xcal" );
$v->setConfig( "unique_id", "andreineculau.com" );
$v->setProperty( 'method', 'PUBLISH' );
$v->setProperty( 'calscale', 'GREGORIAN' );
//$v->setProperty( 'prodid', '-//Google Inc//Google Calendar 70.9054//EN' );

$v->setProperty( "X-WR-CALNAME", "Calendar Ortodox (Andrei Neculau)" );
$v->setProperty( "X-WR-CALDESC", "Sarbatori Religioase. Date furnizate de www.crestinortodox.ro" );
$v->setProperty( "X-WR-TIMEZONE", "Europe/Bucharest" );

$t = new vtimezone();
$t->setProperty( 'tzid', 'Europe/Bucharest' );
$t->setProperty( 'X-LIC-LOCATION', 'Europe/Bucharest' );

//$t->setProperty( 'Last-Modified', '20040110T032845Z' );

$d = new vtimezone( 'daylight' );
$d->setProperty( 'Dtstart', '19700329T030000' );
$d->setProperty( 'Rrule'
               , array( 'FREQ'       => "YEARLY"
                      , 'BYMONTH'    => 3 
                      , 'BYday'      => array( -1, 'DAY' => 'SU' )));
$d->setProperty( 'tzoffsetfrom', '+0200' );
$d->setProperty( 'tzoffsetto', '+0300' );
$d->setproperty( 'tzname', 'EET' );
$t->setComponent( $d );

$s = new vtimezone( 'standard' );
$s->setProperty( 'dtstart', '19701025T040000' );
$s->setProperty( 'rrule'
               , array( 'FREQ'       => "YEARLY"
                      , 'BYMONTH'    => 10 
                      , 'BYday'      => array( -1, 'DAY' => 'SU' )));
$s->setProperty( 'tzoffsetfrom', '+0300' );
$s->setProperty( 'tzoffsetto', '+0200' );
$s->setProperty( 'tzname', 'EET' );
$t->setComponent( $s );

$v->setComponent( $t ); 

foreach ($obj as $obj_event) {
	$date = explode('|', $obj_event['eventDate']);
	$e = new vevent();                             // initiate a new EVENT
	$e->setProperty( 'dtstart'
				   , array('year' => $date[0] , 'month' => $date[1] , 'day' => $date[2])
				   , array( 'VALUE' => 'DATE' ));  // 24 dec 2006 19.30
/*	$e->setProperty( 'dtend'
				   , array('year' => $date[0] , 'month' => $date[1] , 'day' => $date[2]+1)
				   , array( 'VALUE' => 'DATE' ));  // 24 dec 2006 19.30
*/	$e->setProperty( 'duration'
				   , array('day' => 1));                    // 3 hours

	$e->setProperty( 'summary'
				   , $obj_event['eventTitle']);    // describe the event
/*	$e->setProperty( 'description'
				   , $obj_event['eventTitle'] );    // describe the event
*/	$e->setProperty( 'class'
				   , 'PUBLIC');
//	$e->setProperty( 'rrule'
//				   , array( 'FREQ' => 'YEARLY' ));

	$e->setProperty( 'uid'
				   , $date[0] . str_pad($date[1], 2, "0", STR_PAD_LEFT) . str_pad($date[2], 2, "0", STR_PAD_LEFT) . "_" . hash('md5', $obj_event['eventTitle']) . '@andreineculau.com');
	$v->addComponent( $e );                        // add component to calendar
}
	
//$v->returnCalendar();                       // generate and redirect output to user browser
 $filename = $v->getConfig( 'filename' );
    $output   = $v->createCalendar();
    $filesize = strlen( $output );
    header( 'Content-Type: text/calendar' );
    header( 'Content-Length: '.$filesize );
    header( 'Content-Disposition: attachment; filename="'.$filename.'"' );
    header( 'Cache-Control: no-cache' );
    echo $output;
    die();
?>