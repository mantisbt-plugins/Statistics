<?php
# Statistics - a statistics plugin for MantisBT
#

class StatisticsPlugin extends MantisPlugin {

    # Plugin definition
	function register() {
		$this->name         = lang_get( 'plugin_Statistics_title' );
		$this->description  = lang_get ( 'plugin_Statistics_description' );
		$this->page         = 'config';
		$this->version      = '1.1.0';
		$this->requires = array( 'MantisCore' => '2.0.0', );
		$this->author       = 'Cas Nuy';
		$this->url          =  'https://github.com/mantisbt-plugins/Statistics';
	}

    # Plugin configuration
	function config() {
		return array(
            'access_threshold'  => DEVELOPER, // Set global access level requireed to access plugin
			'jpgraph_folder'	=> 'plugins/Statistics/jpgraph/',
			'show_all'			=> ON,
			'size'				=> 'L',
		);
	}

    # Plugin hooks
    function hooks() {
        return array(
            'EVENT_MENU_MAIN'           => 'showreport_menu',
			'EVENT_LAYOUT_RESOURCES'    => 'resources',
        );
    }
	
    # Add start menu item
	function showreport_menu() {
        if ( access_has_global_level( plugin_config_get( 'access_threshold' ) ) ) {
            return array(
                array( 
                    'title' => lang_get( 'plugin_Statistics_title' ),
                    'access_level' => plugin_config_get( 'access_threshold' ),
                    'url' => 'plugin.php?page=Statistics/start_page',
                    'icon' => 'fa-area-chart'
                ),
            );
		}
	}

    # Schema definition
    function schema() {
        return array(
            array( 'CreateTableSQL', array( plugin_table( 'config' ), "
                id                  I       NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
                config_name         C(255)  NOTNULL,
                config_int_value    I       DEFAULT NULL,
                config_char_value   XL      DEFAULT NULL,
                config_extra_value  C(255)  DEFAULT NULL,
                report_id           I       DEFAULT NULL UNSIGNED,
                project_id          I       DEFAULT NULL UNSIGNED,
                user_id             I       DEFAULT NULL UNSIGNED,
                is_default          I       NOTNULL UNSIGNED
                " )
            ),
        );
    }

   # Loading needed styles and javascripts
    function resources() {
        if ( is_page_name( 'plugin.php' ) ) {
            return
                "
                    <link rel='stylesheet' type='text/css' href='" . plugin_file( 'main.css' ) . "'>
                    <link rel='stylesheet' type='text/css' href='" . plugin_file( 'datatables-min.css' ) . "'>
                    <link rel='stylesheet' type='text/css' href='" . plugin_file( 'jquery-ui-min.css' ) . "'>

                    <script src='" . plugin_file( 'datatables-min.js' ) . "'></script>

                ";
        }
    }
}