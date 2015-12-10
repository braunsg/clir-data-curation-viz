<!-- 

clir-data-curation-viz
https://github.com/braunsg/clir-data-curation-viz

Open source code for the CLIR postdoctoral fellowship program in data curation visualization project, 
released as a general framework for creating guided interactive visualizations that can be 
presented over the web

COPYRIGHT (C) 2015 Steven Braun

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
A full copy of the license is included in LICENSE.md.

//////////////////////////////////////////////////////////////////////////////////////////
/////// About this file

token_count_descriptions.php
Last Modified: 2015-12-10, Steven Braun

This file specifies the visualization framework for the frame showing lists and instances of high-frequency tokens.

Note that all animations in this visualization are achieved by stepping through a series of numerically indexed functions that are called in sequence and which initiate each step in the visualization.

 -->
 
<!-- Style properties for this visualization only --> 
<style>

.token_labels {
	font-family: <?php echo $font_default_sans; ?>;
	fill: #000;
	font-size: 0.9em;
	text-align:right;
}

.bar {
  fill: steelblue;
  cursor:pointer;
}

.bar:hover {
  fill: brown;
}

.x_axis {
  font: 10px sans-serif;
}

.x_axis path,
.x_axis line {
  fill: none;
  stroke: #000;
  stroke-width: 0;
  shape-rendering: crispEdges;
}

.concordance_row {
	width: 100%;
	max-width: 100%;
	display: flex;
    flex-flow: row nowrap;
    align-items: stretch;
    font-family: <?php echo $font_default_sans; ?>;
	font-size: 0.8em;
	color: #999999;
}

.concordance_pretext { 
	flex: 3;
	-webkit-flex: 3;
	text-align: right;
	margin-right: 5px;
    white-space:nowrap;
    text-overflow: ellipsis;
    overflow:hidden;	
}

.concordance_token {
	flex: 2;
	-webkit-flex: 2;
	text-align: center;
	color: #CC0000;
	white-space: nowrap;
    -webkit-flex:1 0 auto; /*important*/	
}

.concordance_posttext {
	flex: 3;
	-webkit-flex: 3;
	text-align: left;
	margin-left: 5px;
    white-space:nowrap;
    text-overflow: ellipsis;
    overflow:hidden;	
    flex-shrink:2

}

.text_block {
	font-family: <?php echo $font_default_serif; ?>;
	font-size: 1em;
	overflow-y: hidden;
	width: 100%;
	height: 60%;
	margin: 0px;
	padding: 0px;
	position:relative;
	box-sizing: border-box;
	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;
}

.data_block {
	width: 100%;
	height: 40%;
	overflow-y: scroll;
	border: 1px solid #CCCCCC;
	margin: 0px;
	margin-bottom: 25px;
	padding: 0px;
	box-sizing: border-box;
	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;
	display: none;
}

.dash_line {
	stroke: #CCCCCC;
	stroke-width: 2;
}

</style>

<?php
$con = mysqli_connect($dbhost,$dbuser,$dbpw,$dbname);

// Get force layout data, first for position description text
$max_tokens = 25;
$token_data_descriptions = array();
$token_r_ct_descr_max = 0;
$token_r_ct_descr_min = 100;

$sql = "SELECT tokens.token_id, tokens.token_label, tokens.token_instance_ct, tokens.token_coverage_pct, token_counts_over_time.count_2012, token_counts_over_time.count_2013, token_counts_over_time.count_2014, token_counts_over_time.count_2015, token_counts_over_time.pct_2012, token_counts_over_time.pct_2013, token_counts_over_time.pct_2014, token_counts_over_time.pct_2015 FROM tokens INNER JOIN token_counts_over_time ON tokens.token_id = token_counts_over_time.token_id WHERE tokens.token_source_type='pds' AND tokens.token_display = 1 ORDER BY token_instance_ct DESC LIMIT $max_tokens";
$result = mysqli_query($con,$sql);

$token_reference_array = array();

while($row = mysqli_fetch_assoc($result)) {
	$token_id = $row['token_id'];
	$token_label = $row['token_label'];
	$token_instance_ct = $row['token_instance_ct'];
	$token_coverage_pct = $row['token_coverage_pct'];
	$token_data_descriptions[] = array("token_label" => $token_label,
						   "token_id" => $token_id,
						   "token_instance_ct" => $token_instance_ct,
						   "coverage" => $token_coverage_pct,
						   "year_data" => array("2012" => array("count" => $row["count_2012"], "pct" => $row["pct_2012"]),
											   "2013" => array("count" => $row["count_2013"], "pct" => $row["pct_2013"]),
											   "2014" => array("count" => $row["count_2014"], "pct" => $row["pct_2014"]),
											   "2015" => array("count" => $row["count_2015"], "pct" => $row["pct_2015"])
												)
							);

	for($year = 2012; $year <= 2015; $year++) {
		if($row["count_" . $year] > $token_r_ct_descr_max) {		
			$token_r_ct_descr_max = $row["count_" . $year];
		}
		
		if($row["count_" . $year] < $token_r_ct_descr_min) {
			$token_r_ct_descr_min = $row["count_" . $year];
		}
	}
}

$token_data_descriptions_json = json_encode($token_data_descriptions,true);
unset($token_data_descriptions);

// Now get same data for qualifications texts
$max_tokens = 25;
$token_data_qualifications = array();
$token_r_ct_quals_max = 0;
$token_r_ct_quals_min = 100;

$sql = "SELECT tokens.token_id, tokens.token_label, tokens.token_instance_ct, tokens.token_coverage_pct, token_counts_over_time.count_2012, token_counts_over_time.count_2013, token_counts_over_time.count_2014, token_counts_over_time.count_2015, token_counts_over_time.pct_2012, token_counts_over_time.pct_2013, token_counts_over_time.pct_2014, token_counts_over_time.pct_2015 FROM tokens INNER JOIN token_counts_over_time ON tokens.token_id = token_counts_over_time.token_id WHERE tokens.token_source_type='qualifications' AND tokens.token_display = 1 ORDER BY token_instance_ct DESC LIMIT $max_tokens";
$result = mysqli_query($con,$sql);

$token_reference_array = array();

while($row = mysqli_fetch_assoc($result)) {
	$token_id = $row['token_id'];
	$token_label = $row['token_label'];
	$token_instance_ct = $row['token_instance_ct'];
	$token_coverage_pct = $row['token_coverage_pct'];
	$token_data_qualifications[] = array("token_label" => $token_label,
						   "token_id" => $token_id,
						   "token_instance_ct" => $token_instance_ct,
						   "coverage" => $token_coverage_pct,
						   "year_data" => array("2012" => array("count" => $row["count_2012"], "pct" => $row["pct_2012"]),
											   "2013" => array("count" => $row["count_2013"], "pct" => $row["pct_2013"]),
											   "2014" => array("count" => $row["count_2014"], "pct" => $row["pct_2014"]),
											   "2015" => array("count" => $row["count_2015"], "pct" => $row["pct_2015"])
												)
							);

	for($year = 2012; $year <= 2015; $year++) {
		if($row["count_" . $year] > $token_r_ct_quals_max) {		
			$token_r_ct_quals_max = $row["count_" . $year];
		}
		
		if($row["count_" . $year] < $token_r_ct_quals_min) {
			$token_r_ct_quals_min = $row["count_" . $year];
		}
	}
}

$token_data_qualifications_json = json_encode($token_data_qualifications,true);
unset($token_data_qualifications);

?>
<div class="content_container">
	<div class="container_left">
		<div class="text_block">
			<div class="section_header">The Language of Data</div>
			<div class="text_chunk text_selected">
				<div class="text_container text_hidden">The program's broad approach to data curation fellowships across disciplines provides insight into the knowledge and expertise needed for research data curation. Evidence can be found in the increasing emphasis on data-centric vocabulary used in position descriptions used to recruit fellows. In a token analysis of data curation fellowship position descriptions since 2012, several keywords stand out &ndash; to the right is a list of the top 25 keywords or terms that appear most frequently in the main body of these descriptions.</div>
			</div>
			<div class="text_chunk">
				<div class="text_container text_hidden">We can also rank these terms by the total percentage of position descriptions in which they appear. Unsurprisingly, "research" and "data" both continue to appear with high frequency.</div>
			</div>
			<div class="text_chunk">
				<div class="text_container text_hidden">When we look at how usage of these terms has changed over the past 4 years in fellowship position descriptions, we see considerable growth. Here, larger circles &ndash; and deeper shades of red &ndash; correspond with higher frequency of appearance of each given key term across the position description for each year shown.</div>
			</div>
			<div class="text_chunk">
				<div class="text_container text_hidden">Again, we can view these patterns when mapping words with respect to the total percentage of position descriptions where they appear.</div>
			</div>
			<div class="text_chunk">
				<div class="text_container text_hidden">In viewing the same data for the minimum and preferred qualifications portions of fellowship position descriptions, we see similar patterns in terms of language used.</div>
			</div>
			<div class="text_chunk">
				<div class="text_container text_hidden">Sorting by the total percentage of position descriptions in which these terms appear reveals additional patterns.</div>
			</div>
			<div class="text_chunk">
				<div class="text_container text_hidden">Plotting these terms over time, we can see an increased emphasis on "experience."</div>
			</div>
			<div class="text_chunk">
				<div class="text_container text_hidden">Finally, by plotting percentage, we can start to see some clusters of skill sets and qualifications that appear uniformly across fellowship position descriptions.
					<div class="viz_inner_restart" id="token_ct_descriptions_restart">Start Over</div>	
				</div>
			</div>

			<div class="viz_inner_continue_container">
				<div class="viz_inner_continue" id="token_ct_descriptions_continue">Continue &rarr;</div>
			</div>
		</div>
		<div class="data_block">
			<div class="data_placeholder">Click on terms (bars) at the right to view examples of their usage</div>
		</div>
	</div>
	<div class="container_right"></div>
</div>
	
<script>

		//////////////////////////////////////////////////////////
		// Define functions /////////////////////////////////////
		////////////////////////////////////////////////////////
    
    	var data_placeholder = $("<div class='data_placeholder'>Click on terms (bars) at the right to view examples of their usage</div>");
    	
		function token_data(id, type) {
			$.post("inc/lib/get-token-data.php",{id: id, token_type: type}, function(response) {
				$("#token_count_descriptions .data_block").html(response);
			});
		}

		// A function to clear token instance data when the animation continues forward
		function clear_token_data() {
    		var data_placeholder = $("<div class='data_placeholder'>Click on terms (bars) at the right to view examples of their usage</div>");
			$("#token_count_descriptions .data_block").html(data_placeholder);
		}
		
		function get_bar_width(value) {
			return (token_ct_xScale(Number(value)) - token_ct_margin.left - label_bar_spacer);
		} 

		d3.select("#token_ct_descriptions_continue").on("click", function() {
			if(typeof tk_transition_done === 'undefined') {
				tk_transition_done = 1;
			}
			if(tk_descr_keyPress < tk_descr_transitions.length && tk_transition_done == 1) {
				$("#token_ct_descriptions_continue").fadeTo(200,0);
				tk_transition_done = 0;			
				svg_token_ct.call(tk_descr_transitions[tk_descr_keyPress]);
			}
		});

		function token_resume() {
			tk_transition_done = 1;
			$("#token_ct_descriptions_continue").fadeTo(200,1);
			tk_descr_keyPress++;
		}

	function draw_token_count_chart(token_ct_dataset_name) {

		switch(token_ct_dataset_name) {
			case "token_ct_descriptions":
				var ct_min = <?php echo $token_r_ct_descr_min; ?>;
				var ct_max = <?php echo $token_r_ct_descr_max; ?>;
				token_ct_data = <?php echo $token_data_descriptions_json; ?>;
				token_source_type = "pds";
				break;
			case "token_ct_qualifications":
				var ct_min = <?php echo $token_r_ct_quals_min; ?>;
				var ct_max = <?php echo $token_r_ct_quals_max; ?>;
				token_ct_data = <?php echo $token_data_qualifications_json; ?>;
				token_source_type = "qualifications";
				break;
			
		}

		token_ct_xScale = d3.scale.linear()
			.range([token_ct_margin.left, token_ct_width]);

		token_yr_xScale = d3.scale.ordinal()
			.domain(["2012","2013","2014","2015"])
			.rangeRoundBands([token_ct_margin.left, token_ct_width], 0.2);
		
		token_ct_yScale = d3.scale.ordinal()
			.rangeRoundBands([0, token_ct_height], 0.2);

		token_ct_rScale = d3.scale.linear()
			.range([rMin, rMax])
			.domain([ct_min, ct_max]);
		
		token_ct_colorScale = d3.scale.linear()
			.range(["#CECECE","#660000"])
			.domain([ct_min, ct_max]);
		
		var token_ct_yDomain = token_ct_data.map(function(d) { return d.token_label; });
		token_ct_yScale.domain(token_ct_yDomain);

		token_ct_xScale.domain([0, d3.max(token_ct_data, function(d) { return Number(d.token_instance_ct); })]);

		if(d3.select("#token_count_descriptions").select("svg").size() == 0) {
			svg_token_ct = d3.select("#token_count_descriptions").select(".container_right").append("svg")
				.attr("width", token_ct_width + token_ct_margin.left + token_ct_margin.right)
				.attr("height", token_ct_height + token_ct_margin.top + token_ct_margin.bottom)
				.append("g")
				.attr("transform", "translate(0," + token_ct_margin.top + ")");
		}

		svg_token_xAxis = d3.svg.axis()
			.scale(token_ct_xScale)
			.orient("top");
		
		svg_token_xAxis_line = svg_token_ct.append("g")
			.attr("class","x_axis")
			.attr("transform","translate(" + label_bar_spacer + ",10)")
			.attr("opacity",0)
			.call(svg_token_xAxis);
		

		svg_tokens = svg_token_ct.selectAll(".bar")
			.data(token_ct_data)
			.enter().append("g")
			.attr("transform", function(d) {
				return "translate(0," + token_ct_yScale(d.token_label) + ")"; }
			);
	
		svg_token_bars = svg_tokens.append("rect")
			.attr("class", "bar")
			.attr("x", token_ct_margin.left + label_bar_spacer)
			.attr("width",0)
			.attr("height", token_ct_yScale.rangeBand())
			.on("click", function(d) {
				token_data(d.token_id, token_source_type);
			});


		svg_token_labels = svg_tokens.append("text")
			.attr("class","token_labels")
			.attr("x", token_ct_margin.left)
			.attr("dy", token_ct_yScale.rangeBand()/2)
			.attr("alignment-baseline","middle")
			.attr("text-anchor","end")
			.attr("opacity",0)
			.text(function(d) { return d.token_label; });
		
		svg_token_dashlines = svg_tokens.append("line")
			.attr("class","dash_line")
			.style("stroke-dasharray", ("3, 3"))
			.attr("x1", token_ct_margin.left + label_bar_spacer)
			.attr("y1", token_ct_yScale.rangeBand()/2)
			.attr("x2", token_ct_width)
			.attr("y2", token_ct_yScale.rangeBand()/2)
			.attr("opacity", 0);
		
	}
		
	function tk_initialize(restart) {
		if(restart) {
			frame_controller("freeze");
			restart_text("#token_count_descriptions");
			d3.select("#token_count_descriptions").selectAll("svg").remove();
			
		}
		// Now draw plot, starting with token counts for position description texts only	
		draw_token_count_chart("token_ct_descriptions");

		tk_transition_done = 0;
		tk_transition_ct = 0;
		tk_descr_keyPress = 0;
		tk_descr_loopThrough = 0;	
		svg_token_ct.call(tk_descr_transitions[tk_descr_keyPress]);

	}

	//////////////////////////////////////////////////////////
	// Define transitions ///////////////////////////////////
	////////////////////////////////////////////////////////
	
	tk_descr_transitions = [tk_descr_transition_0, tk_descr_transition_1, tk_descr_transition_2, tk_descr_transition_3, tk_descr_transition_4, tk_descr_transition_1, tk_descr_transition_2, tk_descr_transition_3];

	
	function tk_descr_transition_0() {
		display_text("#token_count_descriptions");
		clear_token_data();
		$("#token_count_descriptions .data_block").fadeIn("medium");

		svg_token_xAxis_line.transition()
			.duration(400)
			.attr("opacity",1);
			
		svg_token_bars.transition()
			.duration(400)
			.ease("quad")
			.delay(function(d,i) { return i*50; })
			.attr("width", function(d) {
				return get_bar_width(d.token_instance_ct);
			})
			.each("start",function() { tk_transition_ct++; })
			.each("end", function() {
				if(--tk_transition_ct == 0) {
					token_resume();
				}
			});
			
		svg_token_labels.transition()
			.duration(400)
			.attr("opacity",1);
		
	}

	function tk_descr_transition_1() {
	
		display_text("#token_count_descriptions");
		
		token_ct_xScale.domain([0,1]);

		svg_token_xAxis_line.transition()
			.duration(1000)
			.call(svg_token_xAxis);

		svg_token_bars.transition()
			.duration(1000)
			.attr("width", function(d) {
				return get_bar_width(d.coverage);
			});

		token_ct_data.sort(function(a, b) {
			return d3.descending(a["coverage"], b["coverage"]);
		});

		token_ct_yScale.domain(token_ct_data.map(function(d) {
			return d.token_label;
		}));

		svg_tokens.transition()
			.duration(1000)
			.delay(1000)
			.attr("transform", function(d) {
				return "translate(0," + token_ct_yScale(d.token_label) + ")";
			})
			.each("start",function() { tk_transition_ct++; })
			.each("end", function() {
				if(--tk_transition_ct == 0) {
					token_resume();
				}
			});

		
	}

	function tk_descr_transition_2() {	
		display_text("#token_count_descriptions");
		$("#token_count_descriptions .data_block").fadeOut("fast", clear_token_data());
		svg_token_xAxis.scale(token_yr_xScale);
		
		svg_token_xAxis_line.transition()
			.duration(1000)
			.call(svg_token_xAxis);

		svg_token_bars.transition()
			.duration(1000)
			.attr("opacity",0)
			.each("end", function() {
				d3.select(this).remove();
			});
		
		svg_token_dashlines.transition()
			.duration(500)
			.attr("opacity",0.8);
		
		svg_token_circles_2012 = svg_tokens.append("circle")
			.attr("cx", function(d) {
				return token_yr_xScale("2012") + label_bar_spacer + token_yr_xScale.rangeBand()/2;
			})
			.attr("cy", token_ct_yScale.rangeBand()/2)
			.attr("r", function(d) {
				return token_ct_rScale(d["year_data"]["2012"]["count"]);
			})
			.attr("fill", function(d) {
				return token_ct_colorScale(d["year_data"]["2012"]["count"]);
			})
			.attr("opacity",0);
			
		svg_token_circles_2012.transition()
			.duration(1000)
			.attr("opacity", 0.8);

		svg_token_circles_2013 = svg_tokens.append("circle")
			.attr("cx", function(d) {
				return token_yr_xScale("2013") + label_bar_spacer + token_yr_xScale.rangeBand()/2;
			})
			.attr("cy", token_ct_yScale.rangeBand()/2)
			.attr("r", function(d) {
				return token_ct_rScale(d["year_data"]["2013"]["count"]);
			})
			.attr("fill", function(d) {
				return token_ct_colorScale(d["year_data"]["2013"]["count"]);
			})
			.attr("opacity",0);
			
		svg_token_circles_2013.transition()
			.duration(1000)
			.attr("opacity", 0.8);

		svg_token_circles_2014 = svg_tokens.append("circle")
			.attr("cx", function(d) {
				return token_yr_xScale("2014") + label_bar_spacer + token_yr_xScale.rangeBand()/2;
			})
			.attr("cy", token_ct_yScale.rangeBand()/2)
			.attr("r", function(d) {
				return token_ct_rScale(d["year_data"]["2014"]["count"]);
			})
			.attr("fill", function(d) {
				return token_ct_colorScale(d["year_data"]["2014"]["count"]);
			})
			.attr("opacity",0);
			
		svg_token_circles_2014.transition()
			.duration(1000)
			.attr("opacity", 0.8);

		svg_token_circles_2015 = svg_tokens.append("circle")
			.attr("cx", function(d) {
				return token_yr_xScale("2015") + label_bar_spacer + token_yr_xScale.rangeBand()/2;
			})
			.attr("cy", token_ct_yScale.rangeBand()/2)
			.attr("r", function(d) {
				return token_ct_rScale(d["year_data"]["2015"]["count"]);
			})
			.attr("fill", function(d) {
				return token_ct_colorScale(d["year_data"]["2015"]["count"]);
			})
			.attr("opacity",0);
			
		svg_token_circles_2015.transition()
			.duration(1000)
			.attr("opacity", 0.8)
			.each("start",function() { tk_transition_ct++; })
			.each("end", function() {
				if(--tk_transition_ct == 0) {
					token_resume();
				}
			});
		
	}
	
	function tk_descr_transition_3() {			
		display_text("#token_count_descriptions");
		
		token_ct_rScale.domain([0,1]);
		token_ct_colorScale.domain([0,1]);
		
		svg_token_circles_2012.transition()
			.duration(1000)
			.attr("r", function(d) {
				return token_ct_rScale(d["year_data"]["2012"]["pct"]);
			})
			.attr("fill", function(d) {
				return token_ct_colorScale(d["year_data"]["2012"]["pct"]);
			});

		svg_token_circles_2013.transition()
			.duration(1000)
			.attr("r", function(d) {
				return token_ct_rScale(d["year_data"]["2013"]["pct"]);
			})
			.attr("fill", function(d) {
				return token_ct_colorScale(d["year_data"]["2013"]["pct"]);
			});

		svg_token_circles_2014.transition()
			.duration(1000)
			.attr("r", function(d) {
				return token_ct_rScale(d["year_data"]["2014"]["pct"]);
			})
			.attr("fill", function(d) {
				return token_ct_colorScale(d["year_data"]["2014"]["pct"]);
			});

		svg_token_circles_2015.transition()
			.duration(1000)
			.attr("r", function(d) {
				return token_ct_rScale(d["year_data"]["2015"]["pct"]);
			})
			.attr("fill", function(d) {
				return token_ct_colorScale(d["year_data"]["2015"]["pct"]);
			})
			.each("start",function() { tk_transition_ct++; })
			.each("end", function() {
				if(--tk_transition_ct == 0) {
					token_resume();
					if(tk_descr_loopThrough == 1) {
						frame_controller("unfreeze");
						$("#token_ct_descriptions_continue").css({"display":"none"});	
						$("#token_ct_descriptions_restart")
							.click(function() { tk_initialize(true); });								
					}
				}
			});
						
	}
		
	function tk_descr_transition_4() {
		svg_token_ct.selectAll("*").remove();
		draw_token_count_chart("token_ct_qualifications");

		tk_descr_loopThrough = 1;
		tk_descr_transition_0();
	}

	//////////////////////////////////////////////////////////
	// Define variables /////////////////////////////////////
	////////////////////////////////////////////////////////

	var token_ct_margin = {top: 50, right: 35, bottom: 30, left: 150},
		token_ct_width = $("#token_count_descriptions .container_right").innerWidth() - token_ct_margin.right,
		token_ct_height = $(window).innerHeight() - token_ct_margin.top - token_ct_margin.bottom;

	var token_ct_xScale,
		token_yr_xScale,
		token_ct_yScale,
		token_ct_rScale,
		token_ct_colorScale,
		token_ct_data,
		token_source_type;
	
	var label_bar_spacer = 15;
	var rMin = 2;
	var rMax = 25;

	var tk_transition_done,
		tk_descr_keyPress,
		tk_descr_loopThrough,
		svg_token_ct;

	// Initialize the visualization
	tk_initialize();
	
</script>
