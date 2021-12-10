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
    $filename = $this->generateFileName();

    // Check if file exists
    if (file_exists($filename)) {
      return true;
    }

    // Generate new file
    $this->handle = fopen($filename, "w");

    // Write header
    fputcsv($this->handle, self::HEADER_FIELDS, "|", '"');

    // Return
    return true;
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
