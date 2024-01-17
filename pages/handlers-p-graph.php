<?php 
# Statistics - a statistics plugin for MantisBT
#
require_once ('statistics_api.php');
require_once ('plugins/Statistics/jpgraph/jpgraph.php');
require_once ('plugins/Statistics/jpgraph/jpgraph_bar.php');

$start		=$_GET['start'];
$end		=$_GET['end'];
$set		=$_GET['set'];
$option		=$_GET['option'];

$specific_where		= helper_project_specific_where( $project_id );

if ( $option == 1 ) {
	switch($set){
		case 0:
		$query = "SELECT mut.id, mut.realname, mut.username, count(*) AS the_count, mbt.status
        FROM {bug} mbt
        LEFT JOIN {user} mut ON mbt.handler_id = mut.id
        WHERE $specific_where
        AND mut.id is not NULL
        AND mbt.date_submitted >= " . $start . "
        AND mbt.date_submitted <= " . $end . "
        GROUP BY mut.id ORDER BY the_count desc limit 10" ;
		$title =  "All Issues";
		break;
		case 1:
		$query = "SELECT mut.id, mut.realname, mut.username, count(*) AS the_count, mbt.status
        FROM {bug} mbt
        LEFT JOIN {user} mut ON mbt.handler_id = mut.id
        WHERE $specific_where
        AND mut.id is not NULL
        AND mbt.date_submitted >= " . $start . "
        AND mbt.date_submitted <= " . $end . "
        AND mbt.status<80 GROUP BY mut.id ORDER BY the_count desc limit 10";
		$title =  "Open Issues";
		break;
		case 2:
		$query = "SELECT mut.id, mut.realname, mut.username, count(*) AS the_count, mbt.status
        FROM {bug} mbt
        LEFT JOIN {user} mut ON mbt.handler_id = mut.id
        WHERE $specific_where
        AND mut.id is not NULL
        AND mbt.date_submitted >= " . $start . "
        AND mbt.date_submitted <= " . $end . "
        AND mbt.status >= 80 GROUP BY mut.id ORDER BY the_count desc limit 10";
		$title =  "Resolved Issues";
		break;
	}
} else {
	switch($set){
		case 0:
		$query = "SELECT mbht.new_value AS id, count(*) as the_count, mbt.status, mut.realname, mut.username
            FROM {bug_history} mbht
            LEFT JOIN {user} mut ON mbht.new_value = CAST(mut.id AS CHAR)
            LEFT JOIN {bug} mbt ON mbht.bug_id = mbt.id
            WHERE $specific_where
            AND field_name = 'handler_id'
            AND mut.id IS NOT NULL
            AND mbt.date_submitted >= " . $start . "
            AND mbt.date_submitted <= " . $end . "
            GROUP BY mbht.new_value ORDER BY the_count desc limit 10" ;
		$title =  "All Issues";
		break;
		case 1:
		$query = "SELECT mbht.new_value AS id, count(*) as the_count, mbt.status, mut.realname, mut.username
            FROM {bug_history} mbht
            LEFT JOIN {user} mut ON mbht.new_value = CAST(mut.id AS CHAR)
            LEFT JOIN {bug} mbt ON mbht.bug_id = mbt.id
            WHERE $specific_where
            AND field_name = 'handler_id'
            AND mut.id IS NOT NULL
            AND mbt.date_submitted >= " . $start . "
            AND mbt.date_submitted <= " . $end . "
            AND mbt.status < 80 GROUP BY mbht.new_value ORDER BY the_count desc limit 10";
		$title =  "Open Issues";
		break;
		case 2:
		$query = "SELECT mbht.new_value AS id, count(*) as the_count, mbt.status
            FROM {bug_history} mbht
            LEFT JOIN {user} mut ON mbht.new_value = CAST(mut.id AS CHAR)
            LEFT JOIN {bug} mbt ON mbht.bug_id = mbt.id
            WHERE $specific_where
            AND field_name = 'handler_id'
            AND mut.id IS NOT NULL
            AND mbt.date_submitted >= " . $start . "
            AND mbt.date_submitted <= " . $end . "
            AND mbt.status >= 80 GROUP BY mbht.new_value ORDER BY the_count desc limit 10";
		$title =  "Resolved Issues";
		break;	
	}
}

$result = db_query( $query );
$total= db_num_rows($result);

 // We need to load data
$data = array();
$legend = array();
if ( $total > 0 ) {
	while($row = db_fetch_array($result) ){
		$data[] = $row['the_count'] ;
		if ( config_get( 'show_realname' ) != 'OFF' ) {
			$legend[] =   $row['realname'];
		} else {
			$legend[] =   $row['username'];
		}

	}
} else {
	$data[] = 1 ;
	$legend[] =  "NO DATA";
}


// Create the Bar Graph.
$graph = new Graph(550,550, 'auto');
$graph->clearTheme();
$graph->SetScale('textlin');
$graph->Set90AndMargin(120,20,50,30);
$graph->SetShadow();

// Set A title for the plot
$graph->title->Set("$title");
$graph->title->SetFont(FF_FONT1,FS_BOLD);

// Setup X-axis
$graph->xaxis->SetTickLabels($legend);
$graph->xaxis->SetFont(FF_FONT1,FS_NORMAL,12);

// Some extra margin looks nicer
$graph->xaxis->SetLabelMargin(10);

// Label align for X-axis
$graph->xaxis->SetLabelAlign('right','center','right');

// Add some grace to y-axis so the bars doesn't go
// all the way to the end of the plot area
$graph->yaxis->scale->SetGrace(20);

// Create plots
$p1 = new BarPlot($data);
$p1->value->SetFont(FF_FONT0);
$graph->Add($p1);
$graph->Stroke();