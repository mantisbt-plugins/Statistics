<?php header("Content-Type: text/javascript"); ?>

jQuery(function () {
    jQuery("#funky").change(function () {
        location.href = jQuery(this).val();
    })
})

<?php
if ( isset( $_GET['r'] ) and !empty( $_GET['r'] ) ) {
    $current_page = strip_tags( $_GET['r'] );
} else { exit; }

if ( $current_page == 'ibpro' ) { // issues by project
    if ( isset( $_SESSION['issues_by_projects_open_chart'] ) and !empty( $_SESSION['issues_by_projects_open_chart'] ) ) {
        echo $_SESSION['issues_by_projects_open_chart'];
    }
    if ( isset( $_SESSION['issues_by_projects_resolved_chart'] ) and !empty( $_SESSION['issues_by_projects_resolved_chart'] ) ) {
        echo $_SESSION['issues_by_projects_resolved_chart'];
    }
    if ( isset( $_SESSION['issues_by_projects_main_js'] ) and !empty( $_SESSION['issues_by_projects_main_js'] ) ) {
        echo $_SESSION['issues_by_projects_main_js'];
    }
} elseif ( $current_page == 'ibsta' ) { // issues by status
    if ( isset( $_SESSION['issues_by_status_open_chart'] ) and !empty( $_SESSION['issues_by_status_open_chart'] ) ) {
        echo $_SESSION['issues_by_status_open_chart'];
    }
    if ( isset( $_SESSION['issues_by_status_resolved_chart'] ) and !empty( $_SESSION['issues_by_status_resolved_chart'] ) ) {
        echo $_SESSION['issues_by_status_resolved_chart'];
    }
    if ( isset( $_SESSION['issues_by_status_main_js'] ) and !empty( $_SESSION['issues_by_status_main_js'] ) ) {
        echo $_SESSION['issues_by_status_main_js'];
    }
} elseif ( $current_page == 'ibsev' ) { // issues by severity
    if ( isset( $_SESSION['issues_by_severity_open_chart'] ) and !empty( $_SESSION['issues_by_severity_open_chart'] ) ) {
        echo $_SESSION['issues_by_severity_open_chart'];
    }
    if ( isset( $_SESSION['issues_by_severity_resolved_chart'] ) and !empty( $_SESSION['issues_by_severity_resolved_chart'] ) ) {
        echo $_SESSION['issues_by_severity_resolved_chart'];
    }
    if ( isset( $_SESSION['issues_by_severity_main_js'] ) and !empty( $_SESSION['issues_by_severity_main_js'] ) ) {
        echo $_SESSION['issues_by_severity_main_js'];
    }
} elseif ( $current_page == 'ibpri' ) { // issues by priority
    if ( isset( $_SESSION['issues_by_priority_open_chart'] ) and !empty( $_SESSION['issues_by_priority_open_chart'] ) ) {
        echo $_SESSION['issues_by_priority_open_chart'];
    }
    if ( isset( $_SESSION['issues_by_priority_resolved_chart'] ) and !empty( $_SESSION['issues_by_priority_resolved_chart'] ) ) {
        echo $_SESSION['issues_by_priority_resolved_chart'];
    }
    if ( isset( $_SESSION['issues_by_priority_main_js'] ) and !empty( $_SESSION['issues_by_priority_main_js'] ) ) {
        echo $_SESSION['issues_by_priority_main_js'];
    }
} elseif ( $current_page == 'ibcat' ) { // issues by category
    if ( isset( $_SESSION['issues_by_category_open_chart'] ) and !empty( $_SESSION['issues_by_category_open_chart'] ) ) {
        echo $_SESSION['issues_by_category_open_chart'];
    }
    if ( isset( $_SESSION['issues_by_category_resolved_chart'] ) and !empty( $_SESSION['issues_by_category_resolved_chart'] ) ) {
        echo $_SESSION['issues_by_category_resolved_chart'];
    }
    if ( isset( $_SESSION['issues_by_category_main_js'] ) and !empty( $_SESSION['issues_by_category_main_js'] ) ) {
        echo $_SESSION['issues_by_category_main_js'];
    }
} elseif ( $current_page == 'ibrep' ) { // issues by reproducibility
    if ( isset( $_SESSION['issues_by_reproducibility_open_chart'] ) and !empty( $_SESSION['issues_by_reproducibility_open_chart'] ) ) {
        echo $_SESSION['issues_by_reproducibility_open_chart'];
    }
    if ( isset( $_SESSION['issues_by_reproducibility_resolved_chart'] ) and !empty( $_SESSION['issues_by_reproducibility_resolved_chart'] ) ) {
        echo $_SESSION['issues_by_reproducibility_resolved_chart'];
    }
    if ( isset( $_SESSION['issues_by_reproducibility_main_js'] ) and !empty( $_SESSION['issues_by_reproducibility_main_js'] ) ) {
        echo $_SESSION['issues_by_reproducibility_main_js'];
    }
} elseif ( $current_page == 'ibres' ) { // issues by resolution
    if ( isset( $_SESSION['issues_by_resolution_open_chart'] ) and !empty( $_SESSION['issues_by_resolution_open_chart'] ) ) {
        echo $_SESSION['issues_by_resolution_open_chart'];
    }
    if ( isset( $_SESSION['issues_by_resolution_resolved_chart'] ) and !empty( $_SESSION['issues_by_resolution_resolved_chart'] ) ) {
        echo $_SESSION['issues_by_resolution_resolved_chart'];
    }
    if ( isset( $_SESSION['issues_by_resolution_main_js'] ) and !empty( $_SESSION['issues_by_resolution_main_js'] ) ) {
        echo $_SESSION['issues_by_resolution_main_js'];
    }
} elseif ( $current_page == 'ibhan' ) { // issues by handlers
    if ( isset( $_SESSION['issues_by_handlers_main_js'] ) and !empty( $_SESSION['issues_by_handlers_main_js'] ) ) {
        echo $_SESSION['issues_by_handlers_main_js'];
    }
} elseif ( $current_page == 'ibmon' ) { // issues by monitors
    if ( isset( $_SESSION['issues_by_monitors_main_js'] ) and !empty( $_SESSION['issues_by_monitors_main_js'] ) ) {
        echo $_SESSION['issues_by_monitors_main_js'];
    }
} elseif ( $current_page == 'ibnot' ) { // issues by notes
    if ( isset( $_SESSION['issues_by_notes_main_js'] ) and !empty( $_SESSION['issues_by_notes_main_js'] ) ) {
        echo $_SESSION['issues_by_notes_main_js'];
    }
} elseif ( $current_page == 'ibtag' ) { // issues by tags
    if ( isset( $_SESSION['issues_by_tags_open_chart'] ) and !empty( $_SESSION['issues_by_tags_open_chart'] ) ) {
        echo $_SESSION['issues_by_tags_open_chart'];
    }
    if ( isset( $_SESSION['issues_by_tags_resolved_chart'] ) and !empty( $_SESSION['issues_by_tags_resolved_chart'] ) ) {
        echo $_SESSION['issues_by_tags_resolved_chart'];
    }
    if ( isset( $_SESSION['issues_by_tags_main_js'] ) and !empty( $_SESSION['issues_by_tags_main_js'] ) ) {
        echo $_SESSION['issues_by_tags_main_js'];
    }
} elseif ( $current_page == 'ibcus' ) { // issues by custom fields
    if ( isset( $_SESSION['issues_by_custom_fields_open_chart'] ) and !empty( $_SESSION['issues_by_custom_fields_open_chart'] ) ) {
        echo $_SESSION['issues_by_custom_fields_open_chart'];
    }
    if ( isset( $_SESSION['issues_by_custom_fields_resolved_chart'] ) and !empty( $_SESSION['issues_by_custom_fields_resolved_chart'] ) ) {
        echo $_SESSION['issues_by_custom_fields_resolved_chart'];
    }
    if ( isset( $_SESSION['issues_by_custom_fields_main_js'] ) and !empty( $_SESSION['issues_by_custom_fields_main_js'] ) ) {
        echo $_SESSION['issues_by_custom_fields_main_js'];
    }
} elseif ( $current_page == 'ibreo' ) { // issues by reopenings
    if ( isset( $_SESSION['issues_by_reopenings_main_js'] ) and !empty( $_SESSION['issues_by_reopenings_main_js'] ) ) {
        echo $_SESSION['issues_by_reopenings_main_js'];
    }
} elseif ( $current_page == 'ibatt' ) { // issues by attachments
    if ( isset( $_SESSION['issues_by_attachments_main_js'] ) and !empty( $_SESSION['issues_by_attachments_main_js'] ) ) {
        echo $_SESSION['issues_by_attachments_main_js'];
    }
} elseif ( $current_page == 'pbhan' ) { // people by handlers
    if ( isset( $_SESSION['people_by_handlers_open_chart'] ) and !empty( $_SESSION['people_by_handlers_open_chart'] ) ) {
        echo $_SESSION['people_by_handlers_open_chart'];
    }
    if ( isset( $_SESSION['people_by_handlers_resolved_chart'] ) and !empty( $_SESSION['people_by_handlers_resolved_chart'] ) ) {
        echo $_SESSION['people_by_handlers_resolved_chart'];
    }
    if ( isset( $_SESSION['people_by_handlers_main_js'] ) and !empty( $_SESSION['people_by_handlers_main_js'] ) ) {
        echo $_SESSION['people_by_handlers_main_js'];
    }
} elseif ( $current_page == 'pbrep' ) { // people by reporters
    if ( isset( $_SESSION['people_by_reporters_open_chart'] ) and !empty( $_SESSION['people_by_reporters_open_chart'] ) ) {
        echo $_SESSION['people_by_reporters_open_chart'];
    }
    if ( isset( $_SESSION['people_by_reporters_resolved_chart'] ) and !empty( $_SESSION['people_by_reporters_resolved_chart'] ) ) {
        echo $_SESSION['people_by_reporters_resolved_chart'];
    }
    if ( isset( $_SESSION['people_by_reporters_main_js'] ) and !empty( $_SESSION['people_by_reporters_main_js'] ) ) {
        echo $_SESSION['people_by_reporters_main_js'];
    }
} elseif ( $current_page == 'pbmon' ) { // people by monitors
    if ( isset( $_SESSION['people_by_monitors_open_chart'] ) and !empty( $_SESSION['people_by_monitors_open_chart'] ) ) {
        echo $_SESSION['people_by_monitors_open_chart'];
    }
    if ( isset( $_SESSION['people_by_monitors_resolved_chart'] ) and !empty( $_SESSION['people_by_monitors_resolved_chart'] ) ) {
        echo $_SESSION['people_by_monitors_resolved_chart'];
    }
    if ( isset( $_SESSION['people_by_monitors_main_js'] ) and !empty( $_SESSION['people_by_monitors_main_js'] ) ) {
        echo $_SESSION['people_by_monitors_main_js'];
    }
} elseif ( $current_page == 'pbnot' ) { // people by notes
    if ( isset( $_SESSION['people_by_notes_open_chart'] ) and !empty( $_SESSION['people_by_notes_open_chart'] ) ) {
        echo $_SESSION['people_by_notes_open_chart'];
    }
    if ( isset( $_SESSION['people_by_notes_resolved_chart'] ) and !empty( $_SESSION['people_by_notes_resolved_chart'] ) ) {
        echo $_SESSION['people_by_notes_resolved_chart'];
    }
    if ( isset( $_SESSION['people_by_notes_main_js'] ) and !empty( $_SESSION['people_by_notes_main_js'] ) ) {
        echo $_SESSION['people_by_notes_main_js'];
    }
} elseif ( $current_page == 'tista' ) { // time in state
    if ( isset( $_SESSION['time_in_state_main_js'] ) and !empty( $_SESSION['time_in_state_main_js'] ) ) {
        echo $_SESSION['time_in_state_main_js'];
    }
} elseif ( $current_page == 'ttres' ) { // time to resolution
    if ( isset( $_SESSION['time_to_resolution_main_js'] ) and !empty( $_SESSION['time_to_resolution_main_js'] ) ) {
        echo $_SESSION['time_to_resolution_main_js'];
    }
} elseif ( $current_page == 'ttfir' ) { // time to first note
    if ( isset( $_SESSION['time_to_first_note_main_js'] ) and !empty( $_SESSION['time_to_first_note_main_js'] ) ) {
        echo $_SESSION['time_to_first_note_main_js'];
    }
} elseif ( $current_page == 'tbope' ) { // trends by open vs. resolved
    if ( isset( $_SESSION['trends_by_open_resolved_main_js'] ) and !empty( $_SESSION['trends_by_open_resolved_main_js'] ) ) {
        echo $_SESSION['trends_by_open_resolved_main_js'];
    }
} elseif ( $current_page == 'tbnot' ) { // trends by notes
    if ( isset( $_SESSION['trends_by_notes_main_js'] ) and !empty( $_SESSION['trends_by_notes_main_js'] ) ) {
        echo $_SESSION['trends_by_notes_main_js'];
    }
}
