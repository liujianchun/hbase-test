<form method="post" role="form" class="form-horizontal" enctype="multipart/form-data">
  <?php if(!empty($error)) echo "<p id='flash-error' class='bg-danger'>$error</p>" ?>

  <div class="form-group">
    <?php echo TCFormBuilder::label($model, "key", "KEY：", ['class' => 'control-label col-sm-2']) ?>
    <div class="col-sm-8">
      <?php echo TCFormBuilder::textField($model, "key", ['class' => 'form-control', 'placeholder' => '请输入程序所用的key，不可重复']) ?>
    </div>
  </div>

  <div class="form-group">
    <?php echo TCFormBuilder::label($model, "type", "类型：", ['class' => 'control-label col-sm-2']) ?>
    <div class="col-sm-3">
      <?php echo TCFormBuilder::selectField($model, "type", SimpleConfigurationModel::$types, ['class' => 'form-control']) ?>
    </div>
  </div>

  <div class="form-group">
    <?php echo TCFormBuilder::label($model, "name", "名称：", ['class' => 'control-label col-sm-2']) ?>
    <div class="col-sm-8">
      <?php echo TCFormBuilder::textField($model, "name", ['class' => 'form-control', 'placeholder' => '请输入后台所显示的名称']) ?>
    </div>
  </div>

  <?php $is_file = in_array($model->type, [SimpleConfigurationModel::TYPE_IMAGE, SimpleConfigurationModel::TYPE_FILE]) ?>
  <div class="form-group value-text" style="<?= $is_file ? 'display:none' : '' ?>">
    <?php echo TCFormBuilder::label($model, "value", "值：", ['class' => 'control-label col-sm-2']) ?>
    <div class="col-sm-8">
      <?php echo TCFormBuilder::textField($model, "value", ['class' => 'form-control', 'placeholder' => '请填入配置的值']) ?>
    </div>
  </div>

  <div class="form-group value-file" style="<?= $is_file ? '' : 'display:none' ?>">
    <?php echo TCFormBuilder::label($model, "value-file", "值：", ['class' => 'control-label col-sm-2']) ?>
    <div class="col-sm-8">
      <?php echo TCFormBuilder::fileField($model, "value-file", ['class' => 'form-control']) ?>
    </div>
  </div>

  <input type="hidden" name="referer"
         value="<?= empty($_POST['referer']) ? $_SERVER['HTTP_REFERER'] : $_POST['referer'] ?>"/>

  <div class="form-group">
    <div class="col-sm-2"></div>
    <div class="col-sm-10">
      <button type="submit" class="btn btn-primary">提交</button>
    </div>
  </div>

</form>

<script>
  $(function() {
    $("#SimpleConfigurationModel_type").change(function() {
      var type = $("#SimpleConfigurationModel_type").val();
      if(type == <?= SimpleConfigurationModel::TYPE_IMAGE ?> || type == <?= SimpleConfigurationModel::TYPE_FILE ?>) {
        $(".value-file").show();
        $(".value-text").hide();
      } else {
        $(".value-file").hide();
        $(".value-text").show();
      }
    });
  })
</script>