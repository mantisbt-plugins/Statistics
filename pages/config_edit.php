<?php
# Statistics - a statistics plugin for MantisBT
#

auth_reauthenticate();
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

$f_access_threshold  = gpc_get_int( 'access_threshold', [DEVELOPER] );
$f_show_all = gpc_get_int( 'show_all', ON );
$f_jpgraph_folder = gpc_get_string( 'jpgraph_folder', 'plugins/Statistics/jpgraph/' );
$f_size = strtoupper( gpc_get_string( 'size', 'L' ) );

plugin_config_set( 'access_threshold', $f_access_threshold );
plugin_config_set( 'jpgraph_folder', $f_jpgraph_folder );
plugin_config_set( 'show_all', $f_show_all );
plugin_config_set( 'size', $f_size );
require_once 'statistics_api.php';

if ( FALSE == form_security_validate('config') ) { exit; };

// runtime setting
if ( isset( $_POST['startdate'] ) ) {
    $t_startdate = (int) $_POST['startdate'];

    if ( array_key_exists( $t_startdate, $startDateInputFilter_arr ) ) {

        $query  = "select config_int_value from " . plugin_table( 'config' ) . " where config_name = 'start_date_input_filter'";
        $result = db_query( $query );

        if ( db_num_rows( $result ) == 0 ) {

            $query = "insert into " . plugin_table( 'config' ) . " (config_name, config_int_value, is_default) values ('start_date_input_filter', '" .  $t_startdate . "', 1)";
            db_query( $query );

        } elseif ( db_num_rows( $result ) == 1 ) {

            $query ="update " . plugin_table( 'config' ) . " set config_int_value = " . $t_startdate  . " where config_name = 'start_date_input_filter'";
            db_query( $query );

        }
    }
}


// runtime setting
if ( isset( $_POST['runtime'] ) ) {
    $t_runtime = (int) $_POST['runtime'];

    if ( in_array( $t_runtime, $showRuntime_arr ) ) {

        $query  = "select config_int_value from " . plugin_table( 'config' ) . " where config_name = 'show_runtime'";
        $result = db_query( $query );

        if ( db_num_rows( $result ) == 0 ) {

            $query = "insert into " . plugin_table( 'config' ) . " (config_name, config_int_value, is_default) values ('show_runtime', '" . $t_runtime . "', 1)";
            db_query( $query );

        } elseif ( db_num_rows( $result ) == 1 ) {

            $query ="update " . plugin_table( 'config' ) . " set config_int_value = " . $t_runtime  . " where config_name = 'show_runtime'";
            db_query( $query );

        }
    }
}


// number of rows
if ( isset( $_POST['numrows'] ) ) {
    $t_numrows = (int) $_POST['numrows'];

    if ( in_array( $t_numrows, $maxResultsInTables_arr ) ) {

        $query  = "select config_int_value from " . plugin_table( 'config' ) . " where config_name = 'no_rows_intables'";
        $result = db_query( $query );

        if ( db_num_rows( $result ) == 0 ) {

            $query = "insert into " . plugin_table( 'config' ) . " (config_name, config_int_value, is_default) values ('no_rows_intables', " . $t_numrows . ", 1)";
            db_query( $query );

        } elseif ( db_num_rows( $result ) == 1 ) {

            $query ="update " . plugin_table( 'config' ) . " set config_int_value = " . $t_numrows . " where config_name = 'no_rows_intables'";
            db_query( $query );

        }
    }
}


// which reports
if ( isset( $_POST['whichreports']) and !empty($_POST['whichreports'] ) ) {

    $t_whichreports = $_POST['whichreports'];

    $addToQuery = '';

    foreach ( $t_whichreports as $key => $val ) {
        if ( array_key_exists( $val, $reports_arr ) ) { $addToQuery = $addToQuery . $val . ","; }
    }

    $addToQuery = rtrim( $addToQuery, "," );

    $query  = "select config_char_value from " . plugin_table( 'config' ) . " where config_name = 'which_reports'";
    $result = db_query( $query );

    if ( db_num_rows( $result ) == 0 ) {

        $query = "insert into " . plugin_table( 'config' ) . " (config_name, config_char_value, is_default) values ('which_reports', '" . $addToQuery . "', 1)";
        db_query( $query );

    } elseif ( db_num_rows( $result ) == 1 ) {

        $query ="update " . plugin_table( 'config' ) . " set config_char_value = '" .  $addToQuery  . "' where config_name = 'which_reports'";
        db_query( $query );

    }
}


form_security_purge( 'plugin_format_config_edit' );
print_header_redirect( plugin_page( 'config', true ) );