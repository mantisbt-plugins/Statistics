<?php
require_once ('statistics_api.php');
// get the paraeters
$start		=$_GET['start'];
$end		=$_GET['end'];
# create a unique filename for download
$filename = uniqid(mt_rand(), true) . '.csv';
// retrieve various definitions
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );
$project_id                 = ALL_PROJECTS;
$specific_where             = helper_project_specific_where( $project_id );
$status_enum_string         = lang_get( 'status_enum_string' );
$status_values              = MantisEnum::getValues( $status_enum_string );
$project_names              = project_names();
$specific_where             = helper_project_specific_where( $project_id );
// execute the query
$query = "SELECT count(*) as the_count, status
		FROM {bug}
		WHERE $specific_where
        AND date_submitted >= " . $start . "
        AND date_submitted <= " . $end . "
		GROUP BY status
		ORDER BY the_count DESC" ;
$result = db_query( $query );
foreach ( $result as $row ) {
     $issues_fetch_from_db[$row['status']] = $row['the_count'];
}
unset( $result );
// move the data into a new array for further hanbdling
$data_table = $data_table_totals = array();
foreach ( $project_names as $key => $val ) {
	$data_table_totals[$key] = 0;
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
foreach ( $project_names as $x => $y) {
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
$content .= lang_get( 'status' ) ;
  $content .= "|";
$content .= lang_get( 'plugin_Statistics_total' );
$content .= "\r\n";
// add the dataset
$i = 0;
foreach ( $issues_fetch_from_db as $key => $val ) {
    $i++;
	$content .=  MantisEnum::getLabel( $status_enum_string, $key ) ;
	  $content .= "|";
	$content .= number_format( $val );
	$content .= "\r\n";

    }

# Dowload results as CSV
header('Content-type: text/enriched');
header("Content-Disposition: attachment; filename=Issues_By_Status.csv");
echo $content;
exit;
return;