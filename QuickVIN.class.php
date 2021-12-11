<?php
/*
  Produced 2021-2022
  By https://amattu.com/links/github
  Copy Alec M.
  License GNU Affero General Public License v3.0
*/

// Class Namespace
namespace amattu\CARFAX;

// Exception Classes
class UnknownHTTPException extends \Exception {}

/**
 * This is a CARFAX QuickVIN API wrapper class
 */
class QuickVIN {
  /**
   * QuickVIN API endpoint
   * 
   * @var string
   */
  private $endpoint = "https://quickvin.carfax.com/1";    
}
