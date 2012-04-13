<?php

$poolSize = 20;
$numRequestsInPool = 0;
$numFinishedFiles = 0;
$mh = curl_multi_init();
$fileHandle = @fopen('domains.txt', 'r');
if ($fileHandle) {

	while(1){

		// Refill pool while its not full and there are lines left in the file. 
		print("\n\n\n".'Added:');
		while($numRequestsInPool < $poolSize && ($line = fgets($fileHandle)) !== false){

			$domain = trim($line);

			// Check if it's in the cache.
			$cachedFilePath = cachedFilePath($domain);
			if(is_file($cachedFilePath)){

			}else{

				print("\n".$domain );

				// Initiate download.
	
				// Set up curl to download the frontpage.
				$URL = 'http://www.'.$domain;
				$ch = curl_init($URL);
				curl_setopt_array($ch, array(
					CURLOPT_RETURNTRANSFER	=> true,
					CURLOPT_FOLLOWLOCATION	=> true,
					CURLOPT_MAXREDIRS		=> 3,
					CURLOPT_TIMEOUT			=> 60,
					CURLOPT_CONNECTTIMEOUT  => 10, 
				));
				
				// Remember what domain this handle is downloading.
				$handleToDomain[$ch] = $domain;
		
				// Add the request to the pool.
				curl_multi_add_handle($mh, $ch);
				++$numRequestsInPool;
			}
		}

		print("\n\n".'Processing cURL: ');
		do {
			print('*');
			$mrc = curl_multi_exec($mh, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		
		if ($active && $mrc == CURLM_OK) {

			print("\n\n".'Waiting for data...');
			if (curl_multi_select($mh) != -1) {

				print("\n\n".'Processing cURL: ');
				do {
					print('*');
					$mrc = curl_multi_exec($mh, $active);
				} while ($mrc == CURLM_CALL_MULTI_PERFORM);
			}
		}
	
		// Handle finished requests.
		print("\n\n\n".'Finished:');
		while(false !== $handleInfo = curl_multi_info_read($mh)){

			// Check if the handle is done.
			if($handleInfo['msg'] == CURLMSG_DONE){
				
				$domain = $handleToDomain[$handleInfo['handle']];

				print("\n".$domain );

				// Read the page from the handle.
				$pageContent = curl_multi_getcontent($handleInfo['handle']);
				
				// Save the page to disk.
				file_put_contents(cachedFilePath($domain), gzdeflate($pageContent));
				++$numFinishedFiles;

				// Remove the handle from the pool.
				curl_multi_remove_handle($mh, $handleInfo['handle']);
				curl_close($handleInfo['handle']);
				unset($handleToDomain[$handleInfo['handle']]);
				--$numRequestsInPool;
			}
		}
		print("\n\n".'Downloaded '.$numFinishedFiles.' files this session.');
	
		// Are we done yet?
		if(feof($fileHandle) && !$numRequestsInPool)
			break;
	}

	curl_multi_close($mh);
	fclose($fileHandle);
	
	print("\n\n".'done');
}


function cachedFilePath($domain){

	// Use the first 2 characters of the file name as the dir.
	$dir = 'cache/' . substr($domain, 0, min(2, strpos($domain, '.'))) . '/';

	// Create it if necessary.
	if(!is_dir($dir))
		mkdir($dir);
	
	return $dir . $domain;
}

