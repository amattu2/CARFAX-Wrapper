<?php
/*
 * Produced: Fri Jan 14 2022
 * Author: Alec M.
 * GitHub: https://amattu.com/links/github
 * Copyright: (C) 2022 Alec M.
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
 * The purpose of this file is to demonstrate the difference between ftp-cronjob.php and a
 * profiled version of the same task. This script is approximately 33% more memory-efficient than the latter.
 *
 * It writes to the FTP RO file during the query loop, and only stores the unique tickets for the purpose
 * of logging
 */

// Files
require(__DIR__ ."/../FTP.class.php");

prof_flag("Start");

// Variables
$config = parse_ini_file(__DIR__ . '/config.ini');
$con = new mysqli($config['DB_HOST'], $config['DB_USER'], $config['DB_PASS'], $config['DB_NAME']);
$wrapper = new amattu\CARFAX\FTP($config["CF_PARTNER"], $config["FTP_USERNAME"], $config["FTP_PASSWORD"]);
$fileName = basename(__FILE__);
$query = "SELECT
    c.VIN AS VIN,
    DATE_FORMAT(a.EstDate, '%m/%d/%Y') AS RO_OPEN_DATE,
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
    e.AccountID as 'ACCOUNT_ID',
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
    LEFT JOIN application.Accounts e ON a.AccountID = e.AccountID
  WHERE a.CF_Exported = 0
    AND a.Private = 0
    AND c.Private = 0
    AND d.Private = 0
    AND a.Deleted = 0
    AND c.Deleted = 0
    AND d.Active = 1
    AND e.Deleted = 0
    AND a.Mileage > 0
    AND a.Total > 0
    AND a.TicketType = 'Invoice'
    AND a.Updated < DATE_SUB(NOW(), INTERVAL 1 WEEK)
  ORDER BY a.EstNum ASC
";
$success = 0;
$tickets = [];

prof_flag("Variables Defined");

// Fetch Tickets
if ($con->select_db("udb_1") && $result = $con->query($query)) {
  prof_flag("Query Success | About to iterate through results");

  // Iterate through line items
  while ($row = $result->fetch_assoc()) {
    // Check for invalid VIN
    if ($row["VIN"] == 0 || strlen($row["VIN"]) != 17) {
      continue;
    }

    // Exported ticket log
    $tickets[$row["RO_INVOICE_NUMBER"]] = $row["ACCOUNT_ID"];

    // Update MANAGEMENT_SYSTEM field
    $row['MANAGEMENT_SYSTEM'] = $config["CF_MANAGEMENT_SYSTEM"];

    // Update unique LOCATION_ID field
    $row['LOCATION_ID'] = $config["CF_MANAGEMENT_SYSTEM"] . "_" . $row["ACCOUNT_ID"];

    // Update LINE_TYPE, PART_NAME_DESCRIPTION, PART_QUANTITY, and LABOR_DESCRIPTION fields
    if ($row['LINE_TYPE'] === 'Labor') {
      $row['SERVICE_DESCRIPTION'] = $row['LINE_DESC'];
    } else {
      $row['PART_NAME_DESCRIPTION'] = $row['LINE_DESC'];
      $row['PART_QUANTITY'] = $row['LINE_QUANTITY'];
    }

    // Delete unexported fields
    unset($row['LINE_DESC']);
    unset($row['LINE_QUANTITY']);
    unset($row['LINE_TYPE']);
    unset($row['ACCOUNT_ID']);

    // Write to file
    $wrapper->write($row);
  }
  $result->close();
  prof_flag("Query Closed | Iterated through rows and wrote to file");
}

// Write Repair Orders
if (!empty($tickets) && $wrapper->getTotalReports() > 0) {
  prof_flag("Isolated Tickets | Found ". count($tickets) ." unique tickets");
}

prof_flag("Cleanup | About to close the connection and wrapper handle");

// Delete file, close connection
//$wrapper->cleanUp();
$con->close();

prof_flag("End | Closed connection and deleted wrapper");
prof_print();

// Call this at each point of interest, passing a descriptive string
function prof_flag($str) {
    global $prof_timing, $prof_names, $prof_memory;
    $prof_timing[] = microtime(true);
    $prof_names[] = $str;
    $prof_memory[] = memory_get_usage();
}

// Call this when you're done and want to see the results
function prof_print() {
  global $prof_timing, $prof_names, $prof_memory;
  $size = count($prof_timing);
  for ($i=0; $i < $size - 1; $i++) {
    echo "<b>{$prof_names[$i]}</b><br>";
    echo sprintf("&nbsp;&nbsp;&nbsp;%f<br>", $prof_timing[$i+1]-$prof_timing[$i]);
    echo sprintf("&nbsp;&nbsp;&nbsp;%s<br>", formatBytes($prof_memory[$i]));
  }
  echo "<b>{$prof_names[$size-1]}</b><br>";
  echo "<b>Script peak usage: ". formatBytes(memory_get_peak_usage()) ."</b>";
}

function formatBytes($bytes, $precision = 2) {
  $units = array('B', 'KB', 'MB', 'GB', 'TB');

  $bytes = max($bytes, 0);
  $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
  $pow = min($pow, count($units) - 1);

  // Uncomment one of the following alternatives
  $bytes /= pow(1024, $pow);
  // $bytes /= (1 << (10 * $pow));

  return round($bytes, $precision) . ' ' . $units[$pow];
}