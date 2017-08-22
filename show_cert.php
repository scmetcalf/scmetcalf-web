<!DOCTYPE html>
<html>
<head>
<style>
table, th, td {
    border: 1px solid black;
}
</style>
</head>
<body>


<?php
$servername = "localhost";
$username = "root";
$password = "Imi.1246";
$dbname = "SSLChecker";




// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function parse($mystring, $findme, $end)
{
$pos = strpos($mystring, $findme);
$stop = strpos($mystring, $end);
$CN = substr($mystring,$pos+strlen($findme),$stop+strlen($mystring));
return $CN;
}

$site = $_GET["URL"];
#sanitize query
if (strpos("x".$site, 'http://') !== false) {
$site = "https://" . substr($site,7,strlen($site));
}
if (strpos("x".$site, 'https://') != true) {
$site = "https://" . substr($site,0,strlen($site));
}
#fix variable
$url = $site;

echo "Looking up certificate for $site"."<br>"."<br>";

$o_parse = parse_url($url, PHP_URL_HOST);
$get = stream_context_create(array("ssl" => array("capture_peer_cert" => TRUE)));
$read = stream_socket_client("ssl://".$o_parse.":443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $get);
$cert = stream_context_get_params($read);
$certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
$CN = parse($certinfo[name], "CN=", ".com"); 

echo "<br> Certificate Common name: $CN <br><br>";
echo "Subject Alternate Names: $certinfo[extentions]"."<br>";
$time_var = date_create( '@' .  $certinfo['validTo_time_t'])->format('c');
$full_date = substr($time_var,0,strpos($time_var,"T"));
#display date format
echo "Expires: $full_date<br><br>";

$sql = "insert into cert (url, cn, date) values ('$url', '$CN', '$full_date')";

if ($conn->query($sql) === TRUE) {
    echo "New record created successfully";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

echo "<br><br>Last 30 queries:<br>";
$sql = "SELECT url, cn, date FROM cert";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    echo "<table><tr><th>URL</th><th>Common Name</th><th>Expiration</th></tr>";
    // output data of each row
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>".$row["url"]."</td><td>".$row["cn"]."</td><td>".$row["date"]."</td></tr>";
    }
    echo "</table>";
} else {
    echo "0 results";
}




?>

