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

/**
 * This is a basic test file for demonstrating the usage of the ServiceHistory class.
*/

// Config .ini file for testing purposes
$conf = parse_ini_file('config.ini');

// Required file
require(__DIR__ . "/ServiceHistory.class.php");

// Configure the Service History class
amattu\CARFAX\ServiceHistory::setLocationId($conf['SH_LOCATIONID']);
amattu\CARFAX\ServiceHistory::setProductDataId($conf['SH_PRODUCTDATAID']);

// Basic example
echo "<pre>";
print_r(amattu\CARFAX\ServiceHistory::get("1G6DF577080179400"));
echo "</pre>";