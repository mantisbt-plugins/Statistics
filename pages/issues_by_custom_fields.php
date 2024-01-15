<?php

# MantisStats - a statistics plugin for MantisBT
#
# Copyright (c) MantisStats.Org
#
# MantisStats is available in two versions: LIGHT and PRO.
#
# MantisStats LIGHT version is free for use (freeware software).
# MantisStats PRO version is available for fee.
# MantisStats LIGHT and PRO versions are NOT open-source software.
#
# A copy of License was delivered to you during the software download. See LICENSE file.
#
# MantisStats is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See License for more
# details.
#
# https://www.mantisstats.org

require_once 'common_includes.php';

layout_page_header();
layout_page_begin( 'plugin.php?page=MantisStats/start_page' );


$project_id                         = helper_get_current_project();
$specific_where                     = helper_project_specific_where( $project_id );
$resolved_status_threshold          = config_get( 'bug_resolved_status_threshold' );
$status_enum_string                 = lang_get( 'status_enum_string' );
$status_values                      = MantisEnum::getValues( $status_enum_string );
$count_states                       = count_states();
$custom_field_names                 = custom_field_names();

// custom fields prep work: permissions and cleansing
$cleanedCustomFieldIds = array();

$customFieldsIds = custom_field_get_linked_ids( $project_id );
foreach ( $customFieldsIds as $key => $val ) {
    if ( custom_field_has_read_access_by_project_id( $val, $project_id, $t_user_id ) ) {
        $cleanedCustomFieldIds[$val] = $custom_field_names[$val];
    }
}

asort( $cleanedCustomFieldIds );

if ( sizeof( $cleanedCustomFieldIds ) > 0 ) {
    $selectedCustomField = key( $cleanedCustomFieldIds );
} else {
    $selectedCustomField = -1;
}

// custom fields prep work: session and cleansing
if ( isset( $_GET['customField'] ) and !empty( $_GET['customField'] ) ) {
    foreach ( $cleanedCustomFieldIds as $k => $v) {
        if ( $k == strip_tags( $_GET['customField'] ) ) {
            $selectedCustomField = $k;
            $_SESSION['customField'] = $k;
            break;
        }
    }
} elseif ( isset( $_SESSION['customField'] ) and !empty( $_SESSION['customField'] ) ) {
    foreach ( $cleanedCustomFieldIds as $k => $v) {
        if ( $k == strip_tags( $_SESSION['customField'] ) ) {
            $selectedCustomField = $k;
            break;
        }
    }
}

// custom fields prep work: drop-down
$customFieldsDropDown = "<strong>" . lang_get( 'plugin_MantisStats_by_custom_field' ) . "</strong>&nbsp;&nbsp;<select name='customField' id='customField'>";

foreach ( $cleanedCustomFieldIds as $key => $val ) {
    $selected = "";
    if ( $selectedCustomField == $key ) { $selected = " selected "; }
    $customFieldsDropDown .= "<option value='" . $key . "'" . $selected . ">" . $val . "</option>";
}

$customFieldsDropDown .= "</select>";

if ( $selectedCustomField == -1 ) { $customFieldsDropDown = "<p /><strong>" . lang_get( 'plugin_MantisStats_custom_f_error' ) . "</strong>"; }

// start and finish dates and times
$db_datetimes = array();

$db_datetimes['start']  = strtotime( cleanDates( 'date-from', $dateFrom, 'begOfTimes' ) . " 00:00:00" );
$db_datetimes['finish'] = strtotime( cleanDates( 'date-to', $dateTo ) . " 23:59:59" );


// get data
$issues_fetch_from_db = array();

$query = "
        SELECT mcfst.value, count(*) as the_count, mbt.status
        FROM {bug} mbt
        LEFT JOIN
            (
                SELECT * FROM {custom_field_string} mcfst WHERE field_id = $selectedCustomField
            ) mcfst ON mbt.id = mcfst.bug_id
        WHERE $specific_where
        AND mbt.date_submitted >= " . $db_datetimes['start'] . "
        AND mbt.date_submitted <= " . $db_datetimes['finish'] . "
        GROUP BY mcfst.value, mbt.status
        ";
$result = db_query( $query );

foreach ( $result as $row ) {
   if ( $row['value'] == NULL or $row['value'] == '' ) {
       $tmp = lang_get( 'plugin_MantisStats_novalue' );
   } else {
       if ( custom_field_type( $selectedCustomField ) == 8 ) { // date field - format accordingly
           $tmp = date( "Y-m-d", $row['value'] );
       } else {
           $tmp = $row['value'];
       }
   }

   $tmp = string_html_specialchars( $tmp );

    if ( isset( $issues_fetch_from_db[$tmp][$row['status']] ) ) {
        $issues_fetch_from_db[$tmp][$row['status']] = $issues_fetch_from_db[$tmp][$row['status']] + $row['the_count'];
    } else {
        $issues_fetch_from_db[$tmp][$row['status']] = $row['the_count'];
    }
}

unset ( $result );

if ( $selectedCustomField == -1 ) { $issues_fetch_from_db = array(); }


// build tables and charts
function tables_and_charts ( $type, $output ) {

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
    <table class='display' id='$type' style='display:none'>
        <thead>
        <tr class='tblheader'>
            <td width='100%'>" . lang_get( 'custom_field' ) . "</td>";

    foreach ( $status_values as $key => $val ) {
        if ( ( $type == 'open' and $val >= $resolved_status_threshold ) || ( $type == 'resolved' and $val < $resolved_status_threshold ) ) { continue; }
        $data_table_print .= "<td class='dt-right nowrap'>" . MantisEnum::getLabel( $status_enum_string, $val ) . "</td>";
    }

    $data_table_print .= "
            <td class='dt-right'>" . lang_get( 'plugin_MantisStats_total' ) . "</td>
        </tr>
        </thead>
        <tbody>";


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


    // build end of the table
    $data_table_print .= "
    </tbody></table>
    ";


    // build charts
    $container = "chartContainer" . ucfirst( $type );
    $chart_data_print = <<<EOT

FusionCharts.ready(function () {
    var myChart = new FusionCharts({
      "type": "bar2d",
      "renderAt": "$container",
      "width": $( window ).width()/2.5,
      "height": "350",
      "dataFormat": "xml",
      "dataSource": "<chart palettecolors='#0075c2' bgcolor='#ffffff' showborder='0' showcanvasborder='0' useplotgradientcolor='0' plotborderalpha='10' placevaluesinside='1' valuefontcolor='#ffffff' showaxislines='1' axislinealpha='25' divlinealpha='10' aligncaptionwithcanvas='0' showalternatevgridcolor='0' captionfontsize='14' subcaptionfontsize='14' subcaptionfontbold='0' tooltipcolor='#ffffff' tooltipborderthickness='0' tooltipbgcolor='#000000' tooltipbgalpha='80' tooltipborderradius='2' tooltippadding='5'>
EOT;

    $i = 0;

    foreach ( $data_table_totals as $key => $val ) {

        $i++;
        if ( $val == 0 or $i > MAX_LINES_IN_BAR_CHARTS ) { break; }

        $chart_data_print .= "<set label='" . htmlspecialchars( $key, ENT_QUOTES ) . "' value='" . $val . "' toolText='" . number_format( $val ) . " [" . htmlspecialchars( $key, ENT_QUOTES ) . "]' />";

    }

    $chart_data_print .= <<<EOT
    </chart>"
    });

  myChart.render();
});
EOT;

    // returns
	if ( $output == 'chart' ) { return $chart_data_print; }
    else                      { return $data_table_print; }

}

$_SESSION['issues_by_custom_fields_open_chart']      = tables_and_charts( "open", "chart" );
$_SESSION['issues_by_custom_fields_resolved_chart']  = tables_and_charts( "resolved", "chart" );

$tmp_cnt_op = $count_states['open'] + 1;
$tmp_cnt_rs = $count_states['resolved'] + 1;

$main_js = <<<EOT
    $(document).ready( function () {
        $('#open').DataTable( {
            dom: 'lfrtip<"clear spacer">T',
            "order": [[ $tmp_cnt_op, 'desc' ], [ 0, 'asc' ]],
            "autoWidth": false,
            "searching": false,
            "lengthChange": false,
            "pageLength": 10,
            "aoColumns": [
                { "asSorting": [ "asc", "desc" ] },
EOT;

$i = 0;
while ( $i <= $count_states['open'] ) {
    $main_js .= <<<EOT
    { "asSorting": [ "desc", "asc" ] },
EOT;
    $i++;
}

$main_js .= <<<EOT
        ],
        $dt_language_snippet
        } );

        $('#open').show();

        $('#resolved').DataTable( {
            dom: 'lfrtip<"clear spacer">T',
            "order": [[ $tmp_cnt_rs, 'desc' ], [ 0, 'asc' ]],
            "autoWidth": false,
            "searching": false,
            "lengthChange": false,
            "pageLength": 10,
            "aoColumns": [
                { "asSorting": [ "asc", "desc" ] },
EOT;

$i = 0;
while ( $i <= $count_states['resolved'] ) {
    $main_js .= <<<EOT
    { "asSorting": [ "desc", "asc" ] },
EOT;
    $i++;
}

$main_js .= <<<EOT
        ],
        $dt_language_snippet
        } );

        $('#resolved').show();

    } );

    $(function() {
        $( "#from" ).datepicker({
            firstDay: 1,
            changeMonth: true,
            changeYear: true,
            maxDate: new Date(),
            showButtonPanel: true,
            dateFormat: "yy-mm-dd"
        });
    });
    $(function() {
        $( "#to" ).datepicker({
            firstDay: 1,
            changeMonth: true,
            changeYear: true,
            maxDate: new Date(),
            showButtonPanel: true,
            dateFormat: "yy-mm-dd"
        });
    });
EOT;

$_SESSION['issues_by_custom_fields_main_js'] = $main_js;

?>


<script type='text/javascript' src="<?php echo plugin_page( 'csp_support&r=ibcus' ); ?>"></script>

                
<div id="wrapper">

        <div id="logo">
            <a href="https://www.mantisstats.org" target="_blank"><img src="<?php echo plugin_file( 'MantisStatsLogoLight.png' ); ?>" width='200' height='70' alt='MantisStats' /></a>
        </div>

        <?php echo $whichReport; ?>

        <p class='space20Before' />

        <div id="titleText">
            <div id="scope"><?php echo lang_get( 'plugin_MantisStats_project' ); ?>: <?php echo project_get_name( $project_id ); ?></div><div id="sup"><?php if ( $project_id == ALL_PROJECTS ) { echo "<sup>&dagger;</sup>"; } ?></div>
        </div>

        <p class="clear" />


        <div id="filter">
            <strong><?php echo lang_get( 'plugin_MantisStats_timeframe' ); ?></strong>

            <form method="get">
                <input type="hidden" name="page" value="MantisStats/issues_by_custom_fields" />
                <?php echo form_security_field( 'date_picker' ) ?>

                <div>
                    <div>
                        <input type="text" name="date-from" id="from" value="<?php echo cleanDates('date-from', $dateFrom); ?>" />
                        -
                        <input type="text" name="date-to" id="to"  value="<?php echo cleanDates('date-to', $dateTo); ?>" />
                    </div>
                    <p />
                    <div id="options">
                        <?php echo $customFieldsDropDown; ?>
                    </div>
                </div>
                <div>
                    <input type="submit" id="displaysubmit" value=<?php echo lang_get( 'plugin_MantisStats_display' ); ?> class="button" />
                </div>
            </form>
        </div>


        <div class="chartBox space50Before" />
            <strong>&raquo; <?php echo lang_get( 'plugin_MantisStats_open_iss_chrt' ); ?></strong>
            <p />
		    <div id="chartContainerOpen" style="display:inline;"><?php echo lang_get( 'plugin_MantisStats_open_issues' ); ?></div>
        </div>

        <div class="chartBox space40Before" />
            <strong>&raquo; <?php echo lang_get( 'plugin_MantisStats_resolved_iss_chrt' ); ?></strong>
            <p />
		    <div id="chartContainerResolved" style="display:inline;"><?php echo lang_get( 'plugin_MantisStats_resolved_issues' ); ?></div>
        </div>


        <p class="space40Before" />
        <strong>&raquo; <?php echo lang_get( 'plugin_MantisStats_open_issues' ); ?></strong>
        <p />
        <?php echo tables_and_charts( "open", "table" ); ?>

        <p class="space40Before" />
        <strong>&raquo; <?php echo lang_get( 'plugin_MantisStats_resolved_issues' ); ?></strong>
        <p />
        <?php echo tables_and_charts( "resolved", "table" ); ?>


        <p class="space40Before" />

        <?php if ( $project_id == ALL_PROJECTS ) { echo "<p />&dagger; " . lang_get( 'plugin_MantisStats_priv_proj_skip' ) . "<br />"; } ?>
        
        <strong>&raquo;</strong> <?php printf( lang_get( 'plugin_MantisStats_charts_maxdisp' ), MAX_LINES_IN_BAR_CHARTS ); ?> <?php if ( $maxResultsInTables ) { printf( lang_get( 'plugin_MantisStats_tables_maxdisp' ), $maxResultsInTables ); } ?>

        <?php if ( $showRuntime == 1 ) { printf( "<p class='graycolor'>" . lang_get( 'plugin_MantisStats_runtime_string' ) . "</p>", round(microtime(true) - $starttime, 5) ); } ?>

</div>

        <?php layout_page_end();