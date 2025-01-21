<?php
require_once __DIR__ . '/vendor/autoload.php';

// Now you can use PHPMailer, Google API Client, and Dotenv directly
use PHPMailer\PHPMailer\PHPMailer;
use Google\Client;
use Dotenv\Dotenv;

// Example usage
$mail = new PHPMailer();
$client = new Client();
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Dependencies loaded successfully!";
?>
