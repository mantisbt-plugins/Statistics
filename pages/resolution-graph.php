<?php 
# Statistics - a statistics plugin for MantisBT
#
require_once ('statistics_api.php');
require_once ('plugins/Statistics/jpgraph/jpgraph.php');
require_once ('plugins/Statistics/jpgraph/jpgraph_pie.php');

$start		=$_GET['start'];
$end		=$_GET['end'];
$set		=$_GET['set'];

$specific_where		= helper_project_specific_where( $project_id );

switch($set){
	case 0:
		$query = "SELECT count(*) as the_count, resolution, status
        FROM {bug}
        WHERE $specific_where
        AND date_submitted >= " . $start . "
        AND date_submitted <= " . $end . "
        GROUP BY resolution
		ORDER BY the_count DESC limit 10";
		$title =  "All Issues";
		break;
	case 1:
		$query = "SELECT count(*) as the_count, resolution, status
        FROM {bug}
        WHERE $specific_where
        AND date_submitted >= " . $start . "
        AND date_submitted <= " . $end . "
        AND status < 80 GROUP BY resolution
		ORDER BY the_count DESC limit 10";
		$title =  "Open Issues";
		break;
	case 2:
		$query = "SELECT count(*) as the_count, resolution, status
        FROM {bug}
        WHERE $specific_where
        AND date_submitted >= " . $start . "
        AND date_submitted <= " . $end . "
        AND status >= 80 GROUP BY resolution
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
		$val = $row['resolution'];
		$legend[] =  MantisEnum::getLabel(lang_get( 'resolution_enum_string' ), $val);
	}
} else {
	$data[] = 1 ;
	$legend[] =  "NO DATA";
}

// Create the Pie Graph.
$graph = new PieGraph(550,550);
$graph->clearTheme();
$graph->SetShadow();

// Set A title for the plot
$graph->title->Set("$title");
$graph->title->SetFont(FF_FONT1,FS_BOLD);

// Create plots
$size=0.35;
$p1 = new PiePlot($data);
$p1->SetLegends($legend);
$p1->SetSize($size);
$p1->value->SetFont(FF_FONT0);
$p1->ExplodeAll(5);
$graph->Add($p1);
$graph->Stroke();
