<div class="col-sm-10" >
	<?php include '_submenu_dailyaccesscount.php' ?>
	<div id="chart-container" style="height:600px;"></div>
</div>
<?php include '_submenu.php' ?>

<?php
$x_categories = array();
foreach($dates as $date){
	$x_categories[] = substr($date, 5);
}
$series = array();
$series[] = array(
		'name' => "访问次数",
		'visible' => true,
		'data' => array_values($counts_group_by_date),
);
?>
<script>$(function() {
  $(document).ready(function() {
  	new Highcharts.Chart({
      credits: { enabled: false },
      legend: {y: 15, borderWidth: 1},
      chart: { renderTo: 'chart-container', defaultSeriesType: 'spline',
        animation: false, marginBottom: 80},
      title: { text: '日访问次数统计 - <?php echo $api_url ? $api_url->path : 'ALL'?>', x: -20},
      yAxis: { min: 0},
      xAxis: {
        categories: <?php echo json_encode($x_categories)?>,
        labels: { step: 3, rotation: -75, align: "right"},
      },
      tooltip: {
        formatter: function() {
          return '<b>' + this.series.name + '</b><br/>' +
          	this.x + ': ' + this.y + ' 次请求';
        }
      },
      series: <?php echo json_encode($series)?>
    });
  });

});</script>
<script src="<?= $base_uri?>/Highcharts-4.0.4/js/highcharts-all.js"></script>
