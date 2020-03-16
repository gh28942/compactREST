# compactREST
CompactREST is a ready-to-use deployable REST service. Just upload your xml file and the CompactREST php file to your server and you're ready to go.



<h2><b>Installation</b></h2>
Simply put the server.php and the XML files (e.g. the two provided example XML files) in the same folder on your server:
<br><br>
<img src="https://raw.githubusercontent.com/gh28942/compactREST/master/screenshot/scr-compactrest-1.jpg">
<p align="center">File Upload with FileZilla</p>
<br><br>
<h2><b>Troubleshooting</b></h2>
If some requests aren‘t working, make sure that the files have the correct chmod permissions. Also set the permissions of the folder containing the files.

<h2><b>Your To-Do List</b></h2>
→ change values of the API keys<br>
→ modify POST method for your XML structure<br>

<h2>Example URLs & Requests</h2>

<h3>GET</h3>

	<your-url>/server.php

	<your-url>/server.php/menu/menu//entry/attr/id/24

	<your-url>/server.php/menu/menu//entry/price/21.95

	<your-url>/server.php/menu/menu/appetizers/entry/name/“Buffalo Wings“

	<your-url>/server.php/menu/menu//price/sum()
	<your-url>/server.php/menu/menu//price/count()
	<your-url>/server.php/menu/menu//price/avg()
	<your-url>/server.php/menu/menu//price/min()
	<your-url>/server.php/menu/menu//price/max()

	<your-url>/server.php/menu/menu//entry/price>10
	<your-url>/server.php/menu/menu//entry/price<10/name

	<your-url>/server.php/menu/menu//entry/price>15/price/avg()




<h3>PUT</h3>

	<your-url>/server.php/weather/station/weather/element/description/massive intensity drizzle 
	Content (Body, raw, JSON):
	{
		"API-key" : "CHANGE-THIS-PUT-API-KEY"
	}


<h3>DELETE</h3>

Delete XML entry:

	<your-url>/server.php/menu/menu/sandwiches/entry/attr/id/15
	Content (Body, raw, JSON):
	{
		"API-key" : "CHANGE-THIS-DELETE-API-KEY"
	}


Delete file:

	URL: <your-url>/server.php/menu
	Content (Body, raw, JSON):
	{
		"filename" : "hello-world.txt",
		"API-key" : "CHANGE-THIS-DELETE-API-KEY"
	}


<h3>POST</h3>

Add to XML:

	URL: <your-url>/server.php/weather/weather/object/1
	Content (Body, raw, JSON):
	{
		"some_value" : "rain",
		"some_attribute" : "10.2",
		"API-key" : "CHANGE-THIS-POST-API-KEY"
	}


Upload file:

	URL: <your-url>/server.php/menu
	Content (Body, raw, JSON):
	{
		"filename" : "documents/text/hello-world.txt",
		"content" : "This is an example text file.\nSecond line.",
		"API-key" : "CHANGE-THIS-POST-API-KEY"
	}


<br><br>
<img src="https://raw.githubusercontent.com/gh28942/compactREST/master/screenshot/scr-compactrest-2.jpg">
<p align="center">File upload POST request example with Postman</p>
<br><br>
