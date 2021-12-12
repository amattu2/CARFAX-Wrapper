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

// Class Namespace
namespace amattu\CARFAX;

// Exceptions
class FileUploadedException extends \Exception {}

/**
 * This is a CARFAX VHR service history FTP integration helper class
 */
class FTP {
  /**
   * VHR FTP host
   *
   * @var string
   */
  private const HOST = "ftp://data.carfax.com";

  /**
   * Report File Header Fields
   *
   * @var array
   */
  private const HEADER_FIELDS = [
    "VIN",
    "RO_OPEN_DATE", /* MM/DD/YYYY */
    "RO_CLOSE_DATE", /* MM/DD/YYYY */
    "MILEAGE",
    "ODOMETER_MEASURE", /* MI OR KM */
    "RO_INVOICE_NUMBER",
    "SERVICE_DESCRIPTION",
    "LABOR_DESCRIPTION",
    "PART_NAME_DESCRIPTION",
    "PART_QUANTITY",
    "MAKE",
    "MODEL",
    "MODEL_YEAR",
    "PLATE",
    "PLATE_STATE",
    "MANAGEMENT_SYSTEM", /* TBD */
    "LOCATION_ID",
    "LOCATION_NAME",
    "ADDRESS",
    "CITY",
    "STATE",
    "POSTAL_CODE",
    "PHONE",
    "URL",
  ];

  /**
   * CARFAX Partner Name
   *
   * @var string
   */
  private $partner_name = "";

  /**
   * CARFAX FTP username
   *
   * @var string
   */
  private $username = "";

  /**
   * CARFAX FTP password
   *
   * @var string
   */
  private $password = "";

  /**
   * Data Report Type
   *
   * @var string ("HIST" or "PROD")
   */
  private $type = "PROD";

  /**
   * Data Report Date
   *
   * @var \DateTime
   */
  private $date = null;

  /**
   * Data Report File Handle
   *
   * @var resource
   */
  private $handle = null;

  /**
   * Data Report File Name
   *
   * @var string
   */
  private $filename = null;

  /**
   * Boolean Indicator of whether or not the file has the header
   *
   * @var bool
   */
  private $has_header = false;

  /**
   * Int Indicator of number of total Repair Order lines written
   *
   * @var int
   */
  private $total_lines = 0;

  /**
   * Boolean Indicator of whether or not the file has been uploaded
   * to the FTP server
   *
   * @var bool
   */
  private $was_uploaded = false;


  /**
   * Class Constructor
   *
   * @param string $partner_name CARFAX Partner Name
   * @param string $username CARFAX FTP username
   * @param string $password CARFAX FTP password
   * @param ?string $type Data Report Type ("HIST" or "PROD")
   * @param ?DateTime $date Data Report Date
   * @throws None
   * @author Alec M.
   */
  public function __construct(string $partner_name, string $username, string $password, string $type = "PROD", \DateTime $date = null)
  {
    // Set connection details
    $this->partner_name = $partner_name;
    $this->username = $username;
    $this->password = $password;

    // Set report type
    $this->type = $type === "HIST" ? "HIST" : "PROD";

    // Set report date
    $this->date = $date ?: new \DateTime();
  }

  /**
   * Write a single Repair Order to the file
   *
   * @param array $data Repair Order data
   * @param ?resource $handle File handle
   * @return bool
   * @throws FileUploadedException
   * @author Alec M.
   */
  public function write(array $data, $handle = null) : bool
  {
    // Check to see if the file was already uploaded
    if ($this->was_uploaded) {
      throw new FileUploadedException("The file has already been uploaded to the FTP server");
    }

    // Keep track of which handle was used
    $usedHandle = true;

    // Check to see if the data array has the correct number of fields
    if (count($data) !== 24) {
      return false;
    }

    // Check each HEADER_FIELDS field to see if it is set in data
    foreach (self::HEADER_FIELDS as $field) {
      if (!isset($data[$field])) {
        return false;
      }
    }

    // Check which handle to use
    if (!$handle) {
      // Check if the report file handle is set
      if (!$this->has_header && !$this->writeHeader()) {
        return false;
      }

      // Check to see if filename is set
      if (!$this->filename) {
        return false;
      }

      // Open the handle if it is not already open
      if (!$this->handle && !($this->handle = fopen(__DIR__ . "/" . $this->filename, "a"))) {
        return false;
      }

      // Update handle reference
      $usedHandle = false;
      $handle = $this->handle;
    }

    // Write the data to the file
    if (!fputs($handle, '"' . implode('"|"', $data) . '"' . "\n")) {
      return false;
    }

    // Close the file if we used our own handle
    if (!$usedHandle) {
      fclose($handle);
      $this->handle = null;
    }

    // Return
    $this->total_lines++;
    return true;
  }

  /**
   * Write all Repair Orders to the file
   *
   * @param array $data An array of Repair Orders
   * @return int number of Repair Orders written
   * @throws FileUploadedException
   * @author Alec M.
   */
  public function writeAll(array $data) : int
  {
    // Check to see if the file was already uploaded
    if ($this->was_uploaded) {
      throw new FileUploadedException("The file has already been uploaded to the FTP server");
    }

    // Keep track of how many were written
    $written = 0;

    // Check if the report file handle is set
    if (!$this->has_header && !$this->writeHeader()) {
      return 0;
    }

    // Check to see if filename is set
    // PS: This should never happen
    if (!$this->filename) {
      return 0;
    }

    // Open the handle if it is not already open
    if (!$this->handle && !($this->handle = fopen(__DIR__ . "/" . $this->filename, "a"))) {
      return 0;
    }

    // Iterate through data and call write()
    foreach ($data as $ro) {
      if ($this->write($ro, $this->handle)) {
        $written++;
      }
    }

    // Return write status
    fclose($this->handle);
    $this->handle = null;
    return $written;
  }

  /**
   * Connect to the FTP server and upload the file
   *
   * @param None
   * @return bool
   * @throws FileUploadedException
   * @author Alec M.
   */
  public function upload() : bool
  {
    // Check to see if the file was already uploaded
    if ($this->was_uploaded) {
      throw new FileUploadedException("The file has already been uploaded to the FTP server");
    }

    // Check to see if the header has been written
    if (!$this->has_header) {
      return false;
    }

    // Check to see if any Repair Orders have been written
    if ($this->total_lines <= 0) {
      return false;
    }

    // Validate FTP partner name
    if (!$this->partner_name) {
      return false;
    }

    // Validate FTP username and password
    if (!$this->username || !$this->password) {
      return false;
    }

    // Connect to FTP server with cURL
    $ch = curl_init();
    $this->handle = fopen(__DIR__ . "/" . $this->filename, "r");
    if ($this->handle && flock($this->handle, LOCK_EX)) {
      curl_setopt($ch, CURLOPT_URL, "ftp://" . SELF::HOST . "/" . $this->filename);
      curl_setopt($ch, CURLOPT_USERPWD, $this->username . ":" . $this->password);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
      curl_setopt($ch, CURLOPT_UPLOAD, 1);
      curl_setopt($ch, CURLOPT_INFILE, $this->handle);
      curl_setopt($ch, CURLOPT_INFILESIZE, filesize($this->filename));

      // Execute the request
      $exec = curl_exec($ch);
      $err = curl_error($ch);

      // Clean up
      curl_close($ch);
      flock($this->handle, LOCK_UN);
      $this->was_uploaded = $exec && !$err;
    }

    // Return
    fclose($this->handle);
    $this->handle = null;
    return $this->was_uploaded;
  }

  /**
   * Clean up the workspace by deleting the report file
   *
   * @param None
   * @return boolean
   * @throws None
   * @author Alec M.
   */
  public function cleanUp() : bool
  {
    // Check to see if the file exists
    if (!file_exists(__DIR__ . "/" . $this->filename)) {
      return true;
    }

    // Reset variables
    $this->total_lines = 0;
    $this->has_header = false;

    // Delete the file
    return unlink(__DIR__ . "/" . $this->filename);
  }

  /**
   * Return the total Repair Orders written to the file
   *
   * @param None
   * @return int number of Repair Orders written
   * @throws None
   * @author Alec M.
   */
  public function getTotalReports() : int
  {
    return $this->total_lines;
  }

  /**
   * Write Report File Header into File
   *
   * @param None
   * @return bool
   * @throws None
   * @author Alec M.
   */
  private function writeHeader() : bool
  {
    // Generate filename
    if (!$this->filename) {
      $this->filename = $this->generateFilename();
    }

    // Check if file exists
    if (file_exists($this->filename) && $this->has_header) {
      return true;
    }

    // Check if the file can be created
    if (!is_readable(__DIR__) || !is_writable(__DIR__)) {
      return false;
    }

    // Generate new file
    if (!($this->handle = fopen(__DIR__ . "/" . $this->filename, "w"))) {
      return false;
    };

    // Write header
    $this->has_header = fputs($this->handle, '"' . implode('"|"', SELF::HEADER_FIELDS) . '"' . "\n") > 0 ? true : false;

    // Return
    fclose($this->handle);
    $this->handle = null;
    return $this->has_header;
  }

  /**
   * Generate Report File Name using Date and Type
   *
   * Notes:
   *    (1) The file name is formatted as:
   *       <partner_name>_<type>_RO_<MMDDYYYY>.txt
   *       PartnerName_DataStatus_DataType_FileExportDate.txt
   *
   * @param None
   * @return string
   * @throws None
   * @author Alec M.
   */
  private function generateFileName() : string
  {
    // Return
    return sprintf("%s_%s_RO_%s%s%s.txt",
      $this->partner_name,
      $this->type,
      $this->date->format("m"),
      $this->date->format("d"),
      $this->date->format("Y")
    );
  }
}
