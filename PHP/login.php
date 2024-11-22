<?php
//Login system - PHP

$loginDetails = fopen("serverKeys.txt", "r");

$server = fgets($loginDetails);
$user = fgets($loginDetails);
$pass = fgets($loginDetails);
$database = fgets($loginDetails);

//Encryption key - this MUST stay in PHP! Do not use in HTML!
$encryptkey = fgets($loginDetails);

//Connect
$conn = new mysqli($server, $user, $pass, $database);

//Check
if ($conn->connect_error) {
    die("Connect failed: ". $conn->connect_error);
}

//If the cookie is present, send them to the dashboard page and die.
if (isset($_COOKIE["nameCookie"]) && isset($_COOKIE["passCookie"])) {
    header("Location: dashboard.php");
    die();
}

//ABOVE THIS LINE IS THE SIGN(ed) IN FEATURE (COOKIES INEXPIRED/EXISTENT)
//BELOW THIS LINE IS THE SIGNIN FEATURE (COOKIES EXPIRED/NON EXISTENT)

//Check for a login attempt.
if(!isset($_POST["userID"]) || !isset($_POST["password"])) {
    include("../HTML/login.html");
    die();
}

//Collect post.
$userID = $_POST["userID"];
$userPass = $_POST["password"];

//Sanitise
function sanitiseStringData($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

//Now lets rectify our data by calling sanitiseStringData for all data, and running int on the userID:
$userPass = sanitiseStringData($userPass);
$userID = (int) $userID;

//Now we will encrypt the user password before committing it.
//First, the necessary variables (keys, methods, etc)

//Cipher method:
$ciphering = "AES-128-CTR";

//Use OpenSSl Encryption method:
$iv_length = openssl_cipher_iv_length($ciphering);
$options = 0;

//Non-NULL Initialization Vector for encryption
$encryption_iv = '1234567891011121';

//Actually encrypt the string now:
$userPass = openssl_encrypt($userPass, $ciphering, $encryptkey, $options, $encryption_iv);

//Prepare a query to retrieve the password from the SQL table
$retrievePassword = $conn->prepare("SELECT Password FROM TblCustomers WHERE UserID = ?");
$retrievePassword->bind_param("i", $userID);

//Check for malform
if(!$retrievePassword) {
    die("Query malformed: ".$conn->error);
}

//Retrieve
$retrievePassword->execute();
$retrievePassword->bind_result($passwordSignature);
$retrievePassword->fetch();
$retrievePassword->close();

if ($passwordSignature == $userPass) { //Password good, assign cookies and redirect to dashboard.
    setcookie("nameCookie", $userID, time() + (86400 *7), "/");
    setcookie("passCookie", $userPass, time() + (86400 * 7), "/"); //86400 = 1 day, * 7 = 1 week
    header("Location: ../HTML/dashboard.php");
    die();
} else { //Password is bad, redirect to the login failure page.
    header("Location: ../HTML/loginfail.html");
    die();
}
?>