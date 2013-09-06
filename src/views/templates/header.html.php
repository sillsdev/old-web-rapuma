		<div id="header" class="png_bg">
			
			<div class="sfcontainer">
				
				<?php if (isset($is_static_page)):?>
				<div class="sf-logo-large">
					<img src="/images/sf_logo_large.png" alt="Scripture Forge" width="96" height="165" class="png_bg" />
				</div>
				<?php endif;?>
				
				<div id="header-nav" class="left">
				<?php if (!isset($is_static_page)):?>
					<img align="left" style="margin: 2px 10px 0 0" src="/images/sf_logo_small.png" alt="Scripture Forge" width="27" height="36" />
				<?php endif;?>
					<ul class="sf-menu">
						<li><a href="/">Home</a></li>
						<li><a href="#">Explore</a>
							<ul>
								<li><a href="#">Jamaica Project 1</a></li>
								<!--
								<li><a href="#">Sub Menu Item 2</a>
									<ul>
										<li><a href="#">Another Sub Menu Item 1</a></li>
										<li><a href="#">Another Sub Menu Item 2</a></li>
										<li><a href="#">Another Sub Menu Item 3</a></li>
									</ul>
								</li>
								-->
								<li><a href="#">Jamaica Project 2</a></li>
								<li><a href="#">Jamaica Project 3</a></li>
							</ul>
						</li>
						<li><a href="/learn_scripture_forge">Learn</a>
							<ul>
								<li><a href="/learn_scripture_forge">About Scripture Forge</a></li>
								<li><a href="/learn_expand_your_team">Expand Your Team</a></li>
								<li><a href="/learn_contribute">Contribute</a></li>
							</ul>
						</li>
						<li><a href="/contribute">Contribute</a></li>
						<li><a href="/discuss">Discuss</a></li>
					</ul>
				</div>
				
				<?php if ($logged_in):?>
					<div class="right">
							<ul class="sf-menu">
								<li><a href="/app/sfchecks#/projects">My Projects</a>
									<ul>
									<?php foreach($projects as $project): ?>
										<li><a href="<?php echo "/app/sfchecks#/project/" . $project['id']; ?>"><?php echo $project['projectname']; ?></a></li>
									<?php endforeach;?>
									</ul>
								</li>
							</ul>
							<ul class="sf-menu">
							<li>
							<a href="#"><img src="<?php echo $small_avatar_url; ?>" width="30px" height="30px" style="float:left; position:relative; top:-6px; border:1px solid white; margin-right:10px" />Hi, <?php echo $user_name; ?></a>
								<ul>
									<?php if ($is_admin):?>
									<li><a href="/app/sfadmin">Site Administration</a></li>
									<?php endif;?>
									<li><a href="/app/userprofile">My Profile</a></li>
									<li><a href="/app/changepassword">Change Password</a></li>
									<li><a href="/auth/logout">Logout</a></li>
								</ul>
							</li>
						</ul>
					</div>
				
				<?php else:?>
					<div id="account" class="right">
						<input type="button" value="Login" class="login-btn left" onclick="window.location='/auth/login'"/> &nbsp; or &nbsp; <a href="#">Create an Account</a>
					</div>
				<?php endif;?>
				
			</div>
		</div>