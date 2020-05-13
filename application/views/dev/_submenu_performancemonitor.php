<?php
$paths = array();
$counts = array();
foreach(PerformanceMonitorApiUrlModel::all() as $m){
	$paths[$m->id] = $m->path;
}

// find access count of these interfaces yesterday, sort the menu by access count
$sql = "select api_url_id, sum(count) as c from " . 
PerformanceMonitorHourlyApiPerformanceDataModel::tableName().
" where `date`=:date group by id";
$stmt = PerformanceMonitorHourlyApiPerformanceDataModel::db()->prepare($sql);
$stmt->execute(array(':date'=>date('Y-m-d', time()-86400)));
while(($row=$stmt->fetch(PDO::FETCH_ASSOC)) != null){
	if(!isset($paths[$row['api_url_id']])) continue;
	$counts[$row['api_url_id']] = $row['c'];
}
arsort($counts);

?>
<div id="performance-monitor-submenu">
	<div class="form-group">
		<label class="control-label" style="float:left;line-height:35px;">API：</label>
		<div style="float:left;width:200px;margin-right:20px;">
			<?php $options = array('name'=>'api_url_id', 'class'=>'form-control');
				$select_options_string = "";
				$select_options_string .= "<option value=\"-1\">ALL</option>";
				foreach($counts as $id=>$count){
					if($id == $api_url_id){
						$select_options_string .= "<option value=\"{$id}\" selected=\"selected\">{$paths[$id]}</option>";
					}else
						$select_options_string .= "<option value=\"{$id}\">{$paths[$id]}</option>";
				}
				echo TCHtml::element("select", $select_options_string, $options, false);?>
		</div>
	</div>
	<div class="form-group">
		<label class="control-label" style="float:left;line-height:35px;">Chart type：</label>
		<div style="float:left;width:200px;margin-right:20px;">
			<?php $options = array('name'=>'chart_type', 'class'=>'form-control');
				$types = array('count'=>'count', 'max'=>'max', 'average'=>'average',
						'percentage_99'=>'percentage 99',
						'percentage_95'=>'percentage 95',
						'percentage_90'=>'percentage 90',
						'percentage_60'=>'percentage 60');
				$select_options_string = "";
				foreach($types as $type=>$label){
					if($type == $chart_type){
						$select_options_string .= "<option value=\"$type\" selected=\"selected\">$label</option>";
					}else{
						$select_options_string .= "<option value=\"$type\">$label</option>";
					}
				}
				echo TCHtml::element("select", $select_options_string, $options, false);?>
		</div>
	</div>
</div>
<script>$(function(){
  $('#performance-monitor-submenu select').change(function(){
    var url = '<?php echo $base_uri?>/dev/performanceMonitor';
    url += '?api_url_id=' + $('select[name=api_url_id]').val();
    url += '&chart_type=' + $('select[name=chart_type]').val();
    window.location.href = url;
  });
});</script>


<?php if(empty($counts)):?>
<p class="text-warning">
请在crontab中增加如下任务以执行访问日志的性能统计分析
<pre class="brush:bash"># 日志性能统计分析
*/5 * * * * php <?php echo APPLICATION_PATH?>/yafc.php performanceMonitor analyzeHourly
0 * * * * php /home/webuser/htdocs/GameCenterServer/yafc.php performanceMonitor analyzeHourly --date=today --hour=prev
</pre></p>
<?php endif;?>

