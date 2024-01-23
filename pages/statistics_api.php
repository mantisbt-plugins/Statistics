<?php
# Statistics - a statistics plugin for MantisBT
#

require_once( 'core.php' );

// Session
function is_session_started() {
    if ( php_sapi_name() !== 'cli' ) {
        if ( version_compare( phpversion(), '5.4.0', '>=' ) ) {
            return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
        } else {
            return session_id() === '' ? FALSE : TRUE;
        }
    }
    return FALSE;
}

if ( is_session_started() === FALSE ) session_start();


// Need plugin to be properly installed
$t_plugins      = plugin_find_all();
$t_this_plugin  = plugin_get_current();
if ( plugin_needs_upgrade( $t_plugins[$t_this_plugin] ) ) { exit; }


// Need valid user ID and access rights
$t_user_id = auth_get_current_user_id();
access_ensure_global_level( plugin_config_get( 'access_threshold' ) );


// Check if plugin table exists
$query = "SELECT 1 FROM information_schema.tables WHERE table_schema = '" . config_get( 'database_name' ) . "' AND table_name = '" . plugin_table( 'config' ) . "'";
$result = db_query( $query );

if($result !== FALSE){
    $plugin_table_exists = 1;
} else {
    $plugin_table_exists = 0;
}


// current time
$time_now = time();


// runtime
$starttime = microtime(true);


// dates received from form, if any
$dateFrom = '';
if ( isset( $_GET['date-from'] ) and !empty( $_GET['date-from'] ) ) {
    if ( FALSE == form_security_validate('date_picker') ) { exit; };
    $dateFrom = strip_tags( $_GET['date-from'] );
    $_SESSION['sess-date-from'] = $dateFrom;
} elseif ( isset( $_SESSION['sess-date-from'] ) and !empty( $_SESSION['sess-date-from'] ) ) {
    $dateFrom = $_SESSION['sess-date-from'];
}
$dateTo = '';
if ( isset( $_GET['date-to'] ) and !empty( $_GET['date-to'] ) ) {
    if ( FALSE == form_security_validate('date_picker') ) { exit; };
    $dateTo = strip_tags( $_GET['date-to'] );
    $_SESSION['sess-date-to'] = $dateTo;
} elseif ( isset( $_SESSION['sess-date-to'] ) and !empty( $_SESSION['sess-date-to'] ) ) {
    $dateTo = $_SESSION['sess-date-to'];
}



// Returns array with number of 'open' and 'resolved' states.
function count_states() {

    global $status_values, $resolved_status_threshold;
    $count_states = array();
    $count_states['open'] = $count_states['resolved'] = 0;

    foreach ( $status_values as $key => $val ) {
        if ( $val < $resolved_status_threshold ) {
            $count_states['open']++;
        } else {
            $count_states['resolved']++;
        }
    }
    return $count_states;
}


// Returns array with project names. Long project names are cut to $project_name_length characters.
function project_names() {

    global $specific_where;
    $project_name_length = 50;
    $project_names = array();

    $query = "SELECT id, name as project_name FROM {project} WHERE " . str_replace( "project_id", "id", $specific_where ) ."  ";
    $result = db_query( $query );
    foreach ( $result as $row ) {
       $project_names[$row['id']] = string_html_specialchars( substr( $row['project_name'], 0, $project_name_length ) );
    }

    return $project_names;
}


// Returns array with tag names. Long tag names are cut to $tag_name_length characters.
function tag_names() {


    $tag_name_length = 30;
    $tag_names = array();

    $query = "SELECT id, name as tag_name FROM {tag} ";
    $result = db_query( $query );

    foreach ( $result as $row ) {
       $tag_names[$row['id']] = string_html_specialchars( substr( $row['tag_name'], 0, $tag_name_length ) );
    }

    return $tag_names;
}


// Returns array with category names. Long category names are cut to $category_name_length characters.
function category_names() {

    $category_name_length = 30;
    $category_names = array();

    $query = "SELECT id, name as category_name FROM {category} ";
    $result = db_query( $query );

    foreach ( $result as $row ) {
       $category_names[$row['id']] = string_html_specialchars( substr( $row['category_name'], 0, $category_name_length ) );
    }

    return $category_names;
}


// Returns array with user names. Long user names are cut to $user_name_length characters.
function user_names( $type = 'username' ) {

     $user_name_length = 50;
    $user_names = array();

    $query = "SELECT id, realname, username FROM {user} ";
    $result = db_query( $query );

    foreach ( $result as $row ) {
        
        if ( $type == 'realname' ) {

            if ( $row['realname'] )     { $tmp = $row['realname']; }
            elseif ( $row['username'] ) { $tmp = $row['username']; }
            else                        { $tmp = lang_get( 'plugin_Statistics_unknown' ); }
        } else {
            if ( $row['username'] )     { $tmp = $row['username']; }
            else                        { $tmp = lang_get( 'plugin_Statistics_unknown' ); }            
        }

        $user_names[$row['id']] = string_html_specialchars( substr( $tmp, 0, $user_name_length ) );
    }

    return $user_names;
}


// Returns array with custom field names. Long custom field names are cut to $custom_field_name_length characters.
function custom_field_names() {

    global $mantis_custom_field_table;
    $custom_field_name_length = 30;
    $custom_field_names = array();

    $query = "SELECT id, name as custom_field_name FROM {custom_field} ";
    $result = db_query( $query );

    foreach ( $result as $row ) {
       $custom_field_names[$row['id']] = string_html_specialchars( substr( $row['custom_field_name'], 0, $custom_field_name_length ) );
    }

    return $custom_field_names;
}


// Return formatted date
function add_zero( $timeval ) {
    if ( $timeval < 10 ) { $timeval = '0' . $timeval; }
    return $timeval;
}


// is this needed???
function waitFormat( $waiting_time ) {

    $one_day        = 60*60*24;
    $one_hour       = 60*60;
    $one_minute     = 60;

    $out = "00:00";

    if ( $waiting_time > $one_day ) { // days

        $days       = floor($waiting_time/$one_day);
        $hours      = floor(($waiting_time - $days*$one_day)/$one_hour);
        $minutes    = floor(($waiting_time - $days*$one_day - $hours*$one_hour)/$one_minute);
        $seconds    = $waiting_time - $days*$one_day - $hours*$one_hour - $minutes*$one_minute;

        $out = $days . "d.&nbsp;" . add_zero($hours) . ":" . add_zero($minutes) . ":" . add_zero($seconds);

    } elseif ( $waiting_time > 60*60 ) { // hours

        $hours      = floor($waiting_time/$one_hour);
        $minutes    = floor(($waiting_time - $hours*$one_hour)/$one_minute);
        $seconds    = $waiting_time - $hours*$one_hour - $minutes*$one_minute;

        $out = add_zero($hours) . ":" . add_zero($minutes) . ":" . add_zero($seconds);

    } elseif ( $waiting_time > 60 ) { // minutes

        $minutes    = floor($waiting_time/$one_minute);
        $seconds    = $waiting_time - $minutes*$one_minute;

        $out = add_zero( $minutes ) . ":" . add_zero( $seconds );

    } elseif ( $waiting_time > 0) { //seconds

        $minutes = 0;
        $seconds = $waiting_time;
        $out = add_zero( $minutes ) . ":" . add_zero( $seconds );

    }

    return $out;
}


// granularity
$granularity_items = array();
$defaultGranularity = 3; // default is Monthly

$granularities = array(
    1 => lang_get( 'plugin_Statistics_daily' ), 2 => lang_get( 'plugin_Statistics_weekly' ),
    3 => lang_get( 'plugin_Statistics_monthly' ), 4 => lang_get( 'plugin_Statistics_yearly' )
);

$selectedGranularity = $defaultGranularity;

if ( isset( $_GET['granularity'] ) and !empty( $_GET['granularity'] ) ) {
    foreach ( $granularities as $k => $v) {
        if ( $k == strip_tags( $_GET['granularity'] ) ) {
            $selectedGranularity = $k;
            $_SESSION['granularity'] = $k;
            break;
        }
    }
} elseif ( isset( $_SESSION['granularity'] ) and !empty( $_SESSION['granularity'] ) ) {
    foreach ( $granularities as $k => $v) {
        if ( $k == $_SESSION['granularity'] ) {
            $selectedGranularity = $k;
            break;
        }
    }
} else { $selectedGranularity = $defaultGranularity; }


// clean dates
function cleanDates( $dateType, $theDate ) {

    global $startDateInputFilter;

    if ( isset( $theDate ) and !empty( $theDate ) and @checkdate( substr( $theDate,5,2 ), substr( $theDate,-2 ), substr( $theDate,0,4 ) ) ) { return $theDate; }

    if ( $dateType == 'date-from' ) {

        if ( $startDateInputFilter == 1 ) { // today
            return date("Y-m-d");
        } elseif ( $startDateInputFilter == 2 ) { // beginning of the week
            return date("Y-m-d", strtotime('monday this week') );
        } elseif ( $startDateInputFilter == 3 ) { // beginning of the month
            return date("Y-m-01");
        } elseif ( $startDateInputFilter == 4 ) { // beginning of the year
            return date("Y-01-01");
        } else { // beginning of year of mantisbt data
            $query = "SELECT date_submitted FROM {bug} ORDER BY date_submitted LIMIT 1 ";
            $result = db_query( $query );
			if($result !== FALSE) {
                 $row = db_fetch_array( $result );
                return date( "Y", $row['date_submitted'] ) . "-01-01";
            } else {
                return date( "Y" ) . "-01-01";
            }
        }
    }
    return date("Y-m-d");
}


// WHICH REPORT TO SHOW
$reportsToShow = array();

$reports_arr = array(
    'issues_by_projects'            => lang_get( 'plugin_Statistics_by_project' ),
    'issues_by_status'              => lang_get( 'plugin_Statistics_by_status' ),
    'issues_by_severity'            => lang_get( 'plugin_Statistics_by_severity' ),
    'issues_by_priority'            => lang_get( 'plugin_Statistics_by_priority' ),
    'issues_by_category'            => lang_get( 'plugin_Statistics_by_category' ),
    'issues_by_reproducibility'     => lang_get( 'plugin_Statistics_by_reprod' ),
    'issues_by_resolution'          => lang_get( 'plugin_Statistics_by_resol' ),
    'issues_by_handlers'            => lang_get( 'plugin_Statistics_handler_stats' ),
    'issues_by_monitors'            => lang_get( 'plugin_Statistics_monitoring_stats' ),
    'issues_by_notes'               => lang_get( 'plugin_Statistics_notes_stats' ),
    'issues_by_tags'                => lang_get( 'plugin_Statistics_tag_stats' ),
//    'issues_by_custom_fields'       => lang_get( 'plugin_Statistics_by_custom_fields'),
    'people_by_reporters'           => lang_get( 'plugin_Statistics_reporters' ),
    'people_by_handlers'            => lang_get( 'plugin_Statistics_handlers' ),
    'people_by_monitors'            => lang_get( 'plugin_Statistics_monitors' ),
//    'people_by_notes'               => lang_get( 'plugin_Statistics_people_notes' ),
    'trends_by_open_resolved'       => lang_get( 'plugin_Statistics_trends_open_res' ),
//    'trends_by_notes'               => lang_get( 'plugin_Statistics_trends_notes' )
);


if ( $plugin_table_exists ) {

    $query  = "select config_char_value from " . plugin_table( 'config' ) . " where config_name = 'which_reports'";

    if ( $result = db_query( $query ) and db_num_rows( $result ) == 1 ) {
		$row = db_fetch_array( $result );
        $reportsToShow_tmp = explode( ',', $row['config_char_value'] );

        foreach ( $reportsToShow_tmp as $key => $value ) {
            if ( array_key_exists( $value, $reports_arr ) ) { $reportsToShow[] = $value; }
        }
    } else {
        $reportsToShow = array_keys( $reports_arr );
    }

} else {
    $reportsToShow = array_keys( $reports_arr );
}

$current_page = '';

if ( isset( $_REQUEST['page'] ) and !empty( $_REQUEST['page'] ) ) {
    $current_page = strip_tags( $_REQUEST['page'] );
    $current_page = substr( $current_page, strlen( lang_get( 'plugin_Statistics_title' ) ) + 1 );
}
/*
if ( !in_array( $current_page, $reportsToShow ) and !in_array( $current_page, array( 'start_page', 'config', 'config_edit' ) ) ) {
    echo '<center>';
    echo '<p>' . error_string( ERROR_ACCESS_DENIED ) . '</p>';
    print_link( plugin_page( 'start_page' ), lang_get( 'proceed' ) );
    echo '</center>';
    exit;
}
*/
$whichReport = "<select id='funky'>\n";

foreach ( $reportsToShow as $key => $val ) {
    if ( $val == $current_page ) { $selected = ' selected '; } else { $selected = ''; }
    $whichReport .= "<option " . $selected . " value='./plugin.php?page=Statistics/" . $val . "'>" . $reports_arr[$val] . "</option>\n";
}

$whichReport .= "</select>";


// NUMBER OF ROWS IN TABLES
$maxResultsInTables_arr = array ( 50, 500, 0 );

if ( $plugin_table_exists ) {

    $query  = "select config_int_value from " . plugin_table( 'config' ) . " where config_name = 'no_rows_intables'";
    $result = db_query( $query );

    if ( db_num_rows( $result ) == 1 ) {
        $row = db_fetch_array( $result );
        $maxResultsInTables = $row['config_int_value'];
    } else {
        $maxResultsInTables = $maxResultsInTables_arr[1];
    }

} else {
    $maxResultsInTables = $maxResultsInTables_arr[1];
}


// SHOW RUNTIME
$showRuntime_arr = array ( 0, 1 );

if ( $plugin_table_exists ) {

    $query  = "select config_int_value from " . plugin_table( 'config' ) . " where config_name = 'show_runtime'";
    $result = db_query( $query );

    if ( db_num_rows( $result ) == 1 ) {
        $row = db_fetch_array( $result );
        $showRuntime = $row['config_int_value'];
    } else {
        $showRuntime = $showRuntime_arr[1];
    }

} else {
    $showRuntime = $showRuntime_arr[1];
}


// START DATE INPUT FILTER
$startDateInputFilter_arr = array (
    1 => lang_get( 'plugin_Statistics_start_date_option1' ),
    2 => lang_get( 'plugin_Statistics_start_date_option2' ),
    3 => lang_get( 'plugin_Statistics_start_date_option3' ),
    4 => lang_get( 'plugin_Statistics_start_date_option4' ),
    5 => lang_get( 'plugin_Statistics_start_date_option5' ),
);

if ( $plugin_table_exists ) {

    $query  = "select config_int_value from " . plugin_table( 'config' ) . " where config_name = 'start_date_input_filter'";
    $result = db_query( $query );

    if ( db_num_rows( $result ) == 1 ) {
        $row = db_fetch_array( $result );
        $startDateInputFilter = $row['config_int_value'];
    } else {
        $startDateInputFilter = 5;
    }

} else {
    $startDateInputFilter = 5;
}


// Max entries in charts
define( 'MAX_LINES_IN_BAR_CHARTS', 10 );


// The rest...
$dt_language_snippet = '

            "language": {
                "processing":     "' . lang_get( 'plugin_Statistics_dt_processing' ) . '",
                "info":           "' . lang_get( 'plugin_Statistics_dt_info' ) . '",
                "infoEmpty":      "' . lang_get( 'plugin_Statistics_dt_infoEmpty' ) . '",
                "infoPostFix":    "' . lang_get( 'plugin_Statistics_dt_infoPostFix' ) . '",
                "loadingRecords": "' . lang_get( 'plugin_Statistics_dt_loadingRecords' ) . '",
                "zeroRecords":    "' . lang_get( 'plugin_Statistics_dt_zeroRecords' ) . '",
                "emptyTable":     "' . lang_get( 'plugin_Statistics_dt_emptyTable' ) . '",
                "paginate": {
                    "first":      "' . lang_get( 'plugin_Statistics_dt_first' ) . '",
                    "previous":   "' . lang_get( 'plugin_Statistics_dt_previous' ) . '",
                    "next":       "' . lang_get( 'plugin_Statistics_dt_next' ) . '",
                    "last":       "' . lang_get( 'plugin_Statistics_dt_last' ) . '"
                },
                "aria": {
                    "sortAscending":  "' . lang_get( 'plugin_Statistics_dt_sortAscending' ) . '",
                    "sortDescending": "' . lang_get( 'plugin_Statistics_dt_sortDescending' ) . '"
                }
            }

';