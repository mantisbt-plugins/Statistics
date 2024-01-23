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
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );
$status_enum_string         = lang_get( 'status_enum_string' );
$status_values              = MantisEnum::getValues( $status_enum_string );
$count_states               = count_states();


// if show_realname config is OFF then use usernames if user has enough permission for it
if ( access_has_global_level( config_get( 'view_handler_threshold' ) ) ) {

    if ( config_get( 'show_realname' ) != 'OFF' ) {
        $user_names = user_names( 'realname' );
    } else {
        $user_names = user_names( 'username' );
    }

} else {
    access_ensure_global_level( config_get( 'view_handler_threshold' ) );
}


// custom fields prep work: permissions and cleansing
$pageOptions = array(
    1 => lang_get( 'plugin_Statistics_for_ppl_cur_handlers' ),
    2 => lang_get( 'plugin_Statistics_for_ppl_all_handlers' )
);

$selectedPageOption = 1;

// dropdown option prep: session and cleansing
if ( isset( $_GET['subType'] ) and !empty( $_GET['subType'] ) ) {
    foreach ( $pageOptions as $k => $v) {
        if ( $k == strip_tags( $_GET['subType'] ) ) {
            $selectedPageOption = $k;
            $_SESSION['subType'] = $k;
            break;
        }
    }
} elseif ( isset( $_SESSION['subType'] ) and !empty( $_SESSION['subType'] ) ) {
    foreach ( $pageOptions as $k => $v) {
        if ( $k == strip_tags( $_SESSION['subType'] ) ) {
            $selectedPageOption = $k;
            break;
        }
    }
}

// drop-down prep
$pageOptionsDropDown = "<strong>" . lang_get( 'plugin_Statistics_for' ) . "</strong>&nbsp;&nbsp;<select name='subType' id='subType'>";

foreach ( $pageOptions as $key => $val ) {
    $selected = "";
    if ( $selectedPageOption == $key ) { $selected = " selected "; }
    $pageOptionsDropDown .= "<option value='" . $key . "'" . $selected . ">" . $val . "</option>";
}

$pageOptionsDropDown .= "</select>";


$start  = strtotime( cleanDates( 'date-from', $dateFrom ) . " 00:00:00" );
$end = strtotime( cleanDates( 'date-to', $dateTo ) . " 23:59:59" );
if ( isset ($_REQUEST['size'] ) ) {
	$t_size= strtoupper ( $_REQUEST['size'] ) ;
}

// get data
$issues_fetch_from_db = array();

if ( $selectedPageOption == 1 ) {
    $query = "SELECT mut.id, count(*) AS the_count, mbt.status
            FROM {bug} mbt
            LEFT JOIN {user} mut ON mbt.handler_id = mut.id
            WHERE $specific_where
            AND mut.id is not NULL
			AND mbt.date_submitted >= " . $start . "
			AND mbt.date_submitted <= " . $end . "
            GROUP BY mut.id, mbt.status
            ORDER BY the_count DESC
            ";
} else {
    $query = "SELECT mbht.new_value AS id, count(*) as the_count, mbt.status
            FROM {bug_history} mbht
            LEFT JOIN {user} mut ON mbht.new_value = CAST(mut.id AS CHAR)
            LEFT JOIN {bug} mbt ON mbht.bug_id = mbt.id
            WHERE $specific_where
            AND field_name = 'handler_id'
            AND mut.id IS NOT NULL
            AND mbt.date_submitted >= " . $start . "
            AND mbt.date_submitted <= " . $end . "
            GROUP BY mbht.new_value, mbt.status
            ";
}

$result = db_query( $query );

foreach ( $result as $row ) {
    $issues_fetch_from_db[$row['id']][$row['status']] = $row['the_count'];
}

unset ( $result );

// build tables and charts
function tables ( $type ) {

	global $resolved_status_threshold, $status_values, $status_enum_string, $issues_fetch_from_db, $user_names, $count_states, $maxResultsInTables;

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
            <td width='100%'>" . lang_get( 'plugin_Statistics_handler_name' ) . "</td>";

    foreach ( $status_values as $key => $val ) {
        if ( ( $type == 'open' and $val >= $resolved_status_threshold ) || ( $type == 'resolved' and $val < $resolved_status_threshold ) ) { continue; }
        $data_table_print .= "<td class='dt-right nowrap'>" . MantisEnum::getLabel( $status_enum_string, $val ) . "&nbsp;</td>";
    }

    $data_table_print .= "
            <td class='dt-right'>" . lang_get( 'plugin_Statistics_total' ) . "</td>
        </tr>
        </thead>
        <tbody>";


    // build table body
    $i = 0;

    arsort( $data_table_totals );

    foreach ( $data_table_totals as $key => $val ) {

        $i++;
        if ( $val == 0 || ( $i > $maxResultsInTables and $maxResultsInTables != 0 ) ) { break; }
        
        if ( !isset( $user_names[$key] ) ) { $user_names[$key] = 'N/A'; }

		$data_table_print .= "
        <tr>
            <td>" . $user_names[$key] . "</td>";

        foreach ( $status_values as $k => $v ) {
            if ( ( $type == 'open' and $v >= $resolved_status_threshold ) || ( $type == 'resolved' and $v < $resolved_status_threshold ) ) { continue; }
            $data_table_print .= "
            <td class='dt-right'>" . number_format( $data_table[$key][$v] ) . "&nbsp;</td>";
        }
        $data_table_print .= "
            <td class='dt-right'>" . number_format( $val ) . "&nbsp;</td>
        </tr>";
	}


return $data_table_print;
}
?>


<script type='text/javascript' src="<?php echo plugin_page( 'csp_support&r=pbhan' ); ?>"></script>

                
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
                <input type="hidden" name="page" value="Statistics/people_by_handlers" />
                <?php echo form_security_field( 'date_picker' ) ?>

                <div>
                    <div>
                        <input type="text" name="date-from" id="from" value="<?php echo cleanDates('date-from', $dateFrom); ?>" />
                        -
                        <input type="text" name="date-to" id="to"  value="<?php echo cleanDates('date-to', $dateTo); ?>" />
                    </div>
                    <p />
                    <div id="options">
                        <?php echo $pageOptionsDropDown; ?>
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
            <div>
 					&nbsp;&nbsp;&nbsp;<a href="plugin.php?page=Statistics/dl_pbhandlers.php&start=<?php echo $start ?>&end=<?php echo $end ?>" class="btn" ><?php echo lang_get( 'plugin_Statistics_download' ); ?></a>
                </div> 
            </form>
        </div>

<table>
<tr>
<?php 
if ( ON == $t_show_all ) {
?>
	<td><img src="plugin.php?page=Statistics/handlers-p-graph.php&start=<?php echo $start ?>&end=<?php echo $end ?>&set=0&option=$selectedPageOption&size=<?php echo $t_size ?> "></td>
	<?php
}
?>
<td><img src="plugin.php?page=Statistics/handlers-p-graph.php&start=<?php echo $start ?>&end=<?php echo $end ?>&set=1&option=$selectedPageOption&size=<?php echo $t_size ?>"></td>
<td><img src="plugin.php?page=Statistics/handlers-p-graph.php&start=<?php echo $start ?>&end=<?php echo $end ?>&set=2&option=$selectedPageOption&size=<?php echo $t_size ?> "></td>
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