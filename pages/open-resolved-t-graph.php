<?php 
# Statistics - a statistics plugin for MantisBT
#
require_once ('statistics_api.php');
$f_jpgraph_folder		= plugin_config_get('jpgraph_folder');
require_once ( $f_jpgraph_folder . 'jpgraph.php');
require_once ( $f_jpgraph_folder . 'jpgraph_bar.php');

$dates		= explode( ',',$_GET['dates'] );
$open		= explode( ',',$_GET['open'] );
$resolved	= explode( ',',$_GET['resolved'] );
$t_size 	= $_GET['size'];

 // We need to load data
$data = array();
$legend = array();


$legend = $dates;
		$title =  "Trend open versus resolved Issues";
$data1y=$open;
$data2y=$resolved;

switch($t_size){
	case 'L':
		$width= 1600;
		$height = 600;
		$size=0.35;
		break;
	case 'M':
		$width= 1400;
		$height = 400;
		$size=0.25;
		break;
	case 'S':
		$width= 1200;
		$height = 300;
		$size=0.15;
		break;
	default:
		$width= 1600;
		$height = 600;
		$size=0.35;
		break;
}

// Create the Bar Graph.
$graph = new Graph($width,$height);
$graph->clearTheme();
$graph->SetScale('textlin');
$graph->SetMargin(60,50,70,50);
$graph->SetShadow();

// Set A title for the plot
$graph->title->Set("$title");
$graph->title->SetFont(FF_FONT1,FS_BOLD);

// Setup X-axis
$graph->xaxis->SetTickLabels($legend);
$graph->xaxis->SetLabelAngle(45);
//$graph->xaxis->SetFont(FF_FONT1,FS_NORMAL,12);

// Some extra margin looks nicer
$graph->xaxis->SetLabelMargin(10);

// Label align for X-axis
$graph->xaxis->SetLabelAlign('right','center','right');

// Add some grace to y-axis so the bars doesn't go
// all the way to the end of the plot area
$graph->yaxis->scale->SetGrace(20);
$graph->yaxis->SetFont(FF_FONT1,FS_BOLD,20);

// Create plots
$pl1 = new BarPlot($data1y);
$pl2 = new BarPlot($data2y);
$gbplot = new GroupBarPlot(array($pl1,$pl2));
$graph->Add($gbplot);
$pl1->SetFillColor("green");
$pl2->SetFillColor("blue");
$pl1->value->Show();
$pl2->value->Show();
$graph->Stroke();