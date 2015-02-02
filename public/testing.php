<?php

// https://gist.github.com/GloryFish/1045396
/**
 * Formats a JSON string for pretty printing
 *
 * @param string $json The JSON to make pretty
 * @param bool $html Insert nonbreaking spaces and <br />s for tabs and linebreaks
 * @return string The prettified output
 * @author Jay Roberts
 */
function _format_json($json, $html = false) {
    $tabcount = 0;
    $result = '';
    $inquote = false;
    $ignorenext = false;

    if ($html) {
        $tab = "&nbsp;&nbsp;&nbsp;";
        $newline = "<br/>";
    } else {
        $tab = "\t";
        $newline = "\n";
    }

    for($i = 0; $i < strlen($json); $i++) {
        $char = $json[$i];

        if ($ignorenext) {
            $result .= $char;
            $ignorenext = false;
        } else {
            switch($char) {
                case '{':
                    $tabcount++;
                    $result .= $char . $newline . str_repeat($tab, $tabcount);
                    break;
                case '}':
                    $tabcount--;
                    $result = trim($result) . $newline . str_repeat($tab, $tabcount) . $char;
                    break;
                case ',':
                    $result .= $char . $newline . str_repeat($tab, $tabcount);
                    break;
                case '"':
                    $inquote = !$inquote;
                    $result .= $char;
                    break;
                case '\\':
                    if ($inquote) $ignorenext = true;
                    $result .= $char;
                    break;
                default:
                    $result .= $char;
            }
        }
    }

    return $result;
}

if (!isset($_POST['user_email']) || !isset($_POST['user_password'])) {
    die("Please enter email and password");
}

/**** Login ****/
$email = $_POST['user_email'];
$password = $_POST['user_password'];

$data = array(
    'checkCookiesEnabled' => 'true',
    'checkSilverlightSupport' => 'false',
    'checkMobileDevice' => 'true',
    'checkStandaloneMode' => 'false',
    'checkTabletDevice' => 'false',
    'portalAccountUsername' => $email,
    'portalAccountPassword' => $password,
    'submit' => ''
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_URL, 'https://my.iusd.org/LoginParent.aspx');
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.93 Safari/537.36');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_COOKIE, 1);
curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , 0);
$data = curl_exec($ch);

$urls = array('classes' => 'https://my.iusd.org/m/api/MobileWebAPI.asmx/GetGradebookNames',
              'summary' => 'https://my.iusd.org/m/api/MobileWebAPI.asmx/GetGradebookSummaryData',
              'initialize' => 'https://my.iusd.org/m/api/MobileWebAPI.asmx/InitializeApplication',
              'class_summary' => 'https://my.iusd.org/m/api/MobileWebAPI.asmx/GetGradebookDetailedSummaryData',
              'class_detail' => 'https://my.iusd.org/m/api/MobileWebAPI.asmx/GetGradebookDetailsData'
);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_URL, $urls[$_POST['type']]);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.93 Safari/537.36');

$header = Array("Accept: application/json, text/javascript, */*; q=0.01",
    "Accept-Encoding:gzip, deflate, sdch",
    "Accept-Language:en-US,en;q=0.8",
    "Connection:keep-alive",
    "Content-Type:application/json; charset=utf-8",
    "Host:my.iusd.org",
    "Referer:https://my.iusd.org/m/loginparent.html",
    "X-Requested-With:XMLHttpRequest"
);

if ($_POST['type'] == 'class_summary') {
    curl_setopt($ch, CURLOPT_POST, 1);
    $payload = json_encode(array("gradebookNumber" => $_POST['gradebookNumber']));
//    $header[] = 'Content-Length: ' . strlen($payload);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
} elseif ($_POST['type'] == 'class_detail') {
    curl_setopt($ch, CURLOPT_POST, 1);
    $payload = json_encode(array("gradebookNumber" => $_POST['gradebookNumber'],
                                "requestedPage" => 1,  // TODO: not sure what these two actually do.
                                "pageSize" => 12));
//    $header[] = 'Content-Length: ' . strlen($payload);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
} else {
    curl_setopt($ch, CURLOPT_POST, 0);

}

curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
curl_setopt($ch, CURLOPT_COOKIE, 1);
curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , 1);
$data = curl_exec($ch);

echo "<pre>";
echo _format_json($data);
echo "</pre>";