<?php

/**
 * @author liujianchun
 */
class GenerateController extends TCControllerBase{

	/**
	 * 生成编辑表单的模版
	 * @param string $table_name 数据库表名称
	 * @param string $controller controller的名称
	 * @param int $type 默认为1,type为1时：生成_form,new,update三个文件;type为2时：生成单个index文件,该文件包含新增和编辑功能
	 * @param string $name 生成的文件识别名称，默认为空
	 * @param string $module 模块名称
	 * @param string $db 默认选择db，注意生成表单的字段在哪个数据库中
	 */
	public function formTemplateAction($table_name,$controller,$type=1,$name='default',$module='index',$db='db'){
		if(!$controller){
			echo "params controller can not be null!\n";
			return false;
		}
		
		if($module != 'index'){
			echo "你设置了{$module}模块,请确认需要生成的表单字段在{$db}中存在?(按回车键确认)";
			fgets(STDIN);
		}
		
		$controller = strtolower($controller);
		$module = strtolower($module);
		if($module == 'index'){
			$path = APPLICATION_DIRECTORY."/views/{$controller}/";
		}else{
			$module = ucfirst($module);
			$path = APPLICATION_DIRECTORY."/modules/{$module}/";
			if(!is_dir($path)){
				echo "ERROR: module is not exist!\n";
				return;
			}
			$path = $path."{$controller}/";
		}
		@mkdir($path,0777,true);
		if($type == 1)
			$this->createTraditionalForm($table_name,$path, $name,$db,$module);
		else
			$this->createSingleForm($table_name,$path, $name,$db);
	}
	
	/**
	 * 生成多页面可编辑的表单
	 */
	private function createTraditionalForm($table_name,$path,$name,$db,$module){
		if($name == 'default') $name = "";
		$form_file = "_form{$name}.php";
		$form_file_path = $path.$form_file;
		$new_file = "new{$name}.php";
		$new_file_path = $path.$new_file;
		$update_file = "update{$name}.php";
		$update_file_path = $path.$update_file;
		$index_file = "index{$name}.php"; 
		$index_file_path = $path.$index_file;
		
		try {
			$sql = "show columns from {$table_name}";
			$columns = TCDbManager::getInstance()->$db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
		}catch (Exception $ex){
			echo $ex->getMessage()."\n";
			return;
		}
		
		$form_item = "";
		$table_thead_th = "";
		$table_tbody_td = "";
		foreach ($columns as $column){
			if($column['Field'] == 'id') continue;
			if($column['Field'] == 'created_at') continue;
			
			$table_thead_th .= <<<TEMPLATE
			
				<th>{$column['Field']}</th>
TEMPLATE;
			
			
			$table_tbody_td .= <<<TEMPLATE
			
				<td><?php echo \$model->{$column['Field']};?></td>
TEMPLATE;
				
			$form_item .= <<<TEMPLATE

	<div class="form-group">
		<?php echo TCFormBuilder::label(\$model, "{$column['Field']}", "{$column['Field']}：", ['class'=>'control-label col-sm-2'])?>
		<div class="col-sm-8">
			<?php echo TCFormBuilder::textField(\$model, "{$column['Field']}", ['class'=>'form-control'])?>
		</div>
	</div>

TEMPLATE;
		}
		
		$actionUri = $module == "index" ? "getActionUri":"getModuleActionUri";
		
		$table_tbody_td .= <<<TEMPLATE
		
				<td>
					<?php echo TCHtml::link("编辑", \$controller->{$actionUri}("update",array("id"=>\$model->id)));?>
					&nbsp;&nbsp;
				</td>
TEMPLATE;
		
		//生成index文件
		$index_file_content = <<<TEMPLATE
<div class="col-sm-10">
	<a href="javascript:void(0)" id="new">新增</a>
	<div style="text-align: right;">总 <?php echo \$models_count;?> 条记录</div>
	<table class="table table-hover">
		<thead>
			<tr>{$table_thead_th}
				<th>编辑</th>
			</tr>
		<thead>
		<tbody>
		<?php foreach(\$models as \$model): ?>
			<tr>{$table_tbody_td}
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php echo TCHtml::pagination(\$page_count)?>
</div>
TEMPLATE;
		file_put_contents($index_file_path, $index_file_content);
		
		
		//生成_form文件
		$form_file_content = <<<TEMPLATE
<form method="post" class="form-horizontal" enctype="multipart/form-data">{$form_item}
	<div class="form-group">
		<div class="col-sm-2"></div>
		<div class="col-sm-10">
			<button type="submit" class="btn btn-primary">提交</button>
		</div>
	</div>
</form>
TEMPLATE;
		file_put_contents($form_file_path, $form_file_content);
		
		
		//生成new和update文件
		$new_and_update_file_content = <<<TEMPLATE
<div class="col-sm-10"><?php include "{$form_file}" ?></div>
TEMPLATE;
		file_put_contents($new_file_path, $new_and_update_file_content);
		file_put_contents($update_file_path, $new_and_update_file_content);
		
		
		echo $form_file." ".$new_file." ".$update_file." generate success!\n";
	}
	
	
	

	
	/**
	 * 生成单页面可编辑的表单
	 */
	private function createSingleForm($table_name,$path,$name,$db){
		if($name == 'default') $name = "";
		$file = "index{$name}.php";
		$file_path = $path.$file;

		try {
			$sql = "show columns from {$table_name}";
			$columns = TCDbManager::getInstance()->$db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
		}catch (Exception $ex){
			echo $ex->getMessage()."\n";
			return;
		}
		
		$table_thead_th = "";
		$table_tbody_td = "";
		$td_a_edit_data = "";
		$detail_dialog_item_row = "";
		$javascript_edit_item_row = "";
		//start foreach
		foreach ($columns as $column){
			if($column['Field'] == 'created_at') continue;
			
			$table_thead_th .= <<<TEMPLATE

				<th>{$column['Field']}</th>
TEMPLATE;
			
			$table_tbody_td .= <<<TEMPLATE

				<td><?php echo \$model->{$column['Field']};?></td>		
TEMPLATE;
			
			if($column['Field'] != 'id'){
				$detail_dialog_item_row .= <<<TEMPLATE

					<div class="form-group">
					  <label class="control-label col-sm-2">{$column['Field']}：</label>		
						<div class="col-sm-8">
					    <input class="form-control" name="{$column['Field']}" />
						</div>
				  </div>			
TEMPLATE;
			}
			
			
			$javascript_edit_item_row .= <<<TEMPLATE

		$("#detail_dialog form input[name={$column['Field']}]").val($(this).attr("data-{$column['Field']}"));
TEMPLATE;
			
			$td_a_edit_data .= " data-{$column['Field']}='<?php echo \$model->{$column['Field']};?>'";
		}
		//end foreach
		
		$table_tbody_td .= <<<TEMPLATE

				<td>
					<a href="javascript:void(0)" class="edit" {$td_a_edit_data}>
						编辑
					</a>
					&nbsp;&nbsp;
				</td>
TEMPLATE;
		
		$content = <<<TEMPLATE
<style>
#new{display:block;text-align:center;background-color:#337ab7;color:white;padding:5px;width:100px;border-radius:10px;}
.modal-body{overflow:hidden;}
</style>

<div class="col-sm-12">
	<a href="javascript:void(0)" id="new">新增</a>
	<div style="text-align: right;">总 <?php echo \$models_count;?> 条记录</div>
	<table class="table table-hover">
		<thead>
			<tr>{$table_thead_th}
				<th>编辑</th>
			</tr>
		<thead>
		<tbody>
		<?php foreach(\$models as \$model): ?>
			<tr>{$table_tbody_td}
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php echo TCHtml::pagination(\$page_count)?>
</div>

<!-- 表单 -->
<div id="detail_dialog" class="modal fade">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
 			<div class="modal-header">
    		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    		<h4 class="modal-title">表单</h4>
  		</div>
 	 		<div class="modal-body">
 	 			<form method='post' class="form-horizontal">{$detail_dialog_item_row}
					<input type="text" name="id" style="display: none"/>
				</form>
			</div>
			<div class="modal-footer">
    		<a type="button" class="btn btn-primary" id="submit">确定</a>
  		</div>
		</div>
	</div>
</div>

<script>
$(function(){
	$("#new").click(function(){
		$('#detail_dialog').modal();
		$("#detail_dialog form input").val("");
	});
	
	$("table .edit").click(function(){
		$('#detail_dialog').modal();{$javascript_edit_item_row}
	});
				
	$("#submit").click(function(){
		$("form").submit();
	});
})
</script>
TEMPLATE;
		file_put_contents($file_path, $content);
		echo $file." generate success!\n";
	}
	

	
}