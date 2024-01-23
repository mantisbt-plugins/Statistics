<?php
# Statistics - a statistics plugin for MantisBT
#
$start		 =$_GET['start'];
$end		 =$_GET['end'];

require_once 'statistics_api.php';
$t_size 					= plugin_config_get('size');
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );


$project_id                 = helper_get_current_project();
$specific_where             = helper_project_specific_where( helper_get_current_project() );
$project_names= project_names();

// 'resolved' options
$resolved_options   = array( 1, 2 );
$resolved_option    = $resolved_options[0];

if ( isset( $_GET['resolution_date_options'] ) and !empty( $_GET['resolution_date_options'] ) ) {
    foreach ( $resolved_options as $k => $v) {
        if ( $v == strip_tags( $_GET['resolution_date_options'] ) ) {
            $resolved_option = $v;
            $_SESSION['resolved_option'] = $v;
            break;
        }
    }
} elseif ( isset( $_SESSION['resolved_option'] ) and !empty( $_SESSION['resolved_option'] ) ) {
    foreach ( $resolved_options as $k => $v) {
        if ( $v == strip_tags( $_SESSION['resolved_option'] ) ) {
            $resolved_option = $v;
            break;
        }
    }
} else { $resolved_option = $resolved_options[0]; }

// [ daily | weekly | monthly | yearly ] granularities
if ( $selectedGranularity == 2 ) {          // Weekly
    $date_format    = 'oW';
    $incr_str       = ' weeks';
} elseif ( $selectedGranularity == 3 ) {    // Monthly
    $date_format = 'Ym';
    $incr_str       = ' months';
} elseif ( $selectedGranularity == 4 ) {    // Yearly
    $date_format = 'Y';
    $incr_str       = ' years';
} else {                                    // If granilarity is Daily
    $date_format = 'Y-m-d';
    $incr_str       = ' days';
}

// Preparing data array
$i = 0;

$incrTimestamp = $start;

while ( $incrTimestamp <= $end ) {
    $i++;
    $granularity_items[] = date( $date_format, $incrTimestamp );
    $incrTimestamp = strtotime( date( "Ymd", $start ) . " + " . $i . $incr_str); // not "o-m-d"?
}

$dateConditionForResolved = " AND h.date_modified >= " . $start . " AND h.date_modified <= " . $end ;
if ( $resolved_option == 1 ) {
    $dateConditionForResolved = " AND h.date_modified >= " . $start . " AND h.date_modified <= " . $end . " AND mbt.date_submitted >= " . $start . " AND mbt.date_submitted <= " . $end;
}

$query = "SELECT date_submitted as the_date
    FROM {bug}
    WHERE date_submitted >= " . $start . "
    AND date_submitted <= " . $end . "
    AND $specific_where
    ";
$result = db_query( $query );
$t_count=db_num_rows($result);

if($t_count > 0) {
    foreach ( $result as $row ) {
        $the_date = date( $date_format, $row['the_date'] );
        if ( isset( $db_data['opened'][$the_date] ) ) {
            $db_data['opened'][$the_date]++;
        } else {
            $db_data['opened'][$the_date] = 1;
        }
    }
    $totals['opened'] = $t_count;
} else { $db_data['opened'] = array(); $totals['opened'] = 0; }

unset ( $result );

$query = "SELECT max(h.date_modified) as the_date, mbt.id
    FROM {bug} mbt
    LEFT JOIN {bug_history} h
    ON mbt.id = h.bug_id
    AND h.type = " . NORMAL_TYPE . "
    AND h.field_name = 'status'
    WHERE mbt.status >= $resolved_status_threshold
    AND h.old_value < '$resolved_status_threshold'
    AND h.new_value >= '$resolved_status_threshold'
    $dateConditionForResolved
    AND $specific_where
    GROUP BY mbt.id
    ";
$result = db_query( $query );
$t_count=db_num_rows($result);
if ( $t_count > 0 ) {
    foreach ( $result as $row ) {
        $the_date = date( $date_format, $row['the_date'] );
        if ( isset( $db_data['resolved'][$the_date] ) ) {
            $db_data['resolved'][$the_date]++;
        } else {
            $db_data['resolved'][$the_date] = 1;
        }
    }
    $totals['resolved'] = $t_count;
} else { $db_data['resolved'] = array(); $totals['resolved'] = 0; }

unset ( $result );

ksort( $project_names );
$content ="";
// set limits
foreach ($project_names as $x => $y) {
  $content .= $x;
  $content .= "|";
  $content .= $y;
  $content .= "\r\n";
}
$content .= "=================";
$content .= "\r\n";
$content .= $specific_where;
$content .= "\r\n";
$content .=  date( config_get( 'short_date_format' ), $start  ) . "-" . date( config_get( 'short_date_format' ), $end ) ;
$content .= "\r\n";
$content .= "=================";
$content .= "\r\n";
// build header row
// build table header
$content .= lang_get( 'plugin_Statistics_date' );
$content .= "|";
$content .= lang_get( 'opened' );
$content .= "|";
$content .= lang_get( 'resolved' );
$content .= "|";
$content .= lang_get( 'balance' );
$content .= "\r\n";

// build table body & graph dataset
$i = 0;
$graph_date		= "";
$graph_open		= "";
$graph_resolved	= "";
$first			= 0;

foreach ( $granularity_items as $key => $val ) {
	if ($first>0){
		$graph_date 	.=",";
		$graph_open 	.=",";
		$graph_resolved .=",";
	}
    $i++;
	$first++;

	if ( $selectedGranularity == 2 )      { $show_date = substr($val, 0, 4) . " " . lang_get('plugin_Statistics_week_short') . " " . substr($val, 4); } // Weekly
    elseif ( $selectedGranularity == 3 )  { $show_date = substr($val, 0, 4) . "." . substr($val, 4); } // Monthly
    else { $show_date = $val; }

    $content .= $show_date ;
	$content .= "|";
	
	if ( isset( $db_data['opened'] ) and array_key_exists( $val, $db_data['opened'] ) ) { $show_count = $db_data['opened'][$val]; } else { $show_count = 0; }
	$content .= $show_count ;
	$content .= "|";
		
    if ( isset( $db_data['resolved'] ) and array_key_exists( $val, $db_data['resolved'] ) ) { $show_count = $db_data['resolved'][$val]; } else { $show_count = 0; }
    $content .= $show_count ;
	$content .= "|";
	
	$balance = @$db_data['opened'][$val] - @$db_data['resolved'][$val];
	if ( $balance > 0 ) { $style = "negative"; $plus = '+'; } else { $style = "positive"; $plus = ''; }

	$content .= $balance ;
	$content .= "\r\n";

}

# Dowload results as CSV
header('Content-type: text/enriched');
header("Content-Disposition: attachment; filename=Trend_by_Open_Resolved.csv");
echo $content;
exit;
return;