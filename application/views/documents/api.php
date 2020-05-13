<div class="col-sm-9" id="interface-document-container">
  <?php if(empty($controller_title)): ?>
    <h1>API接口文档，在右边接口列表中点击查看详细</h1>

    <?php if(!empty($platform_generated)): ?>
      <div class="bs-callout bs-callout-info">
        <h4>接口模型类下载</h4>
        <ul><?php foreach($platform_generated as $platform => $change_time): ?>
            <li>
            <a href="<?php echo $controller->getActionUri('downloadApiClassModel'), '?platform=', $platform ?>">
              <?php echo $platform, ' (', $change_time, ' 更新)' ?></a>
            </li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
    <a href="refreshapimodel" class="btn btn-primary">刷新接口模型类</a>
    <p class="text-warning">
      在进行接口调用时，客户端需要在访问的url中固定添加如下设备相关请求参数：
    <pre class="brush:js">
_: 客户端时间戳，用于防止网络中间商对请求进行cdn缓存
_udid: 设备唯一识别码
_channel: 设备app安装渠道号
_model: 设备型号
_brand: 设备品牌
_ov: 设备操作系统版本号
_v: 设备app版本号
_package: app包名或者iOS的 Bundle Identifier
_locale: 设备的当前设置的语言代码
_carrier: 设备网络供应商
_resolution: 设备屏幕分辨率
_aid: 安卓设备的广告id (仅安卓适用)
_idfa: iOS设备的广告id (仅iOS适用)
</pre>
    </p>
    <?php if(Yaf_Application::app()->getConfig()->get('api.access.sign.check')): ?>
      <p class="text-warning">
        系统已开启API访问sign验证，客户端需要在接口访问的URL地址中添加sign参数，sign计算算法如下：<br/>
        <code>md5( {uri}{params}{api secret} )</code><br/>
        其中，uri表示访问的路径，params表示get请求的参数，参数在参与计算前，需要按照key进行排序，然后计算时采用如下拼接方式：<br/>
        <code>{url encoded key}={url encoded value}&{url encoded key}={url encoded value}....</code><br/>
        注意，起始、结束位置都不带 & 符号。<br/><br/>
        <?php $secret = Yaf_Application::app()->getConfig()->get('api.access.sign.secret'); ?>
        <?php $sign = md5('/api/users/logina=b&c=def&e=eee' . $secret) ?>
        示例：<code>http://***/api/users/login?sign=<?php echo $sign ?>&a=b&c=def&e=eee</code><br/>
        其中sign由<code>md5(/api/users/logina=b&c=def&e=eee{api secret})</code>计算所得<br/>
        如需该系统中所使用的 api secret，请向系统开发管理员索要
      </p>
    <?php endif; ?>

  <?php else: ?>
    <h1>接口文档 - <?= $controller_title ?></h1>
    <div class="bs-callout bs-callout-info">
      <h4>目录</h4>
      <ul><?php foreach($apis as $api): ?>
          <li>
          <a href="#<?= $api->fragment ?>"><?= $api->title ?></a>
          </li><?php endforeach; ?></ul>
    </div>

    <?php
    $api_root = Yaf_Application::app()->getConfig()->get('api.root.url');
    if(empty($api_root)) {
      $api_root = "http://{$_SERVER['HTTP_HOST']}{$base_uri}";
    }

    foreach($apis as $api): ?>
      <h4 id="<?= $api->fragment ?>" class='interface'><?= $api->title ?></h4>
      <pre><?php
        echo $api->method, ' ', $api_root;
        if(empty($api->path)) {
          echo '/api/', lcfirst($page), '/', $api->interface;
        } else {
          echo $api->path;
        } ?></pre>
      <?php echo $api->description ?>

      <?php if(!empty($api->params)): ?>
        <div class="bs-callout bs-callout-params">
          <h5>参数列表</h5>
          <ul><?php foreach($api->params as $param): ?>
              <li>
              <span><?= $param->name ?></span><?= $param->description ?>
              </li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <?php if(!empty($api->result))
        echo "接口返回数据格式参照 Api"
        , ucfirst($page)
        , $api->interface == 'index' ? '' : ucfirst($api->interface)
        , "Result 模型类，接口返回数据格式示例：\n\n",
        '<pre class="brush:js">', $api->result, '</pre>' ?>

    <?php endforeach; ?>


  <?php endif; ?>
</div>
<?php include '_submenu.php' ?>


<link href='<?php echo $base_uri ?>/syntaxhighlighter_3.0.83/styles/shCore.css' rel='stylesheet' type='text/css'/>
<link href='<?php echo $base_uri ?>/syntaxhighlighter_3.0.83/styles/shCoreDefault.css' rel='stylesheet'
      type='text/css'/>
<link href='<?php echo $base_uri ?>/syntaxhighlighter_3.0.83/styles/shThemeDefault.css' rel='stylesheet'
      type='text/css'/>
<script src='<?php echo $base_uri ?>/syntaxhighlighter_3.0.83/scripts/shCore.js' type='text/javascript'></script>
<script src='<?php echo $base_uri ?>/syntaxhighlighter_3.0.83/scripts/shAutoloader.js' type='text/javascript'></script>
<script src='<?php echo $base_uri ?>/syntaxhighlighter_3.0.83/scripts/shBrushJScript.js'
        type='text/javascript'></script>
<script src='<?php echo $base_uri ?>/syntaxhighlighter_3.0.83/scripts/shBrushBash.js' type='text/javascript'></script>
<script>$(function() {
    SyntaxHighlighter.defaults['gutter'] = false;
    SyntaxHighlighter.defaults['toolbar'] = false;
    SyntaxHighlighter.all();
  });</script>

