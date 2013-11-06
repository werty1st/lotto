#!/usr/bin/php -q
<?php
	error_reporting(E_ERROR | E_PARSE);

  

	$options = getopt("d::t::i::");
	
	$d = array_key_exists("d",$options); //debug //meldungen
	$t = array_key_exists("t",$options); //test  //hole immer neue zahlen
	$i = array_key_exists("i",$options); //int   //int-redsys

	
	//debug level
	switch (strlen($options["d"])){
	    case 3: $dddd = true;
	    case 2: $ddd = true;
	    case 1: $dd = true;
	}
	
	$d = true;

//begin

ob_start();

	if ($d)echo "Verarbeite Lotto.\n";
	UpdateLotto();
	if ($d)echo "Verarbeite Lotto fertig.\n";

	if ($d)echo "Verarbeite GluecksSpirale.\n";
	UpdateGluecksSpirale();
	if ($d)echo "Verarbeite GluecksSpirale fertig.\n";

$myStr = ob_get_contents();
ob_end_clean();


if (strpos($myStr," neuere ") || strpos($myStr,"keine alten")) {
    if ($d)echo "Debug1: sende mail\n";
    $header = 'Content-type: text; charset=utf-8';
    mail ( "adams.r@zdf.de" , "Lotto Update" , $myStr, $header );
	echo $myStr;    
} else {
	if ($d)echo "Debug1: keine mail\n";
	echo $myStr;
}


function UpdateLotto()
{
	global $d,$l,$i;

	// if (!$d && !$i && !$t) {
		//echo "Debug1: Path = live\n";
		$basedir   = "/srv/www/redsysdyn.zdf.de/";		
		$categoryID = "/73/74";
		$nodeID     = "/73/74/132112";
		
		//target dir
		$importdir = $basedir."site/import/";
		$lottoxml  = $importdir."redlotto.xml";
		//working dir
		$spooldir  = $basedir."import/lotto/";
		$crcfile   = $spooldir."lotto.json";	
	// } else {
	// 	echo "Debug1: Path = int\n";
	// 	$basedir    = "/srv/www/int-redsysdyn.zdf.de/";
	// 	$categoryID = "/73/74";
	// 	$nodeID     = "/73/74/743";

	// 	//target dir
	// 	$importdir = $basedir."site/import/";
	// 	$lottoxml  = $importdir."redlotto.xml";
	// 	//working dir
	// 	$spooldir  = $basedir."import/lotto/";
	// 	$crcfile   = $spooldir."lotto.json";
	// } 


	include_once $basedir."/libraries/class.imperia.inc.php";
	$clsimperia = new imperia($basedir);

	if ($d)echo "Debug1: gefundene Node ID: $nodeID\n";

	$impdoc = $clsimperia->getDocument($nodeID);

	if ($dd)echo "Debug2: Inhalt ImperiaDoc\n";
	if ($dd) var_dump($impdoc);	


	try {
		if ($d)echo "Debug1: hole aktuelle lottozahlen\n";

		$neuelottozahlen = new lotto649($nodeID);
		$neuelottozahlen->loadnew($impdoc);

	} catch (Exception $e)
	{
		//keine neuen zahlen gefunden
		throw new Exception("Der Script konnte keine aktuellen Lottozahlen abrufen.\n".
							"Inner Exception: ".$e->getMessage()."\n",1);
	}

	try {
		if ($d)echo "Debug1: lade alte lottozahlen\n";
		
		$altelottozahlen = new lotto649($nodeID);
		$isvalid = $altelottozahlen->loadjson(file_get_contents($crcfile));

		//erzeuge einen fehler wenn keine alten zahlen gefunden wurden
		if (!$isvalid)
			throw new Exception("Es sind keine alten Zahlen vorhanden.", 1);

	} catch (Exception $e)
	{
		//keine alten zahlen gefunden
		if ($d)echo "Debug1: ".$e->getMessage()."\n";

		//schreibe neuen zahlen
		if ($d)echo "Debug1: schreibe zahlen\n";

		file_put_contents($crcfile, $neuelottozahlen->toJSON());

		//generate import xml
		$neuelottozahlen->saveXML($lottoxml);		
		return;
	}
	//alte zahlen sind vorhanden

	//alte mit neuen verlgeichen
	//TODO wenn keinen neuen zahlen da sind aber das dokument in imperia gelöscht wurde soll der script weiter laufen

	if( $neuelottozahlen->getHash() == $altelottozahlen->getHash() && (!$dd))
	{
		if ($d)echo "Debug1: es sind keine neueren zahlen vorhanden\n";

	} else {
		if ($d)echo "Debug1: es sind neuere zahlen vorhanden\n";	

		try {
			$neuelottozahlen->savexml($lottoxml);
			file_put_contents($crcfile, $neuelottozahlen->toJSON());

			if ($d)echo "Debug1: es wurde eine neue xml geschrieben\n";

		} catch (Exception $e) {
			throw new Exception("Es konnte keine $lottoxml erstellt werden: ".$e->getMessage()."\n", 1);		
		}
	}

}

function UpdateGluecksSpirale()
{
	global $d,$l,$i;

	// if (!$d && !$i && !$t) {
		$basedir   = "/srv/www/redsysdyn.zdf.de/";		
		$categoryID = "/73/132035";
		$nodeID     = "/73/132035/132106";

		//target dir
		$importdir = $basedir."site/import/";
		$lottoxml  = $importdir."redlottoGS.xml";
		//working dir
		$spooldir  = $basedir."import/lotto/";
		$crcfile   = $spooldir."lottoGS.json";		
	// } else {
	// 	echo "Debug1: Path = int\n";
	// 	$basedir   = "/srv/www/int-redsysdyn.zdf.de/";
	// 	$categoryID = "/73/696";
	// 	$nodeID     = "/73/696/748";		

	// 	//target dir
	// 	$importdir = $basedir."site/import/";
	// 	$lottoxml  = $importdir."redlottoGS.xml";
	// 	//working dir
	// 	$spooldir  = $basedir."import/lotto/";
	// 	$crcfile   = $spooldir."lottoGS.json";
	// } 

	include_once $basedir."/libraries/class.imperia.inc.php";
	$clsimperia = new imperia($basedir);

	if ($d)echo "Debug1: gefundene Node ID: $nodeID\n";

	$impdoc = $clsimperia->getDocument($nodeID);

	if ($dd)echo "Debug2: Inhalt ImperiaDoc\n";
	if ($dd) var_dump($impdoc);


	try {
		if ($d)echo "Debug1: hole aktuelle GlücksSpirale\n";

		$neuelottozahlen = new gluecksspirale($nodeID);
		$neuelottozahlen->loadnew($impdoc);

	} catch (Exception $e)
	{
		//keine neuen zahlen gefunden
		throw new Exception("Der Script konnte keine aktuellen GlücksSpirale abrufen.\n".
							"Inner Exception: ".$e->getMessage()."\n",1);
	}

	try {
		if ($d)echo "Debug1: lade alte GlücksSpirale\n";
		
		$altelottozahlen = new gluecksspirale($nodeID);
		$isvalid = $altelottozahlen->loadjson(file_get_contents($crcfile));

		//erzeuge einen fehler wenn keine alten zahlen gefunden wurden
		if (!$isvalid)
			throw new Exception("Es sind keine alten Zahlen vorhanden.", 1);

	} catch (Exception $e)
	{
		//keine alten zahlen gefunden
		if ($d)echo "Debug1: ".$e->getMessage()."\n";
		
		//schreibe neuen zahlen
		if ($d)echo "Debug1: schreibe zahlen\n";

		file_put_contents($crcfile, $neuelottozahlen->toJSON());

		//generate import xml
		$neuelottozahlen->saveXML($lottoxml);
		return;
	}
	//alte zahlen sind vorhanden

	//alte mit neuen verlgeichen
	if( $neuelottozahlen->getHash() == $altelottozahlen->getHash() && (!$dd))
	{
		if ($d)echo "Debug1: es sind keine neueren zahlen vorhanden\n";

	} else {
		if ($d)echo "Debug1: es sind neuere zahlen vorhanden\n";

		try {
			$neuelottozahlen->savexml($lottoxml);
			file_put_contents($crcfile, $neuelottozahlen->toJSON());
			
			if ($d)echo "Debug1: es wurde eine neue xml geschrieben\n";

		} catch (Exception $e) {
			throw new Exception("Es konnte keine $lottoxml erstellt werden: ".$e->getMessage()."\n", 1);		
		}
	}
}


exit;	
//end

class ziehung 
{
	public $datum;
	// public $zahlenGS;
	// public $quotenGS;
}

class gewinnspiel
{
	public $hash;
	public $samstag;
	public $mittwoch;
	
	protected $ziehung;
	protected $day;
	protected $date;	
	protected $nodeID;

	protected $imperiaDoc;


   function __construct($nodeID) {
       //print "compare to Imperia Doc\n";
   		$this->nodeID = $nodeID;
   }

	protected function saveXML($filename, $xml){
		global $d;

		if (!$this->ImperiaSkipUpdate()){
			file_put_contents($filename, $xml);
			if($d)echo "Debug1: Die Import XML wurde geschrieben\n";
		} else {
			if($d)echo "Debug1: Der Autoimport wurde abgebrochen\n";
		}
	}

	//lädt alte zahlen aus einer datei
	protected function getZahlen($day)
	{
		throw new Exception("Virtual. Must be implemented in Subclass", 1);
	}

	//lädt neue zahlen vom server und vrelgeicht mit imperia
	public function loadnew($imperiaDoc)
	{
		throw new Exception("Virtual. Must be implemented in Subclass", 1);
	}

	public function loadjson($json)
	{
		throw new Exception("Virtual. Must be implemented in Subclass", 1);
	}

	protected function berechne_gesamtausschuettung()
	{
		throw new Exception("Virtual. Must be implemented in Subclass", 1);
	}


	protected function ImperiaSkipUpdate() {
		global $d,$dd;

		//wenn no import button gesetzt
		//wenn ImperiaDoc.datum > ziehung.datum

		$importDatum = $this->date; //->format('d.m.Y');
		$imperiaDatum = DateTime::createFromFormat('d.m.Y', $this->imperiaDoc["lottodatum"]); //03.08.2013


		if ($imperiaDatum > $importDatum){
			if($d)echo "Debug1: Der Autoimport ist älter als Imperia.\n";
			return true;	
		} 
		if ($this->imperiaDoc["disableautoimport"] == "on"){
			if($d)echo "Debug1: Der Autoimport manuell deaktiviert.\n";
			return true;
		} 
		if($d)echo "Debug1: Der Autoimport wird nicht übersprungen.\n";

		return false;
	}


	protected function CurlPost($sURL, &$zahlenarray)
	{
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	    curl_setopt($ch, CURLOPT_URL, $sURL);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_PROXY, "");   
		curl_setopt($ch, CURLOPT_USERAGENT, "curl (ZDF Mainz) Webmaster HR Neue Medien/1.0");	    
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);

	    $sResult = curl_exec($ch);
	    if (curl_errno($ch)) 
	    {
	        // Fehlerausgabe
	        echo curl_error($ch);
	        return false;
	    } else 
	    {
	        // Kein Fehler, Ergebnis zurückliefern:
	        curl_close($ch);
       		$httpresult = preg_replace('/\s\s+/', '&', $sResult);
			parse_str($httpresult,$zahlenarray);
	        return true;
	    }    
	}

	protected function removeUnusedElements($zahlenarray)
	{
		unset($zahlenarray["VERS"]);
		unset($zahlenarray["VA"]);
		unset($zahlenarray["SPIEL"]);
		unset($zahlenarray["ART"]);
		unset($zahlenarray["ABS"]);
		unset($zahlenarray["REV"]);
		unset($zahlenarray["EDATUM"]);
		unset($zahlenarray["DATUM"]);
		unset($zahlenarray["E1"]);
		unset($zahlenarray["E2"]);

		return $zahlenarray;
	}


	public function toJSON()
	{
		$this->hash = $this->getHash();

		if (strnatcmp(phpversion(),'5.4.0') >= 0)
		{
			//'5.4 or higher';
			$myjson = json_encode($this, JSON_PRETTY_PRINT);
		}
		else
		{
			//'5.4 or lower';
			$myjson = json_indent(json_encode($this));
		}
		
		return $myjson;
	}

	public function getHash()
	{
		$this->hash = "";
		return sha1(json_encode($this));
	}

	protected function validate()
	{
		$storedhash = $this->hash;		
		$computedhash = $this->getHash();
		// echo "storedhash: $storedhash\n";
		// echo "computedhash: $computedhash\n";
		return ($storedhash == $computedhash);
	}

}

class lotto649 extends gewinnspiel
{

	public function loadnew($imperiaDoc)
	{
		global $d;
		$this->imperiaDoc = $imperiaDoc;

		if ($d) echo "Debug1: lade Mittwochszahlen\n";
		$this->mittwoch = $this->getZahlen("mi");
		$datemittwoch = DateTime::createFromFormat('Ymd', $this->mittwoch->datum);


		if ($d) echo "Debug1: lade Samstagsszahlen\n";
		$this->samstag = $this->getZahlen("sa");				
		$datesamstag  = DateTime::createFromFormat('Ymd', $this->samstag->datum);


		if ($datemittwoch > $datesamstag)
		{
			//mittwoch ist aktuell
			$this->day = "Mittwoch";
			$this->date = $datemittwoch;
			$this->ziehung = $this->mittwoch;
		} else {
			//samstag ist aktuell
			$this->day = "Samstag";
			$this->date = $datesamstag;
			$this->ziehung = $this->samstag;
		}

	}

	public function loadjson($json)	{
		$tempobj = json_decode($json,true);
		$this->mittwoch = $tempobj["mittwoch"];
		$this->samstag  = $tempobj["samstag"];
		$this->hash     = $tempobj["hash"];

		return $this->validate();
	}	

	public function saveXML($filename){

		$ausschuettung = $this->berechne_gesamtausschuettung($this->ziehung);


		// var_dump($ausschuettung);
		// var_dump($this->day);

		// exit;

		$xml = "<?xml version=\"1.0\" encoding=\"utf-8\" standalone=\"yes\"?>"."\n";
		$xml .= "<IMPERIA_CONTENT>"."\n";
		$xml .= " <autoimport>1</autoimport>"."\n";
		$xml .= " <__imperia_imported>1</__imperia_imported>"."\n";
		$xml .= " <__imperia_uid>56</__imperia_uid>"."\n";
		$xml .= " <__imperia_last_uid>56</__imperia_last_uid>"."\n";
		$xml .= " <do_edit>0</do_edit>"."\n";
		$xml .= " <__imperia_clobber_by_id>".$this->nodeID."</__imperia_clobber_by_id>"."\n";
		$xml .= " <publish_date>2012-01-01 00:00</publish_date>"."\n";
		$xml .= " <skip_approval>1</skip_approval>"."\n";
		$xml .= " <skip_edit>1</skip_edit>"."\n";
		$xml .= " <skip_metaedit>1</skip_metaedit>"."\n";
		$xml .= " <skip_docselector>1</skip_docselector>"."\n";
		$xml .= " <title>Lotto Modul</title>"."\n";		
		$xml .= " <template>zdfde_lotto</template>"."\n";
		$xml .= " <filename>index.html</filename>"."\n";
		$xml .= " <directory></directory>"."\n";
		$xml .= " <__imperia_node_id>".$this->nodeID."</__imperia_node_id>"."\n";
		$xml .= " <copy>/ZDFde/lotto/lotto.xml:TEMPLATE=zdfde_lotto_xml</copy>"."\n";
		$xml .= " <lottodatum>".$this->date->format('d.m.Y')."</lottodatum>"."\n";
		$xml .= " <lottowoche>".$this->date->format('W')."</lottowoche>"."\n";
		$xml .= " <lottotag>".$this->day."</lottotag>"."\n";
		$xml .= " <lotto1>".$this->ziehung->zahlen6a49["0"]."</lotto1>"."\n";
		$xml .= " <lotto2>".$this->ziehung->zahlen6a49["1"]."</lotto2>"."\n";
		$xml .= " <lotto3>".$this->ziehung->zahlen6a49["2"]."</lotto3>"."\n";
		$xml .= " <lotto4>".$this->ziehung->zahlen6a49["3"]."</lotto4>"."\n";
		$xml .= " <lotto5>".$this->ziehung->zahlen6a49["4"]."</lotto5>"."\n";
		$xml .= " <lotto6>".$this->ziehung->zahlen6a49["5"]."</lotto6>"."\n";
		$xml .= " <superzahl>".$this->ziehung->zahlen6a49["S"]."</superzahl>"."\n";
		$xml .= " <spiel77_1>".$this->ziehung->zahlens77["GEZ"][0]."</spiel77_1>"."\n";
		$xml .= " <spiel77_2>".$this->ziehung->zahlens77["GEZ"][1]."</spiel77_2>"."\n";
		$xml .= " <spiel77_3>".$this->ziehung->zahlens77["GEZ"][2]."</spiel77_3>"."\n";
		$xml .= " <spiel77_4>".$this->ziehung->zahlens77["GEZ"][3]."</spiel77_4>"."\n";
		$xml .= " <spiel77_5>".$this->ziehung->zahlens77["GEZ"][4]."</spiel77_5>"."\n";
		$xml .= " <spiel77_6>".$this->ziehung->zahlens77["GEZ"][5]."</spiel77_6>"."\n";
		$xml .= " <spiel77_7>".$this->ziehung->zahlens77["GEZ"][6]."</spiel77_7>"."\n";
		$xml .= " <super6_1>".$this->ziehung->zahlenS6["GEZ"][0]."</super6_1>"."\n";
		$xml .= " <super6_2>".$this->ziehung->zahlenS6["GEZ"][1]."</super6_2>"."\n";
		$xml .= " <super6_3>".$this->ziehung->zahlenS6["GEZ"][2]."</super6_3>"."\n";
		$xml .= " <super6_4>".$this->ziehung->zahlenS6["GEZ"][3]."</super6_4>"."\n";
		$xml .= " <super6_5>".$this->ziehung->zahlenS6["GEZ"][4]."</super6_5>"."\n";
		$xml .= " <super6_6>".$this->ziehung->zahlenS6["GEZ"][5]."</super6_6>"."\n";
		$xml .= " <6RichtigeSZ>".$this->ziehung->quoten6a49["1"]."</6RichtigeSZ>"."\n";
		$xml .= " <6RichtigeSZAnzahl>".$this->ziehung->gewinner6a49["GES-1"]."</6RichtigeSZAnzahl>"."\n";
		$xml .= " <6RichtigeSZJackpot>".$this->ziehung->quoten6a49["J1"]."</6RichtigeSZJackpot>"."\n";
		$xml .= " <6RichtigeSZJackpotBool>".($this->ziehung->quoten6a49["1"] == "0"?"true":"false")."</6RichtigeSZJackpotBool>"."\n";
		$xml .= " <6Richtige>".$this->ziehung->quoten6a49["2"]."</6Richtige>"."\n";
		$xml .= " <6RichtigeAnzahl>".$this->ziehung->gewinner6a49["GES-2"]."</6RichtigeAnzahl>"."\n";
		$xml .= " <6RichtigeJackpot>".$this->ziehung->quoten6a49["J2"]."</6RichtigeJackpot>"."\n";
		$xml .= " <6RichtigeJackpotBool>".($this->ziehung->quoten6a49["2"] == "0"?"true":"false")."</6RichtigeJackpotBool>"."\n";
		$xml .= " <5RichtigeSZ>".$this->ziehung->quoten6a49["3"]."</5RichtigeSZ>"."\n";
		$xml .= " <5RichtigeSZAnzahl>".$this->ziehung->gewinner6a49["GES-3"]."</5RichtigeSZAnzahl>"."\n";
		$xml .= " <5Richtige>".$this->ziehung->quoten6a49["4"]."</5Richtige>"."\n";
		$xml .= " <5RichtigeAnzahl>".$this->ziehung->gewinner6a49["GES-4"]."</5RichtigeAnzahl>"."\n";
		$xml .= " <4RichtigeSZ>".$this->ziehung->quoten6a49["5"]."</4RichtigeSZ>"."\n";
		$xml .= " <4RichtigeSZAnzahl>".$this->ziehung->gewinner6a49["GES-5"]."</4RichtigeSZAnzahl>"."\n";
		$xml .= " <4Richtige>".$this->ziehung->quoten6a49["6"]."</4Richtige>"."\n";
		$xml .= " <4RichtigeAnzahl>".$this->ziehung->gewinner6a49["GES-6"]."</4RichtigeAnzahl>"."\n";
		$xml .= " <3RichtigeSZ>".$this->ziehung->quoten6a49["7"]."</3RichtigeSZ>"."\n";
		$xml .= " <3RichtigeSZAnzahl>".$this->ziehung->gewinner6a49["GES-7"]."</3RichtigeSZAnzahl>"."\n";
		$xml .= " <3Richtige>".$this->ziehung->quoten6a49["8"]."</3Richtige>"."\n";
		$xml .= " <3RichtigeAnzahl>".$this->ziehung->gewinner6a49["GES-8"]."</3RichtigeAnzahl>"."\n";
		$xml .= " <2RichtigeSZ>".$this->ziehung->quoten6a49["9"]."</2RichtigeSZ>"."\n";
		$xml .= " <2RichtigeSZAnzahl>".$this->ziehung->gewinner6a49["GES-9"]."</2RichtigeSZAnzahl>"."\n";
		$xml .= " <6RichtigeSZDE>".$this->ziehung->quoten6a49DE["1"]."</6RichtigeSZDE>"."\n";
		$xml .= " <6RichtigeSZJackpotDE>".$this->ziehung->quoten6a49DE["J1"]."</6RichtigeSZJackpotDE>"."\n";
		$xml .= " <6RichtigeDE>".$this->ziehung->quoten6a49DE["2"]."</6RichtigeDE>"."\n";
		$xml .= " <6RichtigeJackpotDE>".$this->ziehung->quoten6a49DE["J2"]."</6RichtigeJackpotDE>"."\n";
		$xml .= " <5RichtigeSZDE>".$this->ziehung->quoten6a49DE["3"]."</5RichtigeSZDE>"."\n";		
		$xml .= " <5RichtigeDE>".$this->ziehung->quoten6a49DE["4"]."</5RichtigeDE>"."\n";		
		$xml .= " <4RichtigeSZDE>".$this->ziehung->quoten6a49DE["5"]."</4RichtigeSZDE>"."\n";		
		$xml .= " <4RichtigeDE>".$this->ziehung->quoten6a49DE["6"]."</4RichtigeDE>"."\n";		
		$xml .= " <3RichtigeSZDE>".$this->ziehung->quoten6a49DE["7"]."</3RichtigeSZDE>"."\n";		
		$xml .= " <3RichtigeDE>".$this->ziehung->quoten6a49DE["8"]."</3RichtigeDE>"."\n";		
		$xml .= " <2RichtigeSZDE>".$this->ziehung->quoten6a49DE["9"]."</2RichtigeSZDE>"."\n";
		$xml .= " <Gesamtausschuettung>".number_format($ausschuettung, 2, ',', '.')."</Gesamtausschuettung>"."\n";
		$xml .= "</IMPERIA_CONTENT>"."\n";	

		parent::saveXML($filename,$xml);
	}

	protected function getZahlen($tag){
		global $t,$d;

		$zahlen6a49 	= array();
		$zahlens77 	= array();
		$zahlenS6 	= array();
		$quoten6a49  	= array();
		$gewinner6a49  	= array();

		$this->CurlPost("https://extranet.lotto-rlp.de/ssl/nps/data/lo".$tag."_gez.customerinfo", $zahlen6a49);
		$this->CurlPost("https://extranet.lotto-rlp.de/ssl/nps/data/s7".$tag."_gez.customerinfo", $zahlens77);
		$this->CurlPost("https://extranet.lotto-rlp.de/ssl/nps/data/s6".$tag."_gez.customerinfo", $zahlenS6);
		$this->CurlPost("https://extranet.lotto-rlp.de/ssl/nps/data/lo".$tag."_gqu.customerinfo", $quoten6a49);
		$this->CurlPost("https://extranet.lotto-rlp.de/ssl/nps/data/lo".$tag."_egg.customerinfo", $gewinner6a49);


		$myziehung = new ziehung();
		$myziehung->datum = $zahlen6a49["DATUM"];

		//kurz nach der ziehung sind noch keine gewinner oder quoten bekannt, darum löschen
		$ziehungVA = $zahlen6a49["VA"];

		if($t) //t=quoten und gewinner immer leeren
		{
			echo "Test: Keine neuen Quoten\n";
			$quoten6a49["VA"] = 0;
			echo "Test: Keine Gewinner\n";
			$gewinner6a49["VA"] = 0;
		} 

		if($zahlens77["VA"] < $ziehungVA) {
			//kein S77 -> S77 löschen
			$zahlens77["GEZ"] = "";
		}
		if($zahlenS6["VA"] < $ziehungVA) {
			//keine S6 -> S6 löschen
			$zahlenS6["GEZ"] = "";
		}		
		if($quoten6a49["VA"] < $ziehungVA) {
			//keine quoten -> quoten löschen
			if($d) echo "Debug 1: lösche Quoten\n";
			foreach ($quoten6a49 as $key => $value)
				$quoten6a49[$key] = "0";
		}
		if($gewinner6a49["VA"] < $ziehungVA) {
			//keine gewinner -> gewinner löschen
			if($d) echo "Debug 1: lösche Gewinner\n";
			foreach ($gewinner6a49 as $key => $value)
				$gewinner6a49[$key] = "0";
		}

		$myziehung->zahlen6a49 = $this->removeUnusedElements($zahlen6a49);
		$myziehung->zahlens77  = $this->removeUnusedElements($zahlens77);
		$myziehung->zahlenS6   = $this->removeUnusedElements($zahlenS6);
		$myziehung->quoten6a49 = $this->removeUnusedElements($quoten6a49);
		$myziehung->gewinner6a49 = $this->removeUnusedElements($gewinner6a49);

		//sortieren
		$superzahl = $myziehung->zahlen6a49["S"];
		unset($myziehung->zahlen6a49["S"]);
		sort($myziehung->zahlen6a49);
		$myziehung->zahlen6a49["S"] = $superzahl;

		//quoten aufbereiten
		$formatQuoten6a49DE = function($value) {
			return (intval($value) == 0)?"Unbesetzt":number_format($value, 2, ',', '.')."&nbsp;Euro";			
		};
		$formatQuoten6a49 = function($value) {
			return (intval($value) == 0)?"0":number_format($value, 2, ',', '.');
		};		
		//rheinfolge beachten!
    	$myziehung->quoten6a49DE = array_map($formatQuoten6a49DE, $myziehung->quoten6a49);
    	$myziehung->quoten6a49   = array_map($formatQuoten6a49,   $myziehung->quoten6a49);

		return $myziehung;
	}

	protected function berechne_gesamtausschuettung($myziehung){
		$gesamtausschuettung = 0;
		$gewinner6a49 = array_values($myziehung->gewinner6a49);
		$quoten6a49   = array_values($myziehung->quoten6a49);

		 //var_dump($gewinner6a49);
		 //var_dump($myziehung->quoten6a49);

		for($i=0;$i<10;$i++){
				$x1 = parseFloat($quoten6a49[$i]);
				$x2 = $gewinner6a49[$i];
				$gesamtausschuettung += ($x1 * $x2);
				//echo "x1 = $x1 ::: x2 = $x2 ::: gesamtausschuettung = $gesamtausschuettung\n";
		}
		return $gesamtausschuettung;
	}

}

class gluecksspirale extends gewinnspiel
{

	public function loadnew($imperiaDoc)
	{
		global $d;
		$this->imperiaDoc = $imperiaDoc;

		if ($d) echo "Debug1: lade zahlen\n";
		$this->samstag = $this->getZahlen();	
		$datesamstag  = DateTime::createFromFormat('Ymd', $this->samstag->datum);

		//samstag ist aktuell
		$this->day = "Samstag";
		$this->date = $datesamstag;
		$this->ziehung = $this->samstag;
	}

	public function loadjson($json)
	{
		$tempobj = json_decode($json,true);
		$this->samstag  = $tempobj["samstag"];
		$this->hash     = $tempobj["hash"];

		return $this->validate();
	}

	public function saveXML($filename){

		$xml = "<?xml version=\"1.0\" encoding=\"utf-8\" standalone=\"yes\"?>"."\n";
		$xml .= "<IMPERIA_CONTENT>"."\n";
		$xml .= " <autoimport>1</autoimport>"."\n";
		$xml .= " <__imperia_imported>1</__imperia_imported>"."\n";
		$xml .= " <__imperia_uid>56</__imperia_uid>"."\n";
		$xml .= " <__imperia_last_uid>56</__imperia_last_uid>"."\n";
		$xml .= " <do_edit>0</do_edit>"."\n";
		$xml .= " <__imperia_clobber_by_id>".$this->nodeID."</__imperia_clobber_by_id>"."\n";		
		$xml .= " <publish_date>2012-01-01 00:00</publish_date>"."\n";
		$xml .= " <skip_approval>1</skip_approval>"."\n";
		$xml .= " <skip_edit>1</skip_edit>"."\n";
		$xml .= " <skip_metaedit>1</skip_metaedit>"."\n";
		$xml .= " <skip_docselector>1</skip_docselector>"."\n";		
		$xml .= " <title>Lotto GS Modul</title>"."\n";		
		$xml .= " <template>zdfde_lotto_gs</template>"."\n";
		$xml .= " <filename>gluecksspirale.html</filename>"."\n";
		$xml .= " <copy>/ZDFde/gluecksspirale/gluecksspirale.xml:TEMPLATE=zdfde_lotto_gs_xml</copy>"."\n";		
		$xml .= " <directory></directory>"."\n";
		$xml .= " <__imperia_node_id>".$this->nodeID."</__imperia_node_id>"."\n";
		$xml .= " <lottodatum>".$this->date->format('d.m.Y')."</lottodatum>"."\n";
		$xml .= " <lottowoche>".$this->date->format('W')."</lottowoche>"."\n";
		$xml .= " <lottotag>".$this->day."</lottotag>"."\n";
		$xml .= " <endziffer1>".$this->ziehung->zahlenGS["1"]."</endziffer1>"."\n";
		$xml .= " <endziffer1_anzahl>".$this->ziehung->quotenGS["GES-1"]."</endziffer1_anzahl>"."\n";
		$xml .= " <endziffer2>".$this->ziehung->zahlenGS["2"]."</endziffer2>"."\n";
		$xml .= " <endziffer2_anzahl>".$this->ziehung->quotenGS["GES-2"]."</endziffer2_anzahl>"."\n";
		$xml .= " <endziffer3>".$this->ziehung->zahlenGS["3"]."</endziffer3>"."\n";
		$xml .= " <endziffer3_anzahl>".$this->ziehung->quotenGS["GES-3"]."</endziffer3_anzahl>"."\n";
		$xml .= " <endziffer4>".$this->ziehung->zahlenGS["4"]."</endziffer4>"."\n";
		$xml .= " <endziffer4_anzahl>".$this->ziehung->quotenGS["GES-4"]."</endziffer4_anzahl>"."\n";
		$xml .= " <endziffer5>".$this->ziehung->zahlenGS["5"]."</endziffer5>"."\n";
		$xml .= " <endziffer5_anzahl>".$this->ziehung->quotenGS["GES-5"]."</endziffer5_anzahl>"."\n";
		$xml .= " <endziffer6a>".$this->ziehung->zahlenGS["6-1"]."</endziffer6a>"."\n";
		$xml .= " <endziffer6a_anzahl>".$this->ziehung->quotenGS["GES-6"]."</endziffer6a_anzahl>"."\n";
		$xml .= " <endziffer6b>".$this->ziehung->zahlenGS["6-2"]."</endziffer6b>"."\n";
		$xml .= " <endziffer6b_anzahl>".$this->ziehung->quotenGS["WERT-6"]."</endziffer6b_anzahl>"."\n";
		$xml .= " <endziffer7a>".$this->ziehung->zahlenGS["7-1"]."</endziffer7a>"."\n";
		$xml .= " <endziffer7a_anzahl>".$this->ziehung->quotenGS["GES-7"]."</endziffer7a_anzahl>"."\n";
		$xml .= " <endziffer7b>".$this->ziehung->zahlenGS["7-2"]."</endziffer7b>"."\n";
		$xml .= " <endziffer7b_anzahl>".$this->ziehung->quotenGS["WERT-7"]."</endziffer7b_anzahl>"."\n";
		$xml .= "</IMPERIA_CONTENT>"."\n";
		
		parent::saveXML($filename,$xml);
	}

	protected function getZahlen($unused)
	{		
		global $t,$d;

		$zahlenGS 	= array();
		$quotenGS 	= array();

		//glücksspirale
		$this->CurlPost("https://extranet.lotto-rlp.de/ssl/nps/data/gsxx_gez.customerinfo", $zahlenGS);
		$this->CurlPost("https://extranet.lotto-rlp.de/ssl/nps/data/gsxx_egg.customerinfo", $quotenGS);


		$myziehung = new ziehung();
		$myziehung->datum = $zahlenGS["DATUM"];

		// glücksspirale ZIEHEUNG
		//Vergleichsdatum jetzt auf datum von GS setzen
		
		if($quotenGS["VA"] <  $zahlenGS["VA"]) {
			//keine quoten -> quoten löschen
			if($d) echo "Debug 1: lösche Quoten\n";
			foreach ($quotenGS as $key => $value)
				$quotenGS[$key] = "0";
		}


		$myziehung->zahlenGS = $this->removeUnusedElements($zahlenGS);
		$myziehung->quotenGS = $this->removeUnusedElements($quotenGS);


		return $myziehung;
	}

}

function filterArray($array){

	foreach ($array as $key => $value) {
		$array[$key] = intval(array_pop($value));
	}
	asort($array);
	// foreach ($array as $key => $value) {
	// 	$array[$key] = date("D M j G:i:s T Y",intval($value));
	// }
	//var_dump($array);
	end($array);
	return key($array);
}

function json_indent($json) {

    $result      = '';
    $pos         = 0;
    $strLen      = strlen($json);
    $indentStr   = '  ';
    $newLine     = "\n";
    $prevChar    = '';
    $outOfQuotes = true;

    for ($i=0; $i<=$strLen; $i++) {

        // Grab the next character in the string.
        $char = substr($json, $i, 1);

        // Are we inside a quoted string?
        if ($char == '"' && $prevChar != '\\') {
            $outOfQuotes = !$outOfQuotes;

        // If this character is the end of an element,
        // output a new line and indent the next line.
        } else if(($char == '}' || $char == ']') && $outOfQuotes) {
            $result .= $newLine;
            $pos --;
            for ($j=0; $j<$pos; $j++) {
                $result .= $indentStr;
            }
        }

        // Add the character to the result string.
        $result .= $char;

        // If the last character was the beginning of an element,
        // output a new line and indent the next line.
        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos ++;
            }

            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }

        $prevChar = $char;
    }

    return $result;
}

function parseFloat($ptString) { 
            if (strlen($ptString) == 0) { 
                    return false; 
            } 
            
            $pString = str_replace(" ", "", $ptString); 
            
            if (substr_count($pString, ",") > 1) 
                $pString = str_replace(",", "", $pString); 
            
            if (substr_count($pString, ".") > 1) 
                $pString = str_replace(".", "", $pString); 
            
            $pregResult = array(); 
        
            $commaset = strpos($pString,','); 
            if ($commaset === false) {$commaset = -1;} 
        
            $pointset = strpos($pString,'.'); 
            if ($pointset === false) {$pointset = -1;} 
        
            $pregResultA = array(); 
            $pregResultB = array(); 
        
            if ($pointset < $commaset) { 
                preg_match('#(([-]?[0-9]+(\.[0-9])?)+(,[0-9]+)?)#', $pString, $pregResultA); 
            } 
            preg_match('#(([-]?[0-9]+(,[0-9])?)+(\.[0-9]+)?)#', $pString, $pregResultB); 
            if ((isset($pregResultA[0]) && (!isset($pregResultB[0]) 
                    || strstr($preResultA[0],$pregResultB[0]) == 0 
                    || !$pointset))) { 
                $numberString = $pregResultA[0]; 
                $numberString = str_replace('.','',$numberString); 
                $numberString = str_replace(',','.',$numberString); 
            } 
            elseif (isset($pregResultB[0]) && (!isset($pregResultA[0]) 
                    || strstr($pregResultB[0],$preResultA[0]) == 0 
                    || !$commaset)) { 
                $numberString = $pregResultB[0]; 
                $numberString = str_replace(',','',$numberString); 
            } 
            else { 
                return false; 
            } 
            $result = (float)$numberString; 
            return $result; 
} 
?> 
