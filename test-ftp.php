<?php
/*
 * Produced: Thu Jan 06 2022
 * Author: Alec M.
 * GitHub: https://amattu.com/links/github
 * Copyright: (C) 2022 Alec M.
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
 * Use this file to test your FTP credentials using the same connection
 * method that the CARFAX FTP helper class uses
 */

// Parse config details
$config = parse_ini_file(__DIR__ . '/config.ini');

// Connect to the FTP server
$ftp = ftp_connect("data.carfax.com", 21);
echo $ftp ? "connected" : "not conn";

// Login to the FTP server
echo ftp_login($ftp, $config['FTP_USERNAME'], $config['FTP_PASSWORD']) ? "logged in" : "not";

// Disconnect
ftp_close($ftp);
