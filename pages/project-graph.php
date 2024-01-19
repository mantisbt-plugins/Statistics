<?php 
# Statistics - a statistics plugin for MantisBT
#
require_once ('statistics_api.php');
$f_jpgraph_folder	= plugin_config_get('jpgraph_folder');
require_once ( $f_jpgraph_folder . 'jpgraph.php' );
require_once ( $f_jpgraph_folder . 'jpgraph_bar.php' );

$start		=$_GET['start'];
$end		=$_GET['end'];
$set		=$_GET['set'];
$t_size 	= $_GET['size'];

$specific_where		= helper_project_specific_where( $project_id );

switch($set){
	case 0:
		$query = "SELECT count(*) as the_count, {bug}.status, name, project_id
        FROM {bug},  {project}
		WHERE  $specific_where AND {bug}.project_id = {project}.id
        AND date_submitted >= " . $start . " 
        AND date_submitted <= " . $end . " 
        GROUP BY project_id ORDER BY the_count desc limit 10" ;
		$title =  "All Issues";
		break;
	case 1:
		$query = "SELECT count(*) as the_count, {bug}.status, name, project_id
        FROM {bug},  {project}
		WHERE  $specific_where AND {bug}.project_id = {project}.id
        AND date_submitted >= " . $start . "
        AND date_submitted <= " . $end . "
        AND {bug}.status<80 GROUP BY project_id ORDER BY the_count desc limit 10";
		$title =  "Open Issues";
		break;
	case 2:
		$query = "SELECT count(*) as the_count, {bug}.status, name, project_id
        FROM {bug},  {project}
		WHERE  $specific_where AND {bug}.project_id = {project}.id
        AND date_submitted >= " . $start . "
        AND date_submitted <= " . $end . "
        AND {bug}.status>=80 GROUP BY project_id ORDER BY the_count desc limit 10";
		$title =  "Resolved Issues";
		break;
}

$result = db_query( $query );
$total= db_num_rows($result);

 // We need to load data
$data = array();
$legend = array();
if ( $total > 0 ) {
	while($row = db_fetch_array($result) ){
		$data[] = $row['the_count'] ;
		$legend[] =   $row['name'];
	}
} else {
	$data[] = 1 ;
	$legend[] =  "NO DATA";
}
switch($t_size){
	case 'L':
		$width= 550;
		$height = 350;
		break;
	case 'M':
		$width= 450;
		$height = 250;
		break;
	case 'S':
		$width= 350;
		$height = 150;
		break;
	default:
		$width= 550;
		$height = 350;
}
// Create the Bar Graph.
$graph = new Graph($width,$height, 'auto');
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
//$pl->value->Show();
$graph->Stroke();