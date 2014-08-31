<?php
$key = '86a2266c-aa60-4a69-82e3-d19e9e520177';
$option = ($_REQUEST['option'] && $_REQUEST['option'] < 4)?$_REQUEST['option']:0;
$showing = array(
	'airports with direct flights to Stockholm',
	'cheapest direct flights to Stockholm',
	'airports with direct flights from Stockholm',
	'cheapest direct flights from Stockholm'
);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript" src="http://api.skyscanner.net/api/ajax/loader.js?key=<?php echo $key ?>"></script>
<title>SkyScanner.net</title>
</head>

<body>
Showing <b><?php echo $showing[$option] ?></b>
available on <a href="http://www.skyscanner.net">www.skyscanner.net</a><br>
Show airports with direct flights <a href="?option=0">to</a> or <a href="?option=2">from</a> Stockholm<br>
Show cheapest direct flights <a href="?option=1">to</a> or <a href="?option=3">from</a> Stockholm<br>
<div style="margin:5px;height:1px;background-color:#ccc;width:300px"></div>
<?php
	switch ($option) {
		case 0:
			?> 
			<script type="text/javascript">  
				skyscanner.load("maps", "1");  
			  var map;  
				function main(){  
					map = new skyscanner.maps.Map();  
					map.setCulture("en");  
					map.setRoute("","STOC");  
					map.setSize(1000,400);   
					map.setFilter("direct",true);   
					map.setResetAsInitialRouteEnabled(true);   
					map.setDestinationResetEnabled(false);   
					map.draw(document.getElementById("map"));  
				};  
				skyscanner.setOnLoadCallback(main);  
			</script>
			<?
			break;
		case 1:
			?>
			<script type="text/javascript">  
				skyscanner.load('snippets','1');  
				function main(){  
					var snippet=new skyscanner.snippets.SearchPanelControl();  
					snippet.setCurrency('EUR');  
					snippet.setDestination('stoc');  
					snippet.setShape('box400x400');  
					snippet.draw(document.getElementById('map'));  
					}  
				skyscanner.setOnLoadCallback(main);  
			</script> 
			<?
			break;
		case 2:
			?>
			<script type="text/javascript">  
				skyscanner.load("maps", "1");  
			  var map;  
				function main(){  
					map = new skyscanner.maps.Map();  
					map.setCulture("en");  
					map.setRoute("STOC","");  
					map.setSize(1000,400);   
					map.setFilter("direct",true);   
					map.setResetAsInitialRouteEnabled(true);   
					map.setDepartureResetEnabled(false);   
					map.draw(document.getElementById("map"));  
				};  
				skyscanner.setOnLoadCallback(main);  
			</script>
			<?
			break;
		case 3:
			?>
			<script type="text/javascript">  
				skyscanner.load('snippets','1');  
				function main(){  
					var snippet=new skyscanner.snippets.SearchPanelControl();  
					snippet.setCurrency('EUR');  
					snippet.setDeparture('stoc');  
					snippet.setShape('box400x400');  
					snippet.draw(document.getElementById('map'));  
					}  
				skyscanner.setOnLoadCallback(main);  
			</script> 
			<?
			break;
	}
?>
<div id="map">
<?php
	switch ($option) {
		case 0:
		case 1:
			?><a href="http://www.skyscanner.net/flights-to/stoc/cheap-flights-to-stockholm.html" target="_blank">Cheap Flights to Stockholm</a> <?
			break;
		case 2:
		case 3:
			?><a href="http://www.skyscanner.net/flights-from/stoc/cheap-flights-from-stockholm.html" target="_blank">Cheap Flights from Stockholm</a> <?
			break;
	}
?>
</div>
</body>
</html>
