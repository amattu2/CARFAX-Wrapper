<?php
/*
 * Produced: Sun Dec 12 2021
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
  * This is an example of running a MySQLi query to build the FTP file.
  *
  * Note: Obviously this changes depending on your SQL database structuring.
  *
  * Don't run this on a production server. All validation is ommited.
  */

/**
 * Based on simple principles, these are the conditions to exporting a ticket:
 * - The customer is not set to Private (Export disabled)
 * - The vehicle is not set to Private (Export disabled)
 * - The ticket is not set to Private (Export disabled)
 * - The ticket has mileage > 0
 * - The ticket has a cost > 0
 * - The ticket is an Invoice not an Estimate
 *
 * This does not include basics like the ticket date is valid / set
 */

// Include the FTP class
require(__DIR__ . '/FTP.class.php');

// Pull the database credentials from the config file
$config = parse_ini_file(__DIR__ . '/config.ini');

// Initialize the MySQLi connection using config credentials
$mysqli = new mysqli($config['DB_HOST'], $config['DB_USER'], $config['DB_PASS'], $config['DB_NAME']);

// Ensure the connection is working
if ($mysqli->connect_errno) {
  echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
  die();
}

// The query to get the tickets
$query = "SELECT
    c.VIN AS VIN,
    DATE_FORMAT(a.Created, '%m/%d/%Y') AS RO_OPEN_DATE,
    DATE_FORMAT(a.EstDate, '%m/%d/%Y') AS RO_CLOSE_DATE,
    a.Mileage AS MILEAGE,
    'MI' as ODOMETER_MEASURE,
    a.EstNum AS RO_INVOICE_NUMBER,
    'TO BE DONE' AS SERVICE_DESCRIPTION,
    'TO BE DONE' AS LABOR_DESCRIPTION,
    'TO BE DONE' AS PART_NAME_DESCRIPTION,
    'TO BE DONE' AS PART_QUANTITY,
    c.Make AS MAKE,
    c.Model AS MODEL,
    c.ModYear AS MODEL_YEAR,
    '' AS PLATE,
    '' AS PLATE_STATE,
    'TO BE DETERMINED' AS MANAGEMENT_SYSTEM,
    'TO BE DONE' AS LOCATION_ID,
    'TO BE DONE' AS LOCATION_NAME,
    'TO BE DONE' AS ADDRESS,
    'TO BE DONE' AS CITY,
    'TO BE DONE' AS STATE,
    'TO BE DONE' AS POSTAL_CODE,
    'TO BE DONE' AS PHONE,
    'TO BE DONE' AS URL
  FROM Invoices a
    LEFT JOIN InvoiceItems b ON a.EstNum = b.EstNum
    LEFT JOIN Vehicles c ON c.CarId = a.CarId
    LEFT JOIN Customers d ON c.CusId = d.CusId
  WHERE a.Private = 0
    AND c.Private = 0
    AND d.Private = 0
    AND a.Mileage > 0
    AND a.Total > 0
    AND a.TicketType = 'Invoice'
    AND c.VIN != ''
  LIMIT 0, 20
";

// Run the query
$result = $mysqli->query($query);
$count = $result->num_rows;

if (!$result) {
  echo "Failed to run query: (" . $mysqli->errno . ") " . $mysqli->error;
  die();
}
if ($count <= 100) {
  // View the results
  echo 'Printing out: ' . $count . ' rows<br>';
  echo '<pre>';
  while($row = $result->fetch_assoc()) {
    print_r($row);
  }
  echo '</pre>';
} else {
  echo 'Too many rows to print out. Please limit to 100 rows. ';
  echo "Found $count lines/tickets to export.\n";
}

$result->close();

// TBD: Write to FTP file