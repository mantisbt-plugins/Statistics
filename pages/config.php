<?php
# Statistics - a statistics plugin for MantisBT
#

auth_reauthenticate( );
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

layout_page_header( lang_get( 'plugin_format_title' ) );
layout_page_begin( 'manage_overview_page.php' );
print_manage_menu( 'manage_plugin_page.php' );

require_once 'statistics_api.php';

// Which reports to show
$confWhichReportsToShow = '';

foreach ( $reports_arr as $k => $v ) {
    if ( in_array( $k, $reportsToShow ) ) { $checked = " checked "; } else { $checked = " "; }
    $confWhichReportsToShow .= "<label><input " . $checked . " type='checkbox' name='whichreports[]' value='" . $k . "'> " . $v . "</label><br />";
}

// Number of rows in tables
$confNoResultsInTables = '';

foreach ( $maxResultsInTables_arr as $key => $val ) {

    ( $val == $maxResultsInTables ) ? $checked = " checked " : $checked = '';
    ( !$val ) ? $showval = lang_get( 'plugin_Statistics_no_limit' ) : $showval = $val;

    $confNoResultsInTables .= "<label><input " . $checked . " type='radio' name='numrows' value='" . $val . "' /> " . $showval . "</label><br />";

}

// Run-time of reports
$confRunTime = '';

foreach ( $showRuntime_arr as $key => $val ) {

    ( $val == $showRuntime ) ? $checked = " checked " : $checked = '';
    ( !$val ) ? $showval = lang_get( 'plugin_Statistics_runtime_hide' ) : $showval = lang_get( 'plugin_Statistics_runtime_show' );

    $confRunTime .= "<label><input " . $checked . " type='radio' name='runtime' value='" . $val . "' /> " . $showval . "</label><br />";

}


// Start date input filter
$confStartDate = '';

foreach ( $startDateInputFilter_arr as $key => $val ) {

    ( $key == $startDateInputFilter ) ? $checked = " checked " : $checked = '';

    $confStartDate .= "<label><input " . $checked . " type='radio' name='startdate' value='" . $key . "' /> " . $val . "</label><br />";

}

?>


<div class="col-md-12 col-xs-12">
    <div class="space-10"></div>
        <div class="form-container" >

        <form action="<?php echo plugin_page( 'config_edit' ) ?>" method="post">

            <?php echo form_security_field( 'config' ) ?>

            <div class="widget-box widget-color-blue2">
                <div class="widget-header widget-header-small">
                    <h4 class="widget-title lighter">
                        <i class="fa fa-cogs"></i>
                        <?php echo plugin_lang_get( 'configuration' ); ?>
                    </h4>
                </div>

                <div class="widget-body">
                    <div class="widget-main no-padding">
                        <div class="table-responsive">

                            <table class="table table-bordered table-condensed table-striped">

                                <tr>
                                    <th class="category width-40">
                                        <?php echo lang_get( 'plugin_Statistics_reports' ); ?>
                                        <br /><span class="small"><?php echo lang_get( 'plugin_Statistics_which_report'); ?></span>
                                    </th>
                                    <td width="60%">
                                        <?php echo $confWhichReportsToShow; ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th class="category width-40">
                                        <?php echo lang_get( 'plugin_Statistics_data_tables' ); ?>
                                        <br /><span class="small"><?php echo lang_get( 'plugin_Statistics_nrows_intables'); ?></span>
                                    </th>
                                    <td width="60%">
                                        <?php echo $confNoResultsInTables; ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th class="category width-40">
                                        <?php echo lang_get( 'plugin_Statistics_runtime' ); ?>
                                        <br /><span class="small"><?php echo lang_get( 'plugin_Statistics_runtime_sh'); ?></span>
                                    </th>
                                    <td width="60%">
                                        <?php echo $confRunTime; ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th class="category width-40">
                                        <?php echo lang_get( 'plugin_Statistics_start_date_conf1' ); ?>
                                        <br /><span class="small"><?php echo lang_get( 'plugin_Statistics_start_date_conf2'); ?></span>
                                    </th>
                                    <td width="60%">
                                        <?php echo $confStartDate; ?>
                                    </td>
                                </tr>

                            </table>
                        </div>
                    </div>

                    <div class="widget-toolbox padding-8 clearfix">
                        <input type="submit" class="btn btn-primary btn-white btn-round" value="<?php echo lang_get( 'change_configuration' )?>" />
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php 
layout_page_end();