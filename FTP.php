<?php
/*
  Produced 2021-2022
  By https://amattu.com/links/github
  Copy Alec M.
  License GNU Affero General Public License v3.0
*/

// Class Namespace
namespace amattu\CARFAX;

// Exceptions
class FileExistsException extends \Exception {}

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
    "ADDREES",
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
   * Class Constructor
   * 
   * @param string $username
   * @param string $password
   * @param ?string $type
   * @param ?DateTime $date
   * @throws None
   * @author Alec M.
   */
  public function __construct(string $username, string $password, string $type = "PROD", \DateTime $date = null)
  {
    // Set connection details
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
   * @param array $data
   * @param ?resource $handle
   * @return bool
   * @author Alec M.
   */
  public function write(array $data, $handle = null) : bool
  {
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
      if (!$this->handle && !($this->handle = fopen($this->filename, "a"))) {
        return false;
      }  
      
      // Update handle reference
      $usedHandle = false;
      $handle = $this->handle;
    }

    // Write the data to the file
    if (!fputcsv($handle, $data, "|", '"')) {
      return false;
    }

    // Close the file if we used our own handle
    if (!$usedHandle) {
      fclose($handle);
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
   * @throws None
   * @author Alec M.
   */
  public function writeAll(array $data) : int
  {
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
    if (!$this->handle && !($this->handle = fopen($this->filename, "a"))) {
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
    return $written;
  }

  /**
   * Write Report File Header into File
   * 
   * @param None
   * @return bool
   * @throws FileExistsException
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

    // Generate new file
    if (!($this->handle = fopen($this->filename, "w"))) {
      return false;
    };

    // Write header
    $this->has_header = fputcsv($this->handle, self::HEADER_FIELDS, "|", '"') > 0 ? true : false;

    // Return
    fclose($this->handle);    
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
