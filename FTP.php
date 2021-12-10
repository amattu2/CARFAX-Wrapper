<?php
/*
  Produced 2021-2022
  By https://amattu.com/links/github
  Copy Alec M.
  License GNU Affero General Public License v3.0
*/

// Class Namespace
namespace amattu\CARFAX;

/**
 * This is a CARFAX VHR service history FTP integration helper class
 */
class FTP {
  /**
   * VHR FTP host
   * 
   * @var string
   */
  private static $host = "data.carfax.com";

  /**
   * CARFAX FTP username
   * 
   * @var string
   */
  private static $username = "";

  /**
   * CARFAX FTP password
   * 
   * @var string
   */
  private static $password = "";

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
  private static $date;

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
}
