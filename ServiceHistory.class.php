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
 * This is a CARFAX Service History API wrapper class
 */
class ServiceHistory {
  /**
   * Service History API endpoint
   *
   * @var string
   */
  private static $endpoint = "https://servicesocket.carfax.com/data/1";

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
   * A Static function to Update the Product Data ID
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
   * A Static function to use cURL to make a request to the Service History API
   *
   * @param string $VIN
   * @return array [
   *  "Decode" => Array,
   *  "Overview" => Array,
   *  "History" => Array,
   * ]
   * @throws InvalidArgumentException
   * @throws UnexpectedValueException
   * @author Alec M.
   */
  public static function get(string $VIN) : array
  {
    // Validate the VIN
    if (!preg_match("/^[A-Z0-9]{17}$/", $VIN) || strlen($VIN) != 17) {
      throw new \InvalidArgumentException("Invalid VIN provided");
    }

    // Validate the Product Data ID
    if (self::$productDataId == "" || strlen(self::$productDataId) != 16) {
      throw new \UnexpectedValueException("Product Data ID not valid");
    }

    // Validate the Location ID
    if (self::$locationId == "" || strlen(self::$locationId) <= 1 || strlen(self::$locationId) > 50) {
      throw new \UnexpectedValueException("Location ID not valid");
    }

    // Submit the request
    $result = self::post([
      "productDataId" => self::$productDataId,
      "locationId" => self::$locationId,
      "vin" => $VIN,
    ]);
    $formatted_result = [
      "Decode" => [],
      "Overview" => [],
      "Records" => [],
    ];

    // Validate the result
    if (!$result || empty($result)) {
      return $formatted_result;
    }

    // Parse VIN Decode
    if (!empty($result["serviceHistory"])) {
      $formatted_result["Decode"]["VIN"] = $result["serviceHistory"]["vin"];
      $formatted_result["Decode"]["Year"] = $result["serviceHistory"]["year"];
      $formatted_result["Decode"]["Make"] = $result["serviceHistory"]["make"];
      $formatted_result["Decode"]["Model"] = $result["serviceHistory"]["model"];
      $formatted_result["Decode"]["Trim"] = $result["serviceHistory"]["bodyTypeDescription"] ?: "";
      $formatted_result["Decode"]["Driveline"] = $result["serviceHistory"]["driveline"] ?: "";
    }

    // Parse serviceCategories
    if (!empty($result["serviceHistory"]) && !empty($result["serviceHistory"]["serviceCategories"])) {
      foreach ($result["serviceHistory"]["serviceCategories"] as $category) {
        $formatted_result["Overview"][] = [
          "Name" => $category["serviceName"],
          "Date" => isset($category["dateOfLastService"]) ? $category["dateOfLastService"] : null,
          "Odometer" => isset($category["odometerOfLastService"]) ? intval(str_replace(",", "", $category["odometerOfLastService"])) : 0,
        ];
      }
    }

    // Parse displayRecords
    if (!empty($result["serviceHistory"]) && !empty($result["serviceHistory"]["displayRecords"])) {
      foreach ($result["serviceHistory"]["displayRecords"] as $record) {
        $formatted_result["Records"][] = [
          "Date" => $record["displayDate"] !== "Not Reported" ? $record["displayDate"] : null,
          "Odometer" => isset($record["odometer"]) ? intval(str_replace(",", "", $record["odometer"])) : 0,
          "Services" => is_array($record["text"]) ? $record["text"] : [],
          "Type" => $record["type"] === "service" ? "Service" : "Recall",
        ];
      }
    }

    // Return the formatted result
    return $formatted_result;
  }

  /**
   * A private function to submit a POST request to the Service History API
   *
   * @param array $fields
   * @return ?array $response
   * @throws None
   * @author Alec M.
   */
  private static function post(array $fields) : ?array
  {
    // Create a cURL handle
    $ch = curl_init();

    // Set the options
    curl_setopt($ch, CURLOPT_URL, self::$endpoint);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      "Content-Type: application/json",
      "Accept: application/json",
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    // Execute the request
    $data = null;
    $resp = curl_exec($ch);
    $errn = curl_error($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Validate the response
    if (!$resp || $errn || $status_code !== 200 || !($data = json_decode($resp, true))) {
      return null;
    }

    // Check for errorMessages
    if ($data["errorMessages"] && !empty($data["errorMessages"])) {
      return null;
    }

    // Check for serviceHistory
    if (!$data["serviceHistory"] || !is_array($data["serviceHistory"])) {
      return null;
    }

    // Return the parsed response
    return $data;
  }
}
