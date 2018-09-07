<?php
define('FPDF_FONTPATH','/var/www/html/fpdf/font');
require('fpdf/fpdf.php');

function zabbix_login(){
// get cURL resource
    $ch = curl_init();
$cookie_file = '/var/tmp/zbxcookie.txt';

if (! file_exists($cookie_file) || ! is_writable($cookie_file)){
    echo 'Cookie file missing or not writable.';
    exit;
}
    curl_setopt($ch, CURLOPT_URL, 'https://zabbix.tmpdomain.ru/index.php?login=1');
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
      ]);

    // form body
    $body = [
        'name' => 'api',
        'password' => 'Tr8PeotBvA6g',
        'enter' => 'Sign in'
      ];
      $body = http_build_query($body);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
      

    $response = curl_exec($ch);
    
    if(curl_error($ch)) 
         { 
          echo "\n\ncURL error:" . curl_error($ch); 
          echo "\n\ncURL error:" . curl_errno($ch); 
         } 

    curl_close($ch);
}


function zabbix_chart3($title, $date1, ...$items){

// get cURL resource
$ch = curl_init();
$color[0]="00BB00";
$color[1]="BB0000";
$color[2]="0000BB";
$color[3]="BBBB00";
$url_items="";
#items%5B0%5D%5Bitemid%5D=$item_id&items%5B0%5D%5Bsortorder%5D=0&items%5B0%5D%5Bdrawtype%5D=5&items%5B0%5D%5Bcolor%5D=%2200BB00%22&
foreach ($items as $k=>$v){
    $url_items.="&items[$k][itemid]=$v&items[$k][color]=$color[$k]&items[$k][drawtype]=5";
}
//$url_items=urlencode($url_items);
$ttl=urlencode($title);
$url = "https://zabbix.tmpdomain.ru/chart3.php?period=86400&name=$ttl&width=800&height=300&graphtype=0&legend=1&stime=$date1$url_items";
#echo $url."<br>";
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/tmp/zbxcookie.txt');
curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);

$response = curl_exec($ch);
#print_r(curl_getinfo($ch));
curl_close($ch);
#header ("Content-Type: image/png");
return $response;

}

function file_save($fname, $stream) {
    $fp = fopen('/tmp/'.$fname,'w');
    fwrite($fp,$stream);
    fclose($fp);
}


zabbix_login();

$d1 = date("U",strtotime('yesterday midnight'));
$chart1=zabbix_chart3("UDP Packets", $d1, 24782);
$c1 = uniqid(true).".png";
file_save($c1,$chart1);
$chart2=zabbix_chart3("Attached Telemetry", $d1, 24740);
$c2 = uniqid(true).".png";
file_save($c2,$chart2);
$chart3=zabbix_chart3("Загрузка CPU серверной части МП", $d1, 24858);
$c3 = uniqid(true).".png";
file_save($c3,$chart3);
$chart4=zabbix_chart3("Загрузка сетевого интерфейса", $d1, 24879, 24880);
$c4 = uniqid(true).".png";
file_save($c4,$chart4);

class PDF extends FPDF
{
// Page header
function Header()
{
    // Logo
    #$this->Image('logo.png',10,6,30);
    // Arial bold 15
    $this->AddFont('Verdana','','verdana.php');
    
    $this->SetFont('Verdana','',15);
    // Move to the right
    $this->Cell(65);
    
    $d1 = date("d.m.Y", strtotime('yesterday midnight'));// Title
    $str = iconv('UTF-8', 'windows-1251', "Отчет МП $d1");
    $this->Cell(70,10,$str,1,0,'C');
    // Line break
    $this->Ln(20);
}

// Page footer
function Footer()
{
    // Position at 1.5 cm from bottom
    $this->SetY(-15);
    // Arial italic 8
    $this->SetFont('Verdana','',8);
    // Page number
    $str = iconv('UTF-8', 'windows-1251', 'Страница ');

    $this->Cell(0,10,$str.$this->PageNo(),0,0,'C');
}
}

$pdf = new PDF();
$pdf->SetDisplayMode('fullpage','single');
$pdf->SetAutoPageBreak(true);
$pdf->AddFont('Verdana','','verdana.php');
$pdf->AddPage();
$pdf->SetFont('Verdana','',10);

$d2 = date("H:i d.m.Y",strtotime('yesterday midnight'));
$d3 = date("H:i d.m.Y",strtotime('today midnight'));
$str = iconv('UTF-8', 'windows-1251', 'Период '.$d2.' — '.$d3);
$pdf->Cell(40,10,$str);
$pdf->Ln(20);
$pdf->SetFont('Verdana','',12);
$str = iconv('UTF-8', 'windows-1251', '1. UDP-пакеты от «насадки» до сервера МП');
$pdf->Cell(40,10,$str);
$pdf->Ln(10);
$pdf->Image("/tmp/$c1", null, null, -120);

$str = iconv('UTF-8', 'windows-1251', '2. «Привязанные» отметки телематики');
$pdf->Cell(40,10,$str);
$pdf->Ln(10);
$pdf->Image("/tmp/$c2", null, null, -120);

$pdf->AddPage();
$str = iconv('UTF-8', 'windows-1251', '3. Загрузка CPU серверной части МП');
$pdf->Cell(40,10,$str);
$pdf->Ln(10);
$pdf->Image("/tmp/$c3", null, null, -120);

$str = iconv('UTF-8', 'windows-1251', '4. Загрузка сетевого интерфейса');
$pdf->Cell(40,10,$str);
$pdf->Ln(10);
$pdf->Image("/tmp/$c4", null, null, -120);
$d1 = 'report_'.date("d.m.Y",strtotime('today')).".pdf";
$pdf->Output('D', $d1);
unlink("/tmp/$c1");
unlink("/tmp/$c2");
unlink("/tmp/$c3");
unlink("/tmp/$c4");
?>
