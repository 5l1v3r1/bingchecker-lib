<?php

class DomainSearch{

	private $bbddhost = "localhost";
	private $bbddname = null;
	private $bbdduser = null;
	private $bbddpass = null;


	public function setDBOptions($bbddhost,$bbddname,$bbdduser,$bbddpass){
		$this->bbddhost = $bbddhost;
		$this->bbddname = $bbddname;
		$this->bbdduser = $bbdduser;
		$this->bbddpass = $bbddpass;
	}


	public function getDomainAEntry($dominio){
		$nslkp = `nslookup $dominio 8.8.8.8`;
		$tmp = preg_split("/Name:/",$nslkp); //Verificamos que resuelve y quitamos la palabra name
		if(sizeof($tmp)>1){
			$tmp = preg_split("/\n/",$tmp[1]); //separamos por salto de linea
			$tmp = preg_split("/Address:/",$tmp[1]);//Eliminamos Address: Para dejar limpia la IP
			return trim($tmp[1]);
		}
		else{
			return -1;		
		}
		return -1;
		
	}


	public function isSeriuslyAtThere($dominio,$ip){
		$nslkp = `nslookup $dominio 8.8.8.8`;
		$tmp = preg_split("/Name:/",$nslkp); //Verificamos que resuelve y quitamos la palabra name
		if(sizeof($tmp)>1){
			$tmp = preg_split("/\n/",$tmp[1]); //separamos por salto de linea
			$tmp = preg_split("/Address:/",$tmp[1]);//Eliminamos Address: Para dejar limpia la IP
			if(trim($tmp[1])==$ip)
				return true;
			else
				return false;
		}
		else{
			return false;		
		}
		
	}


	public function SearchDomains($ip){
		$ch = curl_init();
		curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => "http://www.bing.com/search?q=ip%3a".$ip, 
			CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1'));
		$res = curl_exec($ch);
		curl_close($ch);
	

		$arraytmp = array();

		$nuevo = split ( "<cite>" , $res );

		for($i=1;$i<sizeof($nuevo);$i++){
			$nuevo2 = split ( "</cite>" , $nuevo[$i] );
			for($z=0;$z<sizeof($nuevo2);$z++){
				$nuevo2[$z]=preg_replace("/https:\/\//","",$nuevo2[$z]);
				$nuevo2[$z]=preg_replace("/http:\/\//","",$nuevo2[$z]);
				$nuevo2[$z]=preg_replace("/www./","",$nuevo2[$z]);
				$tmp= split("/",$nuevo2[$z]);
				$tmp = split("&",$tmp[0]);
				$nuevo2[$z] = strip_tags ( $tmp[0] ); //eliminamos tags html
			}
			$igual=0;
			for($y=0;$y<sizeof($arraytmp);$y++){
				if($nuevo2[0]==$arraytmp[$y])
					$igual=1;
			}
			if($igual==0)
				$arraytmp[]=$nuevo2[0];
		
		}

		/*for($i=0;$i<(sizeof($arraytmp));$i++)
			echo $arraytmp[$i]."\n";*/
		return $arraytmp;

	}

	public function SearchDomainsOnIpRange($ipinitmp,$ipfintmp){
		$ipinitmp = split("\.",$ipinitmp);
		$ipfintmp = split("\.",$ipfintmp);
		$oct = array($ipinitmp[0],$ipinitmp[1],$ipinitmp[2],$ipinitmp[3]);
		$count = $oct[3];
		$tmpreturn=null;

		while($count != -1){
			
			
			$iptmp=$oct[0].".".$oct[1].".".$oct[2].".".$oct[3];
			$tmpres=$this->SearchDomains($iptmp);
			echo "Dominios de ".$iptmp.":\n";
			for($w=0;$w<sizeof($tmpres);$w++){
				if($this->isSeriuslyAtThere($tmpres[$w],$iptmp)){
					echo $tmpres[$w]."\n";
					$tmpreturn[]= array($iptmp,$tmpres[$w]);
				}
			}
			echo "----------------------------\n";			
			

			if($oct[0]==$ipfintmp[0] && $oct[1]==$ipfintmp[1] && $oct[2]==$ipfintmp[2] && $oct[3]==$ipfintmp[3]){//final de todo
				$count=-1;
			}else{
				//Sumatorio de 1er byte
				if($oct[3]>=255 && $oct[2]>=255 && $oct[1]>=255 && $oct[0]<$ipfintmp[0] ){
					$oct[0]++;
					$oct[1]=0;
				}
				//Fin Sumatorio de 1er byte
				//Sumatorio de 2º byte
				if($oct[3]>=255 && $oct[2]>=255 && $oct[1]<$ipfintmp[1] ){
					$oct[1]++;
					$oct[2]=0;
				}
				//Fin Sumatorio de 2º byte
				//Sumatorio de 3er y 4º byte
				if($count < 255 ){ 
					$count++;
					$oct[3]=$count;
				}
				elseif($count >= 255 && $oct[2]<$ipfintmp[2] ){
					$count=0;
					$oct[3]=$count;
					$oct[2]++;			
				}
				else{
					$count=-1;			
				}

			
				//Fin Sumatorio de 3er y 4º byte
			}
			
		}
		return $tmpreturn;	
	}

	public function IncludeToDB($dominio,$ip){
		
		$mysqli = new mysqli($this->bbddhost, $this->bbdduser, $this->bbddpass, $this->bbddname);
		if ($mysqli->connect_errno) {
		    return -1;
		}


		if ($mysqli->query("INSERT INTO dominios VALUES('','$dominio','$ip','$ip',NOW())") === TRUE) {
		    return 1;
		}

		$mysqli->close();
	}
	
	public function IncludeDomainsToDB($domains){ //el array debe ser array(array($dominio,$ip))
		for($i=0;$i<sizeof($domains);$i++){
			if($this->IsOnDB($domains[$i][1])==0){	
				$this->IncludeToDB($domains[$i][1],$domains[$i][0]);
			}
		}
	}

	

	public function IsOnDB($dominio){
		$mysqli = new mysqli($this->bbddhost, $this->bbdduser, $this->bbddpass, $this->bbddname);
		if ($mysqli->connect_errno) {
		    return -1;
		}
		
		if ( $res = $mysqli->query("SELECT * FROM dominios WHERE dominio='$dominio'") ) {
			if($res->num_rows > 0){		    
				$mysqli->close();	
				return 1;
			}
		}
		$mysqli->close();
		return 0;

	}


	public function UpdateDB($dominio,$ip){
		
		$mysqli = new mysqli($this->bbddhost, $this->bbdduser, $this->bbddpass, $this->bbddname);
		if ($mysqli->connect_errno) {
		    return -1;
		}
		
		if ( $res = $mysqli->query("SELECT * FROM dominios WHERE dominio='$dominio'") ) {
			if($res->num_rows <= 0){		    
				$mysqli->close();	
				return 0;
			}
			else{
				$row = $res->fetch_row();
				if ($mysqli->query("UPDATE dominios SET dominio='$dominio',aentry='$ip',aentryold='$row[3]',fecha=NOW())") === TRUE) {
					$mysqli->query("INSERT INTO domainsinIpchangesh VALUES('','$ip','$dominio',NOW())");
					$mysqli->close();
					return 1;
				}
			}
		}

		

		$mysqli->close();
		return 0;
	}
	

	public function checkDomainsIPandSetNew(){
		$tmparray = null;
		$mysqli = new mysqli($this->bbddhost, $this->bbdduser, $this->bbddpass, $this->bbddname);
		if ($mysqli->connect_errno) {
		    return -1;
		}
		
		if ( $res = $mysqli->query("SELECT * FROM dominios") ) {
			if($res->num_rows <= 0){		    
				$mysqli->close();	
				return 0;
			}
			else{
				while($row = $res->fetch_row()){
					$newip = $this->getDomainAEntry($row[1]);
					if($newip!=-1 && $newip != $row[2]){
						$tmparray[] = array($row[1],$row[2],$newip);
						$this->UpdateDB($row[1],$newip);				
					}
				}
				return $tmparray;
			}
		}

		

		$mysqli->close();
		return 0;
	}
	
	public function sendInforme($domains,$emailfrom,$emaildest){ //$domains = array(array($dominio,$ip),array($domname,$ip))
		$ruta = "/tmp/".date("dmyGis").".txt";
		$fp = fopen($ruta, 'w');
		fwrite($fp, "Domain    IP\r\n");	
		for($i=0;$i<sizeof($domains);$i++){
			fwrite($fp, $domains[$i][0].'    '.$domains[$i][1]."\r\n");
		}

		fclose($fp);
		
		$this->multi_attach_mail($emaildest, array($ruta), $emailfrom,'Informe de cambios en dominios');
		
		$isdel = `rm -f $ruta`;
	}

	private function multi_attach_mail($emaildest, $files, $from,$subject){

	    //$from = "Files attach <".$from.">";
	    //$subject = date("d.M H:i")." F=".count($files);
	    //$message = date("Y.m.d H:i:s")."\n".count($files)." attachments";
	    $message = null;
	    $headers = "From: $from";
	 
	    // boundary
	    $semi_rand = md5(time());
	    $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";
	 
	    // headers for attachment
	    $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";
	 
	    // multipart boundary
	    $message = "--{$mime_boundary}\n" . "Content-Type: text/plain; charset=\"iso-8859-1\"\n" .
	    "Content-Transfer-Encoding: 7bit\n\n" . $message . "\n\n";
	 
	    // preparing attachments
	    for($i=0;$i<count($files);$i++){
		if(is_file($files[$i])){
		    $message .= "--{$mime_boundary}\n";
		    $fp =    @fopen($files[$i],"rb");
		$data =    @fread($fp,filesize($files[$i]));
		            @fclose($fp);
		    $data = chunk_split(base64_encode($data));
		    $message .= "Content-Type: application/octet-stream; name=\"".basename($files[$i])."\"\n" .
		    "Content-Description: ".basename($files[$i])."\n" .
		    "Content-Disposition: attachment;\n" . " filename=\"".basename($files[$i])."\"; size=".filesize($files[$i]).";\n" .
		    "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
		    }
		}
	    $message .= "--{$mime_boundary}--";
	    $returnpath = "-f" . $from;
	    $ok = @mail($emaildest, $subject, $message, $headers, $returnpath);
	    if($ok){ return $i; } else { return 0; }
    	}

}
?>

