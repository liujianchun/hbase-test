<div class="col-sm-10">

  <form>
    <div class="form-group" style='overflow:hidden;zoom:1;'>
      <label for='search_input' class='control-label' style='float:left;line-height:40px;'>名称：</label>
      <div class='col-sm-5'>
        <input name='word' value='<?= empty($_GET['word']) ? '' : $_GET['word'] ?>' class='form-control'/>
      </div>
      <button type="submit" class="btn btn-primary">搜索</button>
    </div>
  </form>

  <div style="float:right">总 <?php echo $models_count ?>条记录</div>
  <table class="table table-hover">
    <thead>
    <tr>
      <th style="width:30px;">ID</th>
      <th>KEY</th>
      <th>名称</th>
      <th>值</th>
      <th style="width:50px;">操作</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach($models as $model): ?>
      <tr>
        <td><?php echo $model->id; ?></td>
        <td><?php echo $model->key; ?></td>
        <td><?php echo $model->name ?></td>
        <td><?php
          if($model->type === SimpleConfigurationModel::TYPE_IMAGE) {
            echo "<a target='_blank' href='{$base_uri}{$model->value}'>",
            "<img style='max-width:200px;max-height:40px;' src='{$base_uri}{$model->value}'/>",
            "</a>";
          } else
            echo $model->value
          ?></td>
        <td>
          <a href="edit?id=<?php echo $model->id; ?>">编辑</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php echo TCHtml::pagination($page_count) ?>
</div>
<?= $this->renderSubmenu($controller); ?>