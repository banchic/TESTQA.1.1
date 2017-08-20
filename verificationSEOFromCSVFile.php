<?php
#Устанавливаем опцию для указания файла с SEO
const paramFile = 'fileCSV';

$changesSEO = fileCSVExists();

$ch = curl_init();
#Включаем редирект
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
#Требуются определенные куки для отображения верной страницы
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Cookie: test=seo"]);
foreach($changesSEO as $changeSEO){
    curl_setopt($ch, CURLOPT_URL, $changeSEO["URL"]);

    $response = curl_exec($ch);
    $info = curl_getinfo($ch);

    #Проверяем, что код страницы 200, иначе показываем ошибку
    if($info['http_code'] != 200){
        print "Ошибка: " . $changeSEO["URL"] . " : " . errorCodeTranslator($info['http_code']) ."\n";
        #дальше даже не стоит проверять по данной странице
        continue;
    }
    $responseDom = new DOMDocument();
    #Отключаю ошибки парсинга страницы
    libxml_use_internal_errors(true);
    $responseDom->loadHTML($response);
    libxml_clear_errors();
    $xpathDOM = new DOMXPath($responseDom);

    #Title страницы получаем
    $titles = $xpathDOM->query("//title");
    #Проверяем на то что title такой какой как указан в файле
    if($titles[0]->nodeValue != $changeSEO["TITLE"]){
        print "Ошибка: " . $changeSEO["URL"] . " : Не совпадает TITLE с необходимым(Что должно быть/Что есть): " .
            $changeSEO["TITLE"] . "/" . $titles[0]->nodeValue ."\n";
    }
    #Meta description страницы получаем
    $metaDescriptions = $xpathDOM->query("//meta[@name='description']");
    #Проверяем на то что description такой какой как указан в файле
    if($metaDescriptions[0]->getAttribute('content') != $changeSEO["META DESCRIPTION"]){
        print "Ошибка: " . $changeSEO["URL"] . " : Не совпадает META DESCRIPTION с необходимым(Что должно быть/Что есть): " .
            $changeSEO["META DESCRIPTION"] . "/" . $metaDescriptions[0]->getAttribute('content') ."\n";
    }
}

curl_close($ch);

#Функция получения файла CSV
function fileCSVExists(){
    $options = getopt("fc:",[ paramFile . '::']);
    #Проверяем существование файла, если нет выходим
    if(!isset($options[paramFile]) or (!file_exists($options[paramFile]))){
        print "Ошибка: не найден csv файл.\n";
        exit;
    }
    #Парсим csv файл
    $csv = [];
    if (($handle = fopen($options[paramFile], "r")) !== FALSE) {
        fgetcsv($handle);
        $row = 0;
        while(! feof($handle))
        {
            $tempRow = fgetcsv($handle, 1000, ';');
            if($tempRow){
                $csv[$row]["URL"] = $tempRow[0];
                $csv[$row]["TITLE"] = $tempRow[1];
                $csv[$row]["META DESCRIPTION"] = $tempRow[2];
                $row++;
            }
        }
        fclose($handle);
    }

    #Если файл пустой
    if(count($csv) <= 0){
        print "Ошибка: csv файл пустой.\n";
        exit;
    }

    return $csv;
}

#Обработка http ответов
function errorCodeTranslator($errorStatusCode){
    switch($errorStatusCode) {
        case 401:
            $errorStatus="401: Login failure.  Try logging out and back in.  Password are ONLY used when posting.";
            break;
        case 400:
            $errorStatus="400: Invalid request.  You may have exceeded your rate limit.";
            break;
        case 404:
            $errorStatus="404: Not found.  This shouldn't happen.  Please let me know what happened using the feedback link above.";
            break;
        case 500:
            $errorStatus="500: Twitter servers replied with an error. Hopefully they'll be OK soon!";
            break;
        case 502:
            $errorStatus="502: Twitter servers may be down or being upgraded. Hopefully they'll be OK soon!";
            break;
        case 503:
            $errorStatus="503: Twitter service unavailable. Hopefully they'll be OK soon!";
            break;
        case 504:
            $errorStatus="504: Twitter service unavailable. Hopefully they'll be OK soon!";
            break;
        default:
            $errorStatus="Undocumented error: " . $errorStatusCode;
            break;
    }
    return $errorStatus;
}