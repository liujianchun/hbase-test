<?php
/**
 * @var TCLayoutViewAdapter $this
 * @var Yaf_Request_Abstract $request
 * @var TCControllerBase $controller
 * @var TCControllerFlash $flash
 * @var string $base_uri
 * @var string $content
 * @var string $active_nav_item
 */
$this->registerCss("application.css");
$this->registerJavaScript("application.js");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($this->pageTitle) ?></title>
  <?php
  if($this->pageDescription)
    echo "<meta name=\"description\" content=\"", htmlspecialchars($this->pageDescription), "\">";
  if($this->pageKeywords)
    echo "<meta name=\"keywords\" content=\"", htmlspecialchars($this->pageKeywords), "\">";
  ?>

  <script src='<?php echo $base_uri ?>/js/jquery-1.11.1.min.js' type='text/javascript'></script>
  <!-- Bootstrap core CSS -->
  <link href="<?php echo $base_uri ?>/bootstrap-3.3.1/css/bootstrap.min.css" rel="stylesheet">
  <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
  <!--[if lt IE 9]>
  <script src="<?php echo $base_uri?>/js/html5shiv-3.7.2.min.js"></script>
  <script src="<?php echo $base_uri?>/js/respond-1.4.2.min.js"></script>
  <![endif]-->
</head>
<body>

<nav class="navbar navbar-inverse" role="navigation">
  <div class="container">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar"
              aria-expanded="false" aria-controls="navbar">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="<?php echo $base_uri ?>/">hbase_test</a>
    </div>
    <div id="navbar" class="collapse navbar-collapse">
      <ul class="nav navbar-nav">
        <li class="<?php echo $active_nav_item == Constant::NAV_ITEM_DOCUMENTS ? 'active' : '' ?>">
          <a href="<?php echo $base_uri ?>/documents/api">API文档</a>
        </li>
        <li class="<?= $active_nav_item == Constant::NAV_ITEM_DEV ? 'active' : '' ?>">
          <a href="<?= $base_uri ?>/dev/performanceMonitor">开发者</a>
        </li>
        <?php if(empty($_SESSION['simple.access.control.user'])): ?>
          <li class="<?php echo $active_nav_item == Constant::NAV_ITEM_LOGIN ? 'active' : '' ?>">
            <a href="<?php echo $base_uri ?>/index/login">登录</a>
          </li>
        <?php else: ?>
          <?php $menus = $this->getMenus();
          if(!empty($menus)) {
            function renderMenu($name, $menu, $base_uri) {
              if(!empty($menu['children'])) {
                echo '<li class="dropdown ', ($menu['is_current'] ? 'active' : ''), '">',
                '<a href="javascript:void(0)" data-toggle="dropdown">',
                $name, '<span class="caret"></span></a>';
                echo '<ul class="dropdown-menu" aria-labelledby="dLabel">';
                foreach($menu['children'] as $k => $v) {
                  renderMenu($k, $v, $base_uri);
                }
                echo '</ul>';
                echo '</li>';
              } elseif(!empty($menu['path'])) {
                echo '<li class="', ($menu['is_current'] ? 'active' : ''), '">',
                TCHtml::link($name, $base_uri . '/' . $menu['path']),
                '</li>';
              }
            }

            foreach($menus as $name => $menu) {
              renderMenu($name, $menu, $base_uri);
            }
          } ?>
          <li><a href="<?php echo $base_uri ?>/index/logout">登出(<?= $_SESSION['simple.access.control.user'] ?>)</a></li>
        <?php endif ?>
      </ul>
    </div><!--/.nav-collapse -->
  </div>
</nav>


<div class="container" id="page-content">
  <?php
  if(!empty($breadcrumbs)) {
    echo '<ol class="breadcrumb">';
    foreach($breadcrumbs as $label => $url) {
      echo '<li ';
      if(is_int($label)) echo 'class="active"';
      echo '>';
      if(is_int($label)) echo $url;
      else {
        echo '<a href="';
        if($url[0] === '/') echo $base_uri, $url;
        else echo $url;
        echo '">', $label, '</a>';
      }
      echo '</li>';
    }
    echo '</ol>';
  }
  ?>
  <?php
  // flash messages
  if(!empty($flash['notice'])) echo "<p id='flash-notice' class='bg-success'>{$flash['notice']}</p>";
  if(!empty($flash['warning'])) echo "<p id='flash-warning' class='bg-warning'>{$flash['warning']}</p>";
  if(!empty($flash['error'])) echo "<p id='flash-error' class='bg-danger'>{$flash['error']}</p>";
  ?>
  <?php echo $content ?>
</div>


<script src="<?php echo $base_uri ?>/bootstrap-3.3.1/js/bootstrap.min.js"></script>
<script>var base_uri = '<?php echo $base_uri?>';</script>
<?php include __DIR__ . '/performance_trace.php' ?>
</body>
</html>