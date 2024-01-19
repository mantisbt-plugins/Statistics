<?php
# Statistics - a statistics plugin for MantisBT
#

require_once 'statistics_api.php';
$t_show_all = plugin_config_get('show_all');
$t_size 					= plugin_config_get('size');
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );

layout_page_header();
layout_page_begin( 'plugin.php?page=Statistics/start_page' );

$project_id                 = helper_get_current_project();
$specific_where             = helper_project_specific_where( $project_id );
$status_enum_string         = lang_get( 'status_enum_string' );
$status_values              = MantisEnum::getValues( $status_enum_string );
$count_states               = count_states();

$start	= strtotime( cleanDates( 'date-from', $dateFrom, 'begOfTimes' ) . " 00:00:00" );
$end	= strtotime( cleanDates( 'date-to', $dateTo ) . " 23:59:59" );
if ( isset ($_REQUEST['size'] ) ) {
	$t_size= strtoupper ( $_REQUEST['size'] ) ;
}

// get data
$issues_fetch_from_db = array();

$query = "
        SELECT count(*) as the_count, mct.name, mbt.status
        FROM {bug} mbt
        LEFT JOIN {category} mct
        ON mbt.category_id = mct.id
        WHERE " . str_replace( 'project_id', 'mbt.project_id', $specific_where ) . "
        AND mbt.date_submitted >= " . $start . "
        AND mbt.date_submitted <= " . $end . "
        GROUP BY mct.name, mbt.status
        ";
$result = db_query( $query );

foreach ( $result as $row ) {
    $tmp = string_html_specialchars( $row['name'] );
    $issues_fetch_from_db[$tmp][$row['status']] = $row['the_count'];
}

unset ( $result );


// build tables
function tables ( $type ) {

	global $resolved_status_threshold, $status_values, $status_enum_string, $issues_fetch_from_db, $maxResultsInTables;

	$data_table = $data_table_totals = array();

	foreach ( $issues_fetch_from_db as $key => $val ) {

        if ( !isset( $data_table_totals[$key] ) ) { $data_table_totals[$key] = 0; }

        foreach ( $status_values as $k => $v ) {
            if ( ( $v < $resolved_status_threshold and $type == "open" ) || ( $v >= $resolved_status_threshold and $type == "resolved" ) ) {
                if ( isset( $issues_fetch_from_db[$key][$v] ) ) {
            		$data_table[$key][$v] = $issues_fetch_from_db[$key][$v];
                    $data_table_totals[$key] = $data_table_totals[$key] + $issues_fetch_from_db[$key][$v];
                } else {
                    $data_table[$key][$v] = 0;
                }
            }
        }
    }


    // build table header
    $data_table_print = "
        <thead>
        <tr class='tblheader'>
            <td width='100%'>" . lang_get( 'category' ) . "</td>";

    foreach ( $status_values as $key => $val ) {
        if ( ( $type == 'open' and $val >= $resolved_status_threshold ) || ( $type == 'resolved' and $val < $resolved_status_threshold ) ) { continue; }
        $data_table_print .= "<td class='dt-right nowrap'>" . MantisEnum::getLabel( $status_enum_string, $val ) . "&nbsp;</td>";
    }

    $data_table_print .= "
            <td class='dt-right'>" . lang_get( 'plugin_Statistics_total' ) . "</td>
        </tr>
        </thead>
       ";


    // build table body
    $i = 0;

    arsort( $data_table_totals );

    foreach ( $data_table_totals as $key => $val ) {

        $i++;
        if ( $val == 0 || ( $i > $maxResultsInTables and $maxResultsInTables != 0 ) ) { break; }

		$data_table_print .= "
        <tr>
            <td>" . $key . "</td>";

        foreach ( $status_values as $k => $v ) {
            if ( ( $type == 'open' and $v >= $resolved_status_threshold ) || ( $type == 'resolved' and $v < $resolved_status_threshold ) ) { continue; }
            $data_table_print .= "
            <td class='dt-right'>" . number_format( $data_table[$key][$v] ) . "</td>";
        }
        $data_table_print .= "
            <td class='dt-right'>" . number_format( $val ) . "</td>
        </tr>";
	}


// returns
 return $data_table_print; 

}

?>


<script type='text/javascript' src="<?php echo plugin_page( 'csp_support&r=ibcat' ); ?>"></script>
                
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
                <input type="hidden" name="page" value="Statistics/issues_by_category" />
                <?php echo form_security_field( 'date_picker' ) ?>

                <div>
                    <div>
                        <input type="text" name="date-from" id="from" value="<?php echo cleanDates('date-from', $dateFrom); ?>" />
                        -
                        <input type="text" name="date-to" id="to"  value="<?php echo cleanDates('date-to', $dateTo); ?>" />
                    </div>
                </div>
                                <div>
				<strong><?php echo plugin_lang_get( 'size' ); ?></strong>
				</div>
				<div>
                    <div>
                        <input name="size" type="text" size=1 maxlength=1 value="<?php echo $t_size ; ?>" />
                    </div>
                </div>
				<br>  
				<div>
                    <input type="submit" id="displaysubmit" value=<?php echo lang_get( 'plugin_Statistics_display' ); ?> class="button" />
                </div>
            </form>
        </div>

<table>
<tr>
<?php 
if ( ON == $t_show_all ) {
?>
	<td><img src="plugin.php?page=Statistics/category-graph.php&start=<?php echo $start ?>&end=<?php echo $end ?>&set=0&size=<?php echo $t_size ?> "></td>
<?php
}
?>
<td><img src="plugin.php?page=Statistics/category-graph.php&start=<?php echo $start ?>&end=<?php echo $end ?>&set=1&size=<?php echo $t_size ?> "></td>
<td><img src="plugin.php?page=Statistics/category-graph.php&start=<?php echo $start ?>&end=<?php echo $end ?>&set=2&size=<?php echo $t_size ?> "></td>
</tr>
</table>

<table>
<tr><td> <strong>&raquo; <?php echo lang_get( 'plugin_Statistics_open_issues' ); ?></strong></td></tr>
</table><table>
<?php echo tables( "open" ); ?>
</table>

<table>
<tr><td><strong>&raquo; <?php echo lang_get( 'plugin_Statistics_resolved_issues' ); ?></strong></td></tr>
</table><table>
<?php echo tables( "resolved" ); ?>
</table>

<p class="space40Before" />
<?php if ( $project_id == ALL_PROJECTS ) { echo "<p />&dagger; " . lang_get( 'plugin_Statistics_priv_proj_skip' ) . "<br />"; } ?>
<strong>&raquo;</strong> <?php printf( lang_get( 'plugin_Statistics_charts_maxdisp' ), MAX_LINES_IN_BAR_CHARTS ); ?>
<?php if ( $showRuntime == 1 ) { printf( "<p class='graycolor'>" . lang_get( 'plugin_Statistics_runtime_string' ) . "</p>", round(microtime(true) - $starttime, 5) ); } ?>
</div>
<?php 
layout_page_end();