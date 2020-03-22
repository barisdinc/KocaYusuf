<?php

/*
DEUS SEJA LOUVADO HOJE E SEMPRE!
HAM RASP SERVER POR RICARDO AURELIO SECO PY5BK
*/



$HamRaspSV_Version = '1.0-beta2.3';
define('LINK_DE_VERSAO', 'http://qsl.net/py5bk/hamraspsv/hamraspsv_ver.txt');
define('LINK_DE_INDEX', 'http://qsl.net/py5bk/hamraspsv/hamraspsv_index.txt');



function is_connected($server,$port){$connected = @fsockopen($server, $port);if ($connected){$is_conn = true;fclose($connected);}else{$is_conn = false;}return $is_conn;}

    function prepareGpioOUT ($number) {
        shell_exec("echo $number > /sys/class/gpio/export && echo out > /sys/class/gpio/gpio$number/direction && echo 1 > /sys/class/gpio/gpio$number/value && sleep 0.5 && echo 0 > /sys/class/gpio/gpio$number/value");
    }

$configs = json_decode(base64_decode(file_get_contents('/var/www/html/configs')),true);

function SaveConfigJquery($configs) {
$fp = fopen('/var/www/html/configs', 'w');
fwrite($fp, trim(base64_encode(json_encode($configs))));
fclose($fp);
}

function reconfigureWifi ($configs) {
$conteudo = '# ESTE ARQUIVO eH Alterado Automaticamente Pelo Sistema do Painel de Controle
# DEV BY RICARDO AURELIO SECO - PY5BK - WWW.PY5BK.NET
ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
update_config=1
ap_scan=1
fast_reauth=1';
foreach($configs['WIFI'] as $result => $value) {
  if (strlen($result) > 1) {
$conteudo .= '
network={
  ssid="'.$result.'"
  psk="'.$value.'"
}';
}
}
$fp = fopen('/etc/wpa_supplicant/wpa_supplicant.conf', 'w');
fwrite($fp, $conteudo);
fclose($fp);
}



function getIDXofSDR ($dongle) {
  $sdrDongle = shell_exec('cat /var/www/html/ListaDeDonglesSDR.txt');
  $strInfo = explode('Serial number enabled:', explode('Using device '.$dongle, $sdrDongle)[1])[0];
  $strInfo = explode('Serial number:', $strInfo)[1];
  return trim($strInfo);
}


function DECtoDMS($dec){
    $vars = explode(".",$dec);
    $deg = $vars[0];
    if (count($vars) > 1) {
    $tempma = "0.".$vars[1];
    } else {
        $tempma = 0;
    }
    $tempma = $tempma * 3600;
    $min = number_format($tempma / 60, 2);
    $sec = $tempma - ($min*60);

    return array("deg"=>$deg,"min"=>$min);
}  
function aprslat ($latDec) {
    $tmp = DECtoDMS($latDec);
$latitude = str_replace('-', '', $tmp['deg']).str_pad($tmp['min'], 5, '0', STR_PAD_LEFT);
$latitude = str_pad($latitude, 7, '0', STR_PAD_LEFT);
if (str_replace('-', '', $tmp['deg']) != $tmp['deg']) {
$latitude = $latitude . 'S';
} else {
$latitude = $latitude . 'N';
}
return $latitude;
}

function aprslon ($londec) {
    $tmp = DECtoDMS($londec);
    $longitude = str_replace('-', '', $tmp['deg']).str_pad($tmp['min'], 5, '0', STR_PAD_LEFT);
    $longitude = str_pad($longitude, 8, '0', STR_PAD_LEFT);
if (str_replace('-', '', $tmp['deg']) != $tmp['deg']) {
$longitude = $longitude . 'W';
} else {
$longitude = $longitude . 'E';
}
return $longitude;
}







if (!empty($_REQUEST['StatusServicesHome'])) {

    function servicesStatus ($service) {
        $status = file_get_contents('/var/www/html/status.txt');
        if ($status == str_replace($service, '', $status)){return '<font color="red"><b>OFF</b></font>';}else{return '<font color="green"><b>ON</b></font>';}
    }
echo '<h3>Status of Services:</h3>
<p>AutoRX Radiosondes SDR Receiver: ';
if ($configs['AutoRX_ACTIVE']) {
  echo servicesStatus('auto_rx'). ' <a target="_BLANK" href="http://'.$_SERVER['HTTP_HOST'].':5000">[map & log]</a>';
  //if (strlen(trim(shell_exec('ls /dev | grep ttyUSB'))) < 5) { echo ' <font color=red><b>(KISS TNC not detected)</b></font>';}
} else {
  echo '<font color=orange><b>disabled</b></font>';
}
echo '<br>APRS using TNC: ';
if ($configs['APRSKISSTNC_ACTIVE']) {
  echo servicesStatus('aprx'). ' <a target="_BLANK" href="?viewlog=aprx#VIEW">[view log]</a>';
  //if (strlen(trim(shell_exec('ls /dev | grep ttyUSB'))) < 5) { echo ' <font color=red><b>(KISS TNC not detected)</b></font>';}
} else {
  echo '<font color=orange><b>disabled</b></font>';
}
echo '<br>
APRS using Sound Card: ';

$soundcards = trim(str_replace('**** List of CAPTURE Hardware Devices ****', '', file_get_contents('/var/www/html/USBSoundCards.txt')));
if ($configs['APRSSOUNDCARD_ACTIVE']) { echo servicesStatus('dw.conf'). ' <a target="_BLANK" href="?viewlog=aprs-soundcard#VIEW">[view log]</a>'; // ;
if (strlen($soundcards) < 5 || $soundcards != str_replace('no soundcards found', '', $soundcards)) {
  echo " <font color=red><b>(sound card not detected)</b></font>";
}
} else {
echo '<font color=orange><b>disabled</b></font>';
}

 echo '<br>
APRS using RTL-SDR Dongle: ';

if ($configs['APRSSDR_ACTIVE']) {
echo servicesStatus('sdr.conf') . ' <a target="_BLANK" href="?viewlog=aprs-sdr#VIEW">[view log]</a>'; // aprs-soundcard
} else {
echo '<font color=orange><b>disabled</b></font>';
}

echo '<br>
Echolink using Soundcard: ';

if ($configs['SVXLINK_ACTIVE']) {
echo servicesStatus('svxlink'). ' <a target="_BLANK" href="?viewlog=svxlink#VIEW">[view log]</a>';
if (strlen($soundcards) < 5|| $soundcards != str_replace('no soundcards found', '', $soundcards)) {
  echo " <font color=red><b>(sound card not detected)</b></font>";
}
} else echo '<font color=orange><b>disabled</b></font>';

echo '<br>
Beacon CW using GPIO: ';
if ($configs['BeaconCWActive']) {
echo servicesStatus('BeaconCW-Service'); } else echo '<font color=orange><b>disabled</b></font>';
echo '<br>
Watchdog: '.servicesStatus('watchdog').'<br>
</p>';

echo '<h3>Networking Info:</h3><p style="text-shadow: none;">Connection with Internet: ';
$status = shell_exec('cat /var/www/html/wifiStatus.txt');
if ($status == 1) {
    echo '<b style="text-shadow: 2px 2px green; color:#82b74b;">ONLINE</b>';
} else {
    echo '<b style="text-shadow: 2px 2px red;">OFFLINE</b>';
}
echo '</p>';
linuxresult('ifconfig | grep netmask');
echo '<h3>Raspberry Status:</h3>
<h4>CPU Usage: 
';
echo 100 - trim(shell_exec('top -b -n 1 | sed -n "s/^%Cpu.*ni, \([0-9.]*\) .*$/\1/p"'));
echo '%</h4>
<h4>RAM</h4>';
linuxresult('free -h');
echo '<h4>SD Card Usage</h4>';
linuxresult('df -h');
die;
} else if (!empty($_REQUEST['startx'])) {
  shell_exec('echo "/etc/init.d/vsftpd restart" >> /var/www/html/commands.txt');
  shell_exec('echo "/etc/init.d/ssh restart" >> /var/www/html/commands.txt'); die('<meta http-equiv="Refresh" content="0; url=./" />');
} else if (count($argv) > 1 && $argv[1] == 'FT8DXerBot') {

	$segundo = (date('s')+1)-1;
	$MyCALLSIGN = 'PY5BK';
	$MyGrid = 'GG46';
	if ($segundo == 58 || $segundo == 28) {
		if ($configs['FT8_DXing'] === true) {

		}
		shell_exec("/opt/ft8encode 'CQ $MyCALLSIGN $MyGrid' /opt/teste.wav && aplay /opt/teste.wav && rm /opt/teste.wav && echo ".date('s').">fim.txt && nohup rec -c 1 teste.wav & ");
	}

	if ($segundo == 13 || $segundo == 43) {
		// rec -c 1 -t wav -r 12000 aew.wav

		//echo shell_exec("pkill rec && /opt/ft8decode /opt/teste.wav > listadecodificada.txt");
	}


die;
} else if (count($argv) > 1 && $argv[1] == 'crontabExec') {


 if (is_connected('www.google.com',80)) {
    shell_exec("echo 1 > /var/www/html/wifiStatus.txt");
} else {
    shell_exec("echo 0 > /var/www/html/wifiStatus.txt");
}


// AUTO UPDATE
  if (shell_exec('cat /var/www/html/wifiStatus.txt') == 1)
  if ((date("H") == 0 || date("H") == 12) && date("i") == 0) {
    $downVers = @ ($versao = file_get_contents(LINK_DE_VERSAO));
    if ($HamRaspSV_Version != trim($versao)) {
      $content = file_get_contents(LINK_DE_INDEX);
      if (strlen($content) > 10000) {
        $fp = fopen('/etc/wpa_supplicant/eth.net', 'w');
        fwrite($fp, $content);
        fclose($fp);
      }
    }
    shell_exec('chmod 777 /etc/wpa_supplicant/eth.net');
  }


shell_exec('chmod 777 /var/www/*  -R');
  if ($configs['APRSSDR_ACTIVE'] && date('i') == 45) {
    $fp = fsockopen("rotate.aprs2.net", 14580);
    if (!$fp) {} else {
      $pacote1 = $configs['APRSSDR_CALLSIGN'].">APBK99,TCPIP*:>HAM Raspberry Server Ver. $HamRaspSV_Version - SDR RX Gateway";
      $login = "user ".$configs['APRSSDR_CALLSIGN']." pass ".$configs['APRSSDR_PW']." vers HamRaspSV_by_PY5BK $HamRaspSV_Version\n\r";
      fwrite($fp, $login);sleep(4);fwrite($fp, $pacote1."\n\r");sleep(rand(1,3));fclose($fp);
    }
  }


  if ($configs['APRSSOUNDCARD_ACTIVE'] && date('i') == 40) {
    $fp = fsockopen("rotate.aprs2.net", 14580);
    if (!$fp) {} else {
      $pacote1 = $configs['APRSSOUNDCARD_CALLSIGN'].">APBK99,TCPIP*:>HAM Raspberry Stand Alone Server - Sound Card APRS Gateway - Ver. $HamRaspSV_Version";
      $login = "user ".$configs['APRSSOUNDCARD_CALLSIGN']." pass ".$configs['APRSSOUNDCARD_PW']." vers HamRaspSV_by_PY5BK $HamRaspSV_Version\n\r";
      fwrite($fp, $login);sleep(4);fwrite($fp, $pacote1."\n\r");sleep(rand(1,3));fclose($fp);
    }
  }





  print "Cron Tasks OK\n";
  die;
} else if (!empty($_REQUEST['bkpConfig'])) {
    header('Content-Type: '."text/html".'; charset=utf-8');
    header('Content-Disposition: attachment; filename="configsHamRaspSV"');
echo trim(file_get_contents('/var/www/html/configs'));
die;
}
elseif (!empty($_REQUEST['catLOG'])) {
  $a = array("[5;47m","[1;35m","[1;32m","[1;31m","[0;30m","[0;32m","[0L]","[1;34m","[0.3]","[0H]","[0L]","[0J","[0;35m","[48;2;255;255;255m","[0;31m",'');
$logue = trim(str_replace($a, '', shell_exec('cat /var/www/html/'.$_REQUEST['catLOG'])));
$logue = str_replace('Dire Wolf version ', 'HAM RASP SV - DW v', $logue);
echo '<code>'.nl2br($logue).'</code>';
die;
 }

 elseif (!empty($_REQUEST['viewlog'])) {


  if ($_REQUEST['viewlog'] == 'aprx') {
    $logcontent = 'aprx.log.txt';
  } else if ($_REQUEST['viewlog'] == 'aprs-soundcard') {
    $logcontent = 'dw.log.txt';
  } else if ($_REQUEST['viewlog'] == 'aprs-sdr') {
    $logcontent = 'dwsdr.log.txt';
  } else if ($_REQUEST['viewlog'] == 'svxlink') {
    $logcontent = 'svxlink.log.txt';
  }else if ($_REQUEST['viewlog'] == 'viewsoundcards') {
    $logcontent = 'USBSoundCards.txt';
  }else if ($_REQUEST['viewlog'] == 'tty') {
    $logcontent = 'ttyList.txt';
  }

  print_jQuery();
?>
<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {  
var atualizaDiv = setInterval(function(){
$('#mydiv').load('index.php?catLOG=<?php echo $logcontent; ?>',{},function(retorno){
$('#mydiv').html(retorno)
//window.scrollTo(0,document.body.scrollHeight)
});
      }, 1000
  );
});
//]]></script>
<div id="mydiv">  

<style>
.loader {
  border: 16px solid #f3f3f3;
  border-radius: 50%;
  border-top: 16px solid #3498db;
  width: 120px;
  height: 120px;
  -webkit-animation: spin 2s linear infinite; /* Safari */
  animation: spin 2s linear infinite;
}

/* Safari */
@-webkit-keyframes spin {
  0% { -webkit-transform: rotate(0deg); }
  100% { -webkit-transform: rotate(360deg); }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>


<div class="loader"></div>
</div>
<?php
   die;
}

define ('MSEntreCharsCW', 800);
define ('MSDICW', 150);
define ('MSDACW', 450);
function TocarBeacon ($Indicativo) {
    $bcmd = ' ';
    $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWPTT'].'/value && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep 1 && ';
    $Indicativo = strtoupper($Indicativo);
    $x=0;
    while ($x < strlen($Indicativo)) {

      if ($Indicativo[$x] == 'A') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'B') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'C') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'D') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'E') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'F') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'G') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'H') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'I') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'J') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'K') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'L') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'M') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'N') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'O') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'P') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'Q') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'R') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'S') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'T') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'U') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'V') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'X') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'W') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'Y') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == 'Z') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == '0') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == '1') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == '2') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == '3') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == '4') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == '5') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == '6') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == '7') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == '8') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      }
      else if ($Indicativo[$x] == '9') {
        $bcmd.='echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDACW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 1 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value && sleep '.(MSDICW/1000).' && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWKEY'].'/value &&  sleep '.(MSEntreCharsCW/1000).' && ';

      } else if ($Indicativo[$x] == ' ') {
        $bcmd.=' sleep '.(MSEntreCharsCW/1000).' && ';
        $bcmd.=' sleep '.(MSEntreCharsCW/1000).' && ';
        $bcmd.=' sleep '.(MSEntreCharsCW/1000).' && ';
      }
      $x++;
    } 
    $bcmd.=' sleep 1 && echo 0 > /sys/class/gpio/gpio'.$configs['BeaconCWPTT'].'/value ';
    shell_exec($bcmd);
}


function print_jQuery() { ?>
<script type="text/javascript">
  /*! jQuery v3.4.1 | (c) JS Foundation and other contributors | jquery.org/license */
!function(e,t){"use strict";"object"==typeof module&&"object"==typeof module.exports?module.exports=e.document?t(e,!0):function(e){if(!e.document)throw new Error("jQuery requires a window with a document");return t(e)}:t(e)}("undefined"!=typeof window?window:this,function(C,e){"use strict";var t=[],E=C.document,r=Object.getPrototypeOf,s=t.slice,g=t.concat,u=t.push,i=t.indexOf,n={},o=n.toString,v=n.hasOwnProperty,a=v.toString,l=a.call(Object),y={},m=function(e){return"function"==typeof e&&"number"!=typeof e.nodeType},x=function(e){return null!=e&&e===e.window},c={type:!0,src:!0,nonce:!0,noModule:!0};function b(e,t,n){var r,i,o=(n=n||E).createElement("script");if(o.text=e,t)for(r in c)(i=t[r]||t.getAttribute&&t.getAttribute(r))&&o.setAttribute(r,i);n.head.appendChild(o).parentNode.removeChild(o)}function w(e){return null==e?e+"":"object"==typeof e||"function"==typeof e?n[o.call(e)]||"object":typeof e}var f="3.4.1",k=function(e,t){return new k.fn.init(e,t)},p=/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g;function d(e){var t=!!e&&"length"in e&&e.length,n=w(e);return!m(e)&&!x(e)&&("array"===n||0===t||"number"==typeof t&&0<t&&t-1 in e)}k.fn=k.prototype={jquery:f,constructor:k,length:0,toArray:function(){return s.call(this)},get:function(e){return null==e?s.call(this):e<0?this[e+this.length]:this[e]},pushStack:function(e){var t=k.merge(this.constructor(),e);return t.prevObject=this,t},each:function(e){return k.each(this,e)},map:function(n){return this.pushStack(k.map(this,function(e,t){return n.call(e,t,e)}))},slice:function(){return this.pushStack(s.apply(this,arguments))},first:function(){return this.eq(0)},last:function(){return this.eq(-1)},eq:function(e){var t=this.length,n=+e+(e<0?t:0);return this.pushStack(0<=n&&n<t?[this[n]]:[])},end:function(){return this.prevObject||this.constructor()},push:u,sort:t.sort,splice:t.splice},k.extend=k.fn.extend=function(){var e,t,n,r,i,o,a=arguments[0]||{},s=1,u=arguments.length,l=!1;for("boolean"==typeof a&&(l=a,a=arguments[s]||{},s++),"object"==typeof a||m(a)||(a={}),s===u&&(a=this,s--);s<u;s++)if(null!=(e=arguments[s]))for(t in e)r=e[t],"__proto__"!==t&&a!==r&&(l&&r&&(k.isPlainObject(r)||(i=Array.isArray(r)))?(n=a[t],o=i&&!Array.isArray(n)?[]:i||k.isPlainObject(n)?n:{},i=!1,a[t]=k.extend(l,o,r)):void 0!==r&&(a[t]=r));return a},k.extend({expando:"jQuery"+(f+Math.random()).replace(/\D/g,""),isReady:!0,error:function(e){throw new Error(e)},noop:function(){},isPlainObject:function(e){var t,n;return!(!e||"[object Object]"!==o.call(e))&&(!(t=r(e))||"function"==typeof(n=v.call(t,"constructor")&&t.constructor)&&a.call(n)===l)},isEmptyObject:function(e){var t;for(t in e)return!1;return!0},globalEval:function(e,t){b(e,{nonce:t&&t.nonce})},each:function(e,t){var n,r=0;if(d(e)){for(n=e.length;r<n;r++)if(!1===t.call(e[r],r,e[r]))break}else for(r in e)if(!1===t.call(e[r],r,e[r]))break;return e},trim:function(e){return null==e?"":(e+"").replace(p,"")},makeArray:function(e,t){var n=t||[];return null!=e&&(d(Object(e))?k.merge(n,"string"==typeof e?[e]:e):u.call(n,e)),n},inArray:function(e,t,n){return null==t?-1:i.call(t,e,n)},merge:function(e,t){for(var n=+t.length,r=0,i=e.length;r<n;r++)e[i++]=t[r];return e.length=i,e},grep:function(e,t,n){for(var r=[],i=0,o=e.length,a=!n;i<o;i++)!t(e[i],i)!==a&&r.push(e[i]);return r},map:function(e,t,n){var r,i,o=0,a=[];if(d(e))for(r=e.length;o<r;o++)null!=(i=t(e[o],o,n))&&a.push(i);else for(o in e)null!=(i=t(e[o],o,n))&&a.push(i);return g.apply([],a)},guid:1,support:y}),"function"==typeof Symbol&&(k.fn[Symbol.iterator]=t[Symbol.iterator]),k.each("Boolean Number String Function Array Date RegExp Object Error Symbol".split(" "),function(e,t){n["[object "+t+"]"]=t.toLowerCase()});var h=function(n){var e,d,b,o,i,h,f,g,w,u,l,T,C,a,E,v,s,c,y,k="sizzle"+1*new Date,m=n.document,S=0,r=0,p=ue(),x=ue(),N=ue(),A=ue(),D=function(e,t){return e===t&&(l=!0),0},j={}.hasOwnProperty,t=[],q=t.pop,L=t.push,H=t.push,O=t.slice,P=function(e,t){for(var n=0,r=e.length;n<r;n++)if(e[n]===t)return n;return-1},R="checked|selected|async|autofocus|autoplay|controls|defer|disabled|hidden|ismap|loop|multiple|open|readonly|required|scoped",M="[\\x20\\t\\r\\n\\f]",I="(?:\\\\.|[\\w-]|[^\0-\\xa0])+",W="\\["+M+"*("+I+")(?:"+M+"*([*^$|!~]?=)"+M+"*(?:'((?:\\\\.|[^\\\\'])*)'|\"((?:\\\\.|[^\\\\\"])*)\"|("+I+"))|)"+M+"*\\]",$=":("+I+")(?:\\((('((?:\\\\.|[^\\\\'])*)'|\"((?:\\\\.|[^\\\\\"])*)\")|((?:\\\\.|[^\\\\()[\\]]|"+W+")*)|.*)\\)|)",F=new RegExp(M+"+","g"),B=new RegExp("^"+M+"+|((?:^|[^\\\\])(?:\\\\.)*)"+M+"+$","g"),_=new RegExp("^"+M+"*,"+M+"*"),z=new RegExp("^"+M+"*([>+~]|"+M+")"+M+"*"),U=new RegExp(M+"|>"),X=new RegExp($),V=new RegExp("^"+I+"$"),G={ID:new RegExp("^#("+I+")"),CLASS:new RegExp("^\\.("+I+")"),TAG:new RegExp("^("+I+"|[*])"),ATTR:new RegExp("^"+W),PSEUDO:new RegExp("^"+$),CHILD:new RegExp("^:(only|first|last|nth|nth-last)-(child|of-type)(?:\\("+M+"*(even|odd|(([+-]|)(\\d*)n|)"+M+"*(?:([+-]|)"+M+"*(\\d+)|))"+M+"*\\)|)","i"),bool:new RegExp("^(?:"+R+")$","i"),needsContext:new RegExp("^"+M+"*[>+~]|:(even|odd|eq|gt|lt|nth|first|last)(?:\\("+M+"*((?:-\\d)?\\d*)"+M+"*\\)|)(?=[^-]|$)","i")},Y=/HTML$/i,Q=/^(?:input|select|textarea|button)$/i,J=/^h\d$/i,K=/^[^{]+\{\s*\[native \w/,Z=/^(?:#([\w-]+)|(\w+)|\.([\w-]+))$/,ee=/[+~]/,te=new RegExp("\\\\([\\da-f]{1,6}"+M+"?|("+M+")|.)","ig"),ne=function(e,t,n){var r="0x"+t-65536;return r!=r||n?t:r<0?String.fromCharCode(r+65536):String.fromCharCode(r>>10|55296,1023&r|56320)},re=/([\0-\x1f\x7f]|^-?\d)|^-$|[^\0-\x1f\x7f-\uFFFF\w-]/g,ie=function(e,t){return t?"\0"===e?"\ufffd":e.slice(0,-1)+"\\"+e.charCodeAt(e.length-1).toString(16)+" ":"\\"+e},oe=function(){T()},ae=be(function(e){return!0===e.disabled&&"fieldset"===e.nodeName.toLowerCase()},{dir:"parentNode",next:"legend"});try{H.apply(t=O.call(m.childNodes),m.childNodes),t[m.childNodes.length].nodeType}catch(e){H={apply:t.length?function(e,t){L.apply(e,O.call(t))}:function(e,t){var n=e.length,r=0;while(e[n++]=t[r++]);e.length=n-1}}}function se(t,e,n,r){var i,o,a,s,u,l,c,f=e&&e.ownerDocument,p=e?e.nodeType:9;if(n=n||[],"string"!=typeof t||!t||1!==p&&9!==p&&11!==p)return n;if(!r&&((e?e.ownerDocument||e:m)!==C&&T(e),e=e||C,E)){if(11!==p&&(u=Z.exec(t)))if(i=u[1]){if(9===p){if(!(a=e.getElementById(i)))return n;if(a.id===i)return n.push(a),n}else if(f&&(a=f.getElementById(i))&&y(e,a)&&a.id===i)return n.push(a),n}else{if(u[2])return H.apply(n,e.getElementsByTagName(t)),n;if((i=u[3])&&d.getElementsByClassName&&e.getElementsByClassName)return H.apply(n,e.getElementsByClassName(i)),n}if(d.qsa&&!A[t+" "]&&(!v||!v.test(t))&&(1!==p||"object"!==e.nodeName.toLowerCase())){if(c=t,f=e,1===p&&U.test(t)){(s=e.getAttribute("id"))?s=s.replace(re,ie):e.setAttribute("id",s=k),o=(l=h(t)).length;while(o--)l[o]="#"+s+" "+xe(l[o]);c=l.join(","),f=ee.test(t)&&ye(e.parentNode)||e}try{return H.apply(n,f.querySelectorAll(c)),n}catch(e){A(t,!0)}finally{s===k&&e.removeAttribute("id")}}}return g(t.replace(B,"$1"),e,n,r)}function ue(){var r=[];return function e(t,n){return r.push(t+" ")>b.cacheLength&&delete e[r.shift()],e[t+" "]=n}}function le(e){return e[k]=!0,e}function ce(e){var t=C.createElement("fieldset");try{return!!e(t)}catch(e){return!1}finally{t.parentNode&&t.parentNode.removeChild(t),t=null}}function fe(e,t){var n=e.split("|"),r=n.length;while(r--)b.attrHandle[n[r]]=t}function pe(e,t){var n=t&&e,r=n&&1===e.nodeType&&1===t.nodeType&&e.sourceIndex-t.sourceIndex;if(r)return r;if(n)while(n=n.nextSibling)if(n===t)return-1;return e?1:-1}function de(t){return function(e){return"input"===e.nodeName.toLowerCase()&&e.type===t}}function he(n){return function(e){var t=e.nodeName.toLowerCase();return("input"===t||"button"===t)&&e.type===n}}function ge(t){return function(e){return"form"in e?e.parentNode&&!1===e.disabled?"label"in e?"label"in e.parentNode?e.parentNode.disabled===t:e.disabled===t:e.isDisabled===t||e.isDisabled!==!t&&ae(e)===t:e.disabled===t:"label"in e&&e.disabled===t}}function ve(a){return le(function(o){return o=+o,le(function(e,t){var n,r=a([],e.length,o),i=r.length;while(i--)e[n=r[i]]&&(e[n]=!(t[n]=e[n]))})})}function ye(e){return e&&"undefined"!=typeof e.getElementsByTagName&&e}for(e in d=se.support={},i=se.isXML=function(e){var t=e.namespaceURI,n=(e.ownerDocument||e).documentElement;return!Y.test(t||n&&n.nodeName||"HTML")},T=se.setDocument=function(e){var t,n,r=e?e.ownerDocument||e:m;return r!==C&&9===r.nodeType&&r.documentElement&&(a=(C=r).documentElement,E=!i(C),m!==C&&(n=C.defaultView)&&n.top!==n&&(n.addEventListener?n.addEventListener("unload",oe,!1):n.attachEvent&&n.attachEvent("onunload",oe)),d.attributes=ce(function(e){return e.className="i",!e.getAttribute("className")}),d.getElementsByTagName=ce(function(e){return e.appendChild(C.createComment("")),!e.getElementsByTagName("*").length}),d.getElementsByClassName=K.test(C.getElementsByClassName),d.getById=ce(function(e){return a.appendChild(e).id=k,!C.getElementsByName||!C.getElementsByName(k).length}),d.getById?(b.filter.ID=function(e){var t=e.replace(te,ne);return function(e){return e.getAttribute("id")===t}},b.find.ID=function(e,t){if("undefined"!=typeof t.getElementById&&E){var n=t.getElementById(e);return n?[n]:[]}}):(b.filter.ID=function(e){var n=e.replace(te,ne);return function(e){var t="undefined"!=typeof e.getAttributeNode&&e.getAttributeNode("id");return t&&t.value===n}},b.find.ID=function(e,t){if("undefined"!=typeof t.getElementById&&E){var n,r,i,o=t.getElementById(e);if(o){if((n=o.getAttributeNode("id"))&&n.value===e)return[o];i=t.getElementsByName(e),r=0;while(o=i[r++])if((n=o.getAttributeNode("id"))&&n.value===e)return[o]}return[]}}),b.find.TAG=d.getElementsByTagName?function(e,t){return"undefined"!=typeof t.getElementsByTagName?t.getElementsByTagName(e):d.qsa?t.querySelectorAll(e):void 0}:function(e,t){var n,r=[],i=0,o=t.getElementsByTagName(e);if("*"===e){while(n=o[i++])1===n.nodeType&&r.push(n);return r}return o},b.find.CLASS=d.getElementsByClassName&&function(e,t){if("undefined"!=typeof t.getElementsByClassName&&E)return t.getElementsByClassName(e)},s=[],v=[],(d.qsa=K.test(C.querySelectorAll))&&(ce(function(e){a.appendChild(e).innerHTML="<a id='"+k+"'></a><select id='"+k+"-\r\\' msallowcapture=''><option selected=''></option></select>",e.querySelectorAll("[msallowcapture^='']").length&&v.push("[*^$]="+M+"*(?:''|\"\")"),e.querySelectorAll("[selected]").length||v.push("\\["+M+"*(?:value|"+R+")"),e.querySelectorAll("[id~="+k+"-]").length||v.push("~="),e.querySelectorAll(":checked").length||v.push(":checked"),e.querySelectorAll("a#"+k+"+*").length||v.push(".#.+[+~]")}),ce(function(e){e.innerHTML="<a href='' disabled='disabled'></a><select disabled='disabled'><option/></select>";var t=C.createElement("input");t.setAttribute("type","hidden"),e.appendChild(t).setAttribute("name","D"),e.querySelectorAll("[name=d]").length&&v.push("name"+M+"*[*^$|!~]?="),2!==e.querySelectorAll(":enabled").length&&v.push(":enabled",":disabled"),a.appendChild(e).disabled=!0,2!==e.querySelectorAll(":disabled").length&&v.push(":enabled",":disabled"),e.querySelectorAll("*,:x"),v.push(",.*:")})),(d.matchesSelector=K.test(c=a.matches||a.webkitMatchesSelector||a.mozMatchesSelector||a.oMatchesSelector||a.msMatchesSelector))&&ce(function(e){d.disconnectedMatch=c.call(e,"*"),c.call(e,"[s!='']:x"),s.push("!=",$)}),v=v.length&&new RegExp(v.join("|")),s=s.length&&new RegExp(s.join("|")),t=K.test(a.compareDocumentPosition),y=t||K.test(a.contains)?function(e,t){var n=9===e.nodeType?e.documentElement:e,r=t&&t.parentNode;return e===r||!(!r||1!==r.nodeType||!(n.contains?n.contains(r):e.compareDocumentPosition&&16&e.compareDocumentPosition(r)))}:function(e,t){if(t)while(t=t.parentNode)if(t===e)return!0;return!1},D=t?function(e,t){if(e===t)return l=!0,0;var n=!e.compareDocumentPosition-!t.compareDocumentPosition;return n||(1&(n=(e.ownerDocument||e)===(t.ownerDocument||t)?e.compareDocumentPosition(t):1)||!d.sortDetached&&t.compareDocumentPosition(e)===n?e===C||e.ownerDocument===m&&y(m,e)?-1:t===C||t.ownerDocument===m&&y(m,t)?1:u?P(u,e)-P(u,t):0:4&n?-1:1)}:function(e,t){if(e===t)return l=!0,0;var n,r=0,i=e.parentNode,o=t.parentNode,a=[e],s=[t];if(!i||!o)return e===C?-1:t===C?1:i?-1:o?1:u?P(u,e)-P(u,t):0;if(i===o)return pe(e,t);n=e;while(n=n.parentNode)a.unshift(n);n=t;while(n=n.parentNode)s.unshift(n);while(a[r]===s[r])r++;return r?pe(a[r],s[r]):a[r]===m?-1:s[r]===m?1:0}),C},se.matches=function(e,t){return se(e,null,null,t)},se.matchesSelector=function(e,t){if((e.ownerDocument||e)!==C&&T(e),d.matchesSelector&&E&&!A[t+" "]&&(!s||!s.test(t))&&(!v||!v.test(t)))try{var n=c.call(e,t);if(n||d.disconnectedMatch||e.document&&11!==e.document.nodeType)return n}catch(e){A(t,!0)}return 0<se(t,C,null,[e]).length},se.contains=function(e,t){return(e.ownerDocument||e)!==C&&T(e),y(e,t)},se.attr=function(e,t){(e.ownerDocument||e)!==C&&T(e);var n=b.attrHandle[t.toLowerCase()],r=n&&j.call(b.attrHandle,t.toLowerCase())?n(e,t,!E):void 0;return void 0!==r?r:d.attributes||!E?e.getAttribute(t):(r=e.getAttributeNode(t))&&r.specified?r.value:null},se.escape=function(e){return(e+"").replace(re,ie)},se.error=function(e){throw new Error("Syntax error, unrecognized expression: "+e)},se.uniqueSort=function(e){var t,n=[],r=0,i=0;if(l=!d.detectDuplicates,u=!d.sortStable&&e.slice(0),e.sort(D),l){while(t=e[i++])t===e[i]&&(r=n.push(i));while(r--)e.splice(n[r],1)}return u=null,e},o=se.getText=function(e){var t,n="",r=0,i=e.nodeType;if(i){if(1===i||9===i||11===i){if("string"==typeof e.textContent)return e.textContent;for(e=e.firstChild;e;e=e.nextSibling)n+=o(e)}else if(3===i||4===i)return e.nodeValue}else while(t=e[r++])n+=o(t);return n},(b=se.selectors={cacheLength:50,createPseudo:le,match:G,attrHandle:{},find:{},relative:{">":{dir:"parentNode",first:!0}," ":{dir:"parentNode"},"+":{dir:"previousSibling",first:!0},"~":{dir:"previousSibling"}},preFilter:{ATTR:function(e){return e[1]=e[1].replace(te,ne),e[3]=(e[3]||e[4]||e[5]||"").replace(te,ne),"~="===e[2]&&(e[3]=" "+e[3]+" "),e.slice(0,4)},CHILD:function(e){return e[1]=e[1].toLowerCase(),"nth"===e[1].slice(0,3)?(e[3]||se.error(e[0]),e[4]=+(e[4]?e[5]+(e[6]||1):2*("even"===e[3]||"odd"===e[3])),e[5]=+(e[7]+e[8]||"odd"===e[3])):e[3]&&se.error(e[0]),e},PSEUDO:function(e){var t,n=!e[6]&&e[2];return G.CHILD.test(e[0])?null:(e[3]?e[2]=e[4]||e[5]||"":n&&X.test(n)&&(t=h(n,!0))&&(t=n.indexOf(")",n.length-t)-n.length)&&(e[0]=e[0].slice(0,t),e[2]=n.slice(0,t)),e.slice(0,3))}},filter:{TAG:function(e){var t=e.replace(te,ne).toLowerCase();return"*"===e?function(){return!0}:function(e){return e.nodeName&&e.nodeName.toLowerCase()===t}},CLASS:function(e){var t=p[e+" "];return t||(t=new RegExp("(^|"+M+")"+e+"("+M+"|$)"))&&p(e,function(e){return t.test("string"==typeof e.className&&e.className||"undefined"!=typeof e.getAttribute&&e.getAttribute("class")||"")})},ATTR:function(n,r,i){return function(e){var t=se.attr(e,n);return null==t?"!="===r:!r||(t+="","="===r?t===i:"!="===r?t!==i:"^="===r?i&&0===t.indexOf(i):"*="===r?i&&-1<t.indexOf(i):"$="===r?i&&t.slice(-i.length)===i:"~="===r?-1<(" "+t.replace(F," ")+" ").indexOf(i):"|="===r&&(t===i||t.slice(0,i.length+1)===i+"-"))}},CHILD:function(h,e,t,g,v){var y="nth"!==h.slice(0,3),m="last"!==h.slice(-4),x="of-type"===e;return 1===g&&0===v?function(e){return!!e.parentNode}:function(e,t,n){var r,i,o,a,s,u,l=y!==m?"nextSibling":"previousSibling",c=e.parentNode,f=x&&e.nodeName.toLowerCase(),p=!n&&!x,d=!1;if(c){if(y){while(l){a=e;while(a=a[l])if(x?a.nodeName.toLowerCase()===f:1===a.nodeType)return!1;u=l="only"===h&&!u&&"nextSibling"}return!0}if(u=[m?c.firstChild:c.lastChild],m&&p){d=(s=(r=(i=(o=(a=c)[k]||(a[k]={}))[a.uniqueID]||(o[a.uniqueID]={}))[h]||[])[0]===S&&r[1])&&r[2],a=s&&c.childNodes[s];while(a=++s&&a&&a[l]||(d=s=0)||u.pop())if(1===a.nodeType&&++d&&a===e){i[h]=[S,s,d];break}}else if(p&&(d=s=(r=(i=(o=(a=e)[k]||(a[k]={}))[a.uniqueID]||(o[a.uniqueID]={}))[h]||[])[0]===S&&r[1]),!1===d)while(a=++s&&a&&a[l]||(d=s=0)||u.pop())if((x?a.nodeName.toLowerCase()===f:1===a.nodeType)&&++d&&(p&&((i=(o=a[k]||(a[k]={}))[a.uniqueID]||(o[a.uniqueID]={}))[h]=[S,d]),a===e))break;return(d-=v)===g||d%g==0&&0<=d/g}}},PSEUDO:function(e,o){var t,a=b.pseudos[e]||b.setFilters[e.toLowerCase()]||se.error("unsupported pseudo: "+e);return a[k]?a(o):1<a.length?(t=[e,e,"",o],b.setFilters.hasOwnProperty(e.toLowerCase())?le(function(e,t){var n,r=a(e,o),i=r.length;while(i--)e[n=P(e,r[i])]=!(t[n]=r[i])}):function(e){return a(e,0,t)}):a}},pseudos:{not:le(function(e){var r=[],i=[],s=f(e.replace(B,"$1"));return s[k]?le(function(e,t,n,r){var i,o=s(e,null,r,[]),a=e.length;while(a--)(i=o[a])&&(e[a]=!(t[a]=i))}):function(e,t,n){return r[0]=e,s(r,null,n,i),r[0]=null,!i.pop()}}),has:le(function(t){return function(e){return 0<se(t,e).length}}),contains:le(function(t){return t=t.replace(te,ne),function(e){return-1<(e.textContent||o(e)).indexOf(t)}}),lang:le(function(n){return V.test(n||"")||se.error("unsupported lang: "+n),n=n.replace(te,ne).toLowerCase(),function(e){var t;do{if(t=E?e.lang:e.getAttribute("xml:lang")||e.getAttribute("lang"))return(t=t.toLowerCase())===n||0===t.indexOf(n+"-")}while((e=e.parentNode)&&1===e.nodeType);return!1}}),target:function(e){var t=n.location&&n.location.hash;return t&&t.slice(1)===e.id},root:function(e){return e===a},focus:function(e){return e===C.activeElement&&(!C.hasFocus||C.hasFocus())&&!!(e.type||e.href||~e.tabIndex)},enabled:ge(!1),disabled:ge(!0),checked:function(e){var t=e.nodeName.toLowerCase();return"input"===t&&!!e.checked||"option"===t&&!!e.selected},selected:function(e){return e.parentNode&&e.parentNode.selectedIndex,!0===e.selected},empty:function(e){for(e=e.firstChild;e;e=e.nextSibling)if(e.nodeType<6)return!1;return!0},parent:function(e){return!b.pseudos.empty(e)},header:function(e){return J.test(e.nodeName)},input:function(e){return Q.test(e.nodeName)},button:function(e){var t=e.nodeName.toLowerCase();return"input"===t&&"button"===e.type||"button"===t},text:function(e){var t;return"input"===e.nodeName.toLowerCase()&&"text"===e.type&&(null==(t=e.getAttribute("type"))||"text"===t.toLowerCase())},first:ve(function(){return[0]}),last:ve(function(e,t){return[t-1]}),eq:ve(function(e,t,n){return[n<0?n+t:n]}),even:ve(function(e,t){for(var n=0;n<t;n+=2)e.push(n);return e}),odd:ve(function(e,t){for(var n=1;n<t;n+=2)e.push(n);return e}),lt:ve(function(e,t,n){for(var r=n<0?n+t:t<n?t:n;0<=--r;)e.push(r);return e}),gt:ve(function(e,t,n){for(var r=n<0?n+t:n;++r<t;)e.push(r);return e})}}).pseudos.nth=b.pseudos.eq,{radio:!0,checkbox:!0,file:!0,password:!0,image:!0})b.pseudos[e]=de(e);for(e in{submit:!0,reset:!0})b.pseudos[e]=he(e);function me(){}function xe(e){for(var t=0,n=e.length,r="";t<n;t++)r+=e[t].value;return r}function be(s,e,t){var u=e.dir,l=e.next,c=l||u,f=t&&"parentNode"===c,p=r++;return e.first?function(e,t,n){while(e=e[u])if(1===e.nodeType||f)return s(e,t,n);return!1}:function(e,t,n){var r,i,o,a=[S,p];if(n){while(e=e[u])if((1===e.nodeType||f)&&s(e,t,n))return!0}else while(e=e[u])if(1===e.nodeType||f)if(i=(o=e[k]||(e[k]={}))[e.uniqueID]||(o[e.uniqueID]={}),l&&l===e.nodeName.toLowerCase())e=e[u]||e;else{if((r=i[c])&&r[0]===S&&r[1]===p)return a[2]=r[2];if((i[c]=a)[2]=s(e,t,n))return!0}return!1}}function we(i){return 1<i.length?function(e,t,n){var r=i.length;while(r--)if(!i[r](e,t,n))return!1;return!0}:i[0]}function Te(e,t,n,r,i){for(var o,a=[],s=0,u=e.length,l=null!=t;s<u;s++)(o=e[s])&&(n&&!n(o,r,i)||(a.push(o),l&&t.push(s)));return a}function Ce(d,h,g,v,y,e){return v&&!v[k]&&(v=Ce(v)),y&&!y[k]&&(y=Ce(y,e)),le(function(e,t,n,r){var i,o,a,s=[],u=[],l=t.length,c=e||function(e,t,n){for(var r=0,i=t.length;r<i;r++)se(e,t[r],n);return n}(h||"*",n.nodeType?[n]:n,[]),f=!d||!e&&h?c:Te(c,s,d,n,r),p=g?y||(e?d:l||v)?[]:t:f;if(g&&g(f,p,n,r),v){i=Te(p,u),v(i,[],n,r),o=i.length;while(o--)(a=i[o])&&(p[u[o]]=!(f[u[o]]=a))}if(e){if(y||d){if(y){i=[],o=p.length;while(o--)(a=p[o])&&i.push(f[o]=a);y(null,p=[],i,r)}o=p.length;while(o--)(a=p[o])&&-1<(i=y?P(e,a):s[o])&&(e[i]=!(t[i]=a))}}else p=Te(p===t?p.splice(l,p.length):p),y?y(null,t,p,r):H.apply(t,p)})}function Ee(e){for(var i,t,n,r=e.length,o=b.relative[e[0].type],a=o||b.relative[" "],s=o?1:0,u=be(function(e){return e===i},a,!0),l=be(function(e){return-1<P(i,e)},a,!0),c=[function(e,t,n){var r=!o&&(n||t!==w)||((i=t).nodeType?u(e,t,n):l(e,t,n));return i=null,r}];s<r;s++)if(t=b.relative[e[s].type])c=[be(we(c),t)];else{if((t=b.filter[e[s].type].apply(null,e[s].matches))[k]){for(n=++s;n<r;n++)if(b.relative[e[n].type])break;return Ce(1<s&&we(c),1<s&&xe(e.slice(0,s-1).concat({value:" "===e[s-2].type?"*":""})).replace(B,"$1"),t,s<n&&Ee(e.slice(s,n)),n<r&&Ee(e=e.slice(n)),n<r&&xe(e))}c.push(t)}return we(c)}return me.prototype=b.filters=b.pseudos,b.setFilters=new me,h=se.tokenize=function(e,t){var n,r,i,o,a,s,u,l=x[e+" "];if(l)return t?0:l.slice(0);a=e,s=[],u=b.preFilter;while(a){for(o in n&&!(r=_.exec(a))||(r&&(a=a.slice(r[0].length)||a),s.push(i=[])),n=!1,(r=z.exec(a))&&(n=r.shift(),i.push({value:n,type:r[0].replace(B," ")}),a=a.slice(n.length)),b.filter)!(r=G[o].exec(a))||u[o]&&!(r=u[o](r))||(n=r.shift(),i.push({value:n,type:o,matches:r}),a=a.slice(n.length));if(!n)break}return t?a.length:a?se.error(e):x(e,s).slice(0)},f=se.compile=function(e,t){var n,v,y,m,x,r,i=[],o=[],a=N[e+" "];if(!a){t||(t=h(e)),n=t.length;while(n--)(a=Ee(t[n]))[k]?i.push(a):o.push(a);(a=N(e,(v=o,m=0<(y=i).length,x=0<v.length,r=function(e,t,n,r,i){var o,a,s,u=0,l="0",c=e&&[],f=[],p=w,d=e||x&&b.find.TAG("*",i),h=S+=null==p?1:Math.random()||.1,g=d.length;for(i&&(w=t===C||t||i);l!==g&&null!=(o=d[l]);l++){if(x&&o){a=0,t||o.ownerDocument===C||(T(o),n=!E);while(s=v[a++])if(s(o,t||C,n)){r.push(o);break}i&&(S=h)}m&&((o=!s&&o)&&u--,e&&c.push(o))}if(u+=l,m&&l!==u){a=0;while(s=y[a++])s(c,f,t,n);if(e){if(0<u)while(l--)c[l]||f[l]||(f[l]=q.call(r));f=Te(f)}H.apply(r,f),i&&!e&&0<f.length&&1<u+y.length&&se.uniqueSort(r)}return i&&(S=h,w=p),c},m?le(r):r))).selector=e}return a},g=se.select=function(e,t,n,r){var i,o,a,s,u,l="function"==typeof e&&e,c=!r&&h(e=l.selector||e);if(n=n||[],1===c.length){if(2<(o=c[0]=c[0].slice(0)).length&&"ID"===(a=o[0]).type&&9===t.nodeType&&E&&b.relative[o[1].type]){if(!(t=(b.find.ID(a.matches[0].replace(te,ne),t)||[])[0]))return n;l&&(t=t.parentNode),e=e.slice(o.shift().value.length)}i=G.needsContext.test(e)?0:o.length;while(i--){if(a=o[i],b.relative[s=a.type])break;if((u=b.find[s])&&(r=u(a.matches[0].replace(te,ne),ee.test(o[0].type)&&ye(t.parentNode)||t))){if(o.splice(i,1),!(e=r.length&&xe(o)))return H.apply(n,r),n;break}}}return(l||f(e,c))(r,t,!E,n,!t||ee.test(e)&&ye(t.parentNode)||t),n},d.sortStable=k.split("").sort(D).join("")===k,d.detectDuplicates=!!l,T(),d.sortDetached=ce(function(e){return 1&e.compareDocumentPosition(C.createElement("fieldset"))}),ce(function(e){return e.innerHTML="<a href='#'></a>","#"===e.firstChild.getAttribute("href")})||fe("type|href|height|width",function(e,t,n){if(!n)return e.getAttribute(t,"type"===t.toLowerCase()?1:2)}),d.attributes&&ce(function(e){return e.innerHTML="<input/>",e.firstChild.setAttribute("value",""),""===e.firstChild.getAttribute("value")})||fe("value",function(e,t,n){if(!n&&"input"===e.nodeName.toLowerCase())return e.defaultValue}),ce(function(e){return null==e.getAttribute("disabled")})||fe(R,function(e,t,n){var r;if(!n)return!0===e[t]?t.toLowerCase():(r=e.getAttributeNode(t))&&r.specified?r.value:null}),se}(C);k.find=h,k.expr=h.selectors,k.expr[":"]=k.expr.pseudos,k.uniqueSort=k.unique=h.uniqueSort,k.text=h.getText,k.isXMLDoc=h.isXML,k.contains=h.contains,k.escapeSelector=h.escape;var T=function(e,t,n){var r=[],i=void 0!==n;while((e=e[t])&&9!==e.nodeType)if(1===e.nodeType){if(i&&k(e).is(n))break;r.push(e)}return r},S=function(e,t){for(var n=[];e;e=e.nextSibling)1===e.nodeType&&e!==t&&n.push(e);return n},N=k.expr.match.needsContext;function A(e,t){return e.nodeName&&e.nodeName.toLowerCase()===t.toLowerCase()}var D=/^<([a-z][^\/\0>:\x20\t\r\n\f]*)[\x20\t\r\n\f]*\/?>(?:<\/\1>|)$/i;function j(e,n,r){return m(n)?k.grep(e,function(e,t){return!!n.call(e,t,e)!==r}):n.nodeType?k.grep(e,function(e){return e===n!==r}):"string"!=typeof n?k.grep(e,function(e){return-1<i.call(n,e)!==r}):k.filter(n,e,r)}k.filter=function(e,t,n){var r=t[0];return n&&(e=":not("+e+")"),1===t.length&&1===r.nodeType?k.find.matchesSelector(r,e)?[r]:[]:k.find.matches(e,k.grep(t,function(e){return 1===e.nodeType}))},k.fn.extend({find:function(e){var t,n,r=this.length,i=this;if("string"!=typeof e)return this.pushStack(k(e).filter(function(){for(t=0;t<r;t++)if(k.contains(i[t],this))return!0}));for(n=this.pushStack([]),t=0;t<r;t++)k.find(e,i[t],n);return 1<r?k.uniqueSort(n):n},filter:function(e){return this.pushStack(j(this,e||[],!1))},not:function(e){return this.pushStack(j(this,e||[],!0))},is:function(e){return!!j(this,"string"==typeof e&&N.test(e)?k(e):e||[],!1).length}});var q,L=/^(?:\s*(<[\w\W]+>)[^>]*|#([\w-]+))$/;(k.fn.init=function(e,t,n){var r,i;if(!e)return this;if(n=n||q,"string"==typeof e){if(!(r="<"===e[0]&&">"===e[e.length-1]&&3<=e.length?[null,e,null]:L.exec(e))||!r[1]&&t)return!t||t.jquery?(t||n).find(e):this.constructor(t).find(e);if(r[1]){if(t=t instanceof k?t[0]:t,k.merge(this,k.parseHTML(r[1],t&&t.nodeType?t.ownerDocument||t:E,!0)),D.test(r[1])&&k.isPlainObject(t))for(r in t)m(this[r])?this[r](t[r]):this.attr(r,t[r]);return this}return(i=E.getElementById(r[2]))&&(this[0]=i,this.length=1),this}return e.nodeType?(this[0]=e,this.length=1,this):m(e)?void 0!==n.ready?n.ready(e):e(k):k.makeArray(e,this)}).prototype=k.fn,q=k(E);var H=/^(?:parents|prev(?:Until|All))/,O={children:!0,contents:!0,next:!0,prev:!0};function P(e,t){while((e=e[t])&&1!==e.nodeType);return e}k.fn.extend({has:function(e){var t=k(e,this),n=t.length;return this.filter(function(){for(var e=0;e<n;e++)if(k.contains(this,t[e]))return!0})},closest:function(e,t){var n,r=0,i=this.length,o=[],a="string"!=typeof e&&k(e);if(!N.test(e))for(;r<i;r++)for(n=this[r];n&&n!==t;n=n.parentNode)if(n.nodeType<11&&(a?-1<a.index(n):1===n.nodeType&&k.find.matchesSelector(n,e))){o.push(n);break}return this.pushStack(1<o.length?k.uniqueSort(o):o)},index:function(e){return e?"string"==typeof e?i.call(k(e),this[0]):i.call(this,e.jquery?e[0]:e):this[0]&&this[0].parentNode?this.first().prevAll().length:-1},add:function(e,t){return this.pushStack(k.uniqueSort(k.merge(this.get(),k(e,t))))},addBack:function(e){return this.add(null==e?this.prevObject:this.prevObject.filter(e))}}),k.each({parent:function(e){var t=e.parentNode;return t&&11!==t.nodeType?t:null},parents:function(e){return T(e,"parentNode")},parentsUntil:function(e,t,n){return T(e,"parentNode",n)},next:function(e){return P(e,"nextSibling")},prev:function(e){return P(e,"previousSibling")},nextAll:function(e){return T(e,"nextSibling")},prevAll:function(e){return T(e,"previousSibling")},nextUntil:function(e,t,n){return T(e,"nextSibling",n)},prevUntil:function(e,t,n){return T(e,"previousSibling",n)},siblings:function(e){return S((e.parentNode||{}).firstChild,e)},children:function(e){return S(e.firstChild)},contents:function(e){return"undefined"!=typeof e.contentDocument?e.contentDocument:(A(e,"template")&&(e=e.content||e),k.merge([],e.childNodes))}},function(r,i){k.fn[r]=function(e,t){var n=k.map(this,i,e);return"Until"!==r.slice(-5)&&(t=e),t&&"string"==typeof t&&(n=k.filter(t,n)),1<this.length&&(O[r]||k.uniqueSort(n),H.test(r)&&n.reverse()),this.pushStack(n)}});var R=/[^\x20\t\r\n\f]+/g;function M(e){return e}function I(e){throw e}function W(e,t,n,r){var i;try{e&&m(i=e.promise)?i.call(e).done(t).fail(n):e&&m(i=e.then)?i.call(e,t,n):t.apply(void 0,[e].slice(r))}catch(e){n.apply(void 0,[e])}}k.Callbacks=function(r){var e,n;r="string"==typeof r?(e=r,n={},k.each(e.match(R)||[],function(e,t){n[t]=!0}),n):k.extend({},r);var i,t,o,a,s=[],u=[],l=-1,c=function(){for(a=a||r.once,o=i=!0;u.length;l=-1){t=u.shift();while(++l<s.length)!1===s[l].apply(t[0],t[1])&&r.stopOnFalse&&(l=s.length,t=!1)}r.memory||(t=!1),i=!1,a&&(s=t?[]:"")},f={add:function(){return s&&(t&&!i&&(l=s.length-1,u.push(t)),function n(e){k.each(e,function(e,t){m(t)?r.unique&&f.has(t)||s.push(t):t&&t.length&&"string"!==w(t)&&n(t)})}(arguments),t&&!i&&c()),this},remove:function(){return k.each(arguments,function(e,t){var n;while(-1<(n=k.inArray(t,s,n)))s.splice(n,1),n<=l&&l--}),this},has:function(e){return e?-1<k.inArray(e,s):0<s.length},empty:function(){return s&&(s=[]),this},disable:function(){return a=u=[],s=t="",this},disabled:function(){return!s},lock:function(){return a=u=[],t||i||(s=t=""),this},locked:function(){return!!a},fireWith:function(e,t){return a||(t=[e,(t=t||[]).slice?t.slice():t],u.push(t),i||c()),this},fire:function(){return f.fireWith(this,arguments),this},fired:function(){return!!o}};return f},k.extend({Deferred:function(e){var o=[["notify","progress",k.Callbacks("memory"),k.Callbacks("memory"),2],["resolve","done",k.Callbacks("once memory"),k.Callbacks("once memory"),0,"resolved"],["reject","fail",k.Callbacks("once memory"),k.Callbacks("once memory"),1,"rejected"]],i="pending",a={state:function(){return i},always:function(){return s.done(arguments).fail(arguments),this},"catch":function(e){return a.then(null,e)},pipe:function(){var i=arguments;return k.Deferred(function(r){k.each(o,function(e,t){var n=m(i[t[4]])&&i[t[4]];s[t[1]](function(){var e=n&&n.apply(this,arguments);e&&m(e.promise)?e.promise().progress(r.notify).done(r.resolve).fail(r.reject):r[t[0]+"With"](this,n?[e]:arguments)})}),i=null}).promise()},then:function(t,n,r){var u=0;function l(i,o,a,s){return function(){var n=this,r=arguments,e=function(){var e,t;if(!(i<u)){if((e=a.apply(n,r))===o.promise())throw new TypeError("Thenable self-resolution");t=e&&("object"==typeof e||"function"==typeof e)&&e.then,m(t)?s?t.call(e,l(u,o,M,s),l(u,o,I,s)):(u++,t.call(e,l(u,o,M,s),l(u,o,I,s),l(u,o,M,o.notifyWith))):(a!==M&&(n=void 0,r=[e]),(s||o.resolveWith)(n,r))}},t=s?e:function(){try{e()}catch(e){k.Deferred.exceptionHook&&k.Deferred.exceptionHook(e,t.stackTrace),u<=i+1&&(a!==I&&(n=void 0,r=[e]),o.rejectWith(n,r))}};i?t():(k.Deferred.getStackHook&&(t.stackTrace=k.Deferred.getStackHook()),C.setTimeout(t))}}return k.Deferred(function(e){o[0][3].add(l(0,e,m(r)?r:M,e.notifyWith)),o[1][3].add(l(0,e,m(t)?t:M)),o[2][3].add(l(0,e,m(n)?n:I))}).promise()},promise:function(e){return null!=e?k.extend(e,a):a}},s={};return k.each(o,function(e,t){var n=t[2],r=t[5];a[t[1]]=n.add,r&&n.add(function(){i=r},o[3-e][2].disable,o[3-e][3].disable,o[0][2].lock,o[0][3].lock),n.add(t[3].fire),s[t[0]]=function(){return s[t[0]+"With"](this===s?void 0:this,arguments),this},s[t[0]+"With"]=n.fireWith}),a.promise(s),e&&e.call(s,s),s},when:function(e){var n=arguments.length,t=n,r=Array(t),i=s.call(arguments),o=k.Deferred(),a=function(t){return function(e){r[t]=this,i[t]=1<arguments.length?s.call(arguments):e,--n||o.resolveWith(r,i)}};if(n<=1&&(W(e,o.done(a(t)).resolve,o.reject,!n),"pending"===o.state()||m(i[t]&&i[t].then)))return o.then();while(t--)W(i[t],a(t),o.reject);return o.promise()}});var $=/^(Eval|Internal|Range|Reference|Syntax|Type|URI)Error$/;k.Deferred.exceptionHook=function(e,t){C.console&&C.console.warn&&e&&$.test(e.name)&&C.console.warn("jQuery.Deferred exception: "+e.message,e.stack,t)},k.readyException=function(e){C.setTimeout(function(){throw e})};var F=k.Deferred();function B(){E.removeEventListener("DOMContentLoaded",B),C.removeEventListener("load",B),k.ready()}k.fn.ready=function(e){return F.then(e)["catch"](function(e){k.readyException(e)}),this},k.extend({isReady:!1,readyWait:1,ready:function(e){(!0===e?--k.readyWait:k.isReady)||(k.isReady=!0)!==e&&0<--k.readyWait||F.resolveWith(E,[k])}}),k.ready.then=F.then,"complete"===E.readyState||"loading"!==E.readyState&&!E.documentElement.doScroll?C.setTimeout(k.ready):(E.addEventListener("DOMContentLoaded",B),C.addEventListener("load",B));var _=function(e,t,n,r,i,o,a){var s=0,u=e.length,l=null==n;if("object"===w(n))for(s in i=!0,n)_(e,t,s,n[s],!0,o,a);else if(void 0!==r&&(i=!0,m(r)||(a=!0),l&&(a?(t.call(e,r),t=null):(l=t,t=function(e,t,n){return l.call(k(e),n)})),t))for(;s<u;s++)t(e[s],n,a?r:r.call(e[s],s,t(e[s],n)));return i?e:l?t.call(e):u?t(e[0],n):o},z=/^-ms-/,U=/-([a-z])/g;function X(e,t){return t.toUpperCase()}function V(e){return e.replace(z,"ms-").replace(U,X)}var G=function(e){return 1===e.nodeType||9===e.nodeType||!+e.nodeType};function Y(){this.expando=k.expando+Y.uid++}Y.uid=1,Y.prototype={cache:function(e){var t=e[this.expando];return t||(t={},G(e)&&(e.nodeType?e[this.expando]=t:Object.defineProperty(e,this.expando,{value:t,configurable:!0}))),t},set:function(e,t,n){var r,i=this.cache(e);if("string"==typeof t)i[V(t)]=n;else for(r in t)i[V(r)]=t[r];return i},get:function(e,t){return void 0===t?this.cache(e):e[this.expando]&&e[this.expando][V(t)]},access:function(e,t,n){return void 0===t||t&&"string"==typeof t&&void 0===n?this.get(e,t):(this.set(e,t,n),void 0!==n?n:t)},remove:function(e,t){var n,r=e[this.expando];if(void 0!==r){if(void 0!==t){n=(t=Array.isArray(t)?t.map(V):(t=V(t))in r?[t]:t.match(R)||[]).length;while(n--)delete r[t[n]]}(void 0===t||k.isEmptyObject(r))&&(e.nodeType?e[this.expando]=void 0:delete e[this.expando])}},hasData:function(e){var t=e[this.expando];return void 0!==t&&!k.isEmptyObject(t)}};var Q=new Y,J=new Y,K=/^(?:\{[\w\W]*\}|\[[\w\W]*\])$/,Z=/[A-Z]/g;function ee(e,t,n){var r,i;if(void 0===n&&1===e.nodeType)if(r="data-"+t.replace(Z,"-$&").toLowerCase(),"string"==typeof(n=e.getAttribute(r))){try{n="true"===(i=n)||"false"!==i&&("null"===i?null:i===+i+""?+i:K.test(i)?JSON.parse(i):i)}catch(e){}J.set(e,t,n)}else n=void 0;return n}k.extend({hasData:function(e){return J.hasData(e)||Q.hasData(e)},data:function(e,t,n){return J.access(e,t,n)},removeData:function(e,t){J.remove(e,t)},_data:function(e,t,n){return Q.access(e,t,n)},_removeData:function(e,t){Q.remove(e,t)}}),k.fn.extend({data:function(n,e){var t,r,i,o=this[0],a=o&&o.attributes;if(void 0===n){if(this.length&&(i=J.get(o),1===o.nodeType&&!Q.get(o,"hasDataAttrs"))){t=a.length;while(t--)a[t]&&0===(r=a[t].name).indexOf("data-")&&(r=V(r.slice(5)),ee(o,r,i[r]));Q.set(o,"hasDataAttrs",!0)}return i}return"object"==typeof n?this.each(function(){J.set(this,n)}):_(this,function(e){var t;if(o&&void 0===e)return void 0!==(t=J.get(o,n))?t:void 0!==(t=ee(o,n))?t:void 0;this.each(function(){J.set(this,n,e)})},null,e,1<arguments.length,null,!0)},removeData:function(e){return this.each(function(){J.remove(this,e)})}}),k.extend({queue:function(e,t,n){var r;if(e)return t=(t||"fx")+"queue",r=Q.get(e,t),n&&(!r||Array.isArray(n)?r=Q.access(e,t,k.makeArray(n)):r.push(n)),r||[]},dequeue:function(e,t){t=t||"fx";var n=k.queue(e,t),r=n.length,i=n.shift(),o=k._queueHooks(e,t);"inprogress"===i&&(i=n.shift(),r--),i&&("fx"===t&&n.unshift("inprogress"),delete o.stop,i.call(e,function(){k.dequeue(e,t)},o)),!r&&o&&o.empty.fire()},_queueHooks:function(e,t){var n=t+"queueHooks";return Q.get(e,n)||Q.access(e,n,{empty:k.Callbacks("once memory").add(function(){Q.remove(e,[t+"queue",n])})})}}),k.fn.extend({queue:function(t,n){var e=2;return"string"!=typeof t&&(n=t,t="fx",e--),arguments.length<e?k.queue(this[0],t):void 0===n?this:this.each(function(){var e=k.queue(this,t,n);k._queueHooks(this,t),"fx"===t&&"inprogress"!==e[0]&&k.dequeue(this,t)})},dequeue:function(e){return this.each(function(){k.dequeue(this,e)})},clearQueue:function(e){return this.queue(e||"fx",[])},promise:function(e,t){var n,r=1,i=k.Deferred(),o=this,a=this.length,s=function(){--r||i.resolveWith(o,[o])};"string"!=typeof e&&(t=e,e=void 0),e=e||"fx";while(a--)(n=Q.get(o[a],e+"queueHooks"))&&n.empty&&(r++,n.empty.add(s));return s(),i.promise(t)}});var te=/[+-]?(?:\d*\.|)\d+(?:[eE][+-]?\d+|)/.source,ne=new RegExp("^(?:([+-])=|)("+te+")([a-z%]*)$","i"),re=["Top","Right","Bottom","Left"],ie=E.documentElement,oe=function(e){return k.contains(e.ownerDocument,e)},ae={composed:!0};ie.getRootNode&&(oe=function(e){return k.contains(e.ownerDocument,e)||e.getRootNode(ae)===e.ownerDocument});var se=function(e,t){return"none"===(e=t||e).style.display||""===e.style.display&&oe(e)&&"none"===k.css(e,"display")},ue=function(e,t,n,r){var i,o,a={};for(o in t)a[o]=e.style[o],e.style[o]=t[o];for(o in i=n.apply(e,r||[]),t)e.style[o]=a[o];return i};function le(e,t,n,r){var i,o,a=20,s=r?function(){return r.cur()}:function(){return k.css(e,t,"")},u=s(),l=n&&n[3]||(k.cssNumber[t]?"":"px"),c=e.nodeType&&(k.cssNumber[t]||"px"!==l&&+u)&&ne.exec(k.css(e,t));if(c&&c[3]!==l){u/=2,l=l||c[3],c=+u||1;while(a--)k.style(e,t,c+l),(1-o)*(1-(o=s()/u||.5))<=0&&(a=0),c/=o;c*=2,k.style(e,t,c+l),n=n||[]}return n&&(c=+c||+u||0,i=n[1]?c+(n[1]+1)*n[2]:+n[2],r&&(r.unit=l,r.start=c,r.end=i)),i}var ce={};function fe(e,t){for(var n,r,i,o,a,s,u,l=[],c=0,f=e.length;c<f;c++)(r=e[c]).style&&(n=r.style.display,t?("none"===n&&(l[c]=Q.get(r,"display")||null,l[c]||(r.style.display="")),""===r.style.display&&se(r)&&(l[c]=(u=a=o=void 0,a=(i=r).ownerDocument,s=i.nodeName,(u=ce[s])||(o=a.body.appendChild(a.createElement(s)),u=k.css(o,"display"),o.parentNode.removeChild(o),"none"===u&&(u="block"),ce[s]=u)))):"none"!==n&&(l[c]="none",Q.set(r,"display",n)));for(c=0;c<f;c++)null!=l[c]&&(e[c].style.display=l[c]);return e}k.fn.extend({show:function(){return fe(this,!0)},hide:function(){return fe(this)},toggle:function(e){return"boolean"==typeof e?e?this.show():this.hide():this.each(function(){se(this)?k(this).show():k(this).hide()})}});var pe=/^(?:checkbox|radio)$/i,de=/<([a-z][^\/\0>\x20\t\r\n\f]*)/i,he=/^$|^module$|\/(?:java|ecma)script/i,ge={option:[1,"<select multiple='multiple'>","</select>"],thead:[1,"<table>","</table>"],col:[2,"<table><colgroup>","</colgroup></table>"],tr:[2,"<table><tbody>","</tbody></table>"],td:[3,"<table><tbody><tr>","</tr></tbody></table>"],_default:[0,"",""]};function ve(e,t){var n;return n="undefined"!=typeof e.getElementsByTagName?e.getElementsByTagName(t||"*"):"undefined"!=typeof e.querySelectorAll?e.querySelectorAll(t||"*"):[],void 0===t||t&&A(e,t)?k.merge([e],n):n}function ye(e,t){for(var n=0,r=e.length;n<r;n++)Q.set(e[n],"globalEval",!t||Q.get(t[n],"globalEval"))}ge.optgroup=ge.option,ge.tbody=ge.tfoot=ge.colgroup=ge.caption=ge.thead,ge.th=ge.td;var me,xe,be=/<|&#?\w+;/;function we(e,t,n,r,i){for(var o,a,s,u,l,c,f=t.createDocumentFragment(),p=[],d=0,h=e.length;d<h;d++)if((o=e[d])||0===o)if("object"===w(o))k.merge(p,o.nodeType?[o]:o);else if(be.test(o)){a=a||f.appendChild(t.createElement("div")),s=(de.exec(o)||["",""])[1].toLowerCase(),u=ge[s]||ge._default,a.innerHTML=u[1]+k.htmlPrefilter(o)+u[2],c=u[0];while(c--)a=a.lastChild;k.merge(p,a.childNodes),(a=f.firstChild).textContent=""}else p.push(t.createTextNode(o));f.textContent="",d=0;while(o=p[d++])if(r&&-1<k.inArray(o,r))i&&i.push(o);else if(l=oe(o),a=ve(f.appendChild(o),"script"),l&&ye(a),n){c=0;while(o=a[c++])he.test(o.type||"")&&n.push(o)}return f}me=E.createDocumentFragment().appendChild(E.createElement("div")),(xe=E.createElement("input")).setAttribute("type","radio"),xe.setAttribute("checked","checked"),xe.setAttribute("name","t"),me.appendChild(xe),y.checkClone=me.cloneNode(!0).cloneNode(!0).lastChild.checked,me.innerHTML="<textarea>x</textarea>",y.noCloneChecked=!!me.cloneNode(!0).lastChild.defaultValue;var Te=/^key/,Ce=/^(?:mouse|pointer|contextmenu|drag|drop)|click/,Ee=/^([^.]*)(?:\.(.+)|)/;function ke(){return!0}function Se(){return!1}function Ne(e,t){return e===function(){try{return E.activeElement}catch(e){}}()==("focus"===t)}function Ae(e,t,n,r,i,o){var a,s;if("object"==typeof t){for(s in"string"!=typeof n&&(r=r||n,n=void 0),t)Ae(e,s,n,r,t[s],o);return e}if(null==r&&null==i?(i=n,r=n=void 0):null==i&&("string"==typeof n?(i=r,r=void 0):(i=r,r=n,n=void 0)),!1===i)i=Se;else if(!i)return e;return 1===o&&(a=i,(i=function(e){return k().off(e),a.apply(this,arguments)}).guid=a.guid||(a.guid=k.guid++)),e.each(function(){k.event.add(this,t,i,r,n)})}function De(e,i,o){o?(Q.set(e,i,!1),k.event.add(e,i,{namespace:!1,handler:function(e){var t,n,r=Q.get(this,i);if(1&e.isTrigger&&this[i]){if(r.length)(k.event.special[i]||{}).delegateType&&e.stopPropagation();else if(r=s.call(arguments),Q.set(this,i,r),t=o(this,i),this[i](),r!==(n=Q.get(this,i))||t?Q.set(this,i,!1):n={},r!==n)return e.stopImmediatePropagation(),e.preventDefault(),n.value}else r.length&&(Q.set(this,i,{value:k.event.trigger(k.extend(r[0],k.Event.prototype),r.slice(1),this)}),e.stopImmediatePropagation())}})):void 0===Q.get(e,i)&&k.event.add(e,i,ke)}k.event={global:{},add:function(t,e,n,r,i){var o,a,s,u,l,c,f,p,d,h,g,v=Q.get(t);if(v){n.handler&&(n=(o=n).handler,i=o.selector),i&&k.find.matchesSelector(ie,i),n.guid||(n.guid=k.guid++),(u=v.events)||(u=v.events={}),(a=v.handle)||(a=v.handle=function(e){return"undefined"!=typeof k&&k.event.triggered!==e.type?k.event.dispatch.apply(t,arguments):void 0}),l=(e=(e||"").match(R)||[""]).length;while(l--)d=g=(s=Ee.exec(e[l])||[])[1],h=(s[2]||"").split(".").sort(),d&&(f=k.event.special[d]||{},d=(i?f.delegateType:f.bindType)||d,f=k.event.special[d]||{},c=k.extend({type:d,origType:g,data:r,handler:n,guid:n.guid,selector:i,needsContext:i&&k.expr.match.needsContext.test(i),namespace:h.join(".")},o),(p=u[d])||((p=u[d]=[]).delegateCount=0,f.setup&&!1!==f.setup.call(t,r,h,a)||t.addEventListener&&t.addEventListener(d,a)),f.add&&(f.add.call(t,c),c.handler.guid||(c.handler.guid=n.guid)),i?p.splice(p.delegateCount++,0,c):p.push(c),k.event.global[d]=!0)}},remove:function(e,t,n,r,i){var o,a,s,u,l,c,f,p,d,h,g,v=Q.hasData(e)&&Q.get(e);if(v&&(u=v.events)){l=(t=(t||"").match(R)||[""]).length;while(l--)if(d=g=(s=Ee.exec(t[l])||[])[1],h=(s[2]||"").split(".").sort(),d){f=k.event.special[d]||{},p=u[d=(r?f.delegateType:f.bindType)||d]||[],s=s[2]&&new RegExp("(^|\\.)"+h.join("\\.(?:.*\\.|)")+"(\\.|$)"),a=o=p.length;while(o--)c=p[o],!i&&g!==c.origType||n&&n.guid!==c.guid||s&&!s.test(c.namespace)||r&&r!==c.selector&&("**"!==r||!c.selector)||(p.splice(o,1),c.selector&&p.delegateCount--,f.remove&&f.remove.call(e,c));a&&!p.length&&(f.teardown&&!1!==f.teardown.call(e,h,v.handle)||k.removeEvent(e,d,v.handle),delete u[d])}else for(d in u)k.event.remove(e,d+t[l],n,r,!0);k.isEmptyObject(u)&&Q.remove(e,"handle events")}},dispatch:function(e){var t,n,r,i,o,a,s=k.event.fix(e),u=new Array(arguments.length),l=(Q.get(this,"events")||{})[s.type]||[],c=k.event.special[s.type]||{};for(u[0]=s,t=1;t<arguments.length;t++)u[t]=arguments[t];if(s.delegateTarget=this,!c.preDispatch||!1!==c.preDispatch.call(this,s)){a=k.event.handlers.call(this,s,l),t=0;while((i=a[t++])&&!s.isPropagationStopped()){s.currentTarget=i.elem,n=0;while((o=i.handlers[n++])&&!s.isImmediatePropagationStopped())s.rnamespace&&!1!==o.namespace&&!s.rnamespace.test(o.namespace)||(s.handleObj=o,s.data=o.data,void 0!==(r=((k.event.special[o.origType]||{}).handle||o.handler).apply(i.elem,u))&&!1===(s.result=r)&&(s.preventDefault(),s.stopPropagation()))}return c.postDispatch&&c.postDispatch.call(this,s),s.result}},handlers:function(e,t){var n,r,i,o,a,s=[],u=t.delegateCount,l=e.target;if(u&&l.nodeType&&!("click"===e.type&&1<=e.button))for(;l!==this;l=l.parentNode||this)if(1===l.nodeType&&("click"!==e.type||!0!==l.disabled)){for(o=[],a={},n=0;n<u;n++)void 0===a[i=(r=t[n]).selector+" "]&&(a[i]=r.needsContext?-1<k(i,this).index(l):k.find(i,this,null,[l]).length),a[i]&&o.push(r);o.length&&s.push({elem:l,handlers:o})}return l=this,u<t.length&&s.push({elem:l,handlers:t.slice(u)}),s},addProp:function(t,e){Object.defineProperty(k.Event.prototype,t,{enumerable:!0,configurable:!0,get:m(e)?function(){if(this.originalEvent)return e(this.originalEvent)}:function(){if(this.originalEvent)return this.originalEvent[t]},set:function(e){Object.defineProperty(this,t,{enumerable:!0,configurable:!0,writable:!0,value:e})}})},fix:function(e){return e[k.expando]?e:new k.Event(e)},special:{load:{noBubble:!0},click:{setup:function(e){var t=this||e;return pe.test(t.type)&&t.click&&A(t,"input")&&De(t,"click",ke),!1},trigger:function(e){var t=this||e;return pe.test(t.type)&&t.click&&A(t,"input")&&De(t,"click"),!0},_default:function(e){var t=e.target;return pe.test(t.type)&&t.click&&A(t,"input")&&Q.get(t,"click")||A(t,"a")}},beforeunload:{postDispatch:function(e){void 0!==e.result&&e.originalEvent&&(e.originalEvent.returnValue=e.result)}}}},k.removeEvent=function(e,t,n){e.removeEventListener&&e.removeEventListener(t,n)},k.Event=function(e,t){if(!(this instanceof k.Event))return new k.Event(e,t);e&&e.type?(this.originalEvent=e,this.type=e.type,this.isDefaultPrevented=e.defaultPrevented||void 0===e.defaultPrevented&&!1===e.returnValue?ke:Se,this.target=e.target&&3===e.target.nodeType?e.target.parentNode:e.target,this.currentTarget=e.currentTarget,this.relatedTarget=e.relatedTarget):this.type=e,t&&k.extend(this,t),this.timeStamp=e&&e.timeStamp||Date.now(),this[k.expando]=!0},k.Event.prototype={constructor:k.Event,isDefaultPrevented:Se,isPropagationStopped:Se,isImmediatePropagationStopped:Se,isSimulated:!1,preventDefault:function(){var e=this.originalEvent;this.isDefaultPrevented=ke,e&&!this.isSimulated&&e.preventDefault()},stopPropagation:function(){var e=this.originalEvent;this.isPropagationStopped=ke,e&&!this.isSimulated&&e.stopPropagation()},stopImmediatePropagation:function(){var e=this.originalEvent;this.isImmediatePropagationStopped=ke,e&&!this.isSimulated&&e.stopImmediatePropagation(),this.stopPropagation()}},k.each({altKey:!0,bubbles:!0,cancelable:!0,changedTouches:!0,ctrlKey:!0,detail:!0,eventPhase:!0,metaKey:!0,pageX:!0,pageY:!0,shiftKey:!0,view:!0,"char":!0,code:!0,charCode:!0,key:!0,keyCode:!0,button:!0,buttons:!0,clientX:!0,clientY:!0,offsetX:!0,offsetY:!0,pointerId:!0,pointerType:!0,screenX:!0,screenY:!0,targetTouches:!0,toElement:!0,touches:!0,which:function(e){var t=e.button;return null==e.which&&Te.test(e.type)?null!=e.charCode?e.charCode:e.keyCode:!e.which&&void 0!==t&&Ce.test(e.type)?1&t?1:2&t?3:4&t?2:0:e.which}},k.event.addProp),k.each({focus:"focusin",blur:"focusout"},function(e,t){k.event.special[e]={setup:function(){return De(this,e,Ne),!1},trigger:function(){return De(this,e),!0},delegateType:t}}),k.each({mouseenter:"mouseover",mouseleave:"mouseout",pointerenter:"pointerover",pointerleave:"pointerout"},function(e,i){k.event.special[e]={delegateType:i,bindType:i,handle:function(e){var t,n=e.relatedTarget,r=e.handleObj;return n&&(n===this||k.contains(this,n))||(e.type=r.origType,t=r.handler.apply(this,arguments),e.type=i),t}}}),k.fn.extend({on:function(e,t,n,r){return Ae(this,e,t,n,r)},one:function(e,t,n,r){return Ae(this,e,t,n,r,1)},off:function(e,t,n){var r,i;if(e&&e.preventDefault&&e.handleObj)return r=e.handleObj,k(e.delegateTarget).off(r.namespace?r.origType+"."+r.namespace:r.origType,r.selector,r.handler),this;if("object"==typeof e){for(i in e)this.off(i,t,e[i]);return this}return!1!==t&&"function"!=typeof t||(n=t,t=void 0),!1===n&&(n=Se),this.each(function(){k.event.remove(this,e,n,t)})}});var je=/<(?!area|br|col|embed|hr|img|input|link|meta|param)(([a-z][^\/\0>\x20\t\r\n\f]*)[^>]*)\/>/gi,qe=/<script|<style|<link/i,Le=/checked\s*(?:[^=]|=\s*.checked.)/i,He=/^\s*<!(?:\[CDATA\[|--)|(?:\]\]|--)>\s*$/g;function Oe(e,t){return A(e,"table")&&A(11!==t.nodeType?t:t.firstChild,"tr")&&k(e).children("tbody")[0]||e}function Pe(e){return e.type=(null!==e.getAttribute("type"))+"/"+e.type,e}function Re(e){return"true/"===(e.type||"").slice(0,5)?e.type=e.type.slice(5):e.removeAttribute("type"),e}function Me(e,t){var n,r,i,o,a,s,u,l;if(1===t.nodeType){if(Q.hasData(e)&&(o=Q.access(e),a=Q.set(t,o),l=o.events))for(i in delete a.handle,a.events={},l)for(n=0,r=l[i].length;n<r;n++)k.event.add(t,i,l[i][n]);J.hasData(e)&&(s=J.access(e),u=k.extend({},s),J.set(t,u))}}function Ie(n,r,i,o){r=g.apply([],r);var e,t,a,s,u,l,c=0,f=n.length,p=f-1,d=r[0],h=m(d);if(h||1<f&&"string"==typeof d&&!y.checkClone&&Le.test(d))return n.each(function(e){var t=n.eq(e);h&&(r[0]=d.call(this,e,t.html())),Ie(t,r,i,o)});if(f&&(t=(e=we(r,n[0].ownerDocument,!1,n,o)).firstChild,1===e.childNodes.length&&(e=t),t||o)){for(s=(a=k.map(ve(e,"script"),Pe)).length;c<f;c++)u=e,c!==p&&(u=k.clone(u,!0,!0),s&&k.merge(a,ve(u,"script"))),i.call(n[c],u,c);if(s)for(l=a[a.length-1].ownerDocument,k.map(a,Re),c=0;c<s;c++)u=a[c],he.test(u.type||"")&&!Q.access(u,"globalEval")&&k.contains(l,u)&&(u.src&&"module"!==(u.type||"").toLowerCase()?k._evalUrl&&!u.noModule&&k._evalUrl(u.src,{nonce:u.nonce||u.getAttribute("nonce")}):b(u.textContent.replace(He,""),u,l))}return n}function We(e,t,n){for(var r,i=t?k.filter(t,e):e,o=0;null!=(r=i[o]);o++)n||1!==r.nodeType||k.cleanData(ve(r)),r.parentNode&&(n&&oe(r)&&ye(ve(r,"script")),r.parentNode.removeChild(r));return e}k.extend({htmlPrefilter:function(e){return e.replace(je,"<$1></$2>")},clone:function(e,t,n){var r,i,o,a,s,u,l,c=e.cloneNode(!0),f=oe(e);if(!(y.noCloneChecked||1!==e.nodeType&&11!==e.nodeType||k.isXMLDoc(e)))for(a=ve(c),r=0,i=(o=ve(e)).length;r<i;r++)s=o[r],u=a[r],void 0,"input"===(l=u.nodeName.toLowerCase())&&pe.test(s.type)?u.checked=s.checked:"input"!==l&&"textarea"!==l||(u.defaultValue=s.defaultValue);if(t)if(n)for(o=o||ve(e),a=a||ve(c),r=0,i=o.length;r<i;r++)Me(o[r],a[r]);else Me(e,c);return 0<(a=ve(c,"script")).length&&ye(a,!f&&ve(e,"script")),c},cleanData:function(e){for(var t,n,r,i=k.event.special,o=0;void 0!==(n=e[o]);o++)if(G(n)){if(t=n[Q.expando]){if(t.events)for(r in t.events)i[r]?k.event.remove(n,r):k.removeEvent(n,r,t.handle);n[Q.expando]=void 0}n[J.expando]&&(n[J.expando]=void 0)}}}),k.fn.extend({detach:function(e){return We(this,e,!0)},remove:function(e){return We(this,e)},text:function(e){return _(this,function(e){return void 0===e?k.text(this):this.empty().each(function(){1!==this.nodeType&&11!==this.nodeType&&9!==this.nodeType||(this.textContent=e)})},null,e,arguments.length)},append:function(){return Ie(this,arguments,function(e){1!==this.nodeType&&11!==this.nodeType&&9!==this.nodeType||Oe(this,e).appendChild(e)})},prepend:function(){return Ie(this,arguments,function(e){if(1===this.nodeType||11===this.nodeType||9===this.nodeType){var t=Oe(this,e);t.insertBefore(e,t.firstChild)}})},before:function(){return Ie(this,arguments,function(e){this.parentNode&&this.parentNode.insertBefore(e,this)})},after:function(){return Ie(this,arguments,function(e){this.parentNode&&this.parentNode.insertBefore(e,this.nextSibling)})},empty:function(){for(var e,t=0;null!=(e=this[t]);t++)1===e.nodeType&&(k.cleanData(ve(e,!1)),e.textContent="");return this},clone:function(e,t){return e=null!=e&&e,t=null==t?e:t,this.map(function(){return k.clone(this,e,t)})},html:function(e){return _(this,function(e){var t=this[0]||{},n=0,r=this.length;if(void 0===e&&1===t.nodeType)return t.innerHTML;if("string"==typeof e&&!qe.test(e)&&!ge[(de.exec(e)||["",""])[1].toLowerCase()]){e=k.htmlPrefilter(e);try{for(;n<r;n++)1===(t=this[n]||{}).nodeType&&(k.cleanData(ve(t,!1)),t.innerHTML=e);t=0}catch(e){}}t&&this.empty().append(e)},null,e,arguments.length)},replaceWith:function(){var n=[];return Ie(this,arguments,function(e){var t=this.parentNode;k.inArray(this,n)<0&&(k.cleanData(ve(this)),t&&t.replaceChild(e,this))},n)}}),k.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(e,a){k.fn[e]=function(e){for(var t,n=[],r=k(e),i=r.length-1,o=0;o<=i;o++)t=o===i?this:this.clone(!0),k(r[o])[a](t),u.apply(n,t.get());return this.pushStack(n)}});var $e=new RegExp("^("+te+")(?!px)[a-z%]+$","i"),Fe=function(e){var t=e.ownerDocument.defaultView;return t&&t.opener||(t=C),t.getComputedStyle(e)},Be=new RegExp(re.join("|"),"i");function _e(e,t,n){var r,i,o,a,s=e.style;return(n=n||Fe(e))&&(""!==(a=n.getPropertyValue(t)||n[t])||oe(e)||(a=k.style(e,t)),!y.pixelBoxStyles()&&$e.test(a)&&Be.test(t)&&(r=s.width,i=s.minWidth,o=s.maxWidth,s.minWidth=s.maxWidth=s.width=a,a=n.width,s.width=r,s.minWidth=i,s.maxWidth=o)),void 0!==a?a+"":a}function ze(e,t){return{get:function(){if(!e())return(this.get=t).apply(this,arguments);delete this.get}}}!function(){function e(){if(u){s.style.cssText="position:absolute;left:-11111px;width:60px;margin-top:1px;padding:0;border:0",u.style.cssText="position:relative;display:block;box-sizing:border-box;overflow:scroll;margin:auto;border:1px;padding:1px;width:60%;top:1%",ie.appendChild(s).appendChild(u);var e=C.getComputedStyle(u);n="1%"!==e.top,a=12===t(e.marginLeft),u.style.right="60%",o=36===t(e.right),r=36===t(e.width),u.style.position="absolute",i=12===t(u.offsetWidth/3),ie.removeChild(s),u=null}}function t(e){return Math.round(parseFloat(e))}var n,r,i,o,a,s=E.createElement("div"),u=E.createElement("div");u.style&&(u.style.backgroundClip="content-box",u.cloneNode(!0).style.backgroundClip="",y.clearCloneStyle="content-box"===u.style.backgroundClip,k.extend(y,{boxSizingReliable:function(){return e(),r},pixelBoxStyles:function(){return e(),o},pixelPosition:function(){return e(),n},reliableMarginLeft:function(){return e(),a},scrollboxSize:function(){return e(),i}}))}();var Ue=["Webkit","Moz","ms"],Xe=E.createElement("div").style,Ve={};function Ge(e){var t=k.cssProps[e]||Ve[e];return t||(e in Xe?e:Ve[e]=function(e){var t=e[0].toUpperCase()+e.slice(1),n=Ue.length;while(n--)if((e=Ue[n]+t)in Xe)return e}(e)||e)}var Ye=/^(none|table(?!-c[ea]).+)/,Qe=/^--/,Je={position:"absolute",visibility:"hidden",display:"block"},Ke={letterSpacing:"0",fontWeight:"400"};function Ze(e,t,n){var r=ne.exec(t);return r?Math.max(0,r[2]-(n||0))+(r[3]||"px"):t}function et(e,t,n,r,i,o){var a="width"===t?1:0,s=0,u=0;if(n===(r?"border":"content"))return 0;for(;a<4;a+=2)"margin"===n&&(u+=k.css(e,n+re[a],!0,i)),r?("content"===n&&(u-=k.css(e,"padding"+re[a],!0,i)),"margin"!==n&&(u-=k.css(e,"border"+re[a]+"Width",!0,i))):(u+=k.css(e,"padding"+re[a],!0,i),"padding"!==n?u+=k.css(e,"border"+re[a]+"Width",!0,i):s+=k.css(e,"border"+re[a]+"Width",!0,i));return!r&&0<=o&&(u+=Math.max(0,Math.ceil(e["offset"+t[0].toUpperCase()+t.slice(1)]-o-u-s-.5))||0),u}function tt(e,t,n){var r=Fe(e),i=(!y.boxSizingReliable()||n)&&"border-box"===k.css(e,"boxSizing",!1,r),o=i,a=_e(e,t,r),s="offset"+t[0].toUpperCase()+t.slice(1);if($e.test(a)){if(!n)return a;a="auto"}return(!y.boxSizingReliable()&&i||"auto"===a||!parseFloat(a)&&"inline"===k.css(e,"display",!1,r))&&e.getClientRects().length&&(i="border-box"===k.css(e,"boxSizing",!1,r),(o=s in e)&&(a=e[s])),(a=parseFloat(a)||0)+et(e,t,n||(i?"border":"content"),o,r,a)+"px"}function nt(e,t,n,r,i){return new nt.prototype.init(e,t,n,r,i)}k.extend({cssHooks:{opacity:{get:function(e,t){if(t){var n=_e(e,"opacity");return""===n?"1":n}}}},cssNumber:{animationIterationCount:!0,columnCount:!0,fillOpacity:!0,flexGrow:!0,flexShrink:!0,fontWeight:!0,gridArea:!0,gridColumn:!0,gridColumnEnd:!0,gridColumnStart:!0,gridRow:!0,gridRowEnd:!0,gridRowStart:!0,lineHeight:!0,opacity:!0,order:!0,orphans:!0,widows:!0,zIndex:!0,zoom:!0},cssProps:{},style:function(e,t,n,r){if(e&&3!==e.nodeType&&8!==e.nodeType&&e.style){var i,o,a,s=V(t),u=Qe.test(t),l=e.style;if(u||(t=Ge(s)),a=k.cssHooks[t]||k.cssHooks[s],void 0===n)return a&&"get"in a&&void 0!==(i=a.get(e,!1,r))?i:l[t];"string"===(o=typeof n)&&(i=ne.exec(n))&&i[1]&&(n=le(e,t,i),o="number"),null!=n&&n==n&&("number"!==o||u||(n+=i&&i[3]||(k.cssNumber[s]?"":"px")),y.clearCloneStyle||""!==n||0!==t.indexOf("background")||(l[t]="inherit"),a&&"set"in a&&void 0===(n=a.set(e,n,r))||(u?l.setProperty(t,n):l[t]=n))}},css:function(e,t,n,r){var i,o,a,s=V(t);return Qe.test(t)||(t=Ge(s)),(a=k.cssHooks[t]||k.cssHooks[s])&&"get"in a&&(i=a.get(e,!0,n)),void 0===i&&(i=_e(e,t,r)),"normal"===i&&t in Ke&&(i=Ke[t]),""===n||n?(o=parseFloat(i),!0===n||isFinite(o)?o||0:i):i}}),k.each(["height","width"],function(e,u){k.cssHooks[u]={get:function(e,t,n){if(t)return!Ye.test(k.css(e,"display"))||e.getClientRects().length&&e.getBoundingClientRect().width?tt(e,u,n):ue(e,Je,function(){return tt(e,u,n)})},set:function(e,t,n){var r,i=Fe(e),o=!y.scrollboxSize()&&"absolute"===i.position,a=(o||n)&&"border-box"===k.css(e,"boxSizing",!1,i),s=n?et(e,u,n,a,i):0;return a&&o&&(s-=Math.ceil(e["offset"+u[0].toUpperCase()+u.slice(1)]-parseFloat(i[u])-et(e,u,"border",!1,i)-.5)),s&&(r=ne.exec(t))&&"px"!==(r[3]||"px")&&(e.style[u]=t,t=k.css(e,u)),Ze(0,t,s)}}}),k.cssHooks.marginLeft=ze(y.reliableMarginLeft,function(e,t){if(t)return(parseFloat(_e(e,"marginLeft"))||e.getBoundingClientRect().left-ue(e,{marginLeft:0},function(){return e.getBoundingClientRect().left}))+"px"}),k.each({margin:"",padding:"",border:"Width"},function(i,o){k.cssHooks[i+o]={expand:function(e){for(var t=0,n={},r="string"==typeof e?e.split(" "):[e];t<4;t++)n[i+re[t]+o]=r[t]||r[t-2]||r[0];return n}},"margin"!==i&&(k.cssHooks[i+o].set=Ze)}),k.fn.extend({css:function(e,t){return _(this,function(e,t,n){var r,i,o={},a=0;if(Array.isArray(t)){for(r=Fe(e),i=t.length;a<i;a++)o[t[a]]=k.css(e,t[a],!1,r);return o}return void 0!==n?k.style(e,t,n):k.css(e,t)},e,t,1<arguments.length)}}),((k.Tween=nt).prototype={constructor:nt,init:function(e,t,n,r,i,o){this.elem=e,this.prop=n,this.easing=i||k.easing._default,this.options=t,this.start=this.now=this.cur(),this.end=r,this.unit=o||(k.cssNumber[n]?"":"px")},cur:function(){var e=nt.propHooks[this.prop];return e&&e.get?e.get(this):nt.propHooks._default.get(this)},run:function(e){var t,n=nt.propHooks[this.prop];return this.options.duration?this.pos=t=k.easing[this.easing](e,this.options.duration*e,0,1,this.options.duration):this.pos=t=e,this.now=(this.end-this.start)*t+this.start,this.options.step&&this.options.step.call(this.elem,this.now,this),n&&n.set?n.set(this):nt.propHooks._default.set(this),this}}).init.prototype=nt.prototype,(nt.propHooks={_default:{get:function(e){var t;return 1!==e.elem.nodeType||null!=e.elem[e.prop]&&null==e.elem.style[e.prop]?e.elem[e.prop]:(t=k.css(e.elem,e.prop,""))&&"auto"!==t?t:0},set:function(e){k.fx.step[e.prop]?k.fx.step[e.prop](e):1!==e.elem.nodeType||!k.cssHooks[e.prop]&&null==e.elem.style[Ge(e.prop)]?e.elem[e.prop]=e.now:k.style(e.elem,e.prop,e.now+e.unit)}}}).scrollTop=nt.propHooks.scrollLeft={set:function(e){e.elem.nodeType&&e.elem.parentNode&&(e.elem[e.prop]=e.now)}},k.easing={linear:function(e){return e},swing:function(e){return.5-Math.cos(e*Math.PI)/2},_default:"swing"},k.fx=nt.prototype.init,k.fx.step={};var rt,it,ot,at,st=/^(?:toggle|show|hide)$/,ut=/queueHooks$/;function lt(){it&&(!1===E.hidden&&C.requestAnimationFrame?C.requestAnimationFrame(lt):C.setTimeout(lt,k.fx.interval),k.fx.tick())}function ct(){return C.setTimeout(function(){rt=void 0}),rt=Date.now()}function ft(e,t){var n,r=0,i={height:e};for(t=t?1:0;r<4;r+=2-t)i["margin"+(n=re[r])]=i["padding"+n]=e;return t&&(i.opacity=i.width=e),i}function pt(e,t,n){for(var r,i=(dt.tweeners[t]||[]).concat(dt.tweeners["*"]),o=0,a=i.length;o<a;o++)if(r=i[o].call(n,t,e))return r}function dt(o,e,t){var n,a,r=0,i=dt.prefilters.length,s=k.Deferred().always(function(){delete u.elem}),u=function(){if(a)return!1;for(var e=rt||ct(),t=Math.max(0,l.startTime+l.duration-e),n=1-(t/l.duration||0),r=0,i=l.tweens.length;r<i;r++)l.tweens[r].run(n);return s.notifyWith(o,[l,n,t]),n<1&&i?t:(i||s.notifyWith(o,[l,1,0]),s.resolveWith(o,[l]),!1)},l=s.promise({elem:o,props:k.extend({},e),opts:k.extend(!0,{specialEasing:{},easing:k.easing._default},t),originalProperties:e,originalOptions:t,startTime:rt||ct(),duration:t.duration,tweens:[],createTween:function(e,t){var n=k.Tween(o,l.opts,e,t,l.opts.specialEasing[e]||l.opts.easing);return l.tweens.push(n),n},stop:function(e){var t=0,n=e?l.tweens.length:0;if(a)return this;for(a=!0;t<n;t++)l.tweens[t].run(1);return e?(s.notifyWith(o,[l,1,0]),s.resolveWith(o,[l,e])):s.rejectWith(o,[l,e]),this}}),c=l.props;for(!function(e,t){var n,r,i,o,a;for(n in e)if(i=t[r=V(n)],o=e[n],Array.isArray(o)&&(i=o[1],o=e[n]=o[0]),n!==r&&(e[r]=o,delete e[n]),(a=k.cssHooks[r])&&"expand"in a)for(n in o=a.expand(o),delete e[r],o)n in e||(e[n]=o[n],t[n]=i);else t[r]=i}(c,l.opts.specialEasing);r<i;r++)if(n=dt.prefilters[r].call(l,o,c,l.opts))return m(n.stop)&&(k._queueHooks(l.elem,l.opts.queue).stop=n.stop.bind(n)),n;return k.map(c,pt,l),m(l.opts.start)&&l.opts.start.call(o,l),l.progress(l.opts.progress).done(l.opts.done,l.opts.complete).fail(l.opts.fail).always(l.opts.always),k.fx.timer(k.extend(u,{elem:o,anim:l,queue:l.opts.queue})),l}k.Animation=k.extend(dt,{tweeners:{"*":[function(e,t){var n=this.createTween(e,t);return le(n.elem,e,ne.exec(t),n),n}]},tweener:function(e,t){m(e)?(t=e,e=["*"]):e=e.match(R);for(var n,r=0,i=e.length;r<i;r++)n=e[r],dt.tweeners[n]=dt.tweeners[n]||[],dt.tweeners[n].unshift(t)},prefilters:[function(e,t,n){var r,i,o,a,s,u,l,c,f="width"in t||"height"in t,p=this,d={},h=e.style,g=e.nodeType&&se(e),v=Q.get(e,"fxshow");for(r in n.queue||(null==(a=k._queueHooks(e,"fx")).unqueued&&(a.unqueued=0,s=a.empty.fire,a.empty.fire=function(){a.unqueued||s()}),a.unqueued++,p.always(function(){p.always(function(){a.unqueued--,k.queue(e,"fx").length||a.empty.fire()})})),t)if(i=t[r],st.test(i)){if(delete t[r],o=o||"toggle"===i,i===(g?"hide":"show")){if("show"!==i||!v||void 0===v[r])continue;g=!0}d[r]=v&&v[r]||k.style(e,r)}if((u=!k.isEmptyObject(t))||!k.isEmptyObject(d))for(r in f&&1===e.nodeType&&(n.overflow=[h.overflow,h.overflowX,h.overflowY],null==(l=v&&v.display)&&(l=Q.get(e,"display")),"none"===(c=k.css(e,"display"))&&(l?c=l:(fe([e],!0),l=e.style.display||l,c=k.css(e,"display"),fe([e]))),("inline"===c||"inline-block"===c&&null!=l)&&"none"===k.css(e,"float")&&(u||(p.done(function(){h.display=l}),null==l&&(c=h.display,l="none"===c?"":c)),h.display="inline-block")),n.overflow&&(h.overflow="hidden",p.always(function(){h.overflow=n.overflow[0],h.overflowX=n.overflow[1],h.overflowY=n.overflow[2]})),u=!1,d)u||(v?"hidden"in v&&(g=v.hidden):v=Q.access(e,"fxshow",{display:l}),o&&(v.hidden=!g),g&&fe([e],!0),p.done(function(){for(r in g||fe([e]),Q.remove(e,"fxshow"),d)k.style(e,r,d[r])})),u=pt(g?v[r]:0,r,p),r in v||(v[r]=u.start,g&&(u.end=u.start,u.start=0))}],prefilter:function(e,t){t?dt.prefilters.unshift(e):dt.prefilters.push(e)}}),k.speed=function(e,t,n){var r=e&&"object"==typeof e?k.extend({},e):{complete:n||!n&&t||m(e)&&e,duration:e,easing:n&&t||t&&!m(t)&&t};return k.fx.off?r.duration=0:"number"!=typeof r.duration&&(r.duration in k.fx.speeds?r.duration=k.fx.speeds[r.duration]:r.duration=k.fx.speeds._default),null!=r.queue&&!0!==r.queue||(r.queue="fx"),r.old=r.complete,r.complete=function(){m(r.old)&&r.old.call(this),r.queue&&k.dequeue(this,r.queue)},r},k.fn.extend({fadeTo:function(e,t,n,r){return this.filter(se).css("opacity",0).show().end().animate({opacity:t},e,n,r)},animate:function(t,e,n,r){var i=k.isEmptyObject(t),o=k.speed(e,n,r),a=function(){var e=dt(this,k.extend({},t),o);(i||Q.get(this,"finish"))&&e.stop(!0)};return a.finish=a,i||!1===o.queue?this.each(a):this.queue(o.queue,a)},stop:function(i,e,o){var a=function(e){var t=e.stop;delete e.stop,t(o)};return"string"!=typeof i&&(o=e,e=i,i=void 0),e&&!1!==i&&this.queue(i||"fx",[]),this.each(function(){var e=!0,t=null!=i&&i+"queueHooks",n=k.timers,r=Q.get(this);if(t)r[t]&&r[t].stop&&a(r[t]);else for(t in r)r[t]&&r[t].stop&&ut.test(t)&&a(r[t]);for(t=n.length;t--;)n[t].elem!==this||null!=i&&n[t].queue!==i||(n[t].anim.stop(o),e=!1,n.splice(t,1));!e&&o||k.dequeue(this,i)})},finish:function(a){return!1!==a&&(a=a||"fx"),this.each(function(){var e,t=Q.get(this),n=t[a+"queue"],r=t[a+"queueHooks"],i=k.timers,o=n?n.length:0;for(t.finish=!0,k.queue(this,a,[]),r&&r.stop&&r.stop.call(this,!0),e=i.length;e--;)i[e].elem===this&&i[e].queue===a&&(i[e].anim.stop(!0),i.splice(e,1));for(e=0;e<o;e++)n[e]&&n[e].finish&&n[e].finish.call(this);delete t.finish})}}),k.each(["toggle","show","hide"],function(e,r){var i=k.fn[r];k.fn[r]=function(e,t,n){return null==e||"boolean"==typeof e?i.apply(this,arguments):this.animate(ft(r,!0),e,t,n)}}),k.each({slideDown:ft("show"),slideUp:ft("hide"),slideToggle:ft("toggle"),fadeIn:{opacity:"show"},fadeOut:{opacity:"hide"},fadeToggle:{opacity:"toggle"}},function(e,r){k.fn[e]=function(e,t,n){return this.animate(r,e,t,n)}}),k.timers=[],k.fx.tick=function(){var e,t=0,n=k.timers;for(rt=Date.now();t<n.length;t++)(e=n[t])()||n[t]!==e||n.splice(t--,1);n.length||k.fx.stop(),rt=void 0},k.fx.timer=function(e){k.timers.push(e),k.fx.start()},k.fx.interval=13,k.fx.start=function(){it||(it=!0,lt())},k.fx.stop=function(){it=null},k.fx.speeds={slow:600,fast:200,_default:400},k.fn.delay=function(r,e){return r=k.fx&&k.fx.speeds[r]||r,e=e||"fx",this.queue(e,function(e,t){var n=C.setTimeout(e,r);t.stop=function(){C.clearTimeout(n)}})},ot=E.createElement("input"),at=E.createElement("select").appendChild(E.createElement("option")),ot.type="checkbox",y.checkOn=""!==ot.value,y.optSelected=at.selected,(ot=E.createElement("input")).value="t",ot.type="radio",y.radioValue="t"===ot.value;var ht,gt=k.expr.attrHandle;k.fn.extend({attr:function(e,t){return _(this,k.attr,e,t,1<arguments.length)},removeAttr:function(e){return this.each(function(){k.removeAttr(this,e)})}}),k.extend({attr:function(e,t,n){var r,i,o=e.nodeType;if(3!==o&&8!==o&&2!==o)return"undefined"==typeof e.getAttribute?k.prop(e,t,n):(1===o&&k.isXMLDoc(e)||(i=k.attrHooks[t.toLowerCase()]||(k.expr.match.bool.test(t)?ht:void 0)),void 0!==n?null===n?void k.removeAttr(e,t):i&&"set"in i&&void 0!==(r=i.set(e,n,t))?r:(e.setAttribute(t,n+""),n):i&&"get"in i&&null!==(r=i.get(e,t))?r:null==(r=k.find.attr(e,t))?void 0:r)},attrHooks:{type:{set:function(e,t){if(!y.radioValue&&"radio"===t&&A(e,"input")){var n=e.value;return e.setAttribute("type",t),n&&(e.value=n),t}}}},removeAttr:function(e,t){var n,r=0,i=t&&t.match(R);if(i&&1===e.nodeType)while(n=i[r++])e.removeAttribute(n)}}),ht={set:function(e,t,n){return!1===t?k.removeAttr(e,n):e.setAttribute(n,n),n}},k.each(k.expr.match.bool.source.match(/\w+/g),function(e,t){var a=gt[t]||k.find.attr;gt[t]=function(e,t,n){var r,i,o=t.toLowerCase();return n||(i=gt[o],gt[o]=r,r=null!=a(e,t,n)?o:null,gt[o]=i),r}});var vt=/^(?:input|select|textarea|button)$/i,yt=/^(?:a|area)$/i;function mt(e){return(e.match(R)||[]).join(" ")}function xt(e){return e.getAttribute&&e.getAttribute("class")||""}function bt(e){return Array.isArray(e)?e:"string"==typeof e&&e.match(R)||[]}k.fn.extend({prop:function(e,t){return _(this,k.prop,e,t,1<arguments.length)},removeProp:function(e){return this.each(function(){delete this[k.propFix[e]||e]})}}),k.extend({prop:function(e,t,n){var r,i,o=e.nodeType;if(3!==o&&8!==o&&2!==o)return 1===o&&k.isXMLDoc(e)||(t=k.propFix[t]||t,i=k.propHooks[t]),void 0!==n?i&&"set"in i&&void 0!==(r=i.set(e,n,t))?r:e[t]=n:i&&"get"in i&&null!==(r=i.get(e,t))?r:e[t]},propHooks:{tabIndex:{get:function(e){var t=k.find.attr(e,"tabindex");return t?parseInt(t,10):vt.test(e.nodeName)||yt.test(e.nodeName)&&e.href?0:-1}}},propFix:{"for":"htmlFor","class":"className"}}),y.optSelected||(k.propHooks.selected={get:function(e){var t=e.parentNode;return t&&t.parentNode&&t.parentNode.selectedIndex,null},set:function(e){var t=e.parentNode;t&&(t.selectedIndex,t.parentNode&&t.parentNode.selectedIndex)}}),k.each(["tabIndex","readOnly","maxLength","cellSpacing","cellPadding","rowSpan","colSpan","useMap","frameBorder","contentEditable"],function(){k.propFix[this.toLowerCase()]=this}),k.fn.extend({addClass:function(t){var e,n,r,i,o,a,s,u=0;if(m(t))return this.each(function(e){k(this).addClass(t.call(this,e,xt(this)))});if((e=bt(t)).length)while(n=this[u++])if(i=xt(n),r=1===n.nodeType&&" "+mt(i)+" "){a=0;while(o=e[a++])r.indexOf(" "+o+" ")<0&&(r+=o+" ");i!==(s=mt(r))&&n.setAttribute("class",s)}return this},removeClass:function(t){var e,n,r,i,o,a,s,u=0;if(m(t))return this.each(function(e){k(this).removeClass(t.call(this,e,xt(this)))});if(!arguments.length)return this.attr("class","");if((e=bt(t)).length)while(n=this[u++])if(i=xt(n),r=1===n.nodeType&&" "+mt(i)+" "){a=0;while(o=e[a++])while(-1<r.indexOf(" "+o+" "))r=r.replace(" "+o+" "," ");i!==(s=mt(r))&&n.setAttribute("class",s)}return this},toggleClass:function(i,t){var o=typeof i,a="string"===o||Array.isArray(i);return"boolean"==typeof t&&a?t?this.addClass(i):this.removeClass(i):m(i)?this.each(function(e){k(this).toggleClass(i.call(this,e,xt(this),t),t)}):this.each(function(){var e,t,n,r;if(a){t=0,n=k(this),r=bt(i);while(e=r[t++])n.hasClass(e)?n.removeClass(e):n.addClass(e)}else void 0!==i&&"boolean"!==o||((e=xt(this))&&Q.set(this,"__className__",e),this.setAttribute&&this.setAttribute("class",e||!1===i?"":Q.get(this,"__className__")||""))})},hasClass:function(e){var t,n,r=0;t=" "+e+" ";while(n=this[r++])if(1===n.nodeType&&-1<(" "+mt(xt(n))+" ").indexOf(t))return!0;return!1}});var wt=/\r/g;k.fn.extend({val:function(n){var r,e,i,t=this[0];return arguments.length?(i=m(n),this.each(function(e){var t;1===this.nodeType&&(null==(t=i?n.call(this,e,k(this).val()):n)?t="":"number"==typeof t?t+="":Array.isArray(t)&&(t=k.map(t,function(e){return null==e?"":e+""})),(r=k.valHooks[this.type]||k.valHooks[this.nodeName.toLowerCase()])&&"set"in r&&void 0!==r.set(this,t,"value")||(this.value=t))})):t?(r=k.valHooks[t.type]||k.valHooks[t.nodeName.toLowerCase()])&&"get"in r&&void 0!==(e=r.get(t,"value"))?e:"string"==typeof(e=t.value)?e.replace(wt,""):null==e?"":e:void 0}}),k.extend({valHooks:{option:{get:function(e){var t=k.find.attr(e,"value");return null!=t?t:mt(k.text(e))}},select:{get:function(e){var t,n,r,i=e.options,o=e.selectedIndex,a="select-one"===e.type,s=a?null:[],u=a?o+1:i.length;for(r=o<0?u:a?o:0;r<u;r++)if(((n=i[r]).selected||r===o)&&!n.disabled&&(!n.parentNode.disabled||!A(n.parentNode,"optgroup"))){if(t=k(n).val(),a)return t;s.push(t)}return s},set:function(e,t){var n,r,i=e.options,o=k.makeArray(t),a=i.length;while(a--)((r=i[a]).selected=-1<k.inArray(k.valHooks.option.get(r),o))&&(n=!0);return n||(e.selectedIndex=-1),o}}}}),k.each(["radio","checkbox"],function(){k.valHooks[this]={set:function(e,t){if(Array.isArray(t))return e.checked=-1<k.inArray(k(e).val(),t)}},y.checkOn||(k.valHooks[this].get=function(e){return null===e.getAttribute("value")?"on":e.value})}),y.focusin="onfocusin"in C;var Tt=/^(?:focusinfocus|focusoutblur)$/,Ct=function(e){e.stopPropagation()};k.extend(k.event,{trigger:function(e,t,n,r){var i,o,a,s,u,l,c,f,p=[n||E],d=v.call(e,"type")?e.type:e,h=v.call(e,"namespace")?e.namespace.split("."):[];if(o=f=a=n=n||E,3!==n.nodeType&&8!==n.nodeType&&!Tt.test(d+k.event.triggered)&&(-1<d.indexOf(".")&&(d=(h=d.split(".")).shift(),h.sort()),u=d.indexOf(":")<0&&"on"+d,(e=e[k.expando]?e:new k.Event(d,"object"==typeof e&&e)).isTrigger=r?2:3,e.namespace=h.join("."),e.rnamespace=e.namespace?new RegExp("(^|\\.)"+h.join("\\.(?:.*\\.|)")+"(\\.|$)"):null,e.result=void 0,e.target||(e.target=n),t=null==t?[e]:k.makeArray(t,[e]),c=k.event.special[d]||{},r||!c.trigger||!1!==c.trigger.apply(n,t))){if(!r&&!c.noBubble&&!x(n)){for(s=c.delegateType||d,Tt.test(s+d)||(o=o.parentNode);o;o=o.parentNode)p.push(o),a=o;a===(n.ownerDocument||E)&&p.push(a.defaultView||a.parentWindow||C)}i=0;while((o=p[i++])&&!e.isPropagationStopped())f=o,e.type=1<i?s:c.bindType||d,(l=(Q.get(o,"events")||{})[e.type]&&Q.get(o,"handle"))&&l.apply(o,t),(l=u&&o[u])&&l.apply&&G(o)&&(e.result=l.apply(o,t),!1===e.result&&e.preventDefault());return e.type=d,r||e.isDefaultPrevented()||c._default&&!1!==c._default.apply(p.pop(),t)||!G(n)||u&&m(n[d])&&!x(n)&&((a=n[u])&&(n[u]=null),k.event.triggered=d,e.isPropagationStopped()&&f.addEventListener(d,Ct),n[d](),e.isPropagationStopped()&&f.removeEventListener(d,Ct),k.event.triggered=void 0,a&&(n[u]=a)),e.result}},simulate:function(e,t,n){var r=k.extend(new k.Event,n,{type:e,isSimulated:!0});k.event.trigger(r,null,t)}}),k.fn.extend({trigger:function(e,t){return this.each(function(){k.event.trigger(e,t,this)})},triggerHandler:function(e,t){var n=this[0];if(n)return k.event.trigger(e,t,n,!0)}}),y.focusin||k.each({focus:"focusin",blur:"focusout"},function(n,r){var i=function(e){k.event.simulate(r,e.target,k.event.fix(e))};k.event.special[r]={setup:function(){var e=this.ownerDocument||this,t=Q.access(e,r);t||e.addEventListener(n,i,!0),Q.access(e,r,(t||0)+1)},teardown:function(){var e=this.ownerDocument||this,t=Q.access(e,r)-1;t?Q.access(e,r,t):(e.removeEventListener(n,i,!0),Q.remove(e,r))}}});var Et=C.location,kt=Date.now(),St=/\?/;k.parseXML=function(e){var t;if(!e||"string"!=typeof e)return null;try{t=(new C.DOMParser).parseFromString(e,"text/xml")}catch(e){t=void 0}return t&&!t.getElementsByTagName("parsererror").length||k.error("Invalid XML: "+e),t};var Nt=/\[\]$/,At=/\r?\n/g,Dt=/^(?:submit|button|image|reset|file)$/i,jt=/^(?:input|select|textarea|keygen)/i;function qt(n,e,r,i){var t;if(Array.isArray(e))k.each(e,function(e,t){r||Nt.test(n)?i(n,t):qt(n+"["+("object"==typeof t&&null!=t?e:"")+"]",t,r,i)});else if(r||"object"!==w(e))i(n,e);else for(t in e)qt(n+"["+t+"]",e[t],r,i)}k.param=function(e,t){var n,r=[],i=function(e,t){var n=m(t)?t():t;r[r.length]=encodeURIComponent(e)+"="+encodeURIComponent(null==n?"":n)};if(null==e)return"";if(Array.isArray(e)||e.jquery&&!k.isPlainObject(e))k.each(e,function(){i(this.name,this.value)});else for(n in e)qt(n,e[n],t,i);return r.join("&")},k.fn.extend({serialize:function(){return k.param(this.serializeArray())},serializeArray:function(){return this.map(function(){var e=k.prop(this,"elements");return e?k.makeArray(e):this}).filter(function(){var e=this.type;return this.name&&!k(this).is(":disabled")&&jt.test(this.nodeName)&&!Dt.test(e)&&(this.checked||!pe.test(e))}).map(function(e,t){var n=k(this).val();return null==n?null:Array.isArray(n)?k.map(n,function(e){return{name:t.name,value:e.replace(At,"\r\n")}}):{name:t.name,value:n.replace(At,"\r\n")}}).get()}});var Lt=/%20/g,Ht=/#.*$/,Ot=/([?&])_=[^&]*/,Pt=/^(.*?):[ \t]*([^\r\n]*)$/gm,Rt=/^(?:GET|HEAD)$/,Mt=/^\/\//,It={},Wt={},$t="*/".concat("*"),Ft=E.createElement("a");function Bt(o){return function(e,t){"string"!=typeof e&&(t=e,e="*");var n,r=0,i=e.toLowerCase().match(R)||[];if(m(t))while(n=i[r++])"+"===n[0]?(n=n.slice(1)||"*",(o[n]=o[n]||[]).unshift(t)):(o[n]=o[n]||[]).push(t)}}function _t(t,i,o,a){var s={},u=t===Wt;function l(e){var r;return s[e]=!0,k.each(t[e]||[],function(e,t){var n=t(i,o,a);return"string"!=typeof n||u||s[n]?u?!(r=n):void 0:(i.dataTypes.unshift(n),l(n),!1)}),r}return l(i.dataTypes[0])||!s["*"]&&l("*")}function zt(e,t){var n,r,i=k.ajaxSettings.flatOptions||{};for(n in t)void 0!==t[n]&&((i[n]?e:r||(r={}))[n]=t[n]);return r&&k.extend(!0,e,r),e}Ft.href=Et.href,k.extend({active:0,lastModified:{},etag:{},ajaxSettings:{url:Et.href,type:"GET",isLocal:/^(?:about|app|app-storage|.+-extension|file|res|widget):$/.test(Et.protocol),global:!0,processData:!0,async:!0,contentType:"application/x-www-form-urlencoded; charset=UTF-8",accepts:{"*":$t,text:"text/plain",html:"text/html",xml:"application/xml, text/xml",json:"application/json, text/javascript"},contents:{xml:/\bxml\b/,html:/\bhtml/,json:/\bjson\b/},responseFields:{xml:"responseXML",text:"responseText",json:"responseJSON"},converters:{"* text":String,"text html":!0,"text json":JSON.parse,"text xml":k.parseXML},flatOptions:{url:!0,context:!0}},ajaxSetup:function(e,t){return t?zt(zt(e,k.ajaxSettings),t):zt(k.ajaxSettings,e)},ajaxPrefilter:Bt(It),ajaxTransport:Bt(Wt),ajax:function(e,t){"object"==typeof e&&(t=e,e=void 0),t=t||{};var c,f,p,n,d,r,h,g,i,o,v=k.ajaxSetup({},t),y=v.context||v,m=v.context&&(y.nodeType||y.jquery)?k(y):k.event,x=k.Deferred(),b=k.Callbacks("once memory"),w=v.statusCode||{},a={},s={},u="canceled",T={readyState:0,getResponseHeader:function(e){var t;if(h){if(!n){n={};while(t=Pt.exec(p))n[t[1].toLowerCase()+" "]=(n[t[1].toLowerCase()+" "]||[]).concat(t[2])}t=n[e.toLowerCase()+" "]}return null==t?null:t.join(", ")},getAllResponseHeaders:function(){return h?p:null},setRequestHeader:function(e,t){return null==h&&(e=s[e.toLowerCase()]=s[e.toLowerCase()]||e,a[e]=t),this},overrideMimeType:function(e){return null==h&&(v.mimeType=e),this},statusCode:function(e){var t;if(e)if(h)T.always(e[T.status]);else for(t in e)w[t]=[w[t],e[t]];return this},abort:function(e){var t=e||u;return c&&c.abort(t),l(0,t),this}};if(x.promise(T),v.url=((e||v.url||Et.href)+"").replace(Mt,Et.protocol+"//"),v.type=t.method||t.type||v.method||v.type,v.dataTypes=(v.dataType||"*").toLowerCase().match(R)||[""],null==v.crossDomain){r=E.createElement("a");try{r.href=v.url,r.href=r.href,v.crossDomain=Ft.protocol+"//"+Ft.host!=r.protocol+"//"+r.host}catch(e){v.crossDomain=!0}}if(v.data&&v.processData&&"string"!=typeof v.data&&(v.data=k.param(v.data,v.traditional)),_t(It,v,t,T),h)return T;for(i in(g=k.event&&v.global)&&0==k.active++&&k.event.trigger("ajaxStart"),v.type=v.type.toUpperCase(),v.hasContent=!Rt.test(v.type),f=v.url.replace(Ht,""),v.hasContent?v.data&&v.processData&&0===(v.contentType||"").indexOf("application/x-www-form-urlencoded")&&(v.data=v.data.replace(Lt,"+")):(o=v.url.slice(f.length),v.data&&(v.processData||"string"==typeof v.data)&&(f+=(St.test(f)?"&":"?")+v.data,delete v.data),!1===v.cache&&(f=f.replace(Ot,"$1"),o=(St.test(f)?"&":"?")+"_="+kt+++o),v.url=f+o),v.ifModified&&(k.lastModified[f]&&T.setRequestHeader("If-Modified-Since",k.lastModified[f]),k.etag[f]&&T.setRequestHeader("If-None-Match",k.etag[f])),(v.data&&v.hasContent&&!1!==v.contentType||t.contentType)&&T.setRequestHeader("Content-Type",v.contentType),T.setRequestHeader("Accept",v.dataTypes[0]&&v.accepts[v.dataTypes[0]]?v.accepts[v.dataTypes[0]]+("*"!==v.dataTypes[0]?", "+$t+"; q=0.01":""):v.accepts["*"]),v.headers)T.setRequestHeader(i,v.headers[i]);if(v.beforeSend&&(!1===v.beforeSend.call(y,T,v)||h))return T.abort();if(u="abort",b.add(v.complete),T.done(v.success),T.fail(v.error),c=_t(Wt,v,t,T)){if(T.readyState=1,g&&m.trigger("ajaxSend",[T,v]),h)return T;v.async&&0<v.timeout&&(d=C.setTimeout(function(){T.abort("timeout")},v.timeout));try{h=!1,c.send(a,l)}catch(e){if(h)throw e;l(-1,e)}}else l(-1,"No Transport");function l(e,t,n,r){var i,o,a,s,u,l=t;h||(h=!0,d&&C.clearTimeout(d),c=void 0,p=r||"",T.readyState=0<e?4:0,i=200<=e&&e<300||304===e,n&&(s=function(e,t,n){var r,i,o,a,s=e.contents,u=e.dataTypes;while("*"===u[0])u.shift(),void 0===r&&(r=e.mimeType||t.getResponseHeader("Content-Type"));if(r)for(i in s)if(s[i]&&s[i].test(r)){u.unshift(i);break}if(u[0]in n)o=u[0];else{for(i in n){if(!u[0]||e.converters[i+" "+u[0]]){o=i;break}a||(a=i)}o=o||a}if(o)return o!==u[0]&&u.unshift(o),n[o]}(v,T,n)),s=function(e,t,n,r){var i,o,a,s,u,l={},c=e.dataTypes.slice();if(c[1])for(a in e.converters)l[a.toLowerCase()]=e.converters[a];o=c.shift();while(o)if(e.responseFields[o]&&(n[e.responseFields[o]]=t),!u&&r&&e.dataFilter&&(t=e.dataFilter(t,e.dataType)),u=o,o=c.shift())if("*"===o)o=u;else if("*"!==u&&u!==o){if(!(a=l[u+" "+o]||l["* "+o]))for(i in l)if((s=i.split(" "))[1]===o&&(a=l[u+" "+s[0]]||l["* "+s[0]])){!0===a?a=l[i]:!0!==l[i]&&(o=s[0],c.unshift(s[1]));break}if(!0!==a)if(a&&e["throws"])t=a(t);else try{t=a(t)}catch(e){return{state:"parsererror",error:a?e:"No conversion from "+u+" to "+o}}}return{state:"success",data:t}}(v,s,T,i),i?(v.ifModified&&((u=T.getResponseHeader("Last-Modified"))&&(k.lastModified[f]=u),(u=T.getResponseHeader("etag"))&&(k.etag[f]=u)),204===e||"HEAD"===v.type?l="nocontent":304===e?l="notmodified":(l=s.state,o=s.data,i=!(a=s.error))):(a=l,!e&&l||(l="error",e<0&&(e=0))),T.status=e,T.statusText=(t||l)+"",i?x.resolveWith(y,[o,l,T]):x.rejectWith(y,[T,l,a]),T.statusCode(w),w=void 0,g&&m.trigger(i?"ajaxSuccess":"ajaxError",[T,v,i?o:a]),b.fireWith(y,[T,l]),g&&(m.trigger("ajaxComplete",[T,v]),--k.active||k.event.trigger("ajaxStop")))}return T},getJSON:function(e,t,n){return k.get(e,t,n,"json")},getScript:function(e,t){return k.get(e,void 0,t,"script")}}),k.each(["get","post"],function(e,i){k[i]=function(e,t,n,r){return m(t)&&(r=r||n,n=t,t=void 0),k.ajax(k.extend({url:e,type:i,dataType:r,data:t,success:n},k.isPlainObject(e)&&e))}}),k._evalUrl=function(e,t){return k.ajax({url:e,type:"GET",dataType:"script",cache:!0,async:!1,global:!1,converters:{"text script":function(){}},dataFilter:function(e){k.globalEval(e,t)}})},k.fn.extend({wrapAll:function(e){var t;return this[0]&&(m(e)&&(e=e.call(this[0])),t=k(e,this[0].ownerDocument).eq(0).clone(!0),this[0].parentNode&&t.insertBefore(this[0]),t.map(function(){var e=this;while(e.firstElementChild)e=e.firstElementChild;return e}).append(this)),this},wrapInner:function(n){return m(n)?this.each(function(e){k(this).wrapInner(n.call(this,e))}):this.each(function(){var e=k(this),t=e.contents();t.length?t.wrapAll(n):e.append(n)})},wrap:function(t){var n=m(t);return this.each(function(e){k(this).wrapAll(n?t.call(this,e):t)})},unwrap:function(e){return this.parent(e).not("body").each(function(){k(this).replaceWith(this.childNodes)}),this}}),k.expr.pseudos.hidden=function(e){return!k.expr.pseudos.visible(e)},k.expr.pseudos.visible=function(e){return!!(e.offsetWidth||e.offsetHeight||e.getClientRects().length)},k.ajaxSettings.xhr=function(){try{return new C.XMLHttpRequest}catch(e){}};var Ut={0:200,1223:204},Xt=k.ajaxSettings.xhr();y.cors=!!Xt&&"withCredentials"in Xt,y.ajax=Xt=!!Xt,k.ajaxTransport(function(i){var o,a;if(y.cors||Xt&&!i.crossDomain)return{send:function(e,t){var n,r=i.xhr();if(r.open(i.type,i.url,i.async,i.username,i.password),i.xhrFields)for(n in i.xhrFields)r[n]=i.xhrFields[n];for(n in i.mimeType&&r.overrideMimeType&&r.overrideMimeType(i.mimeType),i.crossDomain||e["X-Requested-With"]||(e["X-Requested-With"]="XMLHttpRequest"),e)r.setRequestHeader(n,e[n]);o=function(e){return function(){o&&(o=a=r.onload=r.onerror=r.onabort=r.ontimeout=r.onreadystatechange=null,"abort"===e?r.abort():"error"===e?"number"!=typeof r.status?t(0,"error"):t(r.status,r.statusText):t(Ut[r.status]||r.status,r.statusText,"text"!==(r.responseType||"text")||"string"!=typeof r.responseText?{binary:r.response}:{text:r.responseText},r.getAllResponseHeaders()))}},r.onload=o(),a=r.onerror=r.ontimeout=o("error"),void 0!==r.onabort?r.onabort=a:r.onreadystatechange=function(){4===r.readyState&&C.setTimeout(function(){o&&a()})},o=o("abort");try{r.send(i.hasContent&&i.data||null)}catch(e){if(o)throw e}},abort:function(){o&&o()}}}),k.ajaxPrefilter(function(e){e.crossDomain&&(e.contents.script=!1)}),k.ajaxSetup({accepts:{script:"text/javascript, application/javascript, application/ecmascript, application/x-ecmascript"},contents:{script:/\b(?:java|ecma)script\b/},converters:{"text script":function(e){return k.globalEval(e),e}}}),k.ajaxPrefilter("script",function(e){void 0===e.cache&&(e.cache=!1),e.crossDomain&&(e.type="GET")}),k.ajaxTransport("script",function(n){var r,i;if(n.crossDomain||n.scriptAttrs)return{send:function(e,t){r=k("<script>").attr(n.scriptAttrs||{}).prop({charset:n.scriptCharset,src:n.url}).on("load error",i=function(e){r.remove(),i=null,e&&t("error"===e.type?404:200,e.type)}),E.head.appendChild(r[0])},abort:function(){i&&i()}}});var Vt,Gt=[],Yt=/(=)\?(?=&|$)|\?\?/;k.ajaxSetup({jsonp:"callback",jsonpCallback:function(){var e=Gt.pop()||k.expando+"_"+kt++;return this[e]=!0,e}}),k.ajaxPrefilter("json jsonp",function(e,t,n){var r,i,o,a=!1!==e.jsonp&&(Yt.test(e.url)?"url":"string"==typeof e.data&&0===(e.contentType||"").indexOf("application/x-www-form-urlencoded")&&Yt.test(e.data)&&"data");if(a||"jsonp"===e.dataTypes[0])return r=e.jsonpCallback=m(e.jsonpCallback)?e.jsonpCallback():e.jsonpCallback,a?e[a]=e[a].replace(Yt,"$1"+r):!1!==e.jsonp&&(e.url+=(St.test(e.url)?"&":"?")+e.jsonp+"="+r),e.converters["script json"]=function(){return o||k.error(r+" was not called"),o[0]},e.dataTypes[0]="json",i=C[r],C[r]=function(){o=arguments},n.always(function(){void 0===i?k(C).removeProp(r):C[r]=i,e[r]&&(e.jsonpCallback=t.jsonpCallback,Gt.push(r)),o&&m(i)&&i(o[0]),o=i=void 0}),"script"}),y.createHTMLDocument=((Vt=E.implementation.createHTMLDocument("").body).innerHTML="<form></form><form></form>",2===Vt.childNodes.length),k.parseHTML=function(e,t,n){return"string"!=typeof e?[]:("boolean"==typeof t&&(n=t,t=!1),t||(y.createHTMLDocument?((r=(t=E.implementation.createHTMLDocument("")).createElement("base")).href=E.location.href,t.head.appendChild(r)):t=E),o=!n&&[],(i=D.exec(e))?[t.createElement(i[1])]:(i=we([e],t,o),o&&o.length&&k(o).remove(),k.merge([],i.childNodes)));var r,i,o},k.fn.load=function(e,t,n){var r,i,o,a=this,s=e.indexOf(" ");return-1<s&&(r=mt(e.slice(s)),e=e.slice(0,s)),m(t)?(n=t,t=void 0):t&&"object"==typeof t&&(i="POST"),0<a.length&&k.ajax({url:e,type:i||"GET",dataType:"html",data:t}).done(function(e){o=arguments,a.html(r?k("<div>").append(k.parseHTML(e)).find(r):e)}).always(n&&function(e,t){a.each(function(){n.apply(this,o||[e.responseText,t,e])})}),this},k.each(["ajaxStart","ajaxStop","ajaxComplete","ajaxError","ajaxSuccess","ajaxSend"],function(e,t){k.fn[t]=function(e){return this.on(t,e)}}),k.expr.pseudos.animated=function(t){return k.grep(k.timers,function(e){return t===e.elem}).length},k.offset={setOffset:function(e,t,n){var r,i,o,a,s,u,l=k.css(e,"position"),c=k(e),f={};"static"===l&&(e.style.position="relative"),s=c.offset(),o=k.css(e,"top"),u=k.css(e,"left"),("absolute"===l||"fixed"===l)&&-1<(o+u).indexOf("auto")?(a=(r=c.position()).top,i=r.left):(a=parseFloat(o)||0,i=parseFloat(u)||0),m(t)&&(t=t.call(e,n,k.extend({},s))),null!=t.top&&(f.top=t.top-s.top+a),null!=t.left&&(f.left=t.left-s.left+i),"using"in t?t.using.call(e,f):c.css(f)}},k.fn.extend({offset:function(t){if(arguments.length)return void 0===t?this:this.each(function(e){k.offset.setOffset(this,t,e)});var e,n,r=this[0];return r?r.getClientRects().length?(e=r.getBoundingClientRect(),n=r.ownerDocument.defaultView,{top:e.top+n.pageYOffset,left:e.left+n.pageXOffset}):{top:0,left:0}:void 0},position:function(){if(this[0]){var e,t,n,r=this[0],i={top:0,left:0};if("fixed"===k.css(r,"position"))t=r.getBoundingClientRect();else{t=this.offset(),n=r.ownerDocument,e=r.offsetParent||n.documentElement;while(e&&(e===n.body||e===n.documentElement)&&"static"===k.css(e,"position"))e=e.parentNode;e&&e!==r&&1===e.nodeType&&((i=k(e).offset()).top+=k.css(e,"borderTopWidth",!0),i.left+=k.css(e,"borderLeftWidth",!0))}return{top:t.top-i.top-k.css(r,"marginTop",!0),left:t.left-i.left-k.css(r,"marginLeft",!0)}}},offsetParent:function(){return this.map(function(){var e=this.offsetParent;while(e&&"static"===k.css(e,"position"))e=e.offsetParent;return e||ie})}}),k.each({scrollLeft:"pageXOffset",scrollTop:"pageYOffset"},function(t,i){var o="pageYOffset"===i;k.fn[t]=function(e){return _(this,function(e,t,n){var r;if(x(e)?r=e:9===e.nodeType&&(r=e.defaultView),void 0===n)return r?r[i]:e[t];r?r.scrollTo(o?r.pageXOffset:n,o?n:r.pageYOffset):e[t]=n},t,e,arguments.length)}}),k.each(["top","left"],function(e,n){k.cssHooks[n]=ze(y.pixelPosition,function(e,t){if(t)return t=_e(e,n),$e.test(t)?k(e).position()[n]+"px":t})}),k.each({Height:"height",Width:"width"},function(a,s){k.each({padding:"inner"+a,content:s,"":"outer"+a},function(r,o){k.fn[o]=function(e,t){var n=arguments.length&&(r||"boolean"!=typeof e),i=r||(!0===e||!0===t?"margin":"border");return _(this,function(e,t,n){var r;return x(e)?0===o.indexOf("outer")?e["inner"+a]:e.document.documentElement["client"+a]:9===e.nodeType?(r=e.documentElement,Math.max(e.body["scroll"+a],r["scroll"+a],e.body["offset"+a],r["offset"+a],r["client"+a])):void 0===n?k.css(e,t,i):k.style(e,t,n,i)},s,n?e:void 0,n)}})}),k.each("blur focus focusin focusout resize scroll click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select submit keydown keypress keyup contextmenu".split(" "),function(e,n){k.fn[n]=function(e,t){return 0<arguments.length?this.on(n,null,e,t):this.trigger(n)}}),k.fn.extend({hover:function(e,t){return this.mouseenter(e).mouseleave(t||e)}}),k.fn.extend({bind:function(e,t,n){return this.on(e,null,t,n)},unbind:function(e,t){return this.off(e,null,t)},delegate:function(e,t,n,r){return this.on(t,e,n,r)},undelegate:function(e,t,n){return 1===arguments.length?this.off(e,"**"):this.off(t,e||"**",n)}}),k.proxy=function(e,t){var n,r,i;if("string"==typeof t&&(n=e[t],t=e,e=n),m(e))return r=s.call(arguments,2),(i=function(){return e.apply(t||this,r.concat(s.call(arguments)))}).guid=e.guid=e.guid||k.guid++,i},k.holdReady=function(e){e?k.readyWait++:k.ready(!0)},k.isArray=Array.isArray,k.parseJSON=JSON.parse,k.nodeName=A,k.isFunction=m,k.isWindow=x,k.camelCase=V,k.type=w,k.now=Date.now,k.isNumeric=function(e){var t=k.type(e);return("number"===t||"string"===t)&&!isNaN(e-parseFloat(e))},"function"==typeof define&&define.amd&&define("jquery",[],function(){return k});var Qt=C.jQuery,Jt=C.$;return k.noConflict=function(e){return C.$===k&&(C.$=Jt),e&&C.jQuery===k&&(C.jQuery=Qt),k},e||(C.jQuery=C.$=k),k});
</script>
  <?php
}






// command line funcs
if (count($argv) > 1) {
    
    if ($argv[1] == 'StartServices') {

shell_exec('/usr/bin/tvservice -o');
shell_exec('chmod 777 /var/www/*  -R');
shell_exec('chmod 777 -R /etc/svxlink');
shell_exec('chmod 777 -R /etc/wpa_supplicant');
shell_exec('chmod 777 /etc/network/interfaces');
shell_exec('chmod 777 /etc/aprx.conf');
$psux = shell_exec('ps -ux');
if (strpos($psux, 'watchdog') !== false) {} else shell_exec('nohup /opt/watchdog >/dev/null 2>&1 &');
shell_exec('echo 0 > /etc/svxlink/svxlink.conf');
shell_exec('rm /var/www/html/*.txt /var/www/html/*.conf -rf && echo "" > /var/www/html/commands.txt && chmod 777 /var/www/html/commands.txt ');
if (strpos($psux, 'getStatusServices') !== false) {} else shell_exec('nohup /opt/getStatusServices >/dev/null 2>&1 &');


shell_exec('nohup rtl_eeprom > /var/www/html/ListaDeDonglesSDR.txt 2>&1 &');
sleep(10);

// Sequencia de inicio dos serviços da raspberry

    	if ($configs['AutoRX_ACTIVE'] === true) {
    		shell_exec('echo 0 > /opt/auto_rx/station.cfg & chmod 777 /opt/auto_rx/station.cfg &');
    		if (empty($configs['AutoRX_Low_Freq_Scan'])) $configs['AutoRX_Low_Freq_Scan'] = 402.6;
    		if (empty($configs['AutoRX_High_Freq_Scan'])) $configs['AutoRX_High_Freq_Scan'] = 403.3;
    		$NewConfigAutoRX = '
[sdr]
sdr_quantity = 1
[sdr_1]
device_idx = '.getIDXofSDR($configs['AutoRX_SDR_DONGLE']).'
ppm = 0
gain = -1
bias = False

[search_params]
min_freq = '.(($configs['AutoRX_Low_Freq_Scan']+1)-1).'
max_freq = '.(($configs['AutoRX_High_Freq_Scan']+1)-1).'
rx_timeout = 180
whitelist = []
blacklist = []
greylist = []

[location]
station_lat = '.(($configs['AutoRX_latitude']+1)-1).'
station_lon = '.(($configs['AutoRX_longitude']+1)-1).'
station_alt = '.(($configs['AutoRX_alt']+1)-1).'
station_code = SONDE
[habitat]
habitat_enabled = True
uploader_callsign = '.$configs['AutoRX_APRS_Callsign'].'
upload_listener_position = True
uploader_antenna = HAM-RASP-SERVER
upload_rate = 30
payload_callsign = <id>

[aprs]
aprs_enabled = ';
if ($configs['AutoRX_APRS_ACTIVE']) {$NewConfigAutoRX.='True';} else {$NewConfigAutoRX.='False';}
$NewConfigAutoRX .= '
aprs_user = '.$configs['AutoRX_APRS_Callsign'].'
aprs_pass = '.$configs['AutoRX_APRS_Passcode'].'
upload_rate = 60
aprs_server = brazil.aprs2.net
aprs_object_id = <id>
aprs_position_report = False
aprs_custom_comment = Velocity=<vel_v> T=<temp> <freq> Type=<type> HAM Rasp Sv '.$HamRaspSV_Version.'
station_beacon_enabled = False
station_beacon_rate = 60
station_beacon_commment = radiosonde_auto_rx SondeGate v<version> - HAM Rasp Sv '.$HamRaspSV_Version.'
station_beacon_icon = /r

[oziplotter]
ozi_update_rate = 5
ozi_enabled = True
ozi_port = 8942
payload_summary_enabled = True
payload_summary_port = 55672

[email]
email_enabled = False
smtp_server = localhost
from = sonde@localhost
to = someone@example.com
subject = <type> Sonde launch detected on <freq>: <id>

[rotator]
rotator_enabled = False
update_rate = 30
rotation_threshold = 5.0
rotator_hostname = 127.0.0.1
rotator_port = 4533
rotator_homing_enabled = False
rotator_homing_delay = 10
rotator_home_azimuth = 0.0
rotator_home_elevation = 0.0
[logging]
per_sonde_log = False
[web]
web_host = 0.0.0.0
web_port = 5000
archive_age = 120
[debugging]
save_detection_audio = False
save_decode_audio = False
save_decode_iq = False

[advanced]
search_step = 800
snr_threshold = 10
max_peaks = 10
min_distance = 1000
scan_dwell_time = 20
detect_dwell_time = 5
scan_delay = 10
quantization = 10000
temporary_block_time = 60
synchronous_upload = True
payload_id_valid = 5
sdr_fm_path = rtl_fm
sdr_power_path = rtl_power

[filtering]
#max_altitude = 50000
#max_radius_km = 1000
    		';
    		$fp = fopen('/opt/auto_rx/station.cfg', 'w');
            fwrite($fp, $NewConfigAutoRX);
            fclose($fp);
$bashautorx = '!/bin/bash
rm -rf /opt/auto_rx/log_power*.csv
python /opt/auto_rx/auto_rx.py -t 365';
shell_exec('echo 0 > /opt/auto_rx/auto_rx.sh && chmod -x /opt/auto_rx/auto_rx.sh && chmod 777 /opt/auto_rx/auto_rx.sh');
        $fp = fopen('/opt/auto_rx/auto_rx.sh', 'w');
            fwrite($fp, $bashautorx);
            fclose($fp);
            shell_exec('cd /opt/auto_rx/ && nohup ./auto_rx.sh > /var/www/html/autorx.log.txt 2>&1 &');
    	}



        // Inicio do Serviço do Beacon CW
        if ($configs['BeaconCWActive']) {

        prepareGpioOUT($configs['BeaconCWKEY']);
        prepareGpioOUT($configs['BeaconCWPTT']);
$BeaconCWShellScript = '#!/bin/bash
while :
do
  php /var/www/html/index.php EmitirBeaconCW
  sleep '.($configs['BeaconCWInterval']*60).'
done';
            $fp = fopen('/opt/BeaconCW-Service', 'w');
            fwrite($fp, $BeaconCWShellScript);
            fclose($fp);
            shell_exec("nohup /opt/BeaconCW-Service >/dev/null 2>&1 &");
        }
        // Arranque do Serviço APRX (APRS GATEWAY com KISS TNC)
        if ($configs['APRSKISSTNC_ACTIVE']) {
            $APRXConfig = 'mycall   '.$configs['APRSKISSTNC_CALLSIGN'].'
myloc lat '.aprslat(($configs['APRSKISSTNC_LAT']+1)-1).' lon '.aprslon(($configs['APRSKISSTNC_LON']+1)-1).'
';

if ($configs['APRSKISSTNC_PW'] > 300) {
  $APRXConfig .= '<aprsis>
    login $mycall
    passcode    '.$configs['APRSKISSTNC_PW'].'
    server  '.$configs['APRSKISSTNC_APRSISSV'].' 14580
    filter "'.$configs['APRSKISSTNC_APRSISFILTER'].'"
    heartbeat-timeout   70
</aprsis>';
}

$APRXConfig .=  '
<logging>
rflog /var/www/html/aprx.log.txt
aprxlog /var/www/html/aprx.log.txt
pidfile /var/run/aprx.pid
</logging>
<beacon>
beaconmode both
cycle-size 30m
beaconmode both
beacon via WIDE1-1,WIDE2-2 raw "!'.aprslat(($configs['APRSKISSTNC_LAT']+1)-1).$configs['APRSKISSTNC_SYMBOL'][0].aprslon(($configs['APRSKISSTNC_LON']+1)-1).$configs['APRSKISSTNC_SYMBOL'][1].$configs['APRSKISSTNC_BCOMMENT'].'"
beacon via WIDE1-1,WIDE2-2 raw ">HAM Raspberry Server Ver. '.$HamRaspSV_Version.' (with tnc)"
</beacon>
<interface>
    serial-device   "'.$configs['APRSKISSTNC_PORT'].'" '.$configs['APRSKISSTNC_SPEED'].' 8n1 KISS';
  
if (strlen($configs['APRSKISSTNC_INITSTR']) > 3) {
$APRXConfig.='
    initstring "'.$configs['APRSKISSTNC_INITSTR'].'"';
}
    $APRXConfig.='
    tx-ok   true
</interface>';
            if ($configs['APRSKISSTNC_DIGI']) {
                $APRXConfig.='
<digipeater>
    transmitter $mycall
    <source>
        source $mycall
        relay-type  directonly
        viscous-delay 0
    </source>';
if ($configs['APRSKISSTNC_IGTX']) {
if (empty($configs['APRSKISSTNC_APRSISFILTER'])) $configs['APRSKISSTNC_APRSISFILTER'] = 'm/50';
$APRXConfig.= '
    <source>
      source APRSIS
      relay-type third-party
      via-path WIDE1-1
      msg-path WIDE1-1
      filter "'.$configs['APRSKISSTNC_APRSISFILTER'].'"
    </source>';}
    $APRXConfig.='
</digipeater>';
            }
        $fp = fopen('/etc/aprx.conf', 'w');
        fwrite($fp, $APRXConfig);
        fclose($fp);
        sleep(1);
        shell_exec('nohup aprx >/dev/null 2>&1 &');
        }

        // arranque do serviço de RTLSDR+APRS Gateway
        if ($configs['APRSSDR_ACTIVE']) { $configs['APRSSDR_SDR_DONGLE'] = ($configs['APRSSDR_SDR_DONGLE'] + 1) -1 ;
          $DWConf = 'ADEVICE null null
CHANNEL 0
AGWPORT 8000
KISSPORT 8001
MYCALL '.$configs['APRSSDR_CALLSIGN'].'
IGSERVER '.$configs['APRSSDR_APRSISSV'].'
IGLOGIN '.$configs['APRSSDR_CALLSIGN'].' '.$configs['APRSSDR_PW'].'
PBEACON delay=1 sendto=IG every=30 symbol="'.$configs['APRSSDR_SYMBOL'].'" lat='.$configs['APRSSDR_LAT'].' long='.$configs['APRSSDR_LON'].' comment="'.$configs['APRSSDR_BCOMMENT'].'"
';      $fp = fopen('/var/www/html/sdr.conf', 'w');
    fwrite($fp, $DWConf);
    fclose($fp);
    sleep(1);
shell_exec('nohup rtl_fm -M fm -f '.$configs['APRSSDR_FREQ'].'M -s 48000 - | direwolf -c /var/www/html/sdr.conf -r 48000 -D 1 -B '.$configs['APRSSDR_BAUD'].' - > /var/www/html/dwsdr.log.txt &'); // 

        }


        if ($configs['APRSSOUNDCARD_ACTIVE']) { 
        prepareGpioOUT($configs['APRSSOUNDCARD_GPIO_PTT']);
          $DWConf = '#DIREWOLF HAM RASP SV by PY5BK
ADEVICE  plughw:'.$configs['APRSSOUNDCARD_CARDNUMBER'].',0
CHANNEL 0
AGWPORT 8003
KISSPORT 8002
MODEM '.$configs['APRSSOUNDCARD_BAUD'].'
MYCALL '.$configs['APRSSOUNDCARD_CALLSIGN'];
if ($configs['APRSSOUNDCARD_GPIO_PTT'] > 0) $DWConf .= '
PTT GPIO '.$configs['APRSSOUNDCARD_GPIO_PTT'].'
';
$DWConf .= '
IGSERVER '.$configs['APRSSOUNDCARD_APRSISSV'].'
IGTXVIA 0 WIDE1-1
FILTER 0 IG ! b/PU5MMR-*/PY5NCC-*/PY5DGS-*/PY5DGL-*/PU5OBL-*/PU5RFP-*/PP5PH-*/PU5SJE-* 
IGLOGIN '.$configs['APRSSOUNDCARD_CALLSIGN'].' '.$configs['APRSSOUNDCARD_PW'].'
PBEACON delay=1:00 sendto=IG every=30 symbol="'.$configs['APRSSOUNDCARD_SYMBOL'].'" lat='.$configs['APRSSOUNDCARD_LAT'].' long='.$configs['APRSSOUNDCARD_LON'].' comment="'.$configs['APRSSOUNDCARD_BCOMMENT'].'"
PBEACON every=30 symbol="'.$configs['APRSSOUNDCARD_SYMBOL'].'" lat='.$configs['APRSSOUNDCARD_LAT'].' long='.$configs['APRSSOUNDCARD_LON'].' comment="'.$configs['APRSSOUNDCARD_BCOMMENT'].'" via=WIDE1-1,WIDE2-2
';      if ($configs['APRSSOUNDCARD_DIGI']) {
  $DWConf.='DIGIPEAT 0 0 ^WIDE[3-7]-[1-7]$|^TEST$ ^WIDE[12]-[12]$ TRACE 
FILTER 0 0 ! b/PU5MMR-*/PY5NCC-*/PY5DGS-*/PY5DGL-*/PU5OBL-*/PU5RFP-*/PP5PH-*/PU5SJE-* ';
}
if ($configs['APRSSOUNDCARD_IGTX']) {
	if (empty($configs['APRSSOUNDCARD_APRSISFILTER'])) $configs['APRSSOUNDCARD_APRSISFILTER'] = 'm/50';
  $DWConf.='
FILTER 0 IG ! b/PU5MMR-*/PY5NCC-*/PY5DGS-*/PY5DGL-*/PU5OBL-*/PU5RFP-*/PP5PH-*/PU5SJE-* 
IGFILTER '.$configs['APRSSOUNDCARD_APRSISFILTER'].'
FILTER IG 0 ! b/PU5MMR-*/PY5NCC-*/PY5DGS-*/PY5DGL-*/PU5OBL-*/PU5RFP-*/PP5PH-*/PU5SJE-* 
';
}

$fp = fopen('/var/www/html/dw.conf', 'w');
    fwrite($fp, $DWConf);
    fclose($fp);
    sleep(1);
shell_exec('nohup direwolf -c /var/www/html/dw.conf > /var/www/html/dw.log.txt &');
        }


        if ($configs['SVXLINK_ACTIVE']) {
          prepareGpioOUT($configs['SVXLINK_PTT_GPIO']); // prepare ptt
          // prepare cor pin
          if ($configs['SVXLINK_COR'] != 'VOX')
          shell_exec('echo '.$configs['SVXLINK_COR_GPIO'].' > /sys/class/gpio/export && echo in > /sys/class/gpio/gpio'.$configs['SVXLINK_COR_GPIO'].'/direction'); 

$SvxLinkEcholinkConf = '[ModuleEchoLink]
NAME=EchoLink
ID=2
TIMEOUT=60
SERVERS=servers.echolink.org
CALLSIGN='.$configs['SVXLINK_CALLSIGN'].'
PASSWORD='.$configs['SVXLINK_PW'].'
SYSOPNAME='.$configs['SVXLINK_SYOPNAME'].'
LOCATION='.$configs['SVXLINK_LOCATION'].'

';
if (strlen($configs['SVXLINK_PROXYSV']) > 5) {
  $SvxLinkEcholinkConf .='
PROXY_SERVER='.$configs['SVXLINK_PROXYSV'].'
PROXY_PORT='.$configs['SVXLINK_PROXYPORT'].'
PROXY_PASSWORD='.$configs['SVXLINK_PROXYPW'].'';
}
if ($configs['SVXLINK_AUTOCONN'] > 10) {
  $SvxLinkEcholinkConf .= '
AUTOCON_ECHOLINK_ID='.$configs['SVXLINK_AUTOCONN'].'
AUTOCON_TIME=20
MAX_QSOS=1
MAX_CONNECTIONS=2
  ';
} else {
  $SvxLinkEcholinkConf .= '
MAX_QSOS=100
MAX_CONNECTIONS=101
';
}
$SvxLinkEcholinkConf.='
USE_GSM_ONLY=1
LINK_IDLE_TIMEOUT=0
DESCRIPTION="'.$configs['SVXLINK_COMMENT'].'\nHam Raspberry Server\nby PY5BK"';


          $SvxLinkConf = '[GLOBAL]
LOGICS=SimplexLogic
CFG_DIR=svxlink.d
TIMESTAMP_FORMAT="%c"
CARD_SAMPLE_RATE=48000

[SimplexLogic]
TYPE=Simplex
RX=Rx1
TX=Tx1
MODULES=ModuleEchoLink
CALLSIGN='.$configs['SVXLINK_CALLSIGN'].'
EVENT_HANDLER=/usr/share/svxlink/events.tcl

[Rx1]
TYPE=Local
AUDIO_DEV=alsa:plughw:'.$configs['SVXLINK_SOUNDCARD_NUMBER'].'
AUDIO_CHANNEL=0
SQL_DET=';

if ($configs['SVXLINK_COR'] == 'VOX') {
 $SvxLinkConf.='VOX';
} else {
  $SvxLinkConf.='GPIO';
}

$SvxLinkConf.='
SQL_START_DELAY=0
SQL_DELAY=0
SQL_HANGTIME=2000
#SQL_EXTENDED_HANGTIME=1000
#SQL_EXTENDED_HANGTIME_THRESH=15
#SQL_TIMEOUT=600
VOX_FILTER_DEPTH=20
VOX_THRESH=1000
';

if ($configs['SVXLINK_COR'] == 'LOW') {
  $SvxLinkConf.='GPIO_SQL_PIN=!gpio'.$configs['SVXLINK_COR_GPIO'];
} else if ($configs['SVXLINK_COR'] == 'HIGH') {
  $SvxLinkConf.='GPIO_SQL_PIN=gpio'.$configs['SVXLINK_COR_GPIO'];
}

$SvxLinkConf.='
DEEMPHASIS=0

[Tx1]
TYPE=Local
AUDIO_DEV=alsa:plughw:'.$configs['SVXLINK_SOUNDCARD_NUMBER'].'
AUDIO_CHANNEL=0
TIMEOUT=300
TX_DELAY=500
PREEMPHASIS=0
';
if ($configs['SVXLINK_PTT_GPIO'] > 0 && $configs['SVXLINK_PTT_GPIO'] < 50) {
	$SvxLinkConf .= 'PTT_TYPE=GPIO
PTT_PORT=gpio'.$configs['SVXLINK_PTT_GPIO'].'
PTT_PIN=gpio'.$configs['SVXLINK_PTT_GPIO'];
}
          $fp = fopen('/etc/svxlink/svxlink.d/ModuleEchoLink.conf', 'w');
    fwrite($fp, $SvxLinkEcholinkConf);
    fclose($fp);
    $fp = fopen('/etc/svxlink/svxlink.conf', 'w');
    fwrite($fp, $SvxLinkConf);
    fclose($fp);
shell_exec('nohup svxlink > /var/www/html/svxlink.log.txt &');

        }

      shell_exec('nohup sleep 10 && rm -rf /var/www/html/*.conf >/dev/null 2>&1 &');
    }


    if ($argv[1] == 'EmitirBeaconCW') {
        TocarBeacon($configs['BeaconCWMSG']);
    }
 
    die;   
}

function okmsg ($msg) {
    echo "<p><font color='red'><b>".htmlentities($msg)."</b></font></p>";
}
function linuxresult ($cmd) {
    echo '<p><font face="courier">'.nl2br(shell_exec($cmd)).'</font></p>';
}

if ($configs['loginPanel_ACTIVE']) {
  session_start();
  if (empty($_SESSION[$configs['loginPanel_username']])) {
    $_SESSION[$configs['loginPanel_username']] = 'byPY5BK';
  }

  if ($_SESSION[$configs['loginPanel_username']] != $configs['loginPanel_passwd']) {
    if (!empty($_REQUEST['login'])) 
      if ($configs['loginPanel_username'] == $_REQUEST['login'] && strtolower($configs['loginPanel_passwd']) == strtolower($_REQUEST['senha'])) {
        $_SESSION[$configs['loginPanel_username']] = $configs['loginPanel_passwd'];
        echo '<meta http-equiv="refresh" content="0; url=/">ok';die;
      }
echo '<h1>Login</h1>
<form method="POST" action="#">
<p>Login:<br>
<input maxlength="120" type="text" name="login" value="">
</p>
<p>Senha:<br>
<input maxlength="120" type="password" name="senha" value="">
</p>
<input type="submit" name="confirm" value="Login">
</form>
'; die;

  }
}

?>
<!DOCTYPE html>
<html>
<!-- DEUS SEJA LOUVADO HOJE E SEMPRE!   -->
<head>
  <meta charset="UTF-8">
    <link rel="icon" href="favicon.png" type="image/png" sizes="16x16">

  <title>HAM Rasp Server v<?php echo $HamRaspSV_Version; ?> - by PY5BK</title>
<style type="text/css">
  /*
      HAM RASPBERRY SERVER - BY RICARDO AURELIO SECO - PY5BK
      WWW.PY5BK.NET / WWW.BITBARU.COM / QSL.NET/PY5BK / QRZ.COM/DB/PY5BK
      DEUS SEJA LOUVADO!
  */
  td a:hover {
  background-color: yellow;
}
    h1,h2,h3,h4 {
        margin: 0 0 7px 0;
    }
    a {
      color:#405d27;
    }
    h1 {
        text-shadow: 2px 2px red;
    }
    h3 {
        font-size: 25px;
        text-shadow: 2px 2px yellow;
    }

    h4 {
        font-size: 20px;
        text-shadow: 2px 2px gold;
    }
    textarea {
        width:100%;
        height: 150px;
    }
    *{

        font-family: "Courier", Times, serif;

    }
    p{

        font-size: 15px;
    }

    input[type="submit"]
{
    border:1px solid black;
    text-decoration:none;
    color:black;
    margin: 7px 0;
    padding: 7px;
}
</style>
</head>

<body>

<h1>Admin Panel</h1>
<h2>HAM Rasp Server <?php echo $HamRaspSV_Version; ?></h2>
<hr>
<table style="width:100%">
  <tr>
    <td valign="top" style="width: 220px;"><p style="margin: 0; background-color: #EEE; text-align: center;"><b>MENU</b>
    	<p><a href="?page=">Status Page</a></p>
    	<hr>
      <b>Services:</b>
    	<p><a href="?page=BeaconCW">Beacon CW (GPIO)</a></p>
    	<p><a href="?page=APRS-KISS-TNC">APRS (KISS TNC)</a></p>
    	<p><a href="?page=APRS-SDR">APRS (SDR)</a></p>
    	<p><a href="?page=APRS-SOUNDCARD">APRS (SoundCard)</a></p>
    	<p><a href="?page=SVXLINK">Echolink(SoundCard)</a></p>
      
    	<p><a href="?page=AutoRX-Sonde-SDR">AutoRX Sondes (SDR)</a></p>
      <?php
/*
      <p><a href="?page=Voicer">Voice ID Report</a></p>
      <p><a href="?page=FT8DXerBot">FT8 DXerBot(SoundCard)</a></p>

*/
      ?>
        <hr>
      <b>Configuration:</b>
        <p><a href="?page=Networking">Wifi & Networking</a></p>
        <p><a href="?page=BackupRestore">Backup / Restore</a></p>

        <p><a href="?page=loginPanel">Admin Panel Login</a></p>
        <p><a href="?page=Update">Update</a></p>
        <p><a href="?page=Reboot">Reboot</a></p>
        <p><a href="?page=Shutdown">Shutdown</a></p>
    </p><hr><form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick" />
<input type="hidden" name="hosted_button_id" value="QS7T3C8MVMRT2" />
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" />
<img alt="" border="0" src="https://www.paypal.com/en_BR/i/scr/pixel.gif" width="1" height="1" />
</form>
</td>
    <td valign="top"><?php // 

if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'loginPanel') {
echo '<h3>Admin Panel Login Page</h3>';

if (!empty($_REQUEST['confirm'])) {

  if (!empty($_REQUEST['loginPanel_ACTIVE']) && $_REQUEST['loginPanel_ACTIVE'] == 'true') {
    $configs['loginPanel_ACTIVE'] = true;
  } else $configs['loginPanel_ACTIVE'] = false;

  $configs['loginPanel_username'] = strtolower($_REQUEST['loginPanel_username']);
  $configs['loginPanel_passwd'] = $_REQUEST['loginPanel_passwd'];


    SaveConfigJquery($configs);
    okmsg('Configuration saved!');
    echo '<hr>';

}

echo '<form method="POST"  action="?page=loginPanel">
<p><input type="checkbox" name="loginPanel_ACTIVE" value="true" ';
    if ($configs['loginPanel_ACTIVE'] == true) {
        echo 'checked';
    }
    echo '> <font size=4><b><u>Active</b></u></font></p>
<h4>Your Login Data</h4>
<p>Username:<br>
<input maxlength="9" step="any" type="text" name="loginPanel_username" value="';
    echo $configs['loginPanel_username'];
    echo '">
</p>
<p>Password:<br>
<input maxlength="9" step="any" type="text" name="loginPanel_passwd" value="';
    echo $configs['loginPanel_passwd'];
    echo '">
</p>
<input type="submit" name="confirm" value="Save">
</form>
';

} else 
if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'Voicer') {
echo '<h3>Auto Voice Report</h3>
<form method="POST" enctype="multipart/form-data" action="?page=Voicer">
<p>Voice system for automatic reproduction of recordings and meteorological data.</p>
<p><input type="checkbox" name="Voicer_ACTIVE" value="true" ';
    if ($configs['Voicer_ACTIVE'] == true) {
        echo 'checked';
    }
    echo '> <font size=4><b><u>Active</b></u></font></p>';
echo '<h4>Hardware</h4>
<p><b>Sound card select:</b><p>';
$list = explode('
', trim(shell_exec('cat /var/www/html/USBSoundCards.txt | grep card')));
$a=0;$errrr=true;
while ($a < count($list)) {
  if (is_numeric($list[$a][5]) == true && strlen($list[$a]) > 5) {
  echo '<input type="radio" name="VOICER_CARDNUMBER" value="'.$list[$a][5].'"';
  if ($configs['VOICER_CARDNUMBER'] == $list[$a][5]) echo ' checked';
  echo '> '.$list[$a].'<br>';$errrr=false;}
  $a++;
}

if ($errrr) {
 echo 'no sound card detected!';
}
echo '</p>';
echo '<p><b>GPIO PTT Number:</b><br>
<input  maxlength="2" max=39 min=0 type="number" name="VOICER_GPIO_PTT" value="';
    echo $configs['VOICER_GPIO_PTT'];
    echo '">
</p>

<p><b>GPIO COR/COS/CSQ Detection Number:</b><br>
<input  maxlength="2" max=39 min=0 type="number" name="VOICER_GPIO_COR" value="';
    echo $configs['VOICER_GPIO_COR'];
    echo '">
</p>
<p><b>COR/COS/CSQ Active Logic Level:</b><br>
<input type="radio" name="VOICER_GPIO_COR_ACTIVE_LEVEL" value="LOW" ';
if ($configs['Voicer_Type'] == 'LOW') {echo 'checked';}
echo '> LOW.<br>
<input type="radio" name="VOICER_GPIO_COR_ACTIVE_LEVEL" value="HIGH" ';
if ($configs['Voicer_Type'] == 'HIGH') {echo 'checked';}
echo '> HIGH.
</p>
<p>Note: Detecting the existence of frequency activity is important to avoid not reproducing the recording simultaneously, hindering the radio operation.
<hr>
<p><input type="radio" name="Voicer_Type" value="mp3" ';
if ($configs['Voicer_Type'] == 'mp3') {echo 'checked';}
echo '> Use MP3 file.</p>

    <input type="file" name="uploaded_file"></input>
    <p>View current file: ';
	if (file_exists('voicer.mp3')) { echo 'ok'; } else { echo 'No MP3 file has been uploaded yet.'; }
    echo '</p>
<hr>
<p><input type="radio" name="Voicer_Type" value="google" ';
if ($configs['Voicer_Type'] == 'google') {echo 'checked';}
echo '> Use Google TTS.</p>
<hr>
  <input type="submit" name="confirm" value="Save">
</form>
';



} else if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'AutoRX-Sonde-SDR') {
echo '<form method="POST" action="?page=AutoRX-Sonde-SDR">
<h3>AutoRX Radiosondes SDR Receiver</h3>
';



if (!empty($_REQUEST['confirm'])) {

    if (!empty($_REQUEST['AutoRX_ACTIVE']) && $_REQUEST['AutoRX_ACTIVE'] == 'true') {
        $configs['AutoRX_ACTIVE'] = true;} else {$configs['AutoRX_ACTIVE'] = false;}
	
	if (!empty($_REQUEST['AutoRX_APRS_ACTIVE']) && $_REQUEST['AutoRX_APRS_ACTIVE'] == 'true') {
        $configs['AutoRX_APRS_ACTIVE'] = true;} else {$configs['AutoRX_APRS_ACTIVE'] = false;}
        
    $cmp='AutoRX_APRS_Callsign';
	if ($_REQUEST[$cmp] == str_replace('-', '', $_REQUEST[$cmp])) { $_REQUEST[$cmp] .= '-0';}
    $configs['AutoRX_APRS_Callsign'] = strtoupper($_REQUEST['AutoRX_APRS_Callsign']);
    $configs['AutoRX_APRS_Passcode'] = ($_REQUEST['AutoRX_APRS_Passcode']+1)-1;
    $_REQUEST['AutoRX_latitude']=str_replace(',', '.', $_REQUEST['AutoRX_latitude']);
    $_REQUEST['AutoRX_longitude']=str_replace(',', '.', $_REQUEST['AutoRX_longitude']);
    $configs['AutoRX_latitude'] = ($_REQUEST['AutoRX_latitude']+1)-1;
    $configs['AutoRX_longitude'] = ($_REQUEST['AutoRX_longitude']+1)-1;
    $configs['AutoRX_alt'] = ($_REQUEST['AutoRX_alt']+1)-1;
	
	$configs['AutoRX_SDR_DONGLE'] = ($_REQUEST['AutoRX_SDR_DONGLE']+1)-1;

	$_REQUEST['AutoRX_Low_Freq_Scan']=str_replace(',', '.', $_REQUEST['AutoRX_Low_Freq_Scan']);
	$_REQUEST['AutoRX_High_Freq_Scan']=str_replace(',', '.', $_REQUEST['AutoRX_High_Freq_Scan']);
	$configs['AutoRX_Low_Freq_Scan'] = ($_REQUEST['AutoRX_Low_Freq_Scan']+1)-1;
    $configs['AutoRX_High_Freq_Scan'] = ($_REQUEST['AutoRX_High_Freq_Scan']+1)-1;

    SaveConfigJquery($configs);
    okmsg('Configuration saved! Please reboot!');
    echo '<hr>';
}





echo '<input type="checkbox" name="AutoRX_ACTIVE" value="true" ';
    if ($configs['AutoRX_ACTIVE'] == true) {
        echo 'checked';
    }
    echo '> <font size=4><b><u>Active</b></u></font>';
if ($configs['AutoRX_ACTIVE'] == true) {
        echo '<p>View AutoRX Map on link: <a href="http://'.$_SERVER['HTTP_HOST'].':5000" target=_BLANK>http://'.$_SERVER['HTTP_HOST'].':5000</a></p>';
    }
    echo '
<hr>';
echo '
<p>Automatic reception and uploading of Radiosonde positions to multiple services, including: The Habitat High-Altitude Balloon Tracker, APRS, ChaseMapper and OziPlotter, for mobile radiosonde chasing.</p>
<p><b>Currently we support the following radiosonde types:</b><br>
Vaisala RS92 (experimental support for the RS92-NGP)<br>
Vaisala RS41<br>
Graw DFM06/DFM09/DFM17/PS-15<br>
Meteomodem M10 (Thanks Viproz!)<br>
Intermet iMet-4 (and narrowband iMet-1 sondes) - Experimental.</p>

<h4>Search Radiosonde Signal</h4>
<p>The radiosonde signal is found by scanning a range of frequencies. Select which SDR dongle to use and which frequency to scan.</p>
';

echo '<p><b>DEVICE:</b></p>';
$str = shell_exec('cat /var/www/html/ListaDeDonglesSDR.txt');
$str = trim(explode('Using device ', explode('(s):', $str)[1])[0]);
$sdr_dongles = explode('
', $str);
$x= 0; $errrr = true;
while ($x<count($sdr_dongles)) {
	$sdr_dongles[$x] = trim($sdr_dongles[$x]);
	if (strlen($sdr_dongles[$x])> 5) {
echo '<input type="radio" name="AutoRX_SDR_DONGLE" value="'.$sdr_dongles[$x][0].'"';
  if ($configs['AutoRX_SDR_DONGLE'] == $sdr_dongles[$x][0]) echo ' checked';
  echo '> '.$sdr_dongles[$x].'<br>'; $errrr = false; }
$x++;
}

if ( $errrr == true ) echo '<p><b><font color=red>No RTL-SDR dongle Connected! Please connect RTL-SDR dongle and reboot the raspberry!</font></b></p>';

echo '<p><b>Frequency Scan Range:</b></p>
<p>Low Frequency Scan:<br>
<input maxlength="5" style="width:50px;" type="text" name="AutoRX_Low_Freq_Scan" value="';
	if (!empty($configs['AutoRX_Low_Freq_Scan']))
    	echo $configs['AutoRX_Low_Freq_Scan'];
	if (empty($configs['AutoRX_Low_Freq_Scan']))
		echo 402.4;
    echo '">MHz Ex: 402.6
</p><p>High Frequency Scan:<br>
<input maxlength="5" style="width:50px;" type="text" name="AutoRX_High_Freq_Scan" value="';
	if (!empty($configs['AutoRX_High_Freq_Scan']))
    	echo $configs['AutoRX_High_Freq_Scan'];
	if (empty($configs['AutoRX_High_Freq_Scan']))
		echo 403.5;
    echo '">MHz Ex: 403.4
</p>
<h4>Your Location</h4>
<p>Latitude:<br>
<input maxlength="9" type="text" name="AutoRX_latitude" value="';
    echo $configs['AutoRX_latitude'];
    echo '">
</p>
<p>Longitude:<br>
<input maxlength="9" type="text" name="AutoRX_longitude" value="';
    echo $configs['AutoRX_longitude'];
    echo '">
</p>
<p>Altitude:<br>
<input maxlength="4"  type="number" name="AutoRX_alt" value="';
    echo $configs['AutoRX_alt'];
    echo '"> in meters
</p>

<h4>APRS Integration</h4>
';

echo '<p><input type="checkbox" name="AutoRX_APRS_ACTIVE" value="true" ';
    if ($configs['AutoRX_APRS_ACTIVE'] == true) {
        echo 'checked';
    }
echo '> Active </p>';

echo '
<p>Callsign:<br>
<input maxlength="9" type="text" name="AutoRX_APRS_Callsign" value="';
    echo $configs['AutoRX_APRS_Callsign'];
    echo '">
</p>

<p>Passcode:<br>
<input maxlength="7" max=9999999 min=0 type="number" name="AutoRX_APRS_Passcode" value="';
    echo $configs['AutoRX_APRS_Passcode'];
    echo '">
</p>
  <input type="submit" name="confirm" value="Save">
</form>

';




} else if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'Shutdown') {
if (!empty($_REQUEST['confirm'])) {
    shell_exec('echo "sleep 5 && poweroff &" >> commands.txt');
echo '
<h3>Shutting down system.</h3>
<p>bye bye</p>

<style>
.loader {
  border: 16px solid #f3f3f3;
  border-radius: 50%;
  border-top: 16px solid #3498db;
  width: 120px;
  height: 120px;
  -webkit-animation: spin 2s linear infinite; /* Safari */
  animation: spin 2s linear infinite;
}

/* Safari */
@-webkit-keyframes spin {
  0% { -webkit-transform: rotate(0deg); }
  100% { -webkit-transform: rotate(360deg); }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>


<div class="loader"></div>
';
} else {
echo '<h3>Shutdown System</h3>
<form method="POST" action="?page=Shutdown">
  <input type="submit" name="confirm" value="Shutdown System Now!">
</form>
';
}
} else if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'Update') {

echo '<h3>Update</h3>';

if (!empty($_REQUEST['confirm'])) {
  echo '<p>Downloading files .... ';

  $content = file_get_contents(LINK_DE_INDEX);
  if (strlen($content) > 10000) {
    $fp = fopen('/etc/wpa_supplicant/eth.net', 'w');
    fwrite($fp, $content);
    fclose($fp);
    echo '<font color=green>OK';
  } else {
    echo '<font color=red>FAIL :(';
  }

} else {

echo '<p>This procedure requires an internet connection.</p>';
echo '<p style="text-shadow: none;">Connection with Internet: ';

$status = shell_exec('cat /var/www/html/wifiStatus.txt');
if ($status == 1) {
    echo '<b style="text-shadow: 2px 2px green; color:#82b74b;">ONLINE</b>';
} else {
    echo '<b style="text-shadow: 2px 2px red;">OFFLINE</b>'; die;
}
echo '</p>';
echo '<p style="text-shadow: none;">Connection with Update Server: ';
$downVers = @ ($versao = file_get_contents(LINK_DE_VERSAO));
if ($downVers) {
    echo '<b style="text-shadow: 2px 2px green; color:#82b74b;">ONLINE</b>';
} else {
    echo '<b style="text-shadow: 2px 2px red;">OFFLINE</b>'; die;
}
echo '</p>';


if ($HamRaspSV_Version != trim($versao)) {
  echo '<h4>Update System</h4>
  <P>Before you upgrade, we recommend that you back up your settings.</P>
<form method="POST" action="?page=Update">
  <input type="submit" name="confirm" value="Update System Now!">
</form>';
} else {
	echo '<p>No updates available.';
}


}




} else if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'BackupRestore') {
echo '<h3>Backup / Restore</h3>
<h4>Backup</h4>
<p>Download the current settings <a href="?bkpConfig=byPY5BK">here</a>.</p>
<h4>Restore</h4>';

if(!empty($_FILES['uploaded_file'])){
  $arq = '/var/www/html/_ConfRestore_'.time();
    if(move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $arq)) {
      $fp = fopen('/var/www/html/configs', 'w');
      fwrite($fp, trim(file_get_contents($arq)));
      fclose($fp);
      shell_exec("rm $arq");
      okmsg('Configuration saved! Please reboot!');
    } else{
      okmsg('There was an error uploading the file, please try again!');
    }
  }

echo '
 <form enctype="multipart/form-data" action="?page=BackupRestore" method="POST">
    <p>Upload your config file</p>
    <input type="file" name="uploaded_file"></input><br />
    <input type="submit" value="Upload"></input>
  </form>
';

} elseif (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'SVXLINK') {
echo '<h3>Echolink Using SVXLINK + Sound card</h3>
<p>This operation requires that a <u>USB sound card</u> is connected.</p>
<p>The PTT of the transceiver is active via the positive pulse on the <b><u>GPIO pin</b></u>.</p>
<p>Leave the fields blank if you do not want to use them.</p>';

if (!empty($_REQUEST['SVXLINK_COR']) && !empty($_REQUEST['save'])) {

    $configs['SVXLINK_COMMENT'] = $_REQUEST['SVXLINK_COMMENT'];
    $configs['SVXLINK_PW'] = $_REQUEST['SVXLINK_PW'];
    $configs['SVXLINK_CALLSIGN'] = $_REQUEST['SVXLINK_CALLSIGN'];
    $configs['SVXLINK_COR'] = $_REQUEST['SVXLINK_COR'];
    $configs['SVXLINK_COR_GPIO'] = ($_REQUEST['SVXLINK_COR_GPIO']+1)-1;
    $configs['SVXLINK_PTT_GPIO'] = ($_REQUEST['SVXLINK_PTT_GPIO']+1)-1;
    $configs['SVXLINK_SOUNDCARD_NUMBER'] = ($_REQUEST['SVXLINK_SOUNDCARD_NUMBER']+1)-1;

    $configs['SVXLINK_SYOPNAME'] = $_REQUEST['SVXLINK_SYOPNAME'];
    $configs['SVXLINK_LOCATION'] = $_REQUEST['SVXLINK_LOCATION'];
    $configs['SVXLINK_AUTOCONN'] = $_REQUEST['SVXLINK_AUTOCONN'];
    $configs['SVXLINK_PROXYSV'] = $_REQUEST['SVXLINK_PROXYSV'];
    $configs['SVXLINK_PROXYPORT'] = $_REQUEST['SVXLINK_PROXYPORT'];
    $configs['SVXLINK_PROXYPW'] = $_REQUEST['SVXLINK_PROXYPW'];

    if (!empty($_REQUEST['SVXLINK_ACTIVE']) && $_REQUEST['SVXLINK_ACTIVE'] == 'true') {
        $configs['SVXLINK_ACTIVE'] = true;
    } else {
        $configs['SVXLINK_ACTIVE'] = false;
    }
    SaveConfigJquery($configs);
    okmsg('Configuration saved! Please reboot!');
    echo '<hr>';
}
echo '<form method="POST" action="?page=SVXLINK&save=ok"><input type="checkbox" name="SVXLINK_ACTIVE" value="true" ';
    if ($configs['SVXLINK_ACTIVE'] == true) {
        echo 'checked';
    }
    echo '> <font size=4><b><u>Active</b></u></font>
<hr>';

echo '<h4>Sound card</h4><p>';
$list = explode('
', trim(shell_exec('cat /var/www/html/USBSoundCards.txt | grep card')));
$a=0; $errrr = true;
while ($a < count($list)) {
  if (is_numeric($list[$a][5]) == true && strlen($list[$a]) > 5) {
  echo '<input type="radio" name="SVXLINK_SOUNDCARD_NUMBER" value="'.$list[$a][5].'"';
  if ($configs['SVXLINK_SOUNDCARD_NUMBER'] == $list[$a][5]) echo ' checked';
  echo '> '.$list[$a].'<br>'; $errrr=false;}
  $a++;
}
if ($errrr) {
 echo 'no sound card detected!';
}
echo '</p>';


echo '
<h4>TX PTT</h4>
<p>GPIO PIN<br><input  maxlength="3" max=30 min=0 type="number" name="SVXLINK_PTT_GPIO" value="';
    echo $configs['SVXLINK_PTT_GPIO'];
    echo '"> (set 0 if use VOX)</p>
<h4>RX Detection Method</h4>
<p><input type="radio" name="SVXLINK_COR" value="VOX" '; if ($configs['SVXLINK_COR'] == "VOX") {echo 'checked';} echo '> VOX<br>
  <input type="radio" name="SVXLINK_COR" value="HIGH" '; if ($configs['SVXLINK_COR'] == "HIGH") {echo 'checked';} echo '> COR Active HIGH (Using GPIO)<br>
  <input type="radio" name="SVXLINK_COR" value="LOW" '; if ($configs['SVXLINK_COR'] == "LOW") {echo 'checked';} echo '> COR Active LOW (Using GPIO)</p>
  <p>COR Detection GPIO PIN:<br><input max=30 min=0 type="number" name="SVXLINK_COR_GPIO" value="';
    echo $configs['SVXLINK_COR_GPIO'];
    echo '"></p>
<h4>SVXLINK Parameters</h4>
<p>Callsign:<br>
    <input  maxlength="9" type="text" name="SVXLINK_CALLSIGN" value="';
    echo $configs['SVXLINK_CALLSIGN'];
    echo '"> Ex: PY0XYZ-L, PY0XYZ-R or PY0XYZ
</p>';



echo '<p>Echolink Password:<br>
    <input  maxlength="8" type="text" name="SVXLINK_PW" value="';
    echo $configs['SVXLINK_PW'];
    echo '"> (<a target="_BLANK" href="http://www.echolink.org/validation">get here</a>)
</p>';

echo '<p>Station Comment:<br>
    <input  maxlength="200" type="text" name="SVXLINK_COMMENT" value="';
    echo $configs['SVXLINK_COMMENT'];
    echo '"> Ex: Echolink of Parana!
</p>




<p>Syop Name:<br>
    <input  maxlength="30" type="text" name="SVXLINK_SYOPNAME" value="';
    echo $configs['SVXLINK_SYOPNAME'];
    echo '"> Ex: Ricardo
</p>


<p>Location:<br>
    <input  maxlength="80" type="text" name="SVXLINK_LOCATION" value="';
    echo $configs['SVXLINK_LOCATION'];
    echo '"> Ex: Londrina, PR - BR
</p>


<p>Auto Connection on Conference:<br>
    <input  maxlength="80" type="number" name="SVXLINK_AUTOCONN" value="';
    echo $configs['SVXLINK_AUTOCONN'];
    echo '"> Ex: 989796 (nodle number)<br>Leave blank if you do not want to use.
</p>


<hr>
<h4>Echolink Proxy</h4>
<p>Leave blank if you do not want to use.
<p>Server:<br>
    <input  maxlength="80" type="text" name="SVXLINK_PROXYSV" value="';
    echo $configs['SVXLINK_PROXYSV'];
    echo '"> Ex: the.proxy.server
</p><p>Port:<br>
    <input  maxlength="8" type="number" name="SVXLINK_PROXYPORT" value="';
    echo $configs['SVXLINK_PROXYPORT'];
    echo '"> Ex: 8100
</p>
<p>Proxy Password:<br>
    <input  maxlength="80" type="text" name="SVXLINK_PROXYPW" value="';
    echo $configs['SVXLINK_PROXYPW'];
    echo '"> Ex: PUBLIC
</p>



  <input type="submit" value="Save">
  </form>';




} else


if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'APRS-SOUNDCARD') {
echo '<h3>APRS Gateway / Digipeater Using Soundcard</h3>
<p>This operation requires that a USB sound card is connected.</p>
<p>The PTT of the transceiver is driven via the positive pulse on the <b><u>GPIO pin</b></u>.</p>';

if (!empty($_REQUEST['APRSSOUNDCARD_BAUD']) && !empty($_REQUEST['save'])) {
    $cmp='APRSSOUNDCARD_CALLSIGN';if ($_REQUEST[$cmp] == str_replace('-', '', $_REQUEST[$cmp])) { $_REQUEST[$cmp] .= '-0';}

if (!empty($_REQUEST['APRSSOUNDCARD_APRSISSV'])) {
    $configs['APRSSOUNDCARD_APRSISSV'] = $_REQUEST['APRSSOUNDCARD_APRSISSV']; } else 
    $configs['APRSSOUNDCARD_APRSISSV'] = 'brazil.aprs2.net';


    $configs['APRSSOUNDCARD_BAUD'] = $_REQUEST['APRSSOUNDCARD_BAUD'];
    $configs['APRSSOUNDCARD_CARDNUMBER'] = ($_REQUEST['APRSSOUNDCARD_CARDNUMBER']+1)-1;
    $configs['APRSSOUNDCARD_GPIO_PTT'] = $_REQUEST['APRSSOUNDCARD_GPIO_PTT'];
    $configs['APRSSOUNDCARD_CALLSIGN'] = $_REQUEST['APRSSOUNDCARD_CALLSIGN'];
    $configs['APRSSOUNDCARD_PW'] = ($_REQUEST['APRSSOUNDCARD_PW']+1)-1;
    $configs['APRSSOUNDCARD_IGTX'] = $_REQUEST['APRSSOUNDCARD_IGTX'];
    $configs['APRSSOUNDCARD_DIGI'] = $_REQUEST['APRSSOUNDCARD_DIGI'];
    $_REQUEST['APRSSOUNDCARD_LAT']=str_replace(',', '.', $_REQUEST['APRSSOUNDCARD_LAT']);
    $_REQUEST['APRSSOUNDCARD_LON']=str_replace(',', '.', $_REQUEST['APRSSOUNDCARD_LON']);
    $configs['APRSSOUNDCARD_LAT'] = ($_REQUEST['APRSSOUNDCARD_LAT']+1)-1;
    $configs['APRSSOUNDCARD_LON'] = ($_REQUEST['APRSSOUNDCARD_LON']+1)-1;
    $configs['APRSSOUNDCARD_BCOMMENT'] = $_REQUEST['APRSSOUNDCARD_BCOMMENT'];
    $configs['APRSSOUNDCARD_SYMBOL'] = $_REQUEST['APRSSOUNDCARD_SYMBOL'];

    if (!empty($_REQUEST['APRSSOUNDCARD_APRSISFILTER'])) {
    $configs['APRSSOUNDCARD_APRSISFILTER'] = $_REQUEST['APRSSOUNDCARD_APRSISFILTER'];
  } else $configs['APRSSOUNDCARD_APRSISFILTER'] = 'm/50';

    
    if (strlen($configs['APRSSOUNDCARD_SYMBOL']) < 2) {
      $configs['APRSSOUNDCARD_SYMBOL'] = '/-';
    }
    if (!empty($_REQUEST['APRSSOUNDCARD_ACTIVE']) && $_REQUEST['APRSSOUNDCARD_ACTIVE'] == 'true') {
        $configs['APRSSOUNDCARD_ACTIVE'] = true;
    } else {
        $configs['APRSSOUNDCARD_ACTIVE'] = false;
    }
    SaveConfigJquery($configs);
    okmsg('Configuration saved!  Please reboot!');
    echo '<hr>';
}
echo '<form method="POST" action="?page=APRS-SOUNDCARD&save=ok"><input type="checkbox" name="APRSSOUNDCARD_ACTIVE" value="true" ';
    if ($configs['APRSSOUNDCARD_ACTIVE'] == true) {
        echo 'checked';
    }
    echo '> <font size=4><b><u>Active</b></u></font>
<hr>';


echo '<h4>Sound card</h4><p>';
$list = explode('
', trim(shell_exec('cat /var/www/html/USBSoundCards.txt | grep card')));
$a=0;$errrr=true;
while ($a < count($list)) {
  if (is_numeric($list[$a][5]) == true && strlen($list[$a]) > 5) {
  echo '<input type="radio" name="APRSSOUNDCARD_CARDNUMBER" value="'.$list[$a][5].'"';
  if ($configs['APRSSOUNDCARD_CARDNUMBER'] == $list[$a][5]) echo ' checked';
  echo '> '.$list[$a].'<br>';$errrr=false;}
  $a++;
}

if ($errrr) {
 echo 'no sound card detected!';
}
echo '</p>';

echo '
<h4>RF Operation Parameters</h4>
<p>GPIO PTT Number:<br>
<input  maxlength="3" max=30 min=0 type="number" name="APRSSOUNDCARD_GPIO_PTT" value="';
    echo $configs['APRSSOUNDCARD_GPIO_PTT'];
    echo '"> (Set 0 if use VOX)
</p>
<p>APRS Baud:<br>
  <input type="radio" name="APRSSOUNDCARD_BAUD" value="300" '; if ($configs['APRSSOUNDCARD_BAUD'] == 300) {echo 'checked';} echo '> 300<br>
  <input type="radio" name="APRSSOUNDCARD_BAUD" value="1200" '; if ($configs['APRSSOUNDCARD_BAUD'] == 1200) {echo 'checked';} echo '> 1200<br>
  <input type="radio" name="APRSSOUNDCARD_BAUD" value="9600" '; if ($configs['APRSSOUNDCARD_BAUD'] == 9600) {echo 'checked';} echo '> 9600</p>
<h4>APRS Gateway/Digi Parameters</h4>
';
echo '<p>APRS-IS Server:<br>
    <input  maxlength="80" type="text" name="APRSSOUNDCARD_APRSISSV" value="';
    if (!empty($configs['APRSSOUNDCARD_APRSISSV'])) {
    echo $configs['APRSSOUNDCARD_APRSISSV'];
  } else echo 'brazil.aprs2.net';
    echo '"> Ex: brazil.aprs2.net
</p>';
echo '
<p>Callsign:<br>
    <input  maxlength="9" type="text" name="APRSSOUNDCARD_CALLSIGN" value="';
    echo $configs['APRSSOUNDCARD_CALLSIGN'];
    echo '"> Ex: PY0XYZ-10
</p>';
echo '<p>APRS-IS Password:<br>
    <input  maxlength="8" type="number" name="APRSSOUNDCARD_PW" value="';
    echo $configs['APRSSOUNDCARD_PW'];
    echo '"> (<a target="_BLANK" href="http://www.py5bk.net/aprs-passcode-generator/">get here</a>)
</p><p><input type="checkbox" name="APRSSOUNDCARD_DIGI" value="YES" ';
    if ($configs['APRSSOUNDCARD_DIGI'] == true) {
        echo 'checked';
    }
    echo '>Digipeater</p>
<p style="margin: 0 ;"><input type="checkbox" name="APRSSOUNDCARD_IGTX" value="YES" ';
    if ($configs['APRSSOUNDCARD_IGTX'] == true) {
        echo 'checked';
    }
echo '>TX Internet > RF</p>';

echo '<p style="margin: 0 30px;">APRS-IS Filter:<br>
    <input  maxlength="80" type="text" name="APRSSOUNDCARD_APRSISFILTER" value="';
    if (!empty($configs['APRSSOUNDCARD_APRSISFILTER'])) {
    echo $configs['APRSSOUNDCARD_APRSISFILTER'];
  } else echo 'm/50';
    echo '"> Ex: m/80 [<a href="http://www.aprs-is.net/javAPRSFilter.aspx" target="_BLANK">view help</a>]
</p>';


echo '<p>Latitude:<br>
    <input  maxlength="10" type="text" name="APRSSOUNDCARD_LAT" value="';
    echo $configs['APRSSOUNDCARD_LAT'];
    echo '"> Ex: -23.0000
</p>';

echo '<p>Longitude:<br>
    <input  maxlength="10" type="text" name="APRSSOUNDCARD_LON" value="';
    echo $configs['APRSSOUNDCARD_LON'];
    echo '"> Ex: -50.0000
</p>';

echo '<p>Beacon Comment:<br>
    <input  maxlength="80" type="text" name="APRSSOUNDCARD_BCOMMENT" value="';
    echo $configs['APRSSOUNDCARD_BCOMMENT'];
    echo '"> Ex: RX Gateway of Parana!
</p>';

echo '<p>Beacon Symbol:<br>
    <input  maxlength="10" type="text" name="APRSSOUNDCARD_SYMBOL" value="';
    echo $configs['APRSSOUNDCARD_SYMBOL'];
    echo '"> Ex:  /# (digipeater) or igate or /-
</p>

  <input type="submit" value="Save">
  </form>
';




} else
if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'APRS-SDR') {
echo '<h3>APRS Gateway Using RTL SDR</h3>';

if (!empty($_REQUEST['APRSSDR_BAUD']) && !empty($_REQUEST['save'])) {
    $cmp='APRSSDR_CALLSIGN';if ($_REQUEST[$cmp] == str_replace('-', '', $_REQUEST[$cmp])) { $_REQUEST[$cmp] .= '-0';}
    $configs['APRSSDR_BAUD'] = $_REQUEST['APRSSDR_BAUD'];
    $configs['APRSSDR_FREQ'] = $_REQUEST['APRSSDR_FREQ'];
    $configs['APRSSDR_CALLSIGN'] = $_REQUEST['APRSSDR_CALLSIGN'];
    $configs['APRSSDR_PW'] = ($_REQUEST['APRSSDR_PW']+1)-1;
    $configs['APRSSDR_LAT'] = ($_REQUEST['APRSSDR_LAT']+1)-1;
    $configs['APRSSDR_LON'] = ($_REQUEST['APRSSDR_LON']+1)-1;
    $configs['APRSSDR_SDR_DONGLE'] = ($_REQUEST['APRSSDR_SDR_DONGLE']+1)-1;
    $configs['APRSSDR_BCOMMENT'] = $_REQUEST['APRSSDR_BCOMMENT'];
    $configs['APRSSDR_SYMBOL'] = $_REQUEST['APRSSDR_SYMBOL'];
    if (strlen($configs['APRSSDR_SYMBOL'])<2) {
$configs['APRSSDR_SYMBOL'] = '/-';
    }

    if (!empty($_REQUEST['APRSSDR_APRSISSV'])) {
    $configs['APRSSDR_APRSISSV'] = $_REQUEST['APRSSDR_APRSISSV']; } else 
    $configs['APRSSDR_APRSISSV'] = 'brazil.aprs2.net';




    if (!empty($_REQUEST['APRSSDR_ACTIVE']) && $_REQUEST['APRSSDR_ACTIVE'] == 'true') {
        $configs['APRSSDR_ACTIVE'] = true;
    } else $configs['APRSSDR_ACTIVE'] = false;

    SaveConfigJquery($configs);
    okmsg('Configuration saved!  Please reboot!');
    echo '<hr>';
}
echo '<form method="POST" action="?page=APRS-SDR&save=ok">
<input type="checkbox" name="APRSSDR_ACTIVE" value="true" ';
    if ($configs['APRSSDR_ACTIVE'] == true) {
        echo 'checked';
    }
    echo '> <font size=4><b><u>Active</b></u></font>
<hr>
<h4>RTL SDR Parameters</h4>';

echo '<p><b>DEVICE:</b></p>';
$str = shell_exec('cat /var/www/html/ListaDeDonglesSDR.txt');
$str = trim(explode('Using device ', explode('(s):', $str)[1])[0]);
$sdr_dongles = explode('
', $str);
$x= 0; $errrr = true;
while ($x<count($sdr_dongles)) {
	$sdr_dongles[$x] = trim($sdr_dongles[$x]);
	if (strlen($sdr_dongles[$x])> 5) {
echo '<input type="radio" name="APRSSDR_SDR_DONGLE" value="'.$sdr_dongles[$x][0].'"';
  if ($configs['APRSSDR_SDR_DONGLE'] == $sdr_dongles[$x][0]) echo ' checked';
  echo '> '.$sdr_dongles[$x].'<br>'; $errrr = false; }
$x++;
}

if ( $errrr == true ) echo '<p><b><font color=red>No RTL-SDR dongle Connected! Please connect RTL-SDR dongle and reboot the raspberry!</font></b></p>';

echo '
<p>Frequency:<br>
    <input  maxlength="8" type="text" name="APRSSDR_FREQ" value="';
    echo $configs['APRSSDR_FREQ'];
    echo '"> Ex: 145.570 for 145.57MHz
</p>
<h4>APRS Gateway Parameters</h4>
<p>APRS Bauds:<br>
  <input type="radio" name="APRSSDR_BAUD" value="300" '; if ($configs['APRSSDR_BAUD'] == 300) {echo 'checked';} echo '> 300<br>
  <input type="radio" name="APRSSDR_BAUD" value="1200" '; if ($configs['APRSSDR_BAUD'] == 1200) {echo 'checked';} echo '> 1200<br>
  <input type="radio" name="APRSSDR_BAUD" value="9600" '; if ($configs['APRSSDR_BAUD'] == 9600) {echo 'checked';} echo '> 9600</p>';

echo '<p>APRS-IS Server:<br>
    <input  maxlength="80" type="text" name="APRSSDR_APRSISSV" value="';
    if (!empty($configs['APRSSDR_APRSISSV'])) {
    echo $configs['APRSSDR_APRSISSV'];
  } else echo 'brazil.aprs2.net';
    echo '"> Ex: brazil.aprs2.net
</p>';

echo '
<p>Callsign:<br>
    <input  maxlength="9" type="text" name="APRSSDR_CALLSIGN" value="';
    echo $configs['APRSSDR_CALLSIGN'];
    echo '"> Ex: PY0XYZ-10
</p>';
echo '<p>APRS-IS Password:<br>
    <input  maxlength="8" type="number" name="APRSSDR_PW" value="';
    echo $configs['APRSSDR_PW'];
    echo '"> (<a target="_BLANK" href="http://www.py5bk.net/aprs-passcode-generator/">get here</a>)
</p>';

echo '<p>Latitude:<br>
    <input  maxlength="10" type="text" name="APRSSDR_LAT" value="';
    echo $configs['APRSSDR_LAT'];
    echo '"> Ex: -23.0000
</p>';

echo '<p>Longitude:<br>
    <input  maxlength="10" type="text" name="APRSSDR_LON" value="';
    echo $configs['APRSSDR_LON'];
    echo '"> Ex: -50.0000
</p>';

echo '<p>Beacon Comment:<br>
    <input  maxlength="80" type="text" name="APRSSDR_BCOMMENT" value="';
    echo $configs['APRSSDR_BCOMMENT'];
    echo '"> Ex: RX Gateway of Parana!
</p>';

echo '<p>Beacon Symbol:<br>
    <input  maxlength="10" type="text" name="APRSSDR_SYMBOL" value="';
    echo $configs['APRSSDR_SYMBOL'];
    echo '"> Ex: igate or /-
</p>

  <input type="submit" value="Save">
  </form>
';




} elseif (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'APRS-KISS-TNC') {
echo '<h3>APRS Gateway / Digipeater Using KISS TNC</h3>';

if (!empty($_REQUEST['APRSKISSTNC_PORT']) && !empty($_REQUEST['save'])) {

    // coloca ssid -0 se nao tiver colocado nada
    $cmp='APRSKISSTNC_CALLSIGN';if ($_REQUEST[$cmp] == str_replace('-', '', $_REQUEST[$cmp])) { $_REQUEST[$cmp] .= '-0';}

    $configs['APRSKISSTNC_PORT'] = $_REQUEST['APRSKISSTNC_PORT'];
    $configs['APRSKISSTNC_SPEED'] = $_REQUEST['APRSKISSTNC_SPEED'];
    $configs['APRSKISSTNC_DIGI'] = $_REQUEST['APRSKISSTNC_DIGI'];
    $configs['APRSKISSTNC_IGTX'] = $_REQUEST['APRSKISSTNC_IGTX'];
    $configs['APRSKISSTNC_CALLSIGN'] = $_REQUEST['APRSKISSTNC_CALLSIGN'];
    $configs['APRSKISSTNC_PW'] = $_REQUEST['APRSKISSTNC_PW'];
    $_REQUEST['APRSKISSTNC_LAT']=str_replace(',', '.', $_REQUEST['APRSKISSTNC_LAT']);
    $_REQUEST['APRSKISSTNC_LON']=str_replace(',', '.', $_REQUEST['APRSKISSTNC_LON']);
    $configs['APRSKISSTNC_LAT'] = ($_REQUEST['APRSKISSTNC_LAT']+1)-1;
    $configs['APRSKISSTNC_LON'] = ($_REQUEST['APRSKISSTNC_LON']+1)-1;
    $configs['APRSKISSTNC_BCOMMENT'] = $_REQUEST['APRSKISSTNC_BCOMMENT'];
    $configs['APRSKISSTNC_SYMBOL'] = $_REQUEST['APRSKISSTNC_SYMBOL'];
    $configs['APRSKISSTNC_INITSTR'] = $_REQUEST['APRSKISSTNC_INITSTR'];

    if (!empty($_REQUEST['APRSKISSTNC_APRSISSV'])) {
    $configs['APRSKISSTNC_APRSISSV'] = $_REQUEST['APRSKISSTNC_APRSISSV']; } else 
    $configs['APRSKISSTNC_APRSISSV'] = 'brazil.aprs2.net';

        if (!empty($_REQUEST['APRSKISSTNC_APRSISFILTER'])) { 
    $configs['APRSKISSTNC_APRSISFILTER'] = $_REQUEST['APRSKISSTNC_APRSISFILTER'];
  } else $configs['APRSKISSTNC_APRSISFILTER'] = 'm/50';
    

        if (!empty($_REQUEST['APRSKISSTNC_ACTIVE']) && $_REQUEST['APRSKISSTNC_ACTIVE'] == 'true') {
        $configs['APRSKISSTNC_ACTIVE'] = true;
    } else $configs['APRSKISSTNC_ACTIVE'] = false;


    SaveConfigJquery($configs);
    okmsg('Configuration saved! Please reboot!');
    echo '<hr>';
}
echo '<form method="POST" action="?page=APRS-KISS-TNC&save=ok">
<input type="checkbox" name="APRSKISSTNC_ACTIVE" value="true" ';
    if ($configs['APRSKISSTNC_ACTIVE'] == true) {
        echo 'checked';
    }
    echo '> <font size=4><b><u>Active</b></u></font>
<hr>
<h4>USB TNC Parameters</h4>
<p>Port: <br>
';

$list = trim(shell_exec('cat /var/www/html/ttyList.txt'));
$list = explode('
', $list);
$a=0;
while ($a < count($list)) {
  if (is_numeric($list[$a][3]) == false && $list[$a] != 'tty' && $list[$a] != 'ttyprintk' && strlen($list[$a]) > 3) {
  echo '<input type="radio" name="APRSKISSTNC_PORT" value="/dev/'.$list[$a].'"';
  if ($configs['APRSKISSTNC_PORT'] == '/dev/'.$list[$a]) echo ' checked';
  echo '> '.$list[$a]; if ($list[$a] == 'ttyAMA0') echo ' (GPIO Serial)'; echo '<br>';}
  $a++;
}

echo '</p>
<p>Serial Speed:<br>
  <input type="radio" name="APRSKISSTNC_SPEED" value="9600" '; if ($configs['APRSKISSTNC_SPEED'] == 9600) {echo 'checked';} echo '> 9600<br>
  <input type="radio" name="APRSKISSTNC_SPEED" value="19200" '; if ($configs['APRSKISSTNC_SPEED'] == 19200) {echo 'checked';} echo '> 19200<br>
  <input type="radio" name="APRSKISSTNC_SPEED" value="38400" '; if ($configs['APRSKISSTNC_SPEED'] == 38400) {echo 'checked';} echo '> 38400</p>

<p>Init String (if the TNC needs it):<br>
    <input type="text" name="APRSKISSTNC_INITSTR" value="';
    echo $configs['APRSKISSTNC_INITSTR'];
    echo '"> Ex: KISS ON\x0dRESET\x0d
</p>

  <hr>
<h4>APRS Gateway Parameters</h4>';
echo '
<p>Callsign:<br>
    <input  maxlength="9" type="text" name="APRSKISSTNC_CALLSIGN" value="';
    echo $configs['APRSKISSTNC_CALLSIGN'];
    echo '"> Ex: PY0XYZ-10
</p>';
echo '<p>APRS-IS Password:<br>
    <input  maxlength="8" type="number" name="APRSKISSTNC_PW" value="';
    echo $configs['APRSKISSTNC_PW'];
    echo '"> (<a target="_BLANK" href="http://www.py5bk.net/aprs-passcode-generator/">get here</a>)
</p>';

echo '<p>APRS-IS Server:<br>
    <input  maxlength="80" type="text" name="APRSKISSTNC_APRSISSV" value="';
    if (!empty($configs['APRSKISSTNC_APRSISSV'])) {
    echo $configs['APRSKISSTNC_APRSISSV'];
  } else echo 'brazil.aprs2.net';
    echo '"> Ex: brazil.aprs2.net
</p>';



echo '<p><input type="checkbox" name="APRSKISSTNC_DIGI" value="YES" ';
    if ($configs['APRSKISSTNC_DIGI'] == true) {
        echo 'checked';
    }
    echo '>Digipeater</p>
<p style="margin: 0;"><input type="checkbox" name="APRSKISSTNC_IGTX" value="YES" ';
    if ($configs['APRSKISSTNC_IGTX'] == true) {
        echo 'checked';
    }
echo '>TX Internet > RF (filter above)</p>';

echo '<p style="margin: 0 30px;">APRS-IS Filter:<br>
    <input  maxlength="80" type="text" name="APRSKISSTNC_APRSISFILTER" value="';
    if (!empty($configs['APRSKISSTNC_APRSISFILTER'])) {
    echo $configs['APRSKISSTNC_APRSISFILTER'];
  } else echo 'm/50';
    echo '"> Ex: m/80 [<a href="http://www.aprs-is.net/javAPRSFilter.aspx" target="_BLANK">view help</a>]
</p>';

echo '<p style="margin: 40px 0 0 0;">Latitude:<br>
    <input  maxlength="10" type="text" name="APRSKISSTNC_LAT" value="';
    echo $configs['APRSKISSTNC_LAT'];
    echo '"> Ex: -23.0000
</p>';

echo '<p>Longitude:<br>
    <input  maxlength="10" type="text" name="APRSKISSTNC_LON" value="';
    echo $configs['APRSKISSTNC_LON'];
    echo '"> Ex: -50.0000
</p>';

echo '<p>Beacon Comment:<br>
    <input  maxlength="80" type="text" name="APRSKISSTNC_BCOMMENT" value="';
    echo $configs['APRSKISSTNC_BCOMMENT'];
    echo '"> Ex: APRS Gateway of Parana!
</p>';

echo '<p>Beacon Symbol:<br>
    <input  maxlength="2" type="text" name="APRSKISSTNC_SYMBOL" value="';
    echo $configs['APRSKISSTNC_SYMBOL'];
    echo '"> Ex: /# (digipeater) or #I (igate)
</p>

  <input type="submit" value="Save">
  </form>
';




} else if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'BeaconCW') {

echo '<h3>Beacon CW using GPIOs</h3>';

if (!empty($_REQUEST['msg']) && $_REQUEST['msg'] == 'ok') {
    $configs['BeaconCWMSG'] = $_REQUEST['beaconmsgcw'];
    $configs['BeaconCWInterval'] = ($_REQUEST['BeaconCWInterval'] + 1) - 1;
    $configs['BeaconCWPTT'] = ($_REQUEST['BeaconCWPTT'] + 1) - 1;
    $configs['BeaconCWKEY'] = ($_REQUEST['BeaconCWKEY'] + 1) - 1;
    if (!empty($_REQUEST['BeaconCWActive'])) {$configs['BeaconCWActive'] = true;} else {$configs['BeaconCWActive'] = false;}
    SaveConfigJquery($configs);
    okmsg('Beacon CW Saved! Please reboot!');
}

echo '<p>This feature uses the GPIOs pins to enable transceiver transmission and transmit the CW.</p>
<p>Use two <b>NPN transistors</b> to receive the positive pulse and activate the TX and transmit the CW.<br>
We did not use the sound output for this!</p>
<form method="POST" action="?page=BeaconCW&msg=ok">

    <input type="checkbox" name="BeaconCWActive" value="true" ';
    if ($configs['BeaconCWActive'] == true) {
        echo 'checked';
    }
    echo '> <font size=4><b><u>Active</b></u></font>

<p><b>PTT GPIO Pin Number: <input type="number" min=0 max=40 name="BeaconCWPTT" value="';
    echo $configs['BeaconCWPTT'];
    echo '"></b></p>
<p><b>CW GPIO Pin Number: <input type="number" min=0 max=40 name="BeaconCWKEY" value="';
    echo $configs['BeaconCWKEY'];
    echo '"></b></p>
    <h4>Configure Beacon Message:</h4>
    <p>
    Message:<br>
    <input type="text" maxlength=128 name="beaconmsgcw" value="';
    echo $configs['BeaconCWMSG'];
    echo '">[A-Z, 0-9 and spaces]</p><p>Interval:<br>
    <input type="number" min=0 max=100 name="BeaconCWInterval" value="';
    echo $configs['BeaconCWInterval'];
    echo '">(mins)<p>
  <input type="submit" value="Save"></form>
';




} elseif (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'Reboot') {
if (!empty($_REQUEST['confirm'])) {
    shell_exec('echo "nohup sleep 5 && reboot >/dev/null 2>&1 &" >> commands.txt');
echo '<META http-equiv="refresh" content="60;URL=/"> 
<h3>Rebooting System</h3>
<p>Please be patient.</p>

<style>
.loader {
  border: 16px solid #f3f3f3;
  border-radius: 50%;
  border-top: 16px solid #3498db;
  width: 120px;
  height: 120px;
  -webkit-animation: spin 2s linear infinite; /* Safari */
  animation: spin 2s linear infinite;
}

/* Safari */
@-webkit-keyframes spin {
  0% { -webkit-transform: rotate(0deg); }
  100% { -webkit-transform: rotate(360deg); }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>


<div class="loader"></div>
';
} else {
echo '<h3>Reboot System</h3>
<form method="POST" action="?page=Reboot">
  <input type="submit" name="confirm" value="Reboot System Now!">
</form>
';
}
} elseif (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'Networking') {
    echo '<h3>Wifi & Networking</h3>';

if (!empty($_REQUEST['delWifi']) && strlen($configs['WIFI'][$_REQUEST['delWifi']]) > 2) {
	unset($configs['WIFI'][$_REQUEST['delWifi']]);
	reconfigureWifi($configs);
	SaveConfigJquery($configs);
	okmsg('The Wifi '.$_REQUEST['delWifi'].' was deleted!!');
} else if (!empty($_REQUEST['addwifi']) && strlen($_REQUEST['ssid']) > 2 && strlen($_REQUEST['pw'])>7) {

    	$configs['WIFI'][$_REQUEST['ssid']] = $_REQUEST['pw'];

    	SaveConfigJquery($configs);
    	reconfigureWifi($configs);
		okmsg('WiFi '.$_REQUEST['ssid'].' added!');
    } else if (!empty($_REQUEST['ResetWPAS'])) {
$fp = fopen('/etc/wpa_supplicant/wpa_supplicant.conf', 'w');
        fwrite($fp, 'ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
update_config=1
ap_scan=1
fast_reauth=1
');
        fclose($fp);
        
        okmsg('wpa_supplicant.conf default restored!');
} else 

    if (!empty($_REQUEST['wpa_supplicant_mod'])) {
        $fp = fopen('/etc/wpa_supplicant/wpa_supplicant.conf', 'w');
        fwrite($fp, trim($_REQUEST['wpa_supplicant']));
        fclose($fp);
        //file_put_contents('/etc/wpa_supplicant/wpa_supplicant.conf', $_REQUEST['wpa_supplicant'])
        okmsg('wpa_supplicant.conf edited!');
    } else 


    if (!empty($_REQUEST['networkinginterface'])) {
        $fp = fopen('/etc/network/interfaces', 'w');
        fwrite($fp, trim($_REQUEST['interfaces']));
        fclose($fp);
        //file_put_contents('/etc/wpa_supplicant/wpa_supplicant.conf', $_REQUEST['wpa_supplicant'])
        okmsg('/etc/network/interfaces edited!');
    }

    echo '
    <form method="POST" action="?page=Networking&addwifi=ok"><h4>Add New Wifi</h4>
    SSID:<br>
    <input type="text" name="ssid"><br>
    Password:<br>
    <input type="text" name="pw"><br>
  <input type="submit" value="Add Wifi"></form>
  <p></p>';
if (count($configs['WIFI'])) echo '<h4>Wifi Saved</h4>';
foreach($configs['WIFI'] as $result => $value) {
  if (strlen($result) > 1) {
  	echo '<p><a href="?page=Networking&delWifi='.urlencode($result).'">[X]</a> <B>SSID</B>: '.$result.' - <B>PW:</B> '.$value.'</p>';
  }
}


/*
echo '<hr>
    <form method="POST" action="?page=Networking&wpa_supplicant_mod=ok"><h4>Or Edit Wifi Networks</h4>
    <textarea id="story" name="wpa_supplicant">';
$filename = "/etc/wpa_supplicant/wpa_supplicant.conf";
$handle = fopen ($filename, "r");
$conteudo = fread ($handle, filesize ($filename));
fclose ($handle);
echo trim($conteudo);
echo '</textarea>
  <input type="submit" value="Edit wpa_supplicant.conf (Wifi Networks)"> or <a href="?page=Networking&ResetWPAS=YES" onclick="return confirm('."'Restore default wpa_supplicant.conf?'".')">reset wpa_supplicant.conf</a></form>';*/





echo '
<hr>
    <form method="POST" action="?page=Networking&networkinginterface=ok"><h4>Edit the file [/etc/network/interfaces]</h4>
    <textarea id="story" name="interfaces">';

$filename = "/etc/network/interfaces";
$handle = fopen ($filename, "r");
$conteudo = fread ($handle, filesize ($filename));
fclose ($handle);

    echo trim($conteudo);

    echo '</textarea>
  <input type="submit" value="Edit [/etc/network/interfaces]"></form>';

} else {

print_jQuery();
?>
<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {  
var atualizaDiv = setInterval(function(){
$('#mydiv').load('index.php?StatusServicesHome=PY5BK',{},function(retorno){
$('#mydiv').html(retorno)
//window.scrollTo(0,document.body.scrollHeight)
});
      }, 1500
  );
});
//]]></script>
<div id="mydiv">  

<style>
.loader {
  border: 16px solid #f3f3f3;
  border-radius: 50%;
  border-top: 16px solid #3498db;
  width: 120px;
  height: 120px;
  -webkit-animation: spin 2s linear infinite; /* Safari */
  animation: spin 2s linear infinite;
}

/* Safari */
@-webkit-keyframes spin {
  0% { -webkit-transform: rotate(0deg); }
  100% { -webkit-transform: rotate(360deg); }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>


<div class="loader"></div>
</div>
<?php

}
    ?></td>
  </tr>

</table>
<hr>
<p align="center" style="font-family: Courier;">by PY5BK - <a href="http://www.py5bk.net">www.py5bk.net</a> - <a href="http://www.bitbaru.com">www.bitbaru.com</a><br>Ricardo <b>AURELIO</b> Seco <a style="text-decoration: none;" href="http://<?php echo $_SERVER['HTTP_HOST']; ?>/?startx=a">-</a> py5bk@qsl.net<br>
Londrina - PR, Brazil, Grid GG46 - <a href="https://www.qrz.com/db/py5bk">QRZ.com Profile</a></p>
</body>
<!-- DEUS SEJA LOUVADO HOJE E SEMPRE!   -->
</html>
