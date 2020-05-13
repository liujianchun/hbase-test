<div class="col-sm-3">
  <div class="list-group">
    <?php foreach($controllers as $controller => $info): ?>
      <a href="<?= $base_uri ?>/documents/api?page=<?= $controller ?>"
         class="list-group-item <?php
         if($_GET['page'] == $controller) echo 'active ';
         ?>"><?php
        echo $info['title'];
        if($info['deprecated']) {
          echo '&nbsp;&nbsp;'
            . '<span class="text-danger glyphicon glyphicon-exclamation-sign" aria-hidden="true" '
            . 'data-toggle="tooltip" data-placement="bottom" title="已弃用"'
            . '></span>';
        };
        ?></a>
    <?php endforeach ?>
  </div>
</div>