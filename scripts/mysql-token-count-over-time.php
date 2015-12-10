<?php

/* ///////////////////////////////////////////////////////////////////////////////////////

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

mysql-token-count-over-time.php

Created: 2015-10-31
Modified: 2015-11-05
Steven Braun

This script takes the highest-frequency tokens from each text source and calculates their
frequency counts per year across time (2012 - present)

/////////////////////////////////////////////////////////////////////////////////////// */

// Initialize some global variables
include("inc/default-config.php");

// Database connection; parameters defined globally in default_config.php
$con = mysqli_connect($dbhost,$dbuser,$dbpw,$dbname);

// Define token exceptions (stopwords) -- words that should not be included in analysis
// Taken from http://www.ranks.nl/stopwords/ (Default English stopwords list)
$stopwords_filename = "inc/stopwords.txt";
$stopwords_file = fopen($stopwords_filename,"r");
$stopwords = fread($stopwords_file,filesize($stopwords_filename));

// Explode list of stopwords into an array
$token_exceptions = explode("\n",$stopwords);

// Define a special list of exceptions based on this set of data -- 
// For example, words like "university" and "Ph.D." should be ignored for token analyses
$special_exceptions = array("university","phd","ph.d.","will");
$token_exceptions = array_merge($token_exceptions,$special_exceptions);

// Define minimum and maximum token search lengths for below
$min_token_length = 1;
$max_token_length = 3;

// Specify what we're searching; 'qualifications' (position qualifications), 'responsibilities' (position responsibilities), or 'pds' (position description main body text)
$sources = array("pds","qualifications","responsibilities");

// Truncate TABLE token_counts_over_time
mysqli_query($con,"TRUNCATE TABLE token_counts_over_time");

// Now start looping through each text source
foreach($sources as $search_content) {

	// Instantiate array to hold token data for text source
	$tokens_array = array();
	print "Counting tokens for source " . $search_content . "...\n";
	sleep(1);
	
	$sql = "SELECT token_id, token_label FROM tokens WHERE token_source_type='$search_content' ORDER BY token_instance_ct DESC LIMIT $global_max_token_count";
	$result = mysqli_query($con,$sql);

	while($row = mysqli_fetch_assoc($result)) {
		$token_id = $row['token_id'];
		$token_label = $row['token_label'];
		$tokens_array[] = array("token_id" => $token_id,
								"token_label" => $token_label);

	}
	
	mysqli_free_result($result);
	
	print "Looping through texts...\n";
	sleep(1);
	
	// Now split search based on content variable defined above;
	// Note that we're only pulling text from position descriptions for
	// DATA CURATION track fellowships, indicated by position_descriptions.pd_dc = 1
	// and for position descriptions with pd_startyear >= 2012
	
	// $sql_qualifier is defined in default-config.php
	$sql_qualifier = $global_pd_sql_qualifier;	
	
	switch($search_content) {
		case "pds":
			// Specify what data fields to query
			$text_sql = "SELECT pd_id, pd_description, pd_startyear FROM position_descriptions " . $sql_qualifier;

			// Specify what data field to use for applied analyses
			$search_field = "pd_description";

			// Specify the name of the field used to indicate the ID of the entity (data object) being analysed
			$entity_id_pointer = "pd_id";
			
			// Specify a semantic descriptor of data object, for tracking purposes below
			$counter_label = "PD";
			break;
		case "qualifications":
			// Specify what data fields to query
			$text_sql = "SELECT pd_id, pd_qualifications_min, pd_qualifications_preferred, pd_startyear FROM position_descriptions " . $sql_qualifier;

			// Specify what data field to use for applied analyses
			$search_field = "pd_qualifications";

			// Specify the name of the field used to indicate the ID of the entity (data object) being analysed
			$entity_id_pointer = "pd_id";

			// Specify a semantic descriptor of data object, for tracking purposes below
			$counter_label = "PD QUALIFICATIONS";
			break;
		case "responsibilities":
			// Specify what data fields to query
			$text_sql = "SELECT pd_id, pd_responsibilities, pd_startyear FROM position_descriptions " . $sql_qualifier;

			// Specify what data field to use for applied analyses
			$search_field = "pd_responsibilities";

			// Specify the name of the field used to indicate the ID of the entity (data object) being analysed
			$entity_id_pointer = "pd_id";

			// Specify a semantic descriptor of data object, for tracking purposes below
			$counter_label = "PD RESPONSIBILITIES";
			break;
	}	
		
	
	// Start looping through tokens and counting their frequencies
	foreach($tokens_array as $token_ind => $token_data) {
	
		// Instantiate an array holding token counts across range of years being analyzed
		$token_counts = array("2012" => array("count" => 0, "entity_ids" => array(), "total_entity_ids" => array()),
							  "2013" => array("count" => 0, "entity_ids" => array(), "total_entity_ids" => array()),
							  "2014" => array("count" => 0, "entity_ids" => array(), "total_entity_ids" => array()),
							  "2015" => array("count" => 0, "entity_ids" => array(), "total_entity_ids" => array()));
		
		$search_token = $token_data["token_label"];
		$token_id = $token_data["token_id"];
		
		// Perform MySQL query to pull position description text in which we'll search for each token
		$result = mysqli_query($con,$text_sql);
		
		// Loop through query results
		while($row = mysqli_fetch_assoc($result)) {
		
			if($search_content === "qualifications") {
				// If searching qualifications, then concatenate min and preferred qualifications fields
				$search_text = $row["pd_qualifications_min"] . " " . $row["pd_qualifications_preferred"];
			} else {
				$search_text = $row[$search_field];
			}
			
			// Get start year of fellowship in position description
			$pd_year = (string)$row['pd_startyear'];
			
			// Get the entity ID for the returned result (position description ID)
			$entity_id = $row[$entity_id_pointer];

			// Add current entity ID to $token_counts array for tracking
			$token_counts[$pd_year]["total_entity_ids"][] = $entity_id;
			
			//////////////////////////////////////////////////////////////////////////////
			//////////////////////////////////////////////////////////////////////////////
			/*
			
			Note: here we're going to be performing essentially the same analyses completed in
			mysql-token-analysis.php; this time, though, we're just searching for and counting 
			instances of KNOWN tokens rather than defining new ones

			*/
			////////////////////////////////////////////////////////////////////////////
			//////////////////////////////////////////////////////////////////////////////

			// Break out search text into sentences (split by period)
			// But first, take out "Ph.D." to avoid weird period splitting results
			$search_text = str_ireplace(array("Ph.D.","Ph.D"),"PhD",$search_text);
	
			// Convert any non-stopping line endings (carriage returns) to periods so that lines can be 
			// split properly below
			$search_text = str_replace("\n",". ",$search_text);

			// Now split text by periods
			// Note here we're splitting by periods followed by at least 1 space so we don't
			// accidentally split by other unexpected uses of periods within words	
			$search_text = explode(". ",$search_text);
		
			// Now loop through sentences
			foreach($search_text as $sentence => $sentence_text) {
	
				if($sentence_text === '' || is_null($sentence_text)) {
					continue;
				}
		
				// Break out unnecessary punctuation in the text to reduce tokens to individual words
				$search_text = preg_replace('~[(),:;\'?!/&]~','',$sentence_text);
		
				// Special punctuation exception: hyphens (-) are converted to spaces
				$search_text = str_ireplace("-"," ",$search_text);		

				// Split the search text into space-delimited tokens (words)
				$original_tokens = preg_split('/\s+/',$search_text);

				// Delete all token exceptions from search text and transform all
				// allowed tokens to lowercase

				// $cleaned_tokens is an array of CLEANED tokens resulting from process below
				$cleaned_tokens = array();	

				// Note: in $cleaned_tokens, we use $i to record the original token index
				// of each word in the sentence from which it has been retrieved
				foreach($original_tokens as $i => $word) {
	
					// Transform token to lowercase and trim
					$check_word = strtolower(trim($word));
		
					// If transformed token is not in list of exceptions AND
					// if non-null, then add to $cleaned_tokens array
					if($check_word == '') {
						continue;
					} else if(!in_array($check_word,$token_exceptions)) {
						$cleaned_tokens[$i] = $check_word;
					} else {
						// IF token IS in list of stop words, record a spacer in the $cleaned_tokens array
						$cleaned_tokens[$i] = encodeStopWord($check_word);
					}
				}
		
				// Get total number of tokens
				$total_tokens = count($cleaned_tokens);
	
				// Now scan through array of tokens at different token lengths
				for($token_length = $min_token_length; $token_length <= $max_token_length; $token_length++) {
					foreach($cleaned_tokens as $token_number => $token) {
						if(strpos($token,$stop_word_delimiter) !== false) {
							continue;
						} else {	
							// Only proceed with parsing token if doesn't run past end of sentence
							if($token_number + $token_length <= $total_tokens) {
								if($token_length == 1) {
									$token_string = $token;
								} else {
								
									// If token length is greater than 1, concatenate consecutive tokens to form string
									$token_string = $token;
									for($i = 1; $i < $token_length; $i++) {
							
										$token_string .= " " . $cleaned_tokens[$token_number + $i];
									}
								}
			
								// Compare $token_string against $search_token we're looking for
								if($token_string === $search_token) {
									$token_counts[$pd_year]["count"]++;
									
									// If the ID for this position description is not listed in the array of
									// PDs in which the token appears, add it
									if(!in_array($entity_id,$token_counts[$pd_year]["entity_ids"])) {
										$token_counts[$pd_year]["entity_ids"][] = $entity_id;
									}
									
								}
							} // END if(token length)
						} // END if([stop word])
					} // END foreach($cleaned_tokens)

				} // END for($token_length)
			} // END foreach($search_text)
		} // END while($row)

		mysqli_free_result($result);
		
		// Assign year counts to variables for processing
		$t_ct_2012 = $token_counts["2012"]["count"];
		$t_ct_2013 = $token_counts["2013"]["count"];
		$t_ct_2014 = $token_counts["2014"]["count"];
		$t_ct_2015 = $token_counts["2015"]["count"];

		print $search_content . "\t" . $search_token . "\t" . $t_ct_2012 . "\t" . $t_ct_2013 . "\t" . $t_ct_2014 . "\t" . $t_ct_2015 . "\n";

		// Insert token count (by year) into token_counts_over_time
		$insert_count_sql = "INSERT INTO token_counts_over_time (token_id, count_2012, count_2013, count_2014, count_2015, pct_2012, pct_2013, pct_2014, pct_2015) " .
				"VALUES ('$token_id'," . $t_ct_2012 . "," . $t_ct_2013 . "," . $t_ct_2014 . "," . $t_ct_2015 . "," .
				count($token_counts["2012"]["entity_ids"])/count($token_counts["2012"]["total_entity_ids"]) . "," .
				count($token_counts["2013"]["entity_ids"])/count($token_counts["2013"]["total_entity_ids"]) . "," .
				count($token_counts["2014"]["entity_ids"])/count($token_counts["2014"]["total_entity_ids"]) . "," .
				count($token_counts["2015"]["entity_ids"])/count($token_counts["2015"]["total_entity_ids"]) . ")";


		if(!mysqli_query($con,$insert_count_sql)) {
			die(mysqli_error($con));
		} else {
			unset($token_counts);
		}																	 
		
	} // END foreach($tokens_array)
	
} // END foreach($search_content)

print "Process complete.\n";
?>