<?php 
# Statistics - a statistics plugin for MantisBT
#
require_once ('statistics_api.php');
$f_jpgraph_folder		= plugin_config_get('jpgraph_folder');
require_once ( $f_jpgraph_folder . 'jpgraph.php');
require_once ( $f_jpgraph_folder . 'jpgraph_bar.php');
$start		=$_GET['start'];
$end		=$_GET['end'];
$set		=$_GET['set'];
$t_size 	= $_GET['size'];
$specific_where		= helper_project_specific_where( helper_get_current_project() );

switch($set){
	case 0:
		$query = "SELECT count(*) as the_count, mbtt.tag_id, mbt.status, name
        FROM {bug} mbt
        JOIN {bug_tag} mbtt on mbt.id = mbtt.bug_id
		join {tag} on mbtt.tag_id = {tag}.id
        WHERE $specific_where
         AND mbt.date_submitted >= " . $start . "
        AND mbt.date_submitted <= " . $end . "
        GROUP BY mbtt.tag_id order by the_count desc limit 10" ;
		$title =  "All Issues";
		break;
	case 1:
		$query = "SELECT count(*) as the_count, mbtt.tag_id, mbt.status, name
        FROM {bug} mbt
        JOIN {bug_tag} mbtt on mbt.id = mbtt.bug_id
		join {tag} on mbtt.tag_id = {tag}.id
        WHERE $specific_where
         AND mbt.date_submitted >= " . $start . "
        AND mbt.date_submitted <= " . $end . "
        AND mbt.status<80  GROUP BY mbtt.tag_id order by the_count desc limit 10" ;
		$title =  "Open Issues";
		break;
	case 2:
		$query = "SELECT count(*) as the_count, mbtt.tag_id, mbt.status, name
        FROM {bug} mbt
        JOIN {bug_tag} mbtt on mbt.id = mbtt.bug_id
		join {tag} on mbtt.tag_id = {tag}.id
        WHERE $specific_where
         AND mbt.date_submitted >= " . $start . "
        AND mbt.date_submitted <= " . $end . "
        AND mbt.status>=80  GROUP BY mbtt.tag_id order by the_count desc limit 10" ;
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
		$legend[] =   $row['name'] ;
	}
} else {
	$data[] = 1 ;
	$legend[] =  "NO DATA";
}
switch($t_size){
	case 'L':
		$width= 550;
		$height = 550;
		$size=0.35;
		break;
	case 'M':
		$width= 450;
		$height = 450;
		$size=0.25;
		break;
	case 'S':
		$width= 350;
		$height = 350;
		$size=0.15;
		break;
	default:
		$width= 550;
		$height = 550;
		$size=0.35;
		break;
}
// Create the Bar Graph.
$graph = new Graph($width,$height);
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