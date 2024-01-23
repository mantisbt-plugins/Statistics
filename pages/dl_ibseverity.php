<?php
require_once ('statistics_api.php');
// get the paraeters
$start		=$_GET['start'];
$end		=$_GET['end'];
// retrieve various definitions
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );
$project_id                 = ALL_PROJECTS;
$specific_where             = helper_project_specific_where( $project_id );
$status_enum_string         = lang_get( 'status_enum_string' );
$severity_enum_string        = lang_get( 'severity_enum_string' );
$severity_values             = MantisEnum::getValues( $severity_enum_string );
$status_values              = MantisEnum::getValues( $status_enum_string );
$project_names              = project_names();
$specific_where             = helper_project_specific_where( $project_id );
// execute the query
$query = "SELECT count(*) as the_count, severity, status
        FROM {bug}
        WHERE $specific_where
        AND date_submitted >= " . $start . "
        AND date_submitted <= " . $end . "
        GROUP BY severity, status " ;
$result = db_query( $query );
foreach ( $result as $row ) {
    $data[$row['severity']][$row['status']] = $row['the_count'];
}
unset( $result );

// move the data into a new array for further hanbdling
$data_table = $data_table_totals = array();
foreach ( $data as $key => $val ) {
    if ( !isset( $data_table_totals[$key] ) ) { $data_table_totals[$key] = 0; }
    foreach ( $status_values as $k => $v ) {
        if ( isset( $data[$key][$v] ) ) {
			$data_table[$key][$v] = $data[$key][$v];
            $data_table_totals[$key] = $data_table_totals[$key] + $data[$key][$v];
        } else {
            $data_table[$key][$v] = 0;
		}
   }
}

arsort( $data_table_totals );
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
$content .= lang_get( 'severity' ) ;
		$content .= "|" ;
foreach ( $status_values as $key => $val ) {
    $content .= MantisEnum::getLabel( $status_enum_string, $val ) ;
	$content .= "|" ;	
}
$content .=  lang_get( 'plugin_Statistics_total' );
$content .= "\r\n";
// add the dataset
foreach ( $data_table_totals as $key => $val ) {
    if ( $val == 0  ) { 
//		break; 
	}
	$content .= MantisEnum::getLabel( $severity_enum_string, $key ) ;
	$content .= "|" ;
    foreach ( $status_values as $k => $v ) {
        $content .= number_format( $data_table[$key][$v] ) ;
		$content .= "|" ;		
    }
    $content .= number_format( $val ) ;
	$content .= "\r\n";
}
# Dowload results as CSV
header('Content-type: text/enriched');
header("Content-Disposition: attachment; filename=Issues_By_Severity.csv");
echo $content;
exit;
return;