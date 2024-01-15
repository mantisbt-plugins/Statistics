<?php
# Statistics - a statistics plugin for MantisBT
#

require_once 'statistics_api.php';

layout_page_header();
layout_page_begin( 'plugin.php?page=Statistics/start_page' );

$project_id                 = helper_get_current_project();
$specific_where             = helper_project_specific_where( $project_id );
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );

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


$start  = strtotime( cleanDates( 'date-from', $dateFrom ) . " 00:00:00" );
$end = strtotime( cleanDates( 'date-to', $dateTo ) . " 23:59:59" );

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

// making granularity drop-down
rsort( $granularity_items );

$granularityOptionsDropDown = "<strong>" . lang_get( 'plugin_Statistics_granularity' ) . "</strong>&nbsp;&nbsp;<select name='granularity' id='granularity'>";

foreach( $granularities as $key => $val ) {
    if ( $selectedGranularity == $key ) { $selectedFormValue = " selected "; } else { $selectedFormValue = ''; }
    $granularityOptionsDropDown .= "<option " . $selectedFormValue . " value='" . $key . "'>" . $val . "</option>";
}

$granularityOptionsDropDown .= "</select>";


// build table header
$data_table_print = "
    <thead>
    <tr class='tblheader'>
        <td width='100%'>" . lang_get( 'plugin_Statistics_date' ) . "</td>
        <td class='dt-right'>" . lang_get( 'opened' ) . "&nbsp;</td>
        <td class='dt-right'>" . lang_get( 'resolved' ) . "&nbsp;</td>
        <td class='dt-right'>" . lang_get( 'balance' ) . "&nbsp;</td>
    </tr>
    </thead>";


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

    $data_table_print .= "<tr><td>" . $show_date . "</td>";
	$graph_date	.= $show_date;

	if ( isset( $db_data['opened'] ) and array_key_exists( $val, $db_data['opened'] ) ) { $show_count = $db_data['opened'][$val]; } else { $show_count = 0; }
	$data_table_print .= "<td class='dt-right'>" . $show_count . "</td>";
	$graph_open	.= $show_count;
	
    if ( isset( $db_data['resolved'] ) and array_key_exists( $val, $db_data['resolved'] ) ) { $show_count = $db_data['resolved'][$val]; } else { $show_count = 0; }
    $data_table_print .= "<td class='dt-right'>" . $show_count . "</td>";
	$graph_resolved .= $show_count;
	
	$balance = @$db_data['opened'][$val] - @$db_data['resolved'][$val];
	if ( $balance > 0 ) { $style = "negative"; $plus = '+'; } else { $style = "positive"; $plus = ''; }

	$data_table_print .=  "<td class='dt-right " . $style . "'>" . $plus . $balance . "</td></tr>";

}

// chart
$chart_data = array ('categories' => '', 'opened' => '', 'resolved' => '');
$granularity_items = array_reverse( $granularity_items );

foreach ( $granularity_items as $key => $val ) {

    if ( $selectedGranularity == 2 )      { $show_date = substr($val, 0, 4) . " " . lang_get('plugin_Statistics_week_short') . " " . substr($val, 4); } // Weekly
    elseif ( $selectedGranularity == 3 )  { $show_date = substr($val, 0, 4) . "." . substr($val, 4); } // Monthly
    else { $show_date = $val; }

    $chart_data['categories'] .= "<category label='" . $show_date . "' />";
    if ( isset( $db_data['opened'] ) and array_key_exists( $val, $db_data['opened'] ) ) { $show_count = $db_data['opened'][$val]; } else { $show_count = 0; }
    $chart_data['opened'] .= "<set tooltext='" . htmlspecialchars( lang_get( 'opened' ), ENT_QUOTES ) . ": " . number_format( $show_count ) . " [" . $show_date . "]' value='" . $show_count . "' />";
    if ( isset( $db_data['resolved'] ) and array_key_exists( $val, $db_data['resolved'] ) ) { $show_count = $db_data['resolved'][$val]; } else { $show_count = 0; }
    $chart_data['resolved'] .= "<set tooltext='" . htmlspecialchars( lang_get( 'resolved' ), ENT_QUOTES ) . ": " . number_format( $show_count ) . " [" . $show_date . "]' value='" . $show_count . "' />";
}

$chart_data_print  = "<categories>" . $chart_data['categories'] . "</categories>";
$chart_data_print .= "<dataset seriesName='" . htmlspecialchars( lang_get( 'opened' ), ENT_QUOTES ) . "' color='c80130'>" . $chart_data['opened'] . "</dataset>";
$chart_data_print .= "<dataset seriesName='" . htmlspecialchars( lang_get( 'resolved' ), ENT_QUOTES ) . "' color='009933'>" . $chart_data['resolved'] . "</dataset>";

?>


<script type='text/javascript' src="<?php echo plugin_page( 'csp_support&r=tbope' ); ?>"></script>

                
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
            <strong><?php echo lang_get( 'plugin_Statistics_timeframe_op_re' ); ?></strong>

            <form method="get">
                <input type="hidden" name="page" value="Statistics/trends_by_open_resolved" />
                <?php echo form_security_field( 'date_picker' ) ?>

                <div>
                    <div>
                        <input type="text" name="date-from" id="from" value="<?php echo cleanDates('date-from', $dateFrom); ?>" />
                        -
                        <input type="text" name="date-to" id="to"  value="<?php echo cleanDates('date-to', $dateTo); ?>" />
                    </div>
                    <p />
                    <div id="options">
                        <?php echo $granularityOptionsDropDown; ?>
                    </div>
                </div>
                <div>
                    <input type="submit" id="displaysubmit" value=<?php echo lang_get( 'plugin_Statistics_display' ); ?> class="button" />
                </div>

                <p class="space20Before" />

                <input type="radio" class="resolution_date_options" name="resolution_date_options" id="op1" <?php if ( $resolved_option == 1 ) { echo "checked"; } ?> value="1">
                <label for="op1" class="inl"><?php echo lang_get( 'plugin_Statistics_res_radio_opt1' ); ?></label>
                <p />
                <input type="radio" class="resolution_date_options" name="resolution_date_options" id="op2" <?php if ( $resolved_option == 2 ) { echo "checked"; } ?> value="2">
                <label for="op2" class="inl"><?php echo lang_get( 'plugin_Statistics_res_radio_opt2' ); ?></label>

            </form>
        </div>


<table>
<tr>
<td><img src="plugin.php?page=Statistics/open-resolved-t-graph.php&start=<?php echo $start ?>&end=<?php echo $end ?>&dates=<?php echo $graph_date ?>&open=<?php echo $graph_open ?>&resolved=<?php echo $graph_resolved ?> "></td>
<td> <?php echo $chart_data_print ?></td>
</tr>
<tr><td align='center'> <p style="color: green"><strong>Opened Issues</strong></p><p style="color: blue"><strong>Resolved Issues</strong></p></td></tr>
</td>
</table>


        <p class="space40Before" />
        <strong>&raquo; <?php echo lang_get( 'plugin_Statistics_open_vs_res' ); ?></strong>
        <p />
        <table>
		<tr><td> <strong>&raquo; <?php echo lang_get( 'plugin_Statistics_open_issues' ); ?></strong></td></tr>
		</table><table>
		<?php echo $data_table_print; ?>
		</table>




        <p class="space40Before" />

        <?php if ( $project_id == ALL_PROJECTS ) { echo "<p />&dagger; " . lang_get( 'plugin_Statistics_priv_proj_skip' ) . "<br />"; } ?>
        
        <?php if ( $showRuntime == 1 ) { printf( "<p class='graycolor'>" . lang_get( 'plugin_Statistics_runtime_string' ) . "</p>", round(microtime(true) - $starttime, 5) ); } ?>

</div>

        <?php layout_page_end();