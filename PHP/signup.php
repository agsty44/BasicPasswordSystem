<?php
//Signup system - PHP

$loginDetails = fopen("serverKeys.txt", "r");

$server = fgets($loginDetails);
$user = fgets($loginDetails);
$pass = fgets($loginDetails);
$database = fgets($loginDetails);

//Encryption key - this MUST stay in PHP! Do not use in HTML!
$encryptkey = fgets($loginDetails);

//Kill login file
fclose($loginDetails);

//Connect
$conn = new mysqli($server, $user, $pass, $database);

//Check
if ($conn->connect_error) {
    die("Connect failed: ". $conn->connect_error);
}

//Check POST existence
if (!isset($_POST["name"]) || !isset($_POST["userID"]) || !isset($_POST["password"])) {
    include("../HTML/signup.html");
    die();
}

//Collect post.
$name = $_POST["name"];
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
$name = sanitiseStringData($name);
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

//Check availability of UserID
$userIDCheck = $conn->prepare("SELECT COUNT(*) FROM TblCustomers WHERE UserID = ?");
$userIDCheck->bind_param("i", $userID);

if(!$userIDCheck) {
    die("Query malformed: ". $conn->error);
}

$userIDCheck->execute();
$userIDCheck->bind_result($idPresence);
$userIDCheck->fetch();
$userIDCheck->close();

if ($idPresence != 0) {
    die("User ID already in use.");
}

$newCustomerAccount = $conn->prepare("INSERT INTO TblCustomers (Name, Password, UserID) VALUES (?, ?, ?)");
$newCustomerAccount->bind_param("ssi", $name, $userPass, $userID);

if (!$newCustomerAccount) {
    die("Query malformed: ". $conn->error);
}

if (!$newCustomerAccount->execute()) {
    die("Query failed: ".$newCustomerAccount->error);
} else {
    echo "Account created.";
}

$newCustomerAccount->close();
$conn->close();

include("../HTML/signup.html");
?>