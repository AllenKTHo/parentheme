<?php

$yr_url='FUNKY';

$yr_name='';
$yr_use_header=$yr_use_footer=true;
$yr_use_banner=false; 
$yr_use_text=false;   
$yr_use_links=false;  
$yr_use_table=true;
$yr_maxage=1800;
$yr_timeout=10;
$yr_datadir='white_cache';
$yr_link_target='_top';
$yr_vis_php_feilmeldinger=false;
if($yr_vis_php_feilmeldinger) {error_reporting(E_ALL);ini_set('display_errors', true);}
else {error_reporting(0);ini_set('display_errors', false);}
$yr_xmlparse = &new YRComms();
$yr_xmldisplay = &new YRDisplay();
$yr_try_curl=true;
die($yr_xmldisplay->generateHTMLCached($yr_url, $yr_name, $yr_xmlparse, $yr_url, $yr_try_curl, $yr_use_header, $yr_use_footer, $yr_use_banner, $yr_use_text, $yr_use_links, $yr_use_table, $yr_maxage, $yr_timeout, $yr_link_target));

function retar($array, $html = false, $level = 0) {
	if(is_array($array)){
		$space = $html ? "&nbsp;" : " ";
		$newline = $html ? "<br />" : "\n";
		$spaces='';
		for ($i = 1; $i <= 3; $i++)$spaces .= $space;
		$tabs=$spaces;
		for ($i = 1; $i <= $level; $i++)$tabs .= $spaces;
		$output = "Array(" . $newline . $newline;
		$cnt=sizeof($array);
		$j=0;
		foreach($array as $key => $value) {
			$j++;
			if (is_array($value)) {
				$level++;
				$value = retar($value, $html, $level);
				$level--;
			}
			else $value="'$value'";
			$output .=  "$tabs'$key'=> $value";
			if($j<$cnt)$output .=  ',';
			$output .=  $newline;
		}
		$output.=$tabs.')'.$newline;
	}
	else{
		$output="'$array'";
	}
	return $output;
}


class YRComms{

	private function getYrDataErrorMessage($msg="Feil"){
		return Array(
      '0'=> Array('tag'=> 'WEATHERDATA','type'=> 'open','level'=> '1'),
      '1'=> Array('tag'=> 'LOCATION','type'=> 'open','level'=> '2'),
      '2'=> Array('tag'=> 'NAME','type'=> 'complete','level'=> '3','value'=> $msg),
      '3'=> Array('tag'=> 'LOCATION','type'=> 'complete','level'=> '3'),
      '4'=> Array( 'tag'=> 'LOCATION', 'type'=> 'close', 'level'=> '2'),
      '5'=> Array( 'tag'=> 'FORECAST', 'type'=> 'open', 'level'=> '2'),
      '6'=> Array( 'tag'=> 'ERROR', 'type'=> 'complete', 'level'=> '3', 'value'=> $msg),
      '7'=> Array( 'tag'=> 'FORECAST', 'type'=> 'close', 'level'=> '2'),
      '8'=> Array( 'tag'=> 'WEATHERDATA', 'type'=> 'close', 'level'=> '1')
		);
	}
	private function getYrXMLErrorMessage($msg="Feil"){
		$msg=$this->getXMLEntities($msg);
		$data=<<<EOT
<weatherdata>
  <location />
  <forecast>
  <error>$msg</error>
    <text>
      <location />
    </text>
  </forecast>
</weatherdata>

EOT
		;
		return $data;
	}
	
	private function loadXMLData($xml_url,$try_curl=true,$timeout=10){
		global $yr_datadir;
		$xml_url.='/varsel.xml';
		$ctx = stream_context_create(array( 'http' => array('timeout' => $timeout)));
  		$data=file_get_contents($xml_url,0,$ctx);
		if(false!=$data){
		}
		else if($try_curl && function_exists('curl_init')){
			$lokal_xml_url = $yr_datadir .'/curl.temp.xml';
			$data='';
			$ch = curl_init($xml_url);
			$fp = fopen($lokal_xml_url, "w");
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_POSTFIELDS, '');
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			curl_exec($ch);
			curl_close($ch);
			fclose($fp);
			$data=file_get_contents($lokal_xml_url,0,$ctx);
			unlink($lokal_xml_url);
			if(false==$data)$data=$this->getYrXMLErrorMessage('Det oppstod en feil mens vÊrdata ble lest fra yr.no. Teknisk info: Mest antakelig: kobling feilet. Nest mest antakelig: Det mangler st¯tte for fopen wrapper, og cURL feilet ogsÂ. Minst antakelig: cURL har ikke rettigheter til Â lagre temp.xml');
		}
		else{
			$data=$this->getYrXMLErrorMessage('Det oppstod en feil mens vÊrdata ble fors¯kt lest fra yr.no. Teknisk info: Denne PHP-installasjon har verken URL enablede fopen_wrappers eller cURL. Dette gj¯r det umulig Â hente ned vÊrdata. Se imiddlertid f¯lgende dokumentasjon: http://no.php.net/manual/en/wrappers.php, http://no.php.net/manual/en/book.curl.php');
		}
		return $data;
	}

	private function parseXMLIntoStruct($data){
		global $yr_datadir;
		$parser = xml_parser_create('ISO-8859-1');
		if((0==$parser)||(FALSE==$parser))return $this->getYrDataErrorMessage('Det oppstod en feil mens vÊrdata ble fors¯kt hentet fra yr.no. Teknisk info: Kunne ikke lage XML parseren.');
		$vals = array();
		if(FALSE==xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1))return $this->getYrDataErrorMessage('Det oppstod en feil mens vÊrdata ble fors¯kt hentet fra yr.no. Teknisk info: Kunne ikke stille inn XML-parseren.');
		if(0==xml_parse_into_struct($parser, $data, $vals, $index))return $this->getYrDataErrorMessage('Det oppstod en feil mens vÊrdata ble fors¯kt hentet fra yr.no. Teknisk info: Parsing av XML feilet.');
		if(FALSE==xml_parser_free($parser))return $this->getYrDataErrorMessage('Det oppstod en feil mens vÊrdata ble fors¯kt hentet fra yr.no. Kunne ikke frigj¯re XML-parseren.');
		return $vals;
	}

	private function sanitizeString($in){
		if(is_array($in))return $in;
		if(null==$in)return null;
		return htmlentities(strip_tags($in));
	}

	public function reviveSafeTags($in){
		return str_ireplace(array('&lt;strong&gt;','&lt;/strong&gt;','&lt;u&gt;','&lt;/u&gt;','&lt;b&gt;','&lt;/b&gt;','&lt;i&gt;','&lt;/i&gt;'),array('<strong>','</strong>','<u>','</u>','<b>','</b>','<i>','</i>'),$in);
	}
	
	private function rearrangeChildren($vals, &$i) {
		$children = array();
		if (isset($vals[$i]['value']))$children['VALUE'] = $this->sanitizeString($vals[$i]['value']);
		while (++$i < count($vals)){
			if(isset($vals[$i]['value']))$val=$this->sanitizeString($vals[$i]['value']);
			else unset($val);
			if(isset($vals[$i]['type']))$typ=$this->sanitizeString($vals[$i]['type']);
			else unset($typ);
			if(isset($vals[$i]['attributes']))$atr=$this->sanitizeString($vals[$i]['attributes']);
			else unset($atr);
			if(isset($vals[$i]['tag']))$tag=$this->sanitizeString($vals[$i]['tag']);
			else unset($tag);
			switch ($vals[$i]['type']){
				case 'cdata': $children['VALUE']=(isset($children['VALUE']))?$val:$children['VALUE'].$val; break;
				case 'complete':
					if (isset($atr)) {
						$children[$tag][]['ATTRIBUTES'] = $atr;
						$index = count($children[$tag])-1;
						if (isset($val))$children[$tag][$index]['VALUE'] = $val;
						else $children[$tag][$index]['VALUE'] = '';
					} else {
						if (isset($val))$children[$tag][]['VALUE'] = $val;
						else $children[$tag][]['VALUE'] = '';
					}
					break;
				case 'open':
					if (isset($atr)) {
						$children[$tag][]['ATTRIBUTES'] = $atr;
						$index = count($children[$tag])-1;
						$children[$tag][$index] = array_merge($children[$tag][$index],$this->rearrangeChildren($vals, $i));
					} else $children[$tag][] = $this->rearrangeChildren($vals, $i);
					break;
				case 'close': return $children;
			}
		}
	}
	private function rearrangeDataStruct($vals){
		$tree = array();
		$i = 0;
		if (isset($vals[$i]['attributes'])) {
			$tree[$vals[$i]['tag']][]['ATTRIBUTES']=$vals[$i]['attributes'];
			$index=count($tree[$vals[$i]['tag']])-1;
			$tree[$vals[$i]['tag']][$index]=array_merge($tree[$vals[$i]['tag']][$index], $this->rearrangeChildren($vals, $i));
		} else $tree[$vals[$i]['tag']][] = $this->rearrangeChildren($vals, $i);
		if(isset($tree['WEATHERDATA'][0]['FORECAST'][0]))return $tree['WEATHERDATA'][0]['FORECAST'][0];
		else return YrComms::getYrDataErrorMessage('Det oppstod en feil ved behandling av data fra yr.no. Vennligst gj¯r administrator oppmerksom pÂ dette! Teknisk: data har feil format.');
	}

	public function getXMLTree($xml_url, $try_curl, $timeout){
		return $this->rearrangeDataStruct($this->parseXMLIntoStruct($this->loadXMLData($xml_url,$try_curl,$timeout)));
	}
	public function parseTime($yr_time, $do24_00=false){
		$yr_time=str_replace(":00:00", "", $yr_time);
		if($do24_00)$yr_time=str_replace("00", "24", $yr_time);
		return $yr_time;
	}
	public function convertEncodingEntities($yrraw){
		$conv=str_replace("�", "&aelig;", $yrraw);
		$conv=str_replace("�", "&oslash;", $conv);
		$conv=str_replace("�", "&aring;", $conv);
		$conv=str_replace("�", "&AElig;", $conv);
		$conv=str_replace("�", "&Oslash;", $conv);
		$conv=str_replace("�", "&Aring;", $conv);
		return $conv;
	}
	public function convertEncodingUTF($yrraw){
		$conv=str_replace("æ", "�", $yrraw);
		$conv=str_replace("ø", "�", $conv);
		$conv=str_replace("å", "�", $conv);
		$conv=str_replace("Æ", "�", $conv);
		$conv=str_replace("Ø", "�", $conv);
		$conv=str_replace("�", "�", $conv);
		return $conv;
	}

	public function getXMLEntities($string){
		return preg_replace('/[^\x09\x0A\x0D\x20-\x7F]/e', '$this->_privateXMLEntities("$0")', $string);
	}

	private function _privateXMLEntities($num){
		$chars = array(
		128 => '&#8364;', 130 => '&#8218;',
		131 => '&#402;', 132 => '&#8222;',
		133 => '&#8230;', 134 => '&#8224;',
		135 => '&#8225;',136 => '&#710;',
		137 => '&#8240;',138 => '&#352;',
		139 => '&#8249;',140 => '&#338;',
		142 => '&#381;', 145 => '&#8216;',
		146 => '&#8217;',147 => '&#8220;',
		148 => '&#8221;',149 => '&#8226;',
		150 => '&#8211;',151 => '&#8212;',
		152 => '&#732;',153 => '&#8482;',
		154 => '&#353;',155 => '&#8250;',
		156 => '&#339;',158 => '&#382;',
		159 => '&#376;');
		$num = ord($num);
		return (($num > 127 && $num < 160) ? $chars[$num] : "&#".$num.";" );
	}
}

class YRDisplay{

	var $ht='';
	var $yr_url='';
	var $yr_name='';
	var $yr_data=Array();
	var $datafile='yr.html';
	var $datapath='';
	var $yr_vindrettninger=array(
    '<img src="img_white/a.png" width="20px" height="38px">',
    '<img src="img_white/b.png" width="20px" height="38px">',
    '<img src="img_white/c.png" width="20px" height="38px">',
    '<img src="img_white/d.png" width="20px" height="38px">',
    '<img src="img_white/e.png" width="20px" height="38px">',
    '<img src="img_white/f.png" width="20px" height="38px">',
    '<img src="img_white/g.png" width="20px" height="38px">',
    '<img src="img_white/h.png" width="20px" height="38px">',
    '<img src="img_white/i.png" width="20px" height="38px">',
    '<img src="img_white/j.png" width="20px" height="38px">',
    '<img src="img_white/k.png" width="20px" height="38px">',
    '<img src="img_white/l.png" width="20px" height="38px">',
    '<img src="img_white/m.png" width="20px" height="38px">',
    '<img src="img_white/n.png" width="20px" height="38px">',
    '<img src="img_white/o.png" width="20px" height="38px">',
    '<img src="img_white/p.png" width="20px" height="38px">',
    '<img src="img_white/a.png" width="20px" height="38px">',
    );

	var $yr_imgpath='img_white';

	public function getHeader($use_full_html){
		if($use_full_html){
			$this->ht.=<<<EOT
<html>
<head>
    <title>() yr.no weather widget</title>
	<style>
		@font-face {
		    font-family: 'source_code_prosemibold';
		    src: url('sourcecodepro-semibold.svg#source_code_prosemibold') format('svg');
		    }
		body {
		    color: white;
		    width: 280px;
		    }
		body, table, tbody, tr, th, td {
		    font-family:'source_code_prosemibold';
		    font-size: 20px;
		    text-align: right;
		    margin: 0;padding: 0;-webkit-border-horizontal-spacing: 0px;-webkit-border-vertical-spacing: 0px;
		    }
		    
		small {font-size: 11px;}
		xs {font-size: 5px;}
		img {margin-top: -4px;}
		p {font-family: Helvetica;text-align: center;}
		table {margin: 0px auto 10px auto;}
		
		th {width:20px;}
		.image {width:53px;}
		.ned {width:49px;}
		.pluss {width:50px;}
		.v {width: 30px;}
		.ms {width:50px;}
		
		tr {display:none;height:32px;}
		tr:nth-child(-n+4) {display:block;}
		tr:nth-child(4) {height:38px;}
	</style>
</head>
<body>

EOT
			;
		}
		$this->ht.=<<<EOT
    <div id="yr-varsel">

EOT
		;
	}

	public function getFooter($use_full_html){
		$this->ht.=<<<EOT
    </div>

EOT
		;
		if($use_full_html){
			$this->ht.=<<<EOT
  </body>
</html>

EOT
			;
		}
	}

   public function getCopyright($target='_top'){
      $url=YRComms::convertEncodingEntities($this->yr_url);
      $this->ht.=<<<EOT
      <p style="display:none;"><a href="http://www.yr.no/" target="_top">V&aelig;rvarsel fra yr.no, levert av Meteorologisk institutt og NRK.</a></p>

EOT
      ;
   }

	public function getWeatherText(){
		if((isset($this->yr_data['TEXT'])) && (isset($this->yr_data['TEXT'][0]['LOCATION']))&& (isset($this->yr_data['TEXT'][0]['LOCATION'][0]['ATTRIBUTES'])) ){
			$yr_place=$this->yr_data['TEXT'][0]['LOCATION'][0]['ATTRIBUTES']['NAME'];
			if(!isset($this->yr_data['TEXT'][0]['LOCATION'][0]['TIME']))return;
			foreach($this->yr_data['TEXT'][0]['LOCATION'][0]['TIME'] as $yr_var2){
				$l=(YRComms::convertEncodingUTF($yr_var2['TITLE'][0]['VALUE']));
				$e=YRComms::reviveSafeTags(YRComms::convertEncodingUTF($yr_var2['BODY'][0]['VALUE']));
				$this->ht.=<<<EOT
      <p><strong>$yr_place $l</strong>:$e</p>

EOT
				;
			}
		}
	}

	public function getLinks($target='_top'){
		$url=YRComms::convertEncodingEntities($this->yr_url);
		$this->ht.=<<<EOT
      <p class="yr-lenker">$this->yr_name p&aring; yr.no:
        <a href="$url/" target="$target">Varsel med kart</a>
        <a href="$url/time_for_time.html" target="$target">Time for time</a>
        <a href="$url/helg.html" target="$target">Helg</a>
        <a href="$url/langtidsvarsel.html" target="$target">Langtidsvarsel</a>
      </p>

EOT
		;
	}

	public function getWeatherTableHeader(){
		$name=$this->yr_name;
		$this->ht.=<<<EOT
      <table>
        <tbody>
EOT
		;
	}

	public function getWeatherTableContent(){
		$thisdate='';
		$dayctr=0;
		if(!isset($this->yr_data['TABULAR'][0]['TIME']))return;
		$a=$this->yr_data['TABULAR'][0]['TIME'];

		foreach($a as $yr_var3){
			list($fromdate, $fromtime)=explode('T', $yr_var3['ATTRIBUTES']['FROM']);
			list($todate, $totime)=explode('T', $yr_var3['ATTRIBUTES']['TO']);
			$fromtime=YRComms::parseTime($fromtime);
			$totime=YRComms::parseTime($totime, 1);
			if($fromdate!=$thisdate){
				$divider=<<<EOT

EOT
				;
				list($thisyear, $thismonth, $thisdate)=explode('-', $fromdate);
				$displaydate=$thisdate.".".$thismonth.".".$thisyear;
				$firstcellcont=$displaydate;
				$thisdate=$fromdate;
				++$dayctr;
			}else $divider=$firstcellcont='';

// Show new date (don't edit)
			if($dayctr<7){
				$this->ht.=$divider;
				$imgno=$yr_var3['SYMBOL'][0]['ATTRIBUTES']['NUMBER'];
				if($imgno<10)$imgno='0'.$imgno;
				switch($imgno){
					case '01': case '02': case '03': case '05': case '06': case '07': case '08':
						if(($totime>=7) and ($totime<=19)) $imgno.="d";
						else $imgno.="n";
						$do_daynight=1; break;
					default: $do_daynight=0;
				}
				
// RAIN IN MM
				$rain=$yr_var3['PRECIPITATION'][0]['ATTRIBUTES']['VALUE'];
				if($rain==0.0)$rain="0";else{$rain=intval($rain);if($rain<1)$rain='&lsaquo;1';else $rain=round($rain);}
				$rain.='<small><xs> </xs>mm</small>';

// RAIN IN "
			//	$rain=$yr_var3['PRECIPITATION'][0]['ATTRIBUTES']['VALUE']*0.0393700787;
			//	if($rain==0)$rain="0";else{if($rain<0.1)$rain="&lsaquo;.1";else{$rain=round($rain, 1);$rain=ltrim($rain, '0');}}
			//	$rain.='<xs> </xs>"';
				
// TEMPERATURE CELCIUS
			 	$temper=round($yr_var3['TEMPERATURE'][0]['ATTRIBUTES']['VALUE']);
				if($temper>=0)$tempclass='pluss';else $tempclass='minus';$temper.='&deg;';

// TEMPERATURE FAHRENHEIT
			//	$temper=round($yr_var3['TEMPERATURE'][0]['ATTRIBUTES']['VALUE']*1.8+32);
			//	if($temper>=0)$tempclass='pluss';else $tempclass='minus';$temper.='&deg;';

// WINDRECTION (Don't edit)
				$winddir=round($yr_var3['WINDDIRECTION'][0]['ATTRIBUTES']['DEG']/22.5);
				$winddirtext=$this->yr_vindrettninger[$winddir];

// WINDSPEED M/S
				$r=round($yr_var3['WINDSPEED'][0]['ATTRIBUTES']['MPS']);$r.='<small><xs> </xs>m/s<small>';
// WINDSPEED MPH
			// 	$r=round($yr_var3['WINDSPEED'][0]['ATTRIBUTES']['MPS']*2.2369362920544);$r.='<small><xs> </xs>mph<small>';

				$s=$yr_var3['SYMBOL'][0]['ATTRIBUTES']['NAME'];
				$w=$yr_var3['WINDSPEED'][0]['ATTRIBUTES']['NAME'];
				$this->ht.=<<<EOT
          <tr>
            <th>$totime</th>
            <td class="image"><img src="$this->yr_imgpath/$imgno.png" width="38px" height="38px" alt="$s" /></td>
            <td class="ned">$rain</td>
            <td class="$tempclass">$temper</td>
            <td class="v">$winddirtext</td>
            <td class="ms">$r</td>
          </tr>

EOT
				;
			}
		}
	}

	public function getWeatherTableFooter($target='_top'){
		$url=YRComms::convertEncodingEntities($this->yr_url);
		$this->ht.=<<<EOT
        </tbody>
	</table>
	<p><a href="$url" target="_top">Weather forecast from yr.no, delivered by the Norwegian Meteorological Institute and the NRK</a></p>

EOT
		;
	}


	private function handleDataDir($clean_datadir=false,$summary=''){
		global $yr_datadir;
		$this->datapath=$yr_datadir .'/'. ($summary!='' ? (md5($summary).'['.$summary.']_') : '').$this->datafile;
		if ($clean_datadir) {
			unlink($this->datapath);
			rmdir($yr_datadir);
		}
		if(!is_dir($yr_datadir))mkdir($yr_datadir,0300);
	}


	public function generateHTMLCached($url,$name,$xml, $url, $try_curl, $useHtmlHeader=true, $useHtmlFooter=true, $useBanner=true, $useText=true, $useLinks=true, $useTable=true, $maxage=0, $timeout=10, $urlTarget='_top'){
		if(null==$name||''==trim($name))$name=array_pop(explode('/',$url));
		$this->handleDataDir(false,htmlentities("$name.$useHtmlHeader.$useHtmlFooter.$useBanner.$useText.$useLinks.$useTable.$maxage.$timeout.$urlTarget"));
		$yr_cached = $this->datapath;
		$name=YRComms::convertEncodingUTF($name);
		$name=YRComms::convertEncodingEntities($name);
		$url=YRComms::convertEncodingUTF($url);
		if(($maxage>0)&&((file_exists($yr_cached))&&((time()-filemtime($yr_cached))<$maxage))){
			$data['value']=file_get_contents($yr_cached);
			if(false==$data['value']){
	  	$data['value']='<p>Det oppstod en feil mens vÊrdata ble lest fra lokalt mellomlager. Vennligst gj¯r administrator oppmerksom pÂ dette! Teknisk: Sjekk at rettighetene er i orden som beskrevet i bruksanvisningen for dette scriptet</p>';
	  	$data['error'] = true;
	  }
		}
		else{
			$data=$this->generateHTML($url,$name,$xml->getXMLTree($url, $try_curl, $timeout),$useHtmlHeader,$useHtmlFooter,$useBanner,$useText,$useLinks,$useTable,$urlTarget);
			if($maxage>0 && !$data['error'] ){
				$f=fopen($yr_cached,"w");
				if(null!=$f){
					fwrite($f,$data['value']);
					fclose($f);
				}
			}
		}
		return $data['value'];
	}

	private function getErrorMessage(){
		if(isset($this->yr_data['ERROR'])){
			$error=$this->yr_data['ERROR'][0]['VALUE'];
			$this->ht.='<p style="color:red; background:black; font-weight:900px">' .$error.'</p>';
	  return true;
		}
		return false;
	}

	public function generateHTML($url,$name,$data,$useHtmlHeader=true,$useHtmlFooter=true,$useBanner=true,$useText=true,$useLinks=true,$useTable=true,$urlTarget='_top'){
		$this->ht='';
		$this->yr_url=$url;
		$this->yr_name=$name;
		$this->yr_data=$data;

		$this->getHeader($useHtmlHeader);
		$data['error'] = $this->getErrorMessage();
		if($useBanner)$this->getBanner($urlTarget);
		$this->getCopyright($urlTarget);
		if($useText)$this->getWeatherText();
		if($useLinks)$this->getLinks($urlTarget);
		if($useTable){
			$this->getWeatherTableHeader();
			$this->getWeatherTableContent();
			$this->getWeatherTableFooter($urlTarget);
		}
		$this->getFooter($useHtmlFooter);

		$data['value'] = $this->ht;
		return $data;
	}
}

?>eturner resultat
		$data['value'] = $this->ht;
		return $data;
	}
}

?>