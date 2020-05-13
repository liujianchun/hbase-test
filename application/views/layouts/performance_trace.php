<?php
if(!Yaf_Application::app()->getConfig()->get('performance.trace.enable')) return;
$this->registerCss("/fuelux/css/fuelux.min.css");
$this->registerJavaScript("/fuelux/js/fuelux.min.js"); 
?>
<div id="performance_trace_block">
<div class="summary">
性能：
总耗时 <?php printf('%.2fms', (microtime(true)-$_SERVER['REQUEST_TIME_FLOAT'])*1000);
$summary = TCPerformanceTracer::summary();
if(!empty($summary)):
	echo "，其中 "; 
	foreach($summary as $tag=>$time):
		echo $tag, "=>";
		printf("%.2fms ", $time);
endforeach;endif;?>
</div>

<?php if(!empty($summary)):?>
	<div id="performance_trace_detail" class="fuelux">
		<ul class="tree" role="tree" id="performance_trace_detail_tree">
		  <li class="tree-branch hide" data-template="treebranch" role="treeitem" aria-expanded="false">
		    <div class="tree-branch-header">
		      <button class="tree-branch-name">
		        <span class="glyphicon icon-caret glyphicon-play"></span>
		        <span class="glyphicon icon-folder glyphicon-folder-close"></span>
		        <span class="tree-label"></span>
		      </button>
		    </div>
		    <ul class="tree-branch-children" role="group"></ul>
		  </li>
		  <li class="tree-item hide" data-template="treeitem" role="treeitem">
		    <button class="tree-item-name">
		      <span class="glyphicon icon-item fueluxicon-bullet"></span>
		      <span class="tree-label"></span>
		    </button>
		  </li>
		</ul>
	</div>
	<script>$(function(){
		var performance_info = <?php echo json_encode(TCPerformanceTracer::convertTraceItemsToJsonObject())?>;
		$('#performance_trace_detail_tree').tree({dataSource:function(parentData, callback){
			var data = [];
			var child_items = [];
			if(!parentData.name) child_items = performance_info;
			else if(parentData.children) child_items = parentData.children;
			if(child_items.length>0){
				for(var i=0; i<child_items.length; i++){
					var item = child_items[i];
					var name = '['+item.tag+'] '+Math.floor(item.time*100)/100+'ms '+item.method+' ('+item.file+':'+item.line+')';
					if(item.children){
						data.push({name:name, type:'folder', children:item.children});
					}else
						data.push({name:name, type:'item'});
				}
			}
			return callback({data:data});
		}, folderSelect:false, multiSelect:false});
	});</script>
<?php endif?>
</div>
