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
$status_values              = MantisEnum::getValues( $status_enum_string );
$project_names              = project_names();
$specific_where             = helper_project_specific_where( $project_id );

$priority_enum_string       = lang_get( 'priority_enum_string' );
$severity_enum_string       = lang_get( 'severity_enum_string' );
// execute the query
// prepare state, priority, severity, number of notes and summary for all issues
$extra = $tmp_nt = array();

$query = "SELECT mbnt.bug_id, COUNT( * ) AS the_count
        FROM {bugnote} mbnt
        LEFT JOIN {bug} mbt ON mbnt.bug_id = mbt.id
        WHERE $specific_where
         AND mbt.date_submitted >= " . $start . "
        AND mbt.date_submitted <= " . $end . "
        GROUP BY mbnt.bug_id;
        ";
$result = db_query( $query );

foreach ( $result as $row ) { $tmp_nt[$row['bug_id']] = $row['the_count']; }

unset( $result );

$query = "SELECT id, status, priority, severity, date_submitted, summary
        FROM {bug} 
        WHERE $specific_where
        AND date_submitted >= " . $start . "
        AND date_submitted <= " . $end . "
        ";
$result = db_query( $query );

foreach ( $result as $row ) {
    $extra[$row['id']]['st'] = $row['status'];
    $extra[$row['id']]['pr'] = MantisEnum::getLabel( $priority_enum_string, $row['priority'] );
    $extra[$row['id']]['sv'] = MantisEnum::getLabel( $severity_enum_string, $row['severity'] );

    if ( isset( $tmp_nt[$row['id']] ) ) { $extra[$row['id']]['nt'] = $tmp_nt[$row['id']]; } else { $extra[$row['id']]['nt'] = 0; }

    $extra[$row['id']]['sm'] = $row['summary'];
}

unset( $result );


// get data
$issues_fetch_from_db = array();

$query = "
        (   SELECT mbt.id, count(*) AS the_count, mbt.status
            FROM {bug} mbt
            LEFT JOIN {bug_monitor} mmt
            ON mbt.id = mmt.bug_id
            WHERE $specific_where
            AND mmt.user_id IS NOT NULL
            AND mbt.date_submitted >= " . $start . "
            AND mbt.date_submitted <= " . $end . "
            GROUP BY mbt.id
        )
            UNION
        (   SELECT mbt.id, 0, mbt.status
            FROM {bug} mbt
            LEFT JOIN {bug_monitor} mmt
            ON mbt.id = mmt.bug_id
            WHERE $specific_where
            AND mmt.user_id IS NULL
            AND mbt.date_submitted >= " . $start . "
            AND mbt.date_submitted <= " . $end . "
        )
            ORDER BY the_count DESC, id ASC;
        ";
$result = db_query( $query );


ksort($project_names);
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
$content .= lang_get( 'plugin_Statistics_issue_summary' );
$content .= "|";
$content .= lang_get( 'summary' );
$content .= "|";
$content .= lang_get( 'status' );
$content .= "|";
$content .= lang_get( 'priority' )  ;
$content .= "|";
$content .= lang_get( 'severity' );
$content .= "|";
$content .= lang_get( 'plugin_Statistics_no_of_notes' );
$content .= "|";
$content .= lang_get( 'plugin_Statistics_no_of_monitors' ) ;
$content .= "\r\n";

// add the dataset
$i['open'] = $i['resolved'] = 0;
$sum['open'] = $sum['resolved'] = 0;
$nomonitors['open'] = $nomonitors['resolved'] = 0;
foreach ( $result as $row ) {
    if ( $row['status'] < $resolved_status_threshold ) {
        $i['open']++;
        $sum['open'] = $sum['open'] + $row['the_count'];
	} else {
		 $i['resolved']++;
        $sum['resolved'] = $sum['resolved'] + $row['the_count'];  
	}
    if( $row['the_count'] == 0 ) { $nomonitors['open']++; }
    if ( ( VS_PRIVATE == bug_get_field( $row['id'], 'view_state' ) ) && ( false == access_has_bug_level( $private_bug_threshold, $row['id'] ) ) ) { continue; }
	$content .= string_get_bug_view_link( $row['id'] ) ;
	$content .= "|";
	$content .= $extra[$row['id']]['sm'] ;
	$content .= "|";
	$content .= MantisEnum::getLabel( $status_enum_string, $extra[$row['id']]['st'] ) ;
	$content .= "|";
	$content .= $extra[$row['id']]['pr'] ;
	$content .= "|";
	$content .= $extra[$row['id']]['sv'] ;
	$content .= "|";
	$content .= $extra[$row['id']]['nt'] ;
	$content .= "|";
	$content .= number_format( $row['the_count'] ) ;
	$content .= "\r\n";
 }
# Dowload results as CSV
header('Content-type: text/enriched');
header("Content-Disposition: attachment; filename=Issues_By_Monitors.csv");
echo $content;
exit;
return;