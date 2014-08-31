<?php

require('misc.web.php');
require('class.extendedclass.php');
require('class.domdocument2xpath.php');
require('class.websource.php');
require('class.websource_tyda.php');

$base_url = 'http://www.tyda.se';
$base_url_here = 'http://labs.andreineculau.com/m.tyda.se';

$tyda = new Websource_Tyda();
$content = $tyda->content($_GET['w']);
//fix tyda.se
/*$content = str_replace('tyda\.se', '!!!', $content);
$content = str_replace('http://www.tyda.se/search/', 'labs.andreineculau.com/m.tyda.se/?word=', $content);
$content = str_replace('http://www.tyda.se', 'labs.andreineculau.com/m.tyda.se', $content);
$content = str_replace('www.tyda.se/search/', 'labs.andreineculau.com/m.tyda.se/?word=', $content);
$content = str_replace('www.tyda.se', 'labs.andreineculau.com/m.tyda.se', $content);*/
//fix images
$content = str_replace('src="/', 'src="' . $base_url . '/', $content);
$content = str_replace('src=\'/', 'src=\'' . $base_url . '/', $content);
//fix links
$content = str_replace('href="/search/', 'href="' . $base_url_here . '/?w=', $content);
$content = str_replace('href="/', 'href="' . $base_url_here . '/', $content);
$content = str_replace('href=\'/', 'href=\'' . $base_url_here . '/', $content);
//no scripts
$content = preg_replace('@<script[^>]*?.*?</script>@siu', '', $content);

echo file_get_contents('header.txt');
echo $content;
echo file_get_contents('footer.txt');