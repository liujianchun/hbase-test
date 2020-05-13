<?php

/**
 * commands for api performance monitor
 * @author liujianchun
 */
class PerformanceMonitorController extends TCControllerBase {
	
	
	public function analyzeDailyAction($date='yesterday'){
		$today = date('Y-m-d');
		if($date=='today') $date = $today;
		elseif($date=='yesterday') $date = date('Y-m-d', time()-86400);
		$current_hour = date('H');
		for($i=0; $i<24; $i++){
			if($date == $today && $i>$current_hour) break;
			$this->analyzeHourlyAction($date, $i);
		}
	}
	
	
	public function analyzeHourlyAction($date='today', $hour='current'){
		$current_hour = date('H');
		if($date=='today') $date = date('Y-m-d');
		elseif($date=='yesterday') $date = date('Y-m-d', time()-86400);
		if($hour==='current') $hour = $current_hour;
		elseif($hour==='prev'){
			$hour = date('H', time()-3600);
			$date = date('Y-m-d', time()-3600);
		}else $hour = sprintf('%02d', intval($hour));
		
		$log_filepath = APPLICATION_DIRECTORY . '/api_access_logs/' . $date . '/' . $hour . '.log';
		$log_filepath_gz = APPLICATION_DIRECTORY . '/api_access_logs/' . $date . '/' . $hour . '.log.gz';
		$log_filepath_exists = file_exists($log_filepath);
		$log_filepath_gz_exists = file_exists($log_filepath_gz);
		
		if(!$log_filepath_exists && !$log_filepath_gz_exists){
			echo "\033[31mlog file not exists!\n";
			echo "\033[33m\t{$log_filepath}\n";
			echo "\033[39m\n";
		}
		
		$use_gz_file = true;
		$handle = null;
		if($log_filepath_exists){
			$handle = fopen($log_filepath, 'r');
			$use_gz_file = false;
		}else{
			$handle = gzopen($log_filepath_gz, 'r');
		}
		
		$counts = array();
		$temp_folder = '/tmp/' . uniqid() . '/';
		@mkdir($temp_folder);
		$temp_handles = array();
		$temp_filepaths = array();
		$sums = array();
		while(true){
			if($use_gz_file) $line = gzgets($handle);
			else $line = fgets($handle);
			if(empty($line)) break;
			$segments = explode("\t", $line);
			if(count($segments)<7) continue; // wrong log data
			$path = $segments[2];
			$time = $segments[5];
			$sums[$path] += $time;
			$counts[$path] ++;
			if(!$temp_handles[$path]){
				$temp_filepaths[$path] = $temp_folder . str_replace('/', '_', $path);
				$temp_handles[$path] = fopen($temp_filepaths[$path], 'w');
			}
			fwrite($temp_handles[$path], $time);
			fwrite($temp_handles[$path], "\n");
		}
		foreach($temp_handles as $path=>$handle){
			fclose($handle);
			exec("sort -n {$temp_filepaths[$path]} > {$temp_filepaths[$path]}.sorted");
			@unlink($temp_filepaths[$path]);
		}
		
		foreach($temp_filepaths as $api_path=>$temp_filepath){
			$api_url_id = PerformanceMonitorApiUrlModel::createIfNotExists($api_path)->id;
			if(!$api_url_id) continue;
			
			$handle = fopen($temp_filepath . '.sorted', 'r');
			$line_number = 0;
			$max = $percentage_99 = $percentage_95 = $percentage_90 = $percentage_60 = 0;
			$line_number_99 = floor($counts[$api_path] * 0.99);
			$line_number_95 = floor($counts[$api_path] * 0.95);
			$line_number_90 = floor($counts[$api_path] * 0.90);
			$line_number_60 = floor($counts[$api_path] * 0.6);
			while(($line = fgets($handle)) != null){
				$max = trim($line);
				if($line_number == $line_number_99) $percentage_99 = trim($line);
				elseif($line_number == $line_number_95) $percentage_95 = trim($line);
				elseif($line_number == $line_number_90) $percentage_90 = trim($line);
				elseif($line_number == $line_number_60) $percentage_60 = trim($line);
				$line_number ++;
			}
			if(!$percentage_99) $percentage_99 = $max;
			if(!$percentage_95) $percentage_95 = $max;
			if(!$percentage_90) $percentage_90 = $max;
			if(!$percentage_60) $percentage_60 = $max;
			fclose($handle);
			
			$sql = "insert into performance_monitor_hourly_api_performance_data
					(`date`, hour, api_url_id, `count`, `average`, `max`, 
						percentage_99, percentage_95, percentage_90, percentage_60)
					values
					(:date, :hour, :api_url_id, :count, :average, :max, 
						:percentage_99, :percentage_95, :percentage_90, :percentage_60)
					on duplicate key update
					`count`=values(`count`),
					`average`=values(`average`),
					`max`=values(`max`),
					`percentage_99`=values(`percentage_99`),
					`percentage_95`=values(`percentage_95`),
					`percentage_90`=values(`percentage_90`),
					`percentage_60`=values(`percentage_60`)
					";
			$stmt = TCDbManager::getInstance()->db->prepare($sql);
			$stmt->execute(array(
					':date' => $date,
					':hour' => $hour,
					':api_url_id' => $api_url_id,
					':count' => $counts[$api_path],
					':average' => 100 * $sums[$api_path] / $counts[$api_path],
					':max' => 100 * $max,
					':percentage_99' => 100 * $percentage_99,
					':percentage_95' => 100 * $percentage_95,
					':percentage_90' => 100 * $percentage_90,
					':percentage_60' => 100 * $percentage_60,
			));
		}
		
		exec("rm -rf $temp_folder");
		
		if($use_gz_file) gzclose($handle);
		else fclose($handle);
		
		
		// compress the log file if the file is history
		if($hour!=$current_hour && $log_filepath_exists && !$log_filepath_gz_exists){
			$in = fopen($log_filepath, 'r');
			$out = gzopen($log_filepath_gz, 'w');
			while(($buffer = fread($in, 1024*10)) != null){
				gzwrite($out, $buffer);
			}
			fclose($in);
			gzclose($out);
			unlink($log_filepath);
		}
	}
	
}

