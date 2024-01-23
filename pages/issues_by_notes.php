<?php
# Statistics - a statistics plugin for MantisBT
#

require_once 'statistics_api.php';

layout_page_header();
layout_page_begin( 'plugin.php?page=Statistics/start_page' );

$project_id                 = helper_get_current_project();
$specific_where             = helper_project_specific_where( $project_id );
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );
$private_bug_threshold      = config_get( 'private_bug_threshold' );
$status_enum_string         = lang_get( 'status_enum_string' );
$priority_enum_string       = lang_get( 'priority_enum_string' );
$severity_enum_string       = lang_get( 'severity_enum_string' );



$start  = strtotime( cleanDates( 'date-from', $dateFrom, 'begOfTimes' ) . " 00:00:00" );
$end = strtotime( cleanDates( 'date-to', $dateTo ) . " 23:59:59" );

// prepare state, priority, severity, number of notes and summary for all issues
$extra = array();

$query = "
        SELECT mbt.id, mbt.status, mbt.priority, mbt.severity, mbt.date_submitted, mbt.summary
        FROM {bug} mbt
        WHERE $specific_where
         AND mbt.date_submitted >= " . $start . "
        AND mbt.date_submitted <= " . $end . "
        GROUP BY mbt.id;
        ";
$result = db_query( $query );

foreach ( $result as $row ) {
    $extra[$row['id']]['st'] = $row['status'];
    $extra[$row['id']]['pr'] = MantisEnum::getLabel( $priority_enum_string, $row['priority'] );
    $extra[$row['id']]['sv'] = MantisEnum::getLabel( $severity_enum_string, $row['severity'] );
    $extra[$row['id']]['sm'] = $row['summary'];
}

unset( $result );


// get data
$issues_fetch_from_db = array();

$query = "
        (   SELECT mbt.id, count(*) AS the_count, mbt.status
            FROM {bug} mbt
            LEFT JOIN {bugnote} mbnt
            ON mbt.id = mbnt.bug_id
            WHERE $specific_where
            AND mbnt.id IS NOT NULL
            AND mbt.date_submitted >= " . $start . "
            AND mbt.date_submitted <= " . $end . "
            GROUP BY mbt.id
        )
            UNION
        (   SELECT mbt.id, 0, mbt.status
            FROM {bug} mbt
            LEFT JOIN {bugnote} mbnt
            ON mbt.id = mbnt.bug_id
            WHERE $specific_where
            AND mbnt.id IS NULL
            AND mbt.date_submitted >= " . $start . "
            AND mbt.date_submitted <= " . $end . "
        )
            ORDER BY the_count DESC, id ASC;
        ";
$result = db_query( $query );

// build tables headers
$data_table_print['open'] = "
    <table class='display' id='open' >
        <thead>
        <tr class='tblheader'>
            <td width='100%'>" . lang_get( 'plugin_Statistics_issue_summary' ) . "&nbsp;</td>
            <td class='dt-right'>" . lang_get( 'status' ) . "&nbsp;</td>
            <td class='dt-right'>" . lang_get( 'priority' ) . "&nbsp;</td>
            <td class='dt-right'>" . lang_get( 'severity' ) . "&nbsp;</td>
            <td class='dt-right nowrap'>" . lang_get( 'plugin_Statistics_no_of_notes' ) . "&nbsp;</td>
        </tr>
        </thead>
        <tbody>";

$data_table_print['resolved'] = "
    <table class='display' id='resolved' '>
        <thead>
        <tr class='tblheader'>
            <td width='100%'>" . lang_get( 'plugin_Statistics_issue_summary' ) . "&nbsp;</td>
            <td class='dt-right'>" . lang_get( 'status' ) . "&nbsp;</td>
            <td class='dt-right'>" . lang_get( 'priority' ) . "&nbsp;</td>
            <td class='dt-right'>" . lang_get( 'severity' ) . "&nbsp;</td>
            <td class='dt-right nowrap'>" . lang_get( 'plugin_Statistics_no_of_notes' ) . "&nbsp;</td>
        </tr>
        </thead>
        <tbody>";


// populate html tables
$i['open'] = $i['resolved'] = 0;
$sum['open'] = $sum['resolved'] = 0;
$nonotes['open'] = $nonotes['resolved'] = 0;

foreach ( $result as $row ) {

    if ( $row['status'] < $resolved_status_threshold ) {

        $i['open']++;
        $sum['open'] = $sum['open'] + $row['the_count'];
        
        if( $row['the_count'] == 0 ) { $nonotes['open']++; }

        if ( ( VS_PRIVATE == bug_get_field( $row['id'], 'view_state' ) ) && ( false == access_has_bug_level( $private_bug_threshold, $row['id'] ) ) ) { continue; }
        if ( $maxResultsInTables != 0 and $i['open'] > $maxResultsInTables ) { continue; }

        $data_table_print['open'] .= "
        <tr>
            <td>" . string_get_bug_view_link( $row['id'] ) . " - " . $extra[$row['id']]['sm'] . "&nbsp;</td>
            <td class='dt-right' nowrap> " . MantisEnum::getLabel( $status_enum_string, $extra[$row['id']]['st'] ) . "&nbsp;</td>
            <td class='dt-right' nowrap> " . $extra[$row['id']]['pr'] . "&nbsp;</td>
            <td class='dt-right' nowrap> " . $extra[$row['id']]['sv'] . "&nbsp;</td>
            <td class='dt-right' nowrap>" . number_format( $row['the_count'] ) . "&nbsp;</td>
        </tr>
        ";

    } else {

        $i['resolved']++;
        $sum['resolved'] = $sum['resolved'] + $row['the_count'];

        if( $row['the_count'] == 0 ) { $nonotes['resolved']++; }

        if ( ( VS_PRIVATE == bug_get_field( $row['id'], 'view_state' ) ) && ( false == access_has_bug_level( $private_bug_threshold, $row['id'] ) ) ) { continue; }
        if ( $maxResultsInTables != 0 and $i['resolved'] > $maxResultsInTables ) { continue; }

        $data_table_print['resolved'] .= "
        <tr>
            <td>" . string_get_bug_view_link( $row['id'] ) . " - " . $extra[$row['id']]['sm'] . "&nbsp;</td>
            <td class='dt-right' nowrap>" . MantisEnum::getLabel( $status_enum_string, $extra[$row['id']]['st'] ) . "&nbsp;</td>
            <td class='dt-right' nowrap>" . $extra[$row['id']]['pr'] . "&nbsp;</td>
            <td class='dt-right' nowrap>" . $extra[$row['id']]['sv'] . "&nbsp;</td>
            <td class='dt-right' nowrap>" . number_format( $row['the_count'] ) . "&nbsp;</td>
        </tr>
        ";
        
    }

}

$data_table_print['open']       .= "</tbody></table>";
$data_table_print['resolved']   .= "</tbody></table>";

unset ( $result );

// summary table
$i['all_states'] = $i['open'] + $i['resolved'];

if ( $i['open'] != 0 ) { $avg['open'] = round( $sum['open'] / $i['open'], 2 ); } else { $avg['open'] = 0; }
if ( $i['resolved'] != 0 ) { $avg['resolved'] = round( $sum['resolved'] / $i['resolved'], 2 ); } else { $avg['resolved'] = 0; }
if ( $i['all_states'] != 0 ) { $avg['all_states'] = round( ( $sum['open'] + $sum['resolved'] ) / $i['all_states'], 2 ); } else { $avg['all_states'] = 0; }

$nonotes['all_states'] = $nonotes['open'] + $nonotes['resolved'];


$summary_table_print = "

    <table class='display' id='summary' '>
        <thead>
        <tr class='tblheader'>
            <td width='100%'>" . lang_get( 'plugin_Statistics_summary_table' ) . "</td>
            <td class='dt-right nowrap'>" . lang_get( 'plugin_Statistics_in_open_issues' ) . "&nbsp;</td>
            <td class='dt-right nowrap'>" . lang_get( 'plugin_Statistics_in_resolved_iss' ) . "&nbsp;</td>
            <td class='dt-right nowrap'>" . lang_get( 'plugin_Statistics_in_all_issues' ) . "&nbsp;</td>
        </tr>
        </thead>
        <tbody>
            <tr>
                <td>" . lang_get( 'plugin_Statistics_average_nt_periss' ) . "</td>
                <td class='dt-right'>" . $avg['open'] . "</td>
                <td class='dt-right'>" . $avg['resolved'] . "</td>
                <td class='dt-right'>" . $avg['all_states'] . "</td>
            </tr>
            <tr>
                <td>" . lang_get( 'plugin_Statistics_no_iss_without_nt' ) . "</td>
                <td class='dt-right'>" . number_format( $nonotes['open'] ) . "</td>
                <td class='dt-right'>" . number_format( $nonotes['resolved'] ) . "</td>
                <td class='dt-right'>" . number_format( $nonotes['all_states'] ) . "</td>
            </tr>
        </tbody>
    </table>
    ";

?>

<script type='text/javascript' src="<?php echo plugin_page( 'csp_support&r=ibnot' ); ?>"></script>
                
<div id="wrapper">

        <div id="logo">
            <a href="https://github.com/mantisbt-plugins/Statistics" target="_blank"><img src="<?php echo plugin_file( 'statistics.png' ); ?>" width='70' height='70' alt='Statistics' /></a>
        </div>

        <?php echo $whichReport; ?>

        <p class='space20Before' />

        <div id="titleText">
            <div id="scope"><?php echo lang_get( 'plugin_Statistics_project' ); ?>: <?php echo project_get_name( $project_id ); ?></div><div id="sup"><?php if ( $project_id == ALL_PROJECTS ) { echo "<sup>&dagger;</sup>"; } ?></div>
        </div>

        <p class="clear" />


        <div id="filter">
            <strong><?php echo lang_get( 'plugin_Statistics_timeframe' ); ?></strong>

            <form method="get">
                <input type="hidden" name="page" value="Statistics/issues_by_notes" />
                <?php echo form_security_field( 'date_picker' ) ?>

                <div>
                    <div>
                        <input type="text" name="date-from" id="from" value="<?php echo cleanDates('date-from', $dateFrom); ?>" />
                        -
                        <input type="text" name="date-to" id="to"  value="<?php echo cleanDates('date-to', $dateTo); ?>" />
                    </div>
                </div>
                <div>
                    <input type="submit" id="displaysubmit" value=<?php echo lang_get( 'plugin_Statistics_display' ); ?> class="button" />
                </div>
           <div>
 					<a href="plugin.php?page=Statistics/dl_ibnotes.php&start=<?php echo $start ?>&end=<?php echo $end ?>" class="btn" ><?php echo lang_get( 'plugin_Statistics_download' ); ?></a>
                </div>    
 </form>
        </div>


        <p class="space40Before" />
        <?php echo $summary_table_print; ?>

        <p class="space40Before" />
        <strong>&raquo; <?php echo lang_get( 'plugin_Statistics_open_issues' ); ?></strong>
        <p />
        <?php echo $data_table_print['open']; ?>

        <p class="space40Before" />
        <strong>&raquo; <?php echo lang_get( 'plugin_Statistics_resolved_issues' ); ?></strong>
        <p />
        <?php echo $data_table_print['resolved']; ?>

        <p class="space40Before" />

        <?php if ( $project_id == ALL_PROJECTS ) { echo "<p />&dagger; " . lang_get( 'plugin_Statistics_priv_proj_skip' ) . "<br />"; } ?>
        
        <?php if ( $maxResultsInTables ) { echo "<strong>&raquo;</strong> "; printf( lang_get( 'plugin_Statistics_tables_maxdisp' ), $maxResultsInTables ); } ?>

        <?php if ( $showRuntime == 1 ) { printf( "<p class='graycolor'>" . lang_get( 'plugin_Statistics_runtime_string' ) . "</p>", round(microtime(true) - $starttime, 5) ); } ?>

</div>

        <?php layout_page_end();