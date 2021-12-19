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


 /**** FILE SETUP ****/
 /**** FILE SETUP ****/
 /**** FILE SETUP ****/

// Include the FTP class
require(__DIR__ . '/FTP.class.php');

// Pull the database credentials from the config file
$config = parse_ini_file(__DIR__ . '/config.ini');

// Initialize the MySQLi connection using config credentials
$mysqli = new mysqli($config['DB_HOST'], $config['DB_USER'], $config['DB_PASS'], $config['DB_NAME']);

// Initialize the FTP wrapper using config credentials
$ftpWrapper = new amattu\CARFAX\FTP($config["CF_PARTNER"], $config["FTP_USERNAME"], $config["FTP_PASSWORD"]);

// Ensure the connection is working
if ($mysqli->connect_errno) {
  echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
  die();
}

/**** MYSQLI DATA EXTRACTION ****/
/**** MYSQLI DATA EXTRACTION ****/
/**** MYSQLI DATA EXTRACTION ****/

// The query to get the tickets
// TODO: Change this to match your database structure
$query = "SELECT
    c.VIN AS VIN,
    DATE_FORMAT(a.Created, '%m/%d/%Y') AS RO_OPEN_DATE,
    DATE_FORMAT(a.EstDate, '%m/%d/%Y') AS RO_CLOSE_DATE,
    a.Mileage AS MILEAGE,
    'MI' as ODOMETER_MEASURE,
    a.EstNum AS RO_INVOICE_NUMBER,
    '' AS SERVICE_DESCRIPTION,
    '' AS LABOR_DESCRIPTION,
    '' AS PART_NAME_DESCRIPTION,
    '' AS PART_QUANTITY,
    b.JobDesc AS LINE_DESC,
    b.Quantity AS LINE_QUANTITY,
    b.InvType AS LINE_TYPE,
    c.Make AS MAKE,
    c.Model AS MODEL,
    c.ModYear AS MODEL_YEAR,
    '' AS PLATE,
    '' AS PLATE_STATE,
    'NA' AS MANAGEMENT_SYSTEM,
    'NA' AS LOCATION_ID,
    e.Name AS LOCATION_NAME,
    e.Street AS ADDRESS,
    e.City AS CITY,
    e.State AS STATE,
    e.Zip AS POSTAL_CODE,
    '' AS PHONE,
    '' AS URL
  FROM Invoices a
    LEFT JOIN InvoiceItems b ON a.EstNum = b.EstNum
    LEFT JOIN Vehicles c ON c.CarId = a.CarId
    LEFT JOIN Customers d ON c.CusId = d.CusId
    LEFT JOIN Accounts e ON a.AccountID = e.AccountID
  WHERE a.Private = 0
    AND c.Private = 0
    AND d.Private = 0
    AND a.Deleted = 0
    AND c.Deleted = 0
    AND d.Active = 1
    AND e.Deleted = 0
    AND a.Mileage > 0
    AND a.Total > 0
    AND a.TicketType = 'Invoice'
    AND CHAR_LENGTH(c.VIN) = 17
  ORDER BY a.EstNum ASC
  LIMIT 310000, 125
";

// Run the query
$result = $mysqli->query($query);
$rows = [];
if ($result) {
  $numRows = $result->num_rows;

  echo "Selected <b>$numRows</b> repair orders to be exported<br><br>";

  // Append the rows to an array
  while($row = $result->fetch_assoc()) {
    // Dynamically adjust values
    $row['MANAGEMENT_SYSTEM'] = $config['CF_MANAGEMENT_SYSTEM'];
    $row['LOCATION_ID'] = $config["CF_LOCATIONID"];
    if ($row['LINE_TYPE'] === 'Labor') {
      $row['SERVICE_DESCRIPTION'] = $row['LINE_DESC'];
    } else {
      $row['PART_NAME_DESCRIPTION'] = $row['LINE_DESC'];
      $row['PART_QUANTITY'] = $row['LINE_QUANTITY'];
    }
    unset($row['LINE_DESC']);
    unset($row['LINE_QUANTITY']);
    unset($row['LINE_TYPE']);

    $rows[] = $row;
  }

  // Close the MySQLi connection
  $mysqli->close();
} else {
  echo "Failed to run query: (" . $mysqli->errno . ") " . $mysqli->error;
  die();
}

/**** WRITE REPAIR ORDERS ****/
/**** WRITE REPAIR ORDERS ****/
/**** WRITE REPAIR ORDERS ****/

// Write all Repair Orders to the file
$written = $ftpWrapper->writeAll($rows);
if ($written > 0) {
  echo "Successfully wrote <b>$written</b> records to file<br><br>";
  unset($rows);
} else {
  echo "Failed to write any records to file";
  die();
}

// Upload the file
// TODO: Uncomment the FTP call to upload the file
$uploaded = false; //$ftpWrapper->upload();
if ($uploaded) {
  echo "Successfully uploaded file to FTP server<br><br>";
} else {
  echo "Failed to upload file to FTP server";
  die();
}

// This will delete the file that we just made
$ftpWrapper->cleanUp();