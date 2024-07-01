<?php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure we are handling GET requests only
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Method Not Allowed"));
    exit;
}

// Validate and retrieve query parameter
if (!isset($_GET['visitor_name'])) {
    http_response_code(400); // Bad Request
    echo json_encode(array("error" => "Missing visitor_name parameter"));
    exit;
}

// Retrieve visitor_name from query parameters
$visitor_name = $_GET['visitor_name'];

// Function to convert IPv6 address to IPv4 using DNS lookup
function convertIPv6toIPv4($ipv6_address)
{
    // Use DNS lookup to find IPv4 address
    $ipv4_address = gethostbyname($ipv6_address);
    // Validate if the returned address is IPv4
    if (filter_var($ipv4_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return $ipv4_address;
    }
    return $ipv6_address; // Return original IPv6 if conversion fails
}

// Function to fetch location and temperature based on IP using ipinfo.io and OpenWeatherMap
function getLocationAndTemperature($visitor_name)
{
    // Get client IP address dynamically
    $client_ip = $_SERVER['REMOTE_ADDR'];

    // Check if client IP is IPv6 and convert it to IPv4 if necessary
    if (filter_var($client_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $client_ip = convertIPv6toIPv4($client_ip);
    }

    // Use your actual access token from ipinfo.io
    $access_token = "b76ab93bc02dbe";
    $ipinfo_url = "https://ipinfo.io/{$client_ip}/json?token={$access_token}";
    $ipinfo_response = @file_get_contents($ipinfo_url);

    if ($ipinfo_response === false) {
        return array(
            "client_ip" => $client_ip,
            "location" => "Unknown",
            "temperature" => "Unknown",
            "greeting" => "Hello, {$visitor_name}! The temperature is Unknown degrees Celsius in Unknown"
        );
    }

    $ipinfo_data = json_decode($ipinfo_response, true);
    $location = isset($ipinfo_data['city']) ? $ipinfo_data['city'] : "Unknown";

    // Use your actual API key from OpenWeatherMap
    $api_key = "c76317c9c51422efee5d9c371c0e27de";
    $weather_url = "https://api.openweathermap.org/data/2.5/weather?q={$location}&units=metric&appid={$api_key}";
    $weather_response = @file_get_contents($weather_url);

    if ($weather_response === false) {
        return array(
            "client_ip" => $client_ip,
            "location" => $location,
            "temperature" => "Unknown",
            "greeting" => "Hello, {$visitor_name}! The temperature is Unknown degrees Celsius in {$location}"
        );
    }

    $weather_data = json_decode($weather_response, true);
    $temperature = isset($weather_data['main']['temp']) ? $weather_data['main']['temp'] : "Unknown";

    return array(
        "client_ip" => $client_ip,
        "location" => $location,
        "temperature" => $temperature,
        "greeting" => "Hello, {$visitor_name}! The temperature is {$temperature} degrees Celsius in {$location}"
    );
}

// Fetch location and temperature
$response = getLocationAndTemperature($visitor_name);

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
