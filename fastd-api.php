#!/usr/bin/php -f
<?php
$url = 'http://register.freifunk-myk.de/srvapi.php';
$out = '/etc/fastd/vpn/peers/';


//Check permissions 
if(!is_dir($out)) die('Output dir missing');
if(!is_writable($out)) die('Output dir not writable');
 
//Import new nodes
$data = file_get_contents($url);
$data = unserialize($data);
 
$active=array();
foreach($data as $router) {
	if(!preg_match('/^[A-F0-9]{2}:[A-F0-9]{2}:[A-F0-9]{2}:[A-F0-9]{2}:[A-F0-9]{2}:[A-F0-9]{2}$/', $router['MAC'])) {
		trigger_error('Router mit falscher MAC?!', E_USER_WARNING);
	}elseif(!preg_match('/^[A-F0-9]{64}$/', $router['PublicKey'])) {
		trigger_error('Router mit falschem Key?!', E_USER_WARNING);
	}else{
		$filename='client_'.str_replace(':', '-', $router['MAC']);
		$fp=fopen($out.$filename, 'w');
		fwrite($fp, 'key "'.$router['PublicKey'].'";'."\n");
		fclose($fp);
		$active[] = $filename;
	}
}

//Check if we fscked up
if(count($active) < 10) die('Less than 10 nodes? Database broken?');

//Remove orphan nodes
$dh = opendir($out);
while(($file = readdir($dh)) !== false) {
	if($file != '.' && $file != '..') {
		if(!in_array($file, $active) && (strpos($file, 'client_') !== false)) {
			unlink($out.$file);
		}
	}
}
 
//Reload fastd
exec('killall -SIGHUP fastd');
 
?>

