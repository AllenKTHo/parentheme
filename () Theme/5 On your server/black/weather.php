<?php

$yr_url='http://www.yr.no/place/Denmark/Capital/Copenhagen';

$yr_name='Cph';

$yr_use_header=$yr_use_footer=true;

$yr_use_banner=false; //yr.no Banner
$yr_use_text=false;   //Tekstvarsel
$yr_use_links=false;  //Lenker til varsel p� yr.no
$yr_use_table=true;  //Tabellen med varselet

$yr_maxage=0;

$yr_timeout=10;

$yr_datadir='yr_cache';

$yr_link_target='_top';

$yr_vis_php_feilmeldinger=false;

if($yr_vis_php_feilmeldinger) {
	error_reporting(E_ALL);
	ini_set('display_errors', true);
}
else {
	error_reporting(0);
	ini_set('display_errors', false);
}

//Opprett en komunikasjon med yr
$yr_xmlparse = &new YRComms();
//Opprett en presentasjon
$yr_xmldisplay = &new YRDisplay();

$yr_try_curl=true;

//Gjenomf�r oppdraget basta bom.
die($yr_xmldisplay->generateHTMLCached($yr_url, $yr_name, $yr_xmlparse, $yr_url, $yr_try_curl, $yr_use_header, $yr_use_footer, $yr_use_banner, $yr_use_text, $yr_use_links, $yr_use_table, $yr_maxage, $yr_timeout, $yr_link_target));


///  ///  ///  ///  ///  ///  ///  ///  ///  ///  ///  ///  ///  ///  ///  /
///  ///  ///  ///  ///  Hjelpekode starter her   ///  ///  ///  ///  ///  //
//  ///  ///  ///  ///  ///  ///  ///  ///  ///  ///  ///  ///  ///  ///  ///


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


// Klasse for lesing og tilrettelegging av YR data
class YRComms{

	//Generer gyldig yr.no array med v�rdata byttet ut med en enkel feilmelding
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

	//Generer gyldig yr.no XML med v�rdata byttet ut med en enkel feilmelding
	private function getYrXMLErrorMessage($msg="Feil"){
		$msg=$this->getXMLEntities($msg);
		//die('errmsg:'.$msg);
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
		//die($data);
		return $data;
	}
	
	// S�rger for � laste ned XML fra yr.no og leverer data tilbake i en streng
	private function loadXMLData($xml_url,$try_curl=true,$timeout=10){
		global $yr_datadir;
		$xml_url.='/varsel.xml';
		// Lag en timeout p� contexten
		$ctx = stream_context_create(array( 'http' => array('timeout' => $timeout)));

		// Pr�v � �pne direkte f�rst
		//NOTE: This will spew ugly errors even when they are handled later. There is no way to avoid this but prefixing with @ (slow) or turning off error reporting
  		$data=file_get_contents($xml_url,0,$ctx);

		if(false!=$data){
			//Jippi vi klarte det med vanlig fopen url wrappers!
		}
		// Vanlig fopen_wrapper feilet, men vi har cURL tilgjengelig
		else if($try_curl && function_exists('curl_init')){
			$lokal_xml_url = $yr_datadir .'/curl.temp.xml';
			$data='';
			$ch = curl_init($xml_url);
			// �pne den lokale temp filen for skrive tilgang (med cURL hooks enablet)
			$fp = fopen($lokal_xml_url, "w");
			// Last fra yr.no til lokal kopi med curl
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_POSTFIELDS, '');
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			curl_exec($ch);
			curl_close($ch);
			// Lukk lokal kopi
			fclose($fp);
			// �pne lokal kopi igjen og les in alt innholdet
			$data=file_get_contents($lokal_xml_url,0,$ctx);
			//Slett temp data
			unlink($lokal_xml_url);
			// Sjekk for feil
			if(false==$data)$data=$this->getYrXMLErrorMessage('Det oppstod en feil mens v�rdata ble lest fra yr.no. Teknisk info: Mest antakelig: kobling feilet. Nest mest antakelig: Det mangler st�tte for fopen wrapper, og cURL feilet ogs�. Minst antakelig: cURL har ikke rettigheter til � lagre temp.xml');
		}
		// Vi har verken fopen_wrappers eller cURL
		else{
			$data=$this->getYrXMLErrorMessage('Det oppstod en feil mens v�rdata ble fors�kt lest fra yr.no. Teknisk info: Denne PHP-installasjon har verken URL enablede fopen_wrappers eller cURL. Dette gj�r det umulig � hente ned v�rdata. Se imiddlertid f�lgende dokumentasjon: http://no.php.net/manual/en/wrappers.php, http://no.php.net/manual/en/book.curl.php');
			//die('<pre>LO:'.retar($data));
		}
		//die('<pre>XML for:'.$xml_url.' WAS: '.$data);
		// N�r vi har kommet hit er det noe som tyder p� at vi har lykkes med � laste v�rdata, ller i det minste lage en teilmelding som beskriver eventuelle problemer
		return $data;
	}

	// Last XML til en array struktur
	private function parseXMLIntoStruct($data){
		global $yr_datadir;
		$parser = xml_parser_create('ISO-8859-1');
		if((0==$parser)||(FALSE==$parser))return $this->getYrDataErrorMessage('Det oppstod en feil mens v�rdata ble fors�kt hentet fra yr.no. Teknisk info: Kunne ikke lage XML parseren.');
		$vals = array();
		//die('<pre>'.retar($data).'</pre>');
		if(FALSE==xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1))return $this->getYrDataErrorMessage('Det oppstod en feil mens v�rdata ble fors�kt hentet fra yr.no. Teknisk info: Kunne ikke stille inn XML-parseren.');
		if(0==xml_parse_into_struct($parser, $data, $vals, $index))return $this->getYrDataErrorMessage('Det oppstod en feil mens v�rdata ble fors�kt hentet fra yr.no. Teknisk info: Parsing av XML feilet.');
		if(FALSE==xml_parser_free($parser))return $this->getYrDataErrorMessage('Det oppstod en feil mens v�rdata ble fors�kt hentet fra yr.no. Kunne ikke frigj�re XML-parseren.');
		//die('<pre>'.retar($vals).'</pre>');
		return $vals;
	}


	// Rense tekst data (av sikkerhetshensyn)
	private function sanitizeString($in){
		//return $in;
		if(is_array($in))return $in;
		if(null==$in)return null;
		return htmlentities(strip_tags($in));
	}

	// Rense tekst data (av sikkerhetshensyn)
	public function reviveSafeTags($in){
		//$in=$in.'<strong>STRONG</strong> <u>UNDERLINE</u> <b>BOLD</b> <i>ITALICS</i>';
		return str_ireplace(array('&lt;strong&gt;','&lt;/strong&gt;','&lt;u&gt;','&lt;/u&gt;','&lt;b&gt;','&lt;/b&gt;','&lt;i&gt;','&lt;/i&gt;'),array('<strong>','</strong>','<u>','</u>','<b>','</b>','<i>','</i>'),$in);
	}



	private function rearrangeChildren($vals, &$i) {
		$children = array(); // Contains node data
		// Sikkerhet: s�rg for at all data som parses strippes for farlige ting
		if (isset($vals[$i]['value']))$children['VALUE'] = $this->sanitizeString($vals[$i]['value']);
		while (++$i < count($vals)){
			// Sikkerhet: s�rg for at all data som parses strippes for farlige ting
			if(isset($vals[$i]['value']))$val=$this->sanitizeString($vals[$i]['value']);
			else unset($val);
			if(isset($vals[$i]['type']))$typ=$this->sanitizeString($vals[$i]['type']);
			else unset($typ);
			if(isset($vals[$i]['attributes']))$atr=$this->sanitizeString($vals[$i]['attributes']);
			else unset($atr);
			if(isset($vals[$i]['tag']))$tag=$this->sanitizeString($vals[$i]['tag']);
			else unset($tag);
			// Fyll inn strukturen v�r slik vi vil ha den
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
	// Omm�bler data til � passe v�rt form�l, og returner
	private function rearrangeDataStruct($vals){
		//die('<pre>'.$this->retar($vals).'<\pre>');
		$tree = array();
		$i = 0;
		if (isset($vals[$i]['attributes'])) {
			$tree[$vals[$i]['tag']][]['ATTRIBUTES']=$vals[$i]['attributes'];
			$index=count($tree[$vals[$i]['tag']])-1;
			$tree[$vals[$i]['tag']][$index]=array_merge($tree[$vals[$i]['tag']][$index], $this->rearrangeChildren($vals, $i));
		} else $tree[$vals[$i]['tag']][] = $this->rearrangeChildren($vals, $i);
		//die("<pre>".retar($tree));
		//Hent ut det vi bryr oss om
		if(isset($tree['WEATHERDATA'][0]['FORECAST'][0]))return $tree['WEATHERDATA'][0]['FORECAST'][0];
		else return YrComms::getYrDataErrorMessage('Det oppstod en feil ved behandling av data fra yr.no. Vennligst gj�r administrator oppmerksom p� dette! Teknisk: data har feil format.');
	}

	// Hovedmetode. Laster XML fra en yr.no URI og parser denne
	public function getXMLTree($xml_url, $try_curl, $timeout){
		// Last inn XML fil og parse til et array hierarcki, omm�bler data til � passe v�rt form�l, og returner
		return $this->rearrangeDataStruct($this->parseXMLIntoStruct($this->loadXMLData($xml_url,$try_curl,$timeout)));
	}

	// Statisk hjelper for � parse ut tid i yr format
	public function parseTime($yr_time, $do24_00=false){
		$yr_time=str_replace(":00:00", "", $yr_time);
		if($do24_00)$yr_time=str_replace("00", "24", $yr_time);
		return $yr_time;
	}

	// Statisk hjelper for � bes�rge riktig encoding ved � oversette spesielle ISO-8859-1 karakterer til HTML/XHTML entiteter
	public function convertEncodingEntities($yrraw){
		$conv=str_replace("�", "&aelig;", $yrraw);
		$conv=str_replace("�", "&oslash;", $conv);
		$conv=str_replace("�", "&aring;", $conv);
		$conv=str_replace("�", "&AElig;", $conv);
		$conv=str_replace("�", "&Oslash;", $conv);
		$conv=str_replace("�", "&Aring;", $conv);
		return $conv;
	}

	// Statisk hjelper for � bes�rge riktig encoding ved� oversette spesielle UTF karakterer til ISO-8859-1
	public function convertEncodingUTF($yrraw){
		$conv=str_replace("æ", "�", $yrraw);
		$conv=str_replace("ø", "�", $conv);
		$conv=str_replace("å", "�", $conv);
		$conv=str_replace("Æ", "�", $conv);
		$conv=str_replace("Ø", "�", $conv);
		$conv=str_replace("Å", "�", $conv);
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

// Klasse for � vise data fra yr. Kompatibel med YRComms sin datastruktur
class YRDisplay{

	// Akkumulator variabl for � holde p� generert HTML
	var $ht='';
	// Yr Url
	var $yr_url='';
	// Yr stedsnavn
	var $yr_name='';
	// Yr data
	var $yr_data=Array();

	//Filename for cached HTML. MD5 hash will be prepended to allow caching of several pages
	var $datafile='yr.html';
	//The complete path to the cache file
	var $datapath='';

	// Norsk grovinndeling av de 360 grader vindretning
	var $yr_vindrettninger=array(
    '<img src="img/a.png" width="20px" height="38px">',
    '<img src="img/b.png" width="20px" height="38px">',
    '<img src="img/c.png" width="20px" height="38px">',
    '<img src="img/d.png" width="20px" height="38px">',
    '<img src="img/e.png" width="20px" height="38px">',
    '<img src="img/f.png" width="20px" height="38px">',
    '<img src="img/g.png" width="20px" height="38px">',
    '<img src="img/h.png" width="20px" height="38px">',
    '<img src="img/i.png" width="20px" height="38px">',
    '<img src="img/j.png" width="20px" height="38px">',
    '<img src="img/k.png" width="20px" height="38px">',
    '<img src="img/l.png" width="20px" height="38px">',
    '<img src="img/m.png" width="20px" height="38px">',
    '<img src="img/n.png" width="20px" height="38px">',
    '<img src="img/o.png" width="20px" height="38px">',
    '<img src="img/p.png" width="20px" height="38px">',
    '<img src="img/a.png" width="20px" height="38px">',
    );

	// Hvor hentes bilder til symboler fra?
	var $yr_imgpath='img';


	//Generer header for varselet
	public function getHeader($use_full_html){
		// Her kan du endre header til hva du vil. NB! Husk � skru det p�, ved � endre instillingene i toppen av dokumentet
		if($use_full_html){
			$this->ht.=<<<EOT
<html>
<head>
    <title>Andreas yr.no</title>
	<style>
		@font-face {
		    font-family: 'source_code_prosemibold';
		    src: url('sourcecodepro-semibold.svg#source_code_prosemibold') format('svg');
		    }
		body {
		    color: black;
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
		a {color:black;text-decoration: none;}
		table {margin: 0px auto 10px auto;}
		
		th {width:20px;}
		.image {width:53px;}
		.ned {width:43px;}
		.pluss {width:56px;}
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

	//Generer footer for varselet
	public function getFooter($use_full_html){
		$this->ht.=<<<EOT
    </div>

EOT
		;
		// Her kan du endre footer til hva du vil. NB! Husk � skru det p�, ved � endre instillingene i toppen av dokumentet
		if($use_full_html){
			$this->ht.=<<<EOT
  </body>
</html>

EOT
			;
		}
	}




   //Generer Copyright for data fra yr.no
   public function getCopyright($target='_top'){
      $url=YRComms::convertEncodingEntities($this->yr_url);
      /*
       Du m� ta med teksten nedenfor og ha med lenke til yr.no.
       Om du fjerner denne teksten og lenkene, bryter du vilk�rene for bruk av data fra yr.no.
       Det er straffbart � bruke data fra yr.no i strid med vilk�rene.
       Du finner vilk�rene p� http://www.yr.no/verdata/1.3316805
       */
      $this->ht.=<<<EOT
      <p style="display:none;"><a href="http://www.yr.no/" target="_top">V&aelig;rvarsel fra yr.no, levert av Meteorologisk institutt og NRK.</a></p>

EOT
      ;
   }


   //Generer tekst for v�ret
	public function getWeatherText(){
		if((isset($this->yr_data['TEXT'])) && (isset($this->yr_data['TEXT'][0]['LOCATION']))&& (isset($this->yr_data['TEXT'][0]['LOCATION'][0]['ATTRIBUTES'])) ){
			$yr_place=$this->yr_data['TEXT'][0]['LOCATION'][0]['ATTRIBUTES']['NAME'];
			if(!isset($this->yr_data['TEXT'][0]['LOCATION'][0]['TIME']))return;
			foreach($this->yr_data['TEXT'][0]['LOCATION'][0]['TIME'] as $yr_var2){
				// Sm� bokstaver
				$l=(YRComms::convertEncodingUTF($yr_var2['TITLE'][0]['VALUE']));
				// Rettet encoding
				$e=YRComms::reviveSafeTags(YRComms::convertEncodingUTF($yr_var2['BODY'][0]['VALUE']));
				// Spytt ut!
				$this->ht.=<<<EOT
      <p><strong>$yr_place $l</strong>:$e</p>

EOT
				;
			}
		}
	}

	//Generer lenker til andre varsel
	public function getLinks($target='_top'){
		// Rens url
		$url=YRComms::convertEncodingEntities($this->yr_url);
		// Spytt ut
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

	//Generer header for v�rdatatabellen
	public function getWeatherTableHeader(){
		$name=$this->yr_name;
		$this->ht.=<<<EOT
      <table>
        <tbody>
EOT
		;
	}


	//Generer innholdet i v�rdatatabellen
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

			// Vis ny dato
			if($dayctr<7){
				$this->ht.=$divider;
				// Behandle symbol
				$imgno=$yr_var3['SYMBOL'][0]['ATTRIBUTES']['NUMBER'];
				if($imgno<10)$imgno='0'.$imgno;
				switch($imgno){
					case '01': case '02': case '03': case '05': case '06': case '07': case '08':
						if(($totime>=7) and ($totime<=19)) $imgno.="d";
						else $imgno.="n";
						$do_daynight=1; break;
					default: $do_daynight=0;
				}
				
				// Behandle regn
				$rain=$yr_var3['PRECIPITATION'][0]['ATTRIBUTES']['VALUE'];
				if($rain==0.0)$rain="0";
				else{
					$rain=intval($rain);
					if($rain<1)$rain='&lsaquo;1';
					else $rain=round($rain);
				}
				$rain.='<small><xs> </xs>mm</small>';
				
				// Behandle vind
				$winddir=round($yr_var3['WINDDIRECTION'][0]['ATTRIBUTES']['DEG']/22.5);
				$winddirtext=$this->yr_vindrettninger[$winddir];
				
				// Behandle temperatur
				$temper=round($yr_var3['TEMPERATURE'][0]['ATTRIBUTES']['VALUE']);
				if($temper>=0)$tempclass='pluss';
				else $tempclass='minus';

				// Rund av vindhastighet
				$r=round($yr_var3['WINDSPEED'][0]['ATTRIBUTES']['MPS']);
				// S� legger vi ut hele den ferdige linjen
				$s=$yr_var3['SYMBOL'][0]['ATTRIBUTES']['NAME'];
				$w=$yr_var3['WINDSPEED'][0]['ATTRIBUTES']['NAME'];

				$this->ht.=<<<EOT
          <tr>
            <th>$totime</th>
            <td class="image"><img src="$this->yr_imgpath/$imgno.png" width="38px" height="38px" alt="$s" /></td>
            <td class="ned">$rain</td>
            <td class="$tempclass">$temper&deg;</td>
            <td class="v">$winddirtext</td>
            <td class="ms">$r<small><xs> </xs>m/s<small></td>
          </tr>

EOT
				;
			}
		}
	}

	//Generer footer for v�rdatatabellen
	public function getWeatherTableFooter($target='_top'){
		$this->ht.=<<<EOT
        </tbody>
	</table>
	<p><a href="http://www.yr.no/" target="_top">V&aelig;rvarsel fra yr.no, levert av Meteorologisk institutt og NRK.</a></p>

EOT
		;
	}


	// Handle cache directory (re)creation and cachefile name selection
	private function handleDataDir($clean_datadir=false,$summary=''){
		global $yr_datadir;
		// The md5 sum is to avoid caching to the same file on parameter changes
		$this->datapath=$yr_datadir .'/'. ($summary!='' ? (md5($summary).'['.$summary.']_') : '').$this->datafile;
		// Delete cache dir
		if ($clean_datadir) {
			unlink($this->datapath);
			rmdir($yr_datadir);
		}
		// Create new cache folder with correct permissions
		if(!is_dir($yr_datadir))mkdir($yr_datadir,0300);
	}


	//Main with caching
	public function generateHTMLCached($url,$name,$xml, $url, $try_curl, $useHtmlHeader=true, $useHtmlFooter=true, $useBanner=true, $useText=true, $useLinks=true, $useTable=true, $maxage=0, $timeout=10, $urlTarget='_top'){
		//Default to the name in the url
		if(null==$name||''==trim($name))$name=array_pop(explode('/',$url));
		$this->handleDataDir(false,htmlentities("$name.$useHtmlHeader.$useHtmlFooter.$useBanner.$useText.$useLinks.$useTable.$maxage.$timeout.$urlTarget"));
		$yr_cached = $this->datapath;
		// Clean name
		$name=YRComms::convertEncodingUTF($name);
		$name=YRComms::convertEncodingEntities($name);
		// Clean URL
		$url=YRComms::convertEncodingUTF($url);
		// Er mellomlagring enablet, og trenger vi egentlig laste ny data, eller holder mellomlagret data?
		if(($maxage>0)&&((file_exists($yr_cached))&&((time()-filemtime($yr_cached))<$maxage))){
			$data['value']=file_get_contents($yr_cached);
			// Sjekk for feil
			if(false==$data['value']){
	  	$data['value']='<p>Det oppstod en feil mens v�rdata ble lest fra lokalt mellomlager. Vennligst gj�r administrator oppmerksom p� dette! Teknisk: Sjekk at rettighetene er i orden som beskrevet i bruksanvisningen for dette scriptet</p>';
	  	$data['error'] = true;
	  }
		}
		// Vi kj�rer live, og saver samtidig en versjon til mellomlager
		else{
			$data=$this->generateHTML($url,$name,$xml->getXMLTree($url, $try_curl, $timeout),$useHtmlHeader,$useHtmlFooter,$useBanner,$useText,$useLinks,$useTable,$urlTarget);
			// Lagre til mellomlager
			if($maxage>0 && !$data['error'] ){
				$f=fopen($yr_cached,"w");
				if(null!=$f){
					fwrite($f,$data['value']);
					fclose($f);
				}
			}
		}
		// Returner resultat
		return $data['value'];
	}

	private function getErrorMessage(){
		if(isset($this->yr_data['ERROR'])){
			$error=$this->yr_data['ERROR'][0]['VALUE'];
			//die(retar($error));
			$this->ht.='<p style="color:red; background:black; font-weight:900px">' .$error.'</p>';
	  return true;
		}
		return false;
	}

	//Main
	public function generateHTML($url,$name,$data,$useHtmlHeader=true,$useHtmlFooter=true,$useBanner=true,$useText=true,$useLinks=true,$useTable=true,$urlTarget='_top'){
		// Fyll inn data fra parametrene
		$this->ht='';
		$this->yr_url=$url;
		$this->yr_name=$name;
		$this->yr_data=$data;

		// Generer HTML i $ht
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

		// Returner resultat
		//return YRComms::convertEncodingEntities($this->ht);
		$data['value'] = $this->ht;
		return $data;
	}
}

?>eturner resultat
		//return YRComms::convertEncodingEntities($this->ht);
		$data['value'] = $this->ht;
		return $data;
	}
}

?>