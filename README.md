Using cURL and PHP to download lots and lots of files
=====================================================
And then searching them for ASCII art
-------------------------------------

Why?
----

*Because*.

How?
----

All the scripts are intended to be run from the command line. Aside from enabling piping and other nifty Unixy functionality, it lets you follow the progress in realtime, while Apache and your browser normally would send the script output in chunks a few kilobytes each.

### Prepare

	$ php alexa-top-1m-csv-2-domains.php

The web stats site Alexa publishes a list of the top one million domains. Download it and convert the CSV to a file with one domain per line with this script.

### Requirements

	$ sudo apt-get install php5-curl php5-tidy

### Download a million files

	$ php download.php

A cURL multihandle is executed in a loop. As downloads complete and the respective handle s are removed, more URLs are added to the multihandle, keeping the number of downloads in progress constant.

I've ran this code reliably for hundreds of thousands of files in a single session.

It could trivially be made into a more serious tool by reading a URL list from stdin, and using the hash of the URL as the cache file name. Feel free to fork it.

### Detecting asciiart

	$ php detect-asciiart.php

I found only one other algorithm to do it, and it wasn't very good. I just use a few simple heuristics, with a blacklist being the most important. I want to make as few assumptions as possible about what constitutes ASCII art. Hence, I don't try to look for more examples of what I have already seen, but just filter out what I can be sure of is *not* art. This means a more sophisticated approach like a Bayesian filter is not useful in this context.


Your code looks like crap
-------------------------

It does, doesn't it? You are welcome to fork it!
