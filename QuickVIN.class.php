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
   * @author Alec M.
   */
  public static function decode(string $plate, string $state, ?string $VIN) : array
  {
    return [];
  }
}
