<?php

class DomainSearch{

	


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

	

}
?>

