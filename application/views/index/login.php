<form class="form-signin" role="form" method="post">
  <h2 class="form-signin-heading">Please sign in</h2>
  
  <?php if(!empty($errors)):?><ul>
	  <?php foreach($errors as $error):?>
	  	<li class='text-danger'><?php echo $error?></li>
	  <?php endforeach;?>
	</ul><?php endif;?>
  
  <label for="inputUsername" class="sr-only">Email address</label>
  <input name="username" id="inputUsername" class="form-control" placeholder="User name" required autofocus>
  <label for="inputPassword" class="sr-only">Password</label>
  <input name="password" type="password" id="inputPassword" class="form-control" placeholder="Password" required>
  <button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
</form>