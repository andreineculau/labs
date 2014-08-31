<?php
$currency_list = array('EUR', 'USD', 'SEK', 'PLN');
$days = $_REQUEST['days']?$_REQUEST['days']:'14';
$until = $_REQUEST['until']?$_REQUEST['until']:date('Y-m-d');

$newdate = strtotime('-'.$days.' day', strtotime($until));
$from = date('Y-m-d', $newdate);

$years = range(2008, 2005);
$months = range(12, 1);
$month_name = array();
foreach ($months as $month){
	$timestamp = mktime(0, 0, 0, $month, 1, 2008);
	$months_name[$month] = date("M", $timestamp);
} unset($timestamp); unset($month);
$days_available = array(365, 270, 180, 90, 60, 30);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="icon" href="/cursbnr/favicon.ico" />
<link rel="SHORTCUT ICON" href="/cursbnr/favicon.ico" />
<title>CursBNR.ro</title>
</head>

<body>
Showing <b><?php echo $days ?> days</b> of data provided by <a href="http://www.cursbnr.ro">www.cursbnr.ro</a><br/>
<form method="get">
	<input type="hidden" name="days" value="<?php echo $days?>">
	<div style="float:left;width:100px">Reference</div>
	<select name="until">
		<option value="">Today</option>
		<?php foreach ($years as $year)
			foreach ($months as $month) {
				$selected = "";
				if ($until == "$year-$month-01") $selected = ' selected="true"'; ?>
				<option value="<?php echo "$year-$month-01"?>"<?php echo $selected ?>><?php echo "$year " . $months_name[$month] ?></option>
			<?php } ?>
	</select>
	<input type="submit" value="Set">
</form>
<div style="float:left;width:100px">Span over</div>
<?php foreach ($days_available as $days_span) { ?>
	<a href="?days=<?php echo $days_span ?>&until=<?php echo $until ?>"><?php echo (int) ($days_span/30) ?> months</a> &middot;
<? } ?>	
<a href="?days=14">2 weeks</a>
<div style="margin:5px;height:1px;background-color:#ccc;width:300px"></div>
<div style="float:left">
<?php for ($i=0; $i<count($currency_list); $i++) {
	$currency = $currency_list[$i]; ?>
	<?php if ($i == 2) echo '</div><div style="float:left;margin-left:5px">'?>
	<h3><?php echo $currency ?></h3>
	<img style="max-width:600px" src="http://cursbnr.ro/grafic.php?currency=<?php echo $currency ?>&from=<?php echo $from ?>&until=<?php echo $until ?>" alt="" /><br/><br/>
<?php } ?>
</div>
</body>
</html>
