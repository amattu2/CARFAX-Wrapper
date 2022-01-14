<?php
/*
 * Produced: Fri Jan 07 2022
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

// Files
require(__DIR__ ."/../FTP.class.php");

// Variables
$config = parse_ini_file(__DIR__ . '/config.ini');
$con = new mysqli($config['DB_HOST'], $config['DB_USER'], $config['DB_PASS'], $config['DB_NAME']);
$wrapper = new amattu\CARFAX\FTP(
	$config["CF_PARTNER"],
	$config["FTP_USERNAME"],
	$config["FTP_PASSWORD"],
);
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
$rows = [];
$written = 0;
$tickets = [];

// Fetch Tickets
if ($con->select_db("udb_1") && $result = $con->query($query)) {
  echo "MySQL query is successful<br><br>";

  // Iterate through line items
  while ($row = $result->fetch_assoc()) {
    // Check for invalid VIN
    if ($row["VIN"] == 0 || strlen($row["VIN"]) != 17) {
      continue;
    }

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
    unset($row['LINE_DESC']);
    unset($row['LINE_QUANTITY']);
    unset($row['LINE_TYPE']);

    // Push RO line
    $rows[] = $row;
  }
  $result->close();
}

// Write Repair Orders
if (!empty($rows)) {
  echo "There are " . count($rows) . " rows to write.<br><br>";

  // Find Unique Tickets
  $tickets = array_intersect_key($rows, array_unique(array_column($rows, 'RO_INVOICE_NUMBER')));

  // Write Tickets
  array_walk($rows, function(&$a) { unset($a['ACCOUNT_ID']); });
  $written = $wrapper->writeAll($rows);
  unset($rows);

  // Output Unique Ticket details
  echo "There were " . count($tickets) . " unique tickets.<br><br> Eg.<code>";
  echo "<pre>";
  print_r($tickets[0]);
  echo "</pre></code><br><br>";
} else {
  echo "There are <b>no</b> rows to write.<br><br>";
}

// Upload Repair Orders
if ($written > 0) {
  echo "Uploading the file to the FTP server<br><br>";
  // TODO: Enable this
  $success = 1; //$wrapper->upload() ? 1 : 0;

  echo "Uploading the file was " . ($success ? "successful" : "unsuccessful") . "<br><br>";
} else {
  echo "There are <b>no</b> rows to upload to the server.<br><br>";
}

// Update Tickets
if ($success && $written > 0) {
  echo "Updating ticket CF_Exported columns<br><br>";

  // Set Exported Flag
  if ($con->select_db("udb_1") && $stmt = $con->prepare("UPDATE Invoices SET Updated = Updated, CF_Exported = 1 WHERE EstNum = ? AND AccountID = ? AND Deleted = 0 LIMIT 1")) {
    $EstNum = 0;
    $AccountID = 0;
    $suc = 0;
    $stmt->bind_param("ii", $EstNum, $AccountID);
    foreach ($tickets as $ticket) {
      $EstNum = $ticket['RO_INVOICE_NUMBER'];
      $AccountID = $ticket['ACCOUNT_ID'];
      $suc += $stmt->execute();
    }
    $stmt->close();
    echo "Updated $suc tickets successfully.<br><br>";
  }

  // Insert Log
  if ($con->select_db("logs") && $stmt = $con->prepare("INSERT INTO TicketExports (EstNum, isCARFAX, AccountID) VALUES (?, 1, ?)")) {
    $EstNum = 0;
    $AccountID = 0;
    $suc = 0;
    $stmt->bind_param("ii", $EstNum, $AccountID);
    foreach ($tickets as $ticket) {
      $EstNum = $ticket['RO_INVOICE_NUMBER'];
      $AccountID = $ticket['ACCOUNT_ID'];
      $suc += $stmt->execute();
    }
    $stmt->close();
    echo "Inserted $suc ticket logs successfully.<br><br>";
  }
} else {
  echo "There are <b>no</b> tickets to update.<br><br>";
}

// Logs
if ($con->select_db("logs") && $stmt = $con->prepare("INSERT INTO Cron (Task, Success) VALUES (?, ?)")) {
  echo "Updated Cron table indicating a final status of $success.<br><br>";
	$stmt->bind_param("si", $fileName, $success);
	$stmt->execute();
	$stmt->close();
}

// Delete file, close connection
//$wrapper->cleanUp();
$con->close();
