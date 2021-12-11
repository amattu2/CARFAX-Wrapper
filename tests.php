<?php
/*
 * Produced: Sat Dec 11 2021
 * Author: Alec M.
 * GitHub: https://amattu.com/links/github
 * Copyright: (C) 2021 Alec M.
 * License: License GNU Affero General Public License v3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * This is a basic test file for demonstrating the usage of the FTP, QuickVIN, and ServiceHistory classes.
*/

// Config .ini file for testing purposes
$conf = parse_ini_file('config.ini');

// FTP Helper Examples
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

// Service History Examples
require(__DIR__ . "/ServiceHistory.class.php");

// Configure the Service History class
amattu\CARFAX\ServiceHistory::setLocationId($conf['SH_LOCATIONID']);
amattu\CARFAX\ServiceHistory::setProductDataId($conf['SH_PRODUCTDATAID']);

$data = amattu\CARFAX\ServiceHistory::get("1G1GCCBX3JX001788");

print_r($data);

//require(__DIR__ . "/QuickVIN.php");