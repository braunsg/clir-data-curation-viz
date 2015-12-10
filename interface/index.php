<!DOCTYPE html>
<head>

	<title>CLIR: Ecologies of Innovation and Discovery</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

	<!-- Initialize default site variables -->
	<?php	
		include("inc/config/default-config.php");
	?>
	
	<!-- Load jQuery, D3 libraries -->
	<script>
	<?php
		include("inc/lib/jquery-1.11.2.min.js");
		include("inc/lib/d3.v3.min.js");	
	?>
	</script>
	
	<!-- Load default functions for site -->
	<script>
	<?php
		include("inc/lib/default-functions.js");
	?>
	</script>

	<!-- Load stylesheet -->
	<style>
	<?php
		include("inc/style/style_layout.php");
	?>
	</style>
	
</head>

<body>
	<div id="body_container">
		<div id="next_frame">
			<div id="nav_flyout_container">
				<div id="nav_flyout_subcontainer">
					<div id="frame_selector_1" class="nav_item nav_deselected">Title</div>	
					<div id="frame_selector_2" class="nav_item nav_deselected">Fellows Map</div>	
					<div id="frame_selector_3" class="nav_item nav_deselected">Language of Data</div>	
					<div id="frame_selector_4" class="nav_item nav_deselected">Semantic Network</div>	
					<div id="frame_selector_5" class="nav_item nav_deselected">Acknowledgments</div>	
				</div>
				<div id="arrow_next" class="arrow_fadein">Begin</div>
			</div>
		</div>
	<?php
		foreach($frames as $frame_id => $frame_number) {
	?>
		<script>
		frame_selector = "<?php echo '#' . $frame_id; ?>";
		</script>

		<div class="frame" id="<?php echo $frame_id; ?>" tabindex="<?php echo $frame_number; ?>">
 			<?php
		 			include("inc/frames/" . $frame_id . ".php");
 			?>
		</div>
	<?php
		} // end foreach
	?>

	</div>
	
</body>

</html>