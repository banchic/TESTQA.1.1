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

#Обработка http ответов, взял с вики
function errorCodeTranslator($errorStatusCode){
    switch($errorStatusCode) {
        case 400:
            $errorStatus="400 Bad Request — сервер обнаружил в запросе клиента синтаксическую ошибку.";
            break;
        case 401:
            $errorStatus="401 Unauthorized — для доступа к запрашиваемому ресурсу требуется аутентификация.";
            break;
        case 403:
            $errorStatus="403 Forbidden — сервер понял запрос, но он отказывается его выполнять из-за ограничений в доступе для клиента к указанному ресурсу.";
            break;
        case 404:
            $errorStatus="404: Not found. Не существует такой страницы.";
            break;
        case 500:
            $errorStatus="500 Internal Server Error — любая внутренняя ошибка сервера, которая не входит в рамки остальных ошибок класса. ";
            break;
        case 502:
            $errorStatus="502 Bad Gateway — сервер, выступая в роли шлюза или прокси-сервера, получил недействительное ответное сообщение от вышестоящего сервера.";
            break;
        case 503:
            $errorStatus="503 Service Unavailable — сервер временно не имеет возможности обрабатывать запросы по техническим причинам (обслуживание, перегрузка и прочее).";
            break;
        case 504:
            $errorStatus="504 Gateway Timeout — сервер в роли шлюза или прокси-сервера не дождался ответа от вышестоящего сервера для завершения текущего запроса. ";
            break;
        default:
            $errorStatus="Недокументированная ошибка: " . $errorStatusCode;
            break;
    }
    return $errorStatus;
}