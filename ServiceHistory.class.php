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
   * @return array
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

    // Create a cURL handle
    $ch = curl_init();

    // Set the options
    curl_setopt($ch, CURLOPT_URL, self::$endpoint);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      "Content-Type: application/json",
      "Accept: application/json",
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
      "productDataId" => self::$productDataId,
      "locationId" => self::$locationId,
      "vin" => $VIN,
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the request
    $data = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    // TODO: Validate the response
    // TODO: Parse and return the response

    return [];
  }
}
