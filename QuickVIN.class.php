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

/**
 * This is a CARFAX QuickVIN API wrapper class
 */
class QuickVIN {
  /**
   * QuickVIN API endpoint
   *
   * @var string
   */
  private static $endpoint = "https://quickvin.carfax.com/1";

  /**
   * CARFAX provided Product Key
   *
   * @var string
   */
  private static $productDataId = "";

  /**
   * CARFAX provided Location ID
   *
   * @var string
   */
  private static $locationId = "";

  /**
   * A Static function to Update the Location ID
   *
   * @param string $locationId
   * @return void
   * @author Alec M.
   */
  public static function setLocationId(string $locationId) : void
  {
    self::$locationId = $locationId;
  }

  /**
   * A static function to Update the Product Data ID
   *
   * @param string $productDataId
   * @return void
   * @author Alec M.
   */
  public static function setProductDataId(string $productDataId) : void
  {
    self::$productDataId = $productDataId;
  }

  /**
   * Decode the Plate Number to a VIN
   *
   * @param string $plate The Plate Number
   * @param string $state The Plate State
   * @param string|null $VIN Optional VIN to decode
   * @return array
   * @throws TypeError
   * @throws InvalidArgumentException
   * @throws UnexpectedValueException
   * @author Alec M.
   */
  public static function decode(string $plate, string $state, ?string $VIN = null) : array
  {
    // Validate the data plate argument
    if (empty($plate) || strlen($plate) < 1 || strlen($plate) > 10) {
      throw new \InvalidArgumentException("Invalid Plate Number provided");
    }

    // Validate the state argument
    if (empty($state) || strlen($state) != 2) {
      throw new \InvalidArgumentException("Invalid Plate State provided");
    }

    // Validate the VIN argument if provided
    if ($VIN && !empty($VIN) && strlen($VIN) != 17) {
      throw new \InvalidArgumentException("Invalid VIN provided");
    }

    // Validate the Product Data ID
    if (empty(self::$productDataId) || strlen(self::$productDataId) != 16) {
      throw new \UnexpectedValueException("Product Data ID is not valid");
    }

    // Validate the Location ID
    if (empty(self::$locationId) || strlen(self::$locationId) <= 1 || strlen(self::$locationId) > 50) {
      throw new \UnexpectedValueException("Location ID is not valid");
    }

    // Make the request
    $data = self::post([
      "license-plate" => "![CDATA[" . $plate . "]]",
      "state" => "![CDATA[" . $state . "]]",
      "vin" => "![CDATA[" . $VIN . "]]",
      "product-data-id" => self::$productDataId,
      "location-id" => self::$locationId,
    ]);

    // TODO: Validate the response

    return $data ?? [];
  }

  /**
   * Send a POST request to the API endpoint
   *
   * @param string $name
   * @return ?array The API response
   * @throws TypeError
   * @author Alec M.
   */
  private static function post(array $data) : ?array
  {
    // Create a cURL handle
    $ch = curl_init();
    $xml = self::buildXML($data);

    // Set the options
    curl_setopt($ch, CURLOPT_URL, self::$endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      "Content-Type: text/xml",
      "Content-Length: " . strlen($xml)
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    // Execute the request
    $data = null;
    $resp = curl_exec($ch);
    $errn = curl_error($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // TODO: Validate the response
    echo $resp;

    return null; // TBD
  }

  /**
   * Generate XML for a API Request
   *
   * @param array $data [key => value, ...]
   * @throws TypeError
   * @return string
   */
  private static function buildXML(array $data) : string
  {
    // Build the XML Request
    $xml = new \SimpleXMLElement("<carfax-request></carfax-request>");

    // Add elements
    foreach ($data as $key => $value) {
      if (!$key || !$value) {
        continue;
      }

      $xml->addChild($key, $value);
    }

    // Return the XML
    return $xml->asXML();
  }
}
