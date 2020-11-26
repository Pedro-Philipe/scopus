<?php
error_reporting(0);
    $file = fopen("scopus.txt", "r") or exit("Unable to open file!");
    $i = 0;
    while(!feof($file)) {
        $dados[] = fgets($file) . "\r\n";
    }

    $iRegistro = 0;
    $dadoLimpo = [];
    $proximasLinhasReferencias = false;
    foreach($dados as $key => $value){

        if(preg_match('/SOURCE: Scopus/', $value)){
            $iRegistro++;
        }
        $autoresArray = [];

        if (preg_match('/^\([0-9]{4}\)/', $value, $anoEvento)) {
            $autores = explode(', ', $dados[$key-3]);
            for ($contador = 0; $contador < count($autores); $contador++) {
                $autoresArray[] = $autores[$contador] . ', ' . $autores[$contador+1];
                $contador++;
            }
            $dadoLimpo[$iRegistro]['AU'] = $autoresArray;
            $dadoLimpo[$iRegistro]['TI'] = $dados[$key-1];
            $dadoLimpo[$iRegistro]['CY'] = preg_replace('/[^0-9]/', '', array_shift($anoEvento));
            $dadoLimpo[$iRegistro]['CL'] = preg_replace('/^\([0-9]{4}\) /', '', $value);
        }

        if(preg_match('/AUTHOR KEYWORDS:/', $value)){
            $dadoLimpo[$iRegistro]['DE'] = explode(": ", $value)[1];
        }else if (preg_match('/INDEX KEYWORDS:/', $value)){
            $dadoLimpo[$iRegistro]['DE'] = explode(": ", $value)[1];
        }

        if(preg_match('/Cited [1-9]{1,9} time/', $value, $numeroCitacoes)){
            $dadoLimpo[$iRegistro]['Z9'] = (int) explode(" ", $numeroCitacoes[0])[1];
        }


        if(preg_match('/LANGUAGE OF ORIGINAL DOCUMENT:/', $value)){
            $dadoLimpo[$iRegistro]['LA'] = explode(": ", $value)[1];
        }

        if (preg_match('/ABSTRACT:/', $value)) {
            $dadoLimpo[$iRegistro]['AB'] = explode(": ", $value)[1];
        }

        if(preg_match('/PUBLISHER:/', $value)){
            $dadoLimpo[$iRegistro]['SO'] = explode(": ", $value)[1];
        }

        if(preg_match('/AFFILIATIONS:/', $value)){
            $dadoLimpo[$iRegistro]['C1'] = explode(": ", $value)[1];
            $dadoLimpo[$iRegistro]['C1'] = explode(';', $dadoLimpo[$iRegistro]['C1'])[0];
        }

        if(preg_match('/REFERENCES:/', $value)){
	    $proximasLinhasReferencias = true;
/*            preg_match_all("/[A-Z][a-z]{1,30}, [A-Z]{1}.,/", $value, $autores);
            preg_match_all("/(20|19)[0-9]{2}/", $value, $ano);
            //$teste['teste'][] = explode(')', $value);
	    $autorEmLinha = '';
		if(empty($autores[0]) && $ano){
		    $autorEmLinha = '[Anonymous], ' . $ano[0][0];
		} else{
		    foreach($autores[0] as $autor){
                        $autorEmLinha = $autorEmLinha . $autor;
	            }
                    $autorEmLinha = $autorEmLinha . ' ' . $ano[0][0];
		}
var_dump($autorEmLinha);

            $dadoLimpo[$iRegistro]['SO'] = explode(":i ", $value)[1];

            #$teste['teste'][] = $ano[0][0];
	    //$teste['teste'] = preg_match_all("/[A-Z][a-z]{1,30}, [A-Z]{1}.(,|[A-Z].,|-[A-Z],)/",)
            $dadoLimpo[$iRegistro]['SO'] = explode(": ", $value)[1];
*/
        }

	if($proximasLinhasReferencias === true){
	    preg_match_all("/[A-Z][a-z]{1,30}, [A-Z]{1}.,/", $value, $autores);
            preg_match_all("/(20|19)[0-9]{2}/", $value, $ano);
	    $autorEmLinha = '';
		if(empty($autores[0]) && $ano){
		    $autorEmLinha = '[Anonymous], ' . $ano[0][0];
		} else{
		    foreach($autores[0] as $autor){
                        $autorEmLinha = $autorEmLinha . $autor;
	            }
                    $autorEmLinha = $autorEmLinha . ' ' . $ano[0][0];
		}
		$dadoLimpo[$iRegistro]['CR'][] = $autorEmLinha;
	}

	if(preg_match('/[A-Z]{3}:/', $value) && $proximasLinhasReferencias === true && !preg_match('/REFERENCES:/', $value)){
	    $proximasLinhasReferencias = false;
	}

    }

    $fh = fopen( 'extracao.txt', 'w' );
    fclose($fh);

    $fp = fopen('extracao.txt', 'w');

    $publicacao = '';
    foreach($dadoLimpo as $key => $value){
        if(count($value['AU']) > 1){
            $publicacao .= 'AU' . ' ' . trim($value['AU'][0]). "\r\n";
            if(count($value['AU']) > 1){
                unset($value['AU'][0]);
                foreach($value['AU'] as $autor){
                    $publicacao .= '   ' . trim($autor) . "\r\n";
                }
            }
        }

        if(array_key_exists('SO', $value)){
            $publicacao .= 'SO ' . trim($value['SO']) . "\r\n";
        }
        // echo count($value['DE']);
        if(array_key_exists('DE', $value)){
            $publicacao .= 'DE ' . trim($value['DE']) . "\r\n";
        }

        if(array_key_exists('AB', $value)){
            $publicacao .= 'AB ' . trim($value['AB']) . "\r\n";
        }

        if(array_key_exists('C1', $value)){
            $publicacao .= 'C1 ' . $value['C1'] . "\r\n";
        }

        $publicacao .= 'TI ' . trim($value['TI']) . "\r\n";
        $publicacao .= 'LA ' . trim($value['LA']) . "\r\n";
        $publicacao .= 'CY ' . trim($value['CY']) . "\r\n";
        $publicacao .= 'PY ' . trim($value['CY']) . "\r\n";
        $publicacao .= 'CL ' . trim($value['CL']) . "\r\n";
        if($value['Z9']){
            $publicacao .= 'Z9 ' . $value['Z9'] . "\r\n";
            $publicacao .= 'TC ' . $value['Z9'] . "\r\n";
        }

	if(count($value['CR']) > 1){
            $publicacao .= 'CR' . ' ' . $value['CR'][0]. "\r\n";
            if(count($value['CR']) > 1){
                unset($value['CR'][0]);
                foreach($value['CR'] as $autor){
                    $publicacao .= '   ' . trim($autor) . "\r\n";
                }
            }
        }





        $publicacao .= "ER\r\n";
    }


    fwrite($fp, $publicacao);
    fclose($fp);

    fclose($file);
#[A-Z][a-z]{1,30}, [A-Z]{1}.(,|[A-Z].,|-[A-Z],)
?>


