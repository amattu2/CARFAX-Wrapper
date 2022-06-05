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
namespace CARFAX;

use DateTime;
use Exception;

class FileUploadedException extends Exception {}

/**
 * This is a CARFAX VHR service history FTP integration helper class
 */
class FTP {
  /**
   * VHR FTP host
   *
   * @var string
   */
  private const HOST = "data.carfax.com";

  /**
   * VHR FTP port
   *
   * @var int
   */
  private const PORT = 21;

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
    "MANAGEMENT_SYSTEM",
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
   * @var DateTime
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
   * @param string $username CARFAX FTP username
   * @param string $password CARFAX FTP password
   * @param string|null $partner_name CARFAX Partner Name
   * @param string|null $type Data Report Type ("HIST" or "PROD")
   * @param DateTime|null $date Data Report Date
   * @author Alec M.
   */
  public function __construct(string $username, string $password, string $partner_name = "", string $type = "PROD", DateTime $date = null)
  {
    // Set connection details
    $this->username = $username;
    $this->password = $password;
    $this->partner_name = $partner_name;

    // Set report type
    $this->type = $type === "HIST" ? "HIST" : "PROD";

    // Set report date
    $this->date = $date ?: new DateTime();
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

    // Check if ftp_connect is available
    if (!function_exists("ftp_connect")) {
      throw new Exception("FTP extension is not available on this server");
    }

    // Check if report file exists
    if (!file_exists(__DIR__ . "/" . $this->filename)) {
      return false;
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

    // Open FTP Connection and upload
    if (($ftp = @ftp_connect(FTP::HOST, FTP::PORT)) && @ftp_login($ftp, $this->username, $this->password)) {
      // Push file
      $status = ftp_put($ftp, $this->filename, __DIR__ . "/" . $this->filename, FTP_BINARY);

      // Close Connection
      $this->was_uploaded = $status;
      ftp_close($ftp);
    }

    // Return
    return $this->was_uploaded;
  }

  /**
   * Clean up the workspace by deleting the report file
   *
   * @return boolean
   * @author Alec M.
   */
  public function cleanUp() : bool
  {
    // Check to see if file is a file
    if (!is_file(__DIR__ . "/" . $this->filename)) {
      return false;
    }

    // Check to see if the file exists
    if (!file_exists(__DIR__ . "/" . $this->filename)) {
      return true;
    }

    // Delete the file
    if (unlink(__DIR__ . "/" . $this->filename)) {
      // Reset variables
      $this->total_lines = 0;
      $this->has_header = false;

      // Return
      return true;
    }

    // Default
    return false;
  }

  /**
   * Return the total Repair Orders written to the file
   *
   * @return int number of Repair Orders written
   * @author Alec M.
   */
  public function getTotalReports() : int
  {
    return $this->total_lines;
  }

  /**
   * Return full path to report file
   *
   * @return string|null full file path
   */
  public function getFilePath() : ?string
  {
    // Check if report file exists
    if (!file_exists(__DIR__ . "/" . $this->filename)) {
      return null;
    }

    // Default
    return __DIR__ . "/" . $this->filename;
  }

  /**
   * Get report file name
   *
   * @return string|null report file name
   * @author Alec M.
   */
  public function getFileName() : ?string
  {
    // Check if report file exists
    if (!file_exists(__DIR__ . "/" . $this->filename)) {
      return null;
    }

    // Default
    return $this->filename;
  }

  /**
   * Write Report File Header into File
   *
   * @return bool
   * @author Alec M.
   */
  private function writeHeader() : bool
  {
    // Generate filename
    if (!$this->filename) {
      $this->filename = $this->generateFilename();
    }

    // Check if file exists
    if (file_exists(__DIR__. "/" . $this->filename) && $this->has_header) {
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
   * @return string
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
