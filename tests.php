<?php
/**
 * This is a basic test file for demonstrating the usage of the FTP, QuickVIN, and ServiceHistory classes.
*/

require(__DIR__ . "/FTP.class.php");

$ftpWrapper = new amattu\CARFAX\FTP("examplePTNER", "username", "password");

$ftpWrapper->write([
    "VIN" => "1G1GCCBX3JX001788",
    "RO_OPEN_DATE" => "01/01/2017",
    "RO_CLOSE_DATE" => "01/01/2017",
    "MILEAGE" => 198301,
    "ODOMETER_MEASURE" => "MI",
    "RO_INVOICE_NUMBER" => "123456789",
    "SERVICE_DESCRIPTION" => "TEST DESC",
    "LABOR_DESCRIPTION" => "TEST LABOR",
    "PART_NAME_DESCRIPTION" => "TEST PART",
    "PART_QUANTITY" => 1,
    "MAKE" => "HONDA",
    "MODEL" => "CIVIC",
    "MODEL_YEAR" => 2015,
    "PLATE" => "8CY8CZZ",
    "PLATE_STATE" => "CA",
    "MANAGEMENT_SYSTEM" => "examplePTNER",
    "LOCATION_ID" => "exampleLOC",
    "LOCATION_NAME" => "Example Location, LLC",
    "ADDRESS" => "123 Main St",
    "CITY" => "San Francisco",
    "STATE" => "CA",
    "POSTAL_CODE" => "94105",
    "PHONE" => "415-555-1212",
    "URL" => "http://example.com",
]);
$ftpWrapper->write([
    "VIN" => "2G1GCCBX3JX001788",
    "RO_OPEN_DATE" => "01/01/2017",
    "RO_CLOSE_DATE" => "01/01/2017",
    "MILEAGE" => 198301,
    "ODOMETER_MEASURE" => "MI",
    "RO_INVOICE_NUMBER" => "123456789",
    "SERVICE_DESCRIPTION" => "TEST DESC",
    "LABOR_DESCRIPTION" => "TEST LABOR",
    "PART_NAME_DESCRIPTION" => "TEST PART",
    "PART_QUANTITY" => 1,
    "MAKE" => "HONDA",
    "MODEL" => "CIVIC",
    "MODEL_YEAR" => 2015,
    "PLATE" => "8CY8CZZ",
    "PLATE_STATE" => "CA",
    "MANAGEMENT_SYSTEM" => "examplePTNER",
    "LOCATION_ID" => "exampleLOC",
    "LOCATION_NAME" => "Example Location, LLC",
    "ADDRESS" => "123 Main St",
    "CITY" => "San Francisco",
    "STATE" => "CA",
    "POSTAL_CODE" => "94105",
    "PHONE" => "415-555-1212",
    "URL" => "http://example.com",
]);

//require(__DIR__ . "/QuickVIN.php");
//require(__DIR__ . "/ServiceHistory.php");