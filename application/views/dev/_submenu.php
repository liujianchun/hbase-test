<div class="col-sm-2">
  <div class="list-group">
    <a href="<?= $base_uri ?>/dev/performanceMonitor"
       class="list-group-item <?php if($request->action == 'performancemonitor') echo 'active' ?>">小时性能统计</a>
    <a href="<?= $base_uri ?>/dev/dailyAccessCount"
       class="list-group-item <?php if($request->action == 'dailyaccesscount') echo 'active' ?>">日访问次数统计</a>
    <a href="<?= $base_uri ?>/dev/slowapis"
       class="list-group-item <?php if($request->action == 'slowapis') echo 'active' ?>">API 统计列表</a>
  </div>
</div>