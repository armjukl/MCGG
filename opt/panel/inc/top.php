<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<div class="navbar navbar-inverse navbar-fixed-top">
	<div class="navbar-inner">
		<a class="brand" href="dashboard.php"><strong>MCGG</strong> Web Panel</a>
		<ul class="nav pull-right">
			<li><a href="./?logout"><i class="icon-off icon-white"></i></a></li>
		</ul>
	</div>
</div>
<ul class="nav nav-tabs" id="myTab">
	<li <?php echo basename($_SERVER["SCRIPT_NAME"]) == "dashboard.php" ? 'class="active"' : ""; ?>>
		<a href="dashboard.php">Dashboard</a>
	</li>
	<li <?php echo basename($_SERVER["SCRIPT_NAME"]) == "file-manager.php" ? 'class="active"' : ""; ?>>
		<a href="files.php">File Manager</a>
	</li>
	<li <?php echo basename($_SERVER["SCRIPT_NAME"]) == "console.php" ? 'class="active"' : ""; ?>>
		<a href="console.php">Console</a>
	</li>
</ul>
