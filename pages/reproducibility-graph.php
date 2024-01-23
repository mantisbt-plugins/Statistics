<?php 
# Statistics - a statistics plugin for MantisBT
#
require_once ('statistics_api.php');
$f_jpgraph_folder		= plugin_config_get('jpgraph_folder');
require_once ( $f_jpgraph_folder . 'jpgraph.php');
require_once ( $f_jpgraph_folder . 'jpgraph_pie.php');

$start		=$_GET['start'];
$end		=$_GET['end'];
$set		=$_GET['set'];
$t_size 	= $_GET['size'];

$specific_where		= helper_project_specific_where( helper_get_current_project() );

switch($set){
	case 0:
		$query = "SELECT count(*) as the_count, reproducibility, status
        FROM {bug}
        WHERE $specific_where
        AND date_submitted >= " . $start . "
        AND date_submitted <= " . $end . "
        GROUP BY reproducibility
		ORDER BY the_count DESC limit 10";
		$title =  "All Issues";
		break;
	case 1:
		$query = "SELECT count(*) as the_count, reproducibility, status
        FROM {bug}
        WHERE $specific_where
        AND date_submitted >= " . $start . "
        AND date_submitted <= " . $end . "
        AND status < 80 GROUP BY reproducibility
		ORDER BY the_count DESC limit 10";
		$title =  "Open Issues";
		break;
	case 2:
		$query = "SELECT count(*) as the_count, reproducibility, status
        FROM {bug}
        WHERE $specific_where
        AND date_submitted >= " . $start . "
        AND date_submitted <= " . $end . "
        AND status >= 80 GROUP BY reproducibility
		ORDER BY the_count DESC limit 10";
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
		$val = $row['reproducibility'];
		$legend[] =  MantisEnum::getLabel(lang_get( 'reproducibility_enum_string' ), $val);
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
// Create the Pie Graph.
$graph = new PieGraph($width,$height);
$graph->clearTheme();
$graph->SetShadow();

// Set A title for the plot
$graph->title->Set("$title");
$graph->title->SetFont(FF_FONT1,FS_BOLD);

// Create plots
$p1 = new PiePlot($data);
$p1->SetLegends($legend);
$p1->SetSize($size);
$p1->value->SetFont(FF_FONT0);
$p1->ExplodeAll(5);
$graph->Add($p1);
$graph->Stroke();
