<img src="schema.png" width="300"/><br>

<?php

//CONEXÃO COM BANCO DE DADOS
$conexao = mysqli_connect("localhost", "usuario", "123", "crawler");

//PARTE 4
//FUNÇÃO QUE SELECIONA O QUE ESTA DELIMITADO     
function retirarEntre($conteudo, $inicio, $fim){
    $r = explode($inicio, $conteudo);
    if (isset($r[1])){
        $r = explode($fim, $r[1]);
        return $r[0];
    }
    return '';
}

//PARTE 5
//FUNÇÃO PARA EXTRAIR INFORMAÇÕES E O VIDEO
function extrair_video($link_pagina_video){
    
    $sql_pagina_video = "";
    
    $pagina_video = file_get_contents( $link_pagina_video );
    $pagina_video = retirarEntre($pagina_video, "<table class=\"table itemDisplayTable\">", "</td></tr></tbody></table>");
    
    //TITULO
    $titulo = retirarEntre($pagina_video, "<td class=\"metadataFieldValue dc_title\">", "</td>");
    $sql_pagina_video = $sql_pagina_video . " '$titulo', ";

    //PALAVRAS-CHAVE
    $palavras_chave = retirarEntre($pagina_video, "<td class=\"metadataFieldValue dc_subject\">", "</td></tr>");   
    $sql_pagina_video = $sql_pagina_video . " '$palavras_chave', ";

    //REFERENCIA
    $referencia = retirarEntre($pagina_video, "<td class=\"metadataFieldValue dc_identifier_citation\">", "</td>");
    $sql_pagina_video = $sql_pagina_video . " '$referencia', ";
    
    //RESUMO
    $resumo = retirarEntre($pagina_video, "<td class=\"metadataFieldValue dc_description_resumo\">", "</td>");
    $sql_pagina_video = $sql_pagina_video . " \"$resumo\", ";
    
    //ABSTRACT
    $abstract = retirarEntre($pagina_video, "<td class=\"metadataFieldValue dc_description_abstract\">", "</td>");
    $sql_pagina_video = $sql_pagina_video . " \"$abstract\", ";
    
    //DESCRICAO
    $descricao = retirarEntre($pagina_video, "<td class=\"metadataFieldValue dc_description\">", "</td>");
    $sql_pagina_video = $sql_pagina_video . " '$descricao', ";

    //VIDEO MP4
    $url_video = @explode("mp4", $pagina_video);    
    $url_video = @retirarEntre($url_video[2], "<a class=\"btn btn-primary\" target=\"_blank\" href=\"", " ");
    
    //SE NAO ACHAR UM MP4, PROCURAR  SE TEM WMV E EXTRAIR
    if($url_video==""){

	   //VIDEO WMV
	   $url_video = explode("wmv", $pagina_video);	
	   $url_video = retirarEntre($url_video[2], "<a class=\"btn btn-primary\" target=\"_blank\" href=\"", " ");
	   $url_video = "http://repositorio.roca.utfpr.edu.br".$url_video."wmv";

	
	   //Se mesmo assim ainda nao achar o WMV, faça procura novamente e extraia
	   if($url_video=="http://repositorio.roca.utfpr.edu.br"."wmv"){

        	$url_video = explode("wmv", $pagina_video);

            $url_video = retirarEntre($url_video[3], "/td><td class=\"standard\" align=\"center\"><a class=\"btn btn-primary\" target=\"_blank\" href=\"", "\">");
            $url_video = "http://repositorio.roca.utfpr.edu.br$url_video"."wmv";

            if($url_video==""){
                echo "Não encontrado!";
            }
            
    }

    }else{
	   //SE ACHAR UM MP4, COLOCA MP4 NO FINAL
	   $url_video = "http://repositorio.roca.utfpr.edu.br". $url_video ."mp4";
    }

    $sql_pagina_video = $sql_pagina_video . " '$url_video' ";

    return $sql_pagina_video;


}


//PARTE 1
//World Wide Web
$pagina_roca = file_get_contents("http://repositorio.roca.utfpr.edu.br/jspui/browse?type=tipologia&order=ASC&rpp=20&value=video");


$pagina_roca = retirarEntre($pagina_roca, '</th></tr><tr>', "</table>");

$separa = explode("<tr>", $pagina_roca);

$total_reas = count($separa);

echo "Número de REAs encontrados: " . $total_reas ."<br><br>";

//PARTE 2
//MULTI-THREADED DOWNLOADER E SCHEDULER (agendador)
$rea = 1;

for($x=0; $x < $total_reas; $x++){

    $sql = "";
    
    $separa1 = retirarEntre($separa[$x], "<td headers=\"t1\" >", "</tr>");
    

    //CAMPUS
    $campus = retirarEntre($separa1, "<em>", "</em>");
    $sql = $sql . " '$campus', ";
   
    //ANO
    $ano  = retirarEntre($separa1, "\">", "</td>");
    $sql = $sql . " '$ano', "  ;

    //AUTORES    
    $autores = retirarEntre($separa1, "<td headers=\"t4\" >", "</tr>");    
    $sql = $sql . " '$autores', ";
    
    //PARTE 3, LINK PARA ENTRAR NA PAGINA QUE TEM VIDEO
    //QUEUE URL (FILA DE URLs)
    $link_pagina_video = retirarEntre($separa1, "<a href=\"", "\">");


    $link_pagina_video = "http://repositorio.roca.utfpr.edu.br".$link_pagina_video;
    $sql = $sql . " '$link_pagina_video', ";

    $dados_da_pagina = extrair_video( $link_pagina_video );
    $sql = $sql . $dados_da_pagina;

    //PARTE 6 FIM
    //STORAGE (TEXT AND METADATA)
    $query = "INSERT INTO videos (campus, ano, autores, link_para_pagina, titulo, palavras_chave, referencia, resumo, abstract, descricao, link_video) VALUES ( $sql )";

    $query = mysqli_query($conexao, $query) or die(mysqli_error($conexao));
    
   echo "<font color=green>[OK] REA $rea INCLUÍDO COM SUCESSO USANDO CRAWLER AUTOMATICAMENTE</font><br><br>";

    $rea++;

}
?>
