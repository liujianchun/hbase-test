<div class="col-sm-10" >
	<?php include '_submenu_performancemonitor.php' ?>
	<div id="chart-container" style="height:600px;"></div>
</div>
<?php include '_submenu.php' ?>

<?php
$series = array();
foreach($chart_dates as $i=>$date){
	$series[] = array(
			'name' => $chart_date_names[$i],
			'visible' => $chart_visibles[$i],
			'data' => $chart_values[$date],
	);
}
?>
<script>$(function() {
  var chart;
  $(document).ready(function() {
    chart = new Highcharts.Chart({
      credits: { enabled: false },
      legend: {y: 15, borderWidth: 1},
      chart: { renderTo: 'chart-container', defaultSeriesType: 'spline',
        animation: false, marginBottom: 80},
      title: { text: '每小时接口性能统计 - <?php echo $chart_type?>', x: -20},
      yAxis: { min: 0},
      xAxis: {
        categories: ['00', '01', '02', '03', '04', '05',
          '06', '07', '08', '09', '10', '11', '12', '13', '14',
          '15', '16', '17', '18', '19', '20', '21', '22', '23'
        ],
      },
      tooltip: {
        formatter: function() {
          return '<b>' + this.series.name + '</b><br/>' +
            parseInt(this.x, 10) + ':00 ~' + (parseInt(this.x, 10) + 1) + ':00 ' + ': ' +
            this.y + ' <?php if($chart_type=='count') echo '次请求'; else echo 'ms'?>';
        }
      },
      series: <?php echo json_encode($series)?>
    });
  });

});</script>
<script src="<?= $base_uri?>/Highcharts-4.0.4/js/highcharts-all.js"></script>
