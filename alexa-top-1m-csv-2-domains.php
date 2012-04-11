<?php

$inFile = @fopen('top-1m.csv', 'r');
$outFile = @fopen('domains.txt', 'a');

if ($inFile && $outFile) {

	// Read line by line until failing.
	while(($line = fgets($inFile)) !== false){
		
		// Extract the URL from the line.
		list(, $URL) = explode(',', $line);

		// Get the domain from the URL, ignoring errors.
		$domain = parse_url('http://'.trim($URL), PHP_URL_HOST);
		if(!$domain)
			continue;

		// Save the domain.
		fwrite($outFile, $domain."\n");
	}

	fclose($outFile);
	fclose($inFile);
	
}

print('done');
