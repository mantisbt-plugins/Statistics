<?php
# Statistics - a statistics plugin for MantisBT
#
require_once 'statistics_api.php';
print_header_redirect( plugin_page( $reportsToShow[0], true ) );