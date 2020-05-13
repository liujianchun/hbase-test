<?php
/**
 * @var PerformanceMonitorHourlyApiPerformanceDataModel[] $models
 */


?>

  <div class="col-sm-10">
    <form class="form-inline">
      <div class="form-group">
        <label for="input-date">日期：</label>
        <input class="form-control" id="input-date" name="date" placeholder="请输入进行查询的日期" value="<?= $date ?>"/>
      </div>
      <div class="form-group">
        <label for="input-date">小时：</label>
        <select name="hour" class="form-control">
          <option value="-1">全部</option>
          <?php for($i = 0; $i < 24; $i++) { ?>
            <option value="<?= $i ?>"
              <?php if($hour === $i) echo 'selected' ?>
            ><?= $i ?></option>
          <?php } ?>
        </select>
      </div>
      <div class="form-group">
        <input class="form-control" value="提交" type="submit"/>
      </div>
    </form>
    <table class="table table-hover table-striped">
      <thead>
      <tr>
        <th>API 接口</th>
        <th>总耗时</th>
        <th>次数</th>
        <th>最大时长</th>
        <th>平均时长</th>
        <th>99%时长</th>
        <th>95%时长</th>
        <th>90%时长</th>
        <th>60%时长</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach($models as $model) { ?>
        <tr>
          <td><?= $model->api ?></td>
          <td><?= $model->sum / 100 ?></td>
          <td><?= $model->count ?></td>
          <td><?= $model->max / 100 ?></td>
          <td><?= $model->average / 100 ?></td>
          <td><?= $model->percentage_99 / 100 ?></td>
          <td><?= $model->percentage_95 / 100 ?></td>
          <td><?= $model->percentage_90 / 100 ?></td>
          <td><?= $model->percentage_60 / 100 ?></td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>


<?php include '_submenu.php' ?>