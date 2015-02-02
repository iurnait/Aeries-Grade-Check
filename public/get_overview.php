<?php

if (!isset($_POST['user_email']) || !isset($_POST['user_password'])) {
  die("Please enter email and password");
}

$email = $_POST['user_email'];
$password = $_POST['user_password'];

$data = array(
  'checkCookiesEnabled' => 'true',
  'checkSilverlightSupport' => 'false',
  'checkMobileDevice' => 'false',
  'checkStandaloneMode' => 'false',
  'checkTabletDevice' => 'false',
  'portalAccountUsername' => $email,
  'portalAccountPassword' => $password,
  'submit' => ''
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_URL, 'https://my.iusd.org/LoginParent.aspx?page=GradebookSummary.aspx');
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.93 Safari/537.36');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_COOKIE, 1);
curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , 0);
$data = curl_exec($ch);

if(!$data){
  die('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
}

$all_array = array();

$row_num = 1;
while (strpos($data, 'ctl00_MainContent_subGBS_DataDetails_ctl' . str_pad($row_num, 2, "0", STR_PAD_LEFT) . '_trGBKItem') > 0) {
  // The id of the table row
  $row = 'ctl00_MainContent_subGBS_DataDetails_ctl' . str_pad($row_num, 2, "0", STR_PAD_LEFT) . '_trGBKItem';

  // Extract the inner html of the table row
  $start_string = '<tr id="' . $row . '">';
  $end_string = '</tr>';
  $start_pos = strpos($data, $start_string) + strlen($start_string);
  $end_pos = strpos($data, $end_string, strpos($data, $end_string, $start_pos) + 1); // hack to escape nested table
  $row_html = substr($data, $start_pos, $end_pos - $start_pos);

  // Remove all tags inside this row except for the table data tags.
  // TODO: the title attribute of the span tag for the grade shows a more detailed grade. Figure out how to extract that.
  $row_html = strip_tags($row_html, '<td>');

  // Strip all of extra whitespacey stuff
  $row_html = preg_replace('/\s+/S', " ", $row_html);
  // Strip all of the html tags, replace with ;;; so that we can convert to array
  $row_html = preg_replace('/<[^>]+>/', ';;;', $row_html);

  // TODO: the above regex replacement inserts ;;; for every starting and closing tags, which means that half of the elements are empty.
  $row_array = explode(';;;', $row_html);

  $info = [
    "Name" => $row_array[5],
    "Term" => $row_array[7],
    "Period" => $row_array[9],
    "Teacher" => $row_array[11],
    "Percent" => $row_array[13],
    "Mark" => $row_array[17],
    "Missing Assignments" => $row_array[21],
    "Last Update" => $row_array[36]];

  $all_array[] = $info;

  $row_num++;
}

echo "<pre>";
echo json_encode($all_array, JSON_PRETTY_PRINT);
echo "</pre>";
