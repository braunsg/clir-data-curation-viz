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

mysql-token-analysis.php

Created: 2015-09-20
Modified: 2015-11-05
Steven Braun

This script identifies the token strings with highest occurrence (i.e., most common words/terms/strings)
in CLIR fellowship position description text (position description, qualifications, job duties, etc.), 
transforms those instances into node and link data for
network analyses, and injects the output into the MySQL database

/////////////////////////////////////////////////////////////////////////////////////// */

// Initialize some global variables
include("inc/default-config.php");

// Database connection; parameters defined globally in default_config.php
$con = mysqli_connect($dbhost,$dbuser,$dbpw,$dbname);

// Special functions -- here, a custom function to rank order arrays below (see usage)
function cmp_by_instance($a,$b) {
	return $b["total_count"] - $a["total_count"];
}

// Clear current tables? If TRUE, then truncate listed tables 
// Useful if redoing analyses and want to replace existing data
$clear_current_data = true;

if($clear_current_data == true) {
	print "Clearing tables...\n";
	mysqli_query($con,"TRUNCATE TABLE concordance");
	mysqli_query($con,"TRUNCATE TABLE links");
	mysqli_query($con,"TRUNCATE TABLE tokens");
	print "Clearing process complete.\n\n";
}

// Define token exceptions (stopwords) -- words that should not be included in analysis
// Taken from http://www.ranks.nl/stopwords/ (Default English stopwords list)
$stopwords_filename = "inc/stopwords.txt";
$stopwords_file = fopen($stopwords_filename,"r");
$stopwords = fread($stopwords_file,filesize($stopwords_filename));

// Explode list of stopwords into an array
$token_exceptions = explode("\n",$stopwords);

// Define a special list of exceptions based on this set of data -- 
// For example, words like "university" and "Ph.D." should be ignored for frequency counts
$special_exceptions = array("university","phd","ph.d.","will");
$token_exceptions = array_merge($token_exceptions,$special_exceptions);

// Define a token ID reference/dummy variable
$token_id = 0;

// Specify what we're searching; 'qualifications' (position qualifications), 'responsibilities' (position responsibilities), or 'pds' (position description main body text)
$search_content_settings = array("pds" => true, "qualifications" => true, "responsibilities" => true);

// Define minimum and maximum token search lengths for below
$min_token_length = 1;
$max_token_length = 3;

// Loop through each search content source marked as true above
foreach($search_content_settings as $search_content => $setting) {
	if($setting == false) {
		continue;
	}
	
	// Now split search based on content variable defined above;
	// Note that we're only pulling text from position descriptions for
	// DATA CURATION track fellowships, indicated by position_descriptions.pd_dc = 1
	// and for position descriptions with pd_startyear >= 2012
	
	// $sql_qualifier is defined in default-config.php
	$sql_qualifier = $global_pd_sql_qualifier;	
	
	switch($search_content) {
		case "pds":
			$sql = "SELECT pd_id, pd_description FROM position_descriptions " . $sql_qualifier;
			$counter_label = "PD";
			break;
		case "qualifications":
			$sql = "SELECT pd_id, pd_qualifications_min, pd_qualifications_preferred FROM position_descriptions " . $sql_qualifier;
			$counter_label = "PD QUALIFICATIONS";
			break;
		case "responsibilities":
			$sql = "SELECT pd_id, pd_responsibilities FROM position_descriptions " . $sql_qualifier;
			$counter_label = "PD RESPONSIBILITIES";
			break;

	}


	// Execute MySQL query and retrieve result
	$result = mysqli_query($con,$sql);

	// Total number of returned results
	$total_results = mysqli_num_rows($result);

	// Counter for tracking which result row is currently being analyzed
	$counter = 0;

	// An array that will hold all token string incidence data across all results
	// for each text source type (pds, qualifications, responsibilities)
	$tokens_array = array();

	print "Starting token search process for $search_content...\n";
	sleep(1);
	
	// Loop through returned results from query
	while($row = mysqli_fetch_assoc($result)) {
		$counter++;

		// Grab different text content depending on $search_content definition
		if($search_content === "pds") {

			// Assign PD text to variables
			$pd_descr = $row['pd_description'];

			// Grab the MySQL ID for the PD for later reference
			// Assign PD ID to the $entity_id
			$entity_id = $row['pd_id'];

			// Specify which field to apply analyis
			$search_text = $pd_descr;
			$search_text_descr = "descriptions";
				
		} else if ($search_content === "qualifications") {

			// Assign qualifications text to variables
			$min_qual = $row['pd_qualifications_min'];
			$pref_qual = $row['pd_qualifications_preferred'];

			// Grab the MySQL ID for the PD for later reference
			// Assign PD ID to the $entity_id
			$entity_id = $row['pd_id'];

			// Specify which field to apply analysis
			// Here, we're concatenating the minimum and preferred qualifications fields
			$search_text = $min_qual . " " . $pref_qual;
			$search_text_descr = "qualifications";
		
		
		} else if ($search_content === "responsibilities") {

			// Assign PD text to variables
			$responsibilities = $row['pd_responsibilities'];

			// Grab the MySQL ID for the PD for later reference
			// Assign PD ID to the $entity_id
			$entity_id = $row['pd_id'];

			// Specify which field to apply analysis
			$search_text = $responsibilities;
			$search_text_descr = "responsibilities";
		
		
		}
	
		print $counter_label . " " . $counter . " of " . $total_results . "\t(" . $entity_id . ")\n";		
	
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
			// allowed tokens to lowercase; 
			// $cleaned_tokens is an array of CLEANED tokens resulting from this process
			$cleaned_tokens = array();	
			
			// Note: in $cleaned_tokens, we use $i to record the original token index
			// of each word in the sentence from which it has been retrieved
			foreach($original_tokens as $i => $word) {
	
				// Transform token to lowercase and trim excess whitespace
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
	
			// Now scan through array of tokens at different token lengths, defined above
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
			
							// Define the context for this token instance
							// Token context length: the number of tokens on either side of
							// the token of interest that gives a window of its context
							// in the original text
							$token_context_length = 5;
							
							// Define start of the pretext;
							// If token is near the beginning of the sentence, start
							// may be index 0 (the first word in the sentence)
							$pretext_start = max($token_number - $token_context_length,0);
							if($token_number < $token_context_length) {
								$pretext_length = $token_number;
							} else {
								$pretext_length = $token_context_length;
							}
				
							$pretext = array_slice($original_tokens,$pretext_start,$pretext_length);
							
							// Define post-text slice; here, if the token is near the end of the sentence,
							// array_slice will just return as many as it can until it reaches the last word
							$posttext = array_slice($original_tokens,$token_number + $token_length, $token_context_length);

							// Define array with context information
							// Note: $pretext and $posttext above are arrays, so here we transform them
							// into strings of words separated by spaces
							$instance = array("entity_id" => $entity_id,
											  "pretext" => implode(" ",$pretext),
											  "posttext" => implode(" ",$posttext));
							  
							// If the token string isn't in master $tokens_array, then add/increment in $tokens_array							  
							if(!array_key_exists($token_string,$tokens_array)) {
							
								// Count number of stopwords in token string
								$stopword_ct = substr_count($token_string,$stop_word_delimiter);
								
								// Instantiate new token string entry in $tokens_array
								$tokens_array[$token_string] = array("total_count" => 1, 
																	 "token_id" => "", 
																	 "token_length" => $token_length,
																	 "stopword_count" => $stopword_ct,
																	 "instances" => array());

								// Insert token into table to generate unique ID
								$sql = "INSERT INTO tokens (token_label,token_length,token_stop_words_ct,token_source_type) " .
										"VALUES ('" . mysqli_real_escape_string($con,$token_string) . "',$token_length,$stopword_ct,'$search_content')";

								if(!mysqli_query($con,$sql)) {
									die(mysqli_error($con));
								} else {
									
									// Retrieve newly defined token ID
									$token_row_index = mysqli_insert_id($con);
									
									// Token ID will be an alphanumeric string, left-padded with 0s
									$token_id = "token_" . str_pad($token_row_index,7,'0',STR_PAD_LEFT);
									$tokens_array[$token_string]["token_id"] = $token_id;
									
									// Insert newly minted ID into tokens table
									$update_token_id = "UPDATE tokens SET token_id = '$token_id' WHERE token_row_index = $token_row_index";
									if(!mysqli_query($con,$update_token_id)) {
										die(mysqli_error($con));
									}
									
								}
																	 
							} else {
							
								// Else if token string is already in $tokens_array, then just increment count of occurrence
								$tokens_array[$token_string]["total_count"]++;
							}

							// Now add instance of this token
							$tokens_array[$token_string]["instances"][] = $instance;
						}
					} // END if([stop word])
				} // END foreach($cleaned_tokens)
			} // END for($token_length)
		} // END foreach($sentence_text)
	} // END while($row = mysqli_fetch_assoc($result))

	// Now sort through token frequencies, ranked in descending order of "total_count"
	uasort($tokens_array, "cmp_by_instance");

	// Define the max number of tokens we're going to record in tokens table
	// this parameter is defined in default-config.php
	$max_tokens = $global_max_token_count;

	$tokens_array = array_slice($tokens_array,0,$max_tokens);

	print "Done performing token search.\n";
	print "Pulling out token instance contexts...\n";
	sleep(1);
	
	// Define numeric token index for tracking
	$token_index = 0;
	
	// Now loop through each token and record instances in table token_instances
	foreach($tokens_array as $token_label => $token_data) {
		$token_index++;
		$token_id = $token_data["token_id"];
		$token_length = $token_data["token_length"];
		$stopword_ct = $token_data["stopword_count"];

		// Define an empty array to hold IDs for position descriptions in which the token appears
		$entity_ids_array = array();
	
		print "\tTOKEN " . $token_index . "/" . $max_tokens . "\n";
		
		// Add token data to token_instances summary table
		foreach($token_data["instances"] as $ind => $instance) {
			$entity_id = $instance["entity_id"];
			if(!in_array($entity_id,$entity_ids_array)) {
				$entity_ids_array[] = $instance["entity_id"];
			}
			
			$sql = "INSERT INTO token_instances (token_id,token_pretext,token_posttext,entity_id,entity_type) " .
					"VALUES ('$token_id','" . mysqli_real_escape_string($con,$instance["pretext"]) . "','" . mysqli_real_escape_string($con,$instance["posttext"]) . "','" . $entity_id . "','" . $search_content . "')";
			if(!mysqli_query($con,$sql)) {
				print $sql . "\n";
				die(mysqli_error($con));
			}
		}	

		// Now update tokens table
		$token_count = $token_data["total_count"];
		$entity_ct = count($entity_ids_array);
		$entity_coverage_pct = round($entity_ct/$total_results,2);
	
		$sql = "UPDATE tokens SET token_instance_ct = $token_count, token_entity_ct = $entity_ct, token_coverage_pct = $entity_coverage_pct WHERE token_id = '$token_id'";

		if(!mysqli_query($con,$sql)) {
			die(mysqli_error($con));
		}
		$tokens_array[$token_label]["entity_ids"] = $entity_ids_array;
		unset($entity_ids_array);
	}
} // END foreach($search_content)

print "Total process complete.\n";

?>