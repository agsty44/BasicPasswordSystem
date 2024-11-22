<?php
//Dashboard approval and page loading - PHP

$loginDetails = fopen("serverKeys.txt", "r");

$server = fgets($loginDetails);
$user = fgets($loginDetails);
$pass = fgets($loginDetails);
$database = fgets($loginDetails);

//Connect
$conn = new mysqli($server, $user, $pass, $database);

//Check
if ($conn->connect_error) {
    die("Connect failed: ". $conn->connect_error);
}

//If the cookie isn't present, send them to the login page and die.
if (!isset($_COOKIE["nameCookie"]) || !isset($_COOKIE["passCookie"])) {
    header("Location: ../HTML/login.php");
    die();
}

//Lets extract the cookie for easier reference
$nameCookie = $_COOKIE["nameCookie"];
$passCookie = $_COOKIE["passCookie"];

//We also need to cast the ID cookie to int.
$nameCookie = (int) $nameCookie;

//Prepare a statement that takes the password and ID from the cookie and get relevant details (just the name for now.)
$retrieveMatchingDetails = $conn->prepare("SELECT Name FROM TblCustomers WHERE UserID = ? AND Password = ?");

//Bind params.
$retrieveMatchingDetails->bind_param("is", $nameCookie, $passCookie);

//Check validity
if (!$retrieveMatchingDetails) {
    die("Query malformed: ".$conn->error);
}

//Lets grab that persons name so we can display it!
$retrieveMatchingDetails->execute();
$retrieveMatchingDetails->bind_result($fullName);
$retrieveMatchingDetails->fetch();
$retrieveMatchingDetails->close();

if ($fullName == "") { //No name returned, account doesn't exist. Return them to login.php after deleting cookies as cookies are clearly bad.
    setcookie("nameCookie", "", time() - 1);
    setcookie("passCookie", "", time() - 1); //Time() - 1 means delete cookie.
    header("Location: ../HTML/login.php");
}

include("../HTML/dashboard.html");
?>