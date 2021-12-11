# Introduction
This is a CARFAX Vehicle History Reporting, QuickVIN, and Service History Check integration toolkit. It provides the interface to achieve the following results:
- Report your DMS/SMS vehicle repair ticket history
- Perform a QuickVIN search
- Perform a Search History Check

As achieved through proprietary APIs and integration procedures. Using this PHP based toolkit is not possible without an existing CARFAX Service Data Transfer Facilitation Agreement, and it relies on API keys that are not publicly subscribable (You need to work with someone in the CARFAX Partner Development department).

P.S.,
[GitHub Copilot](https://copilot.github.com/) was partially used for development, which is the reason for the unnecessarily long and descriptive comments.

# Usage
## FTP
This is a helper class for reporting repair data to the CARFAX VHR system. It substantially eases the load required of a developer to implement CARFAX vehicle history reporting from a proprietary DMS/SMS system.

### constructor
#### Usage
Initialize the class component using the constructor

```PHP
$wrapper = new amattu\CARFAX\FTP();
```

#### PHPDoc
```PHP
/**
 * Class Constructor
 *
 * @param string $partner_name CARFAX Partner Name
 * @param string $username CARFAX FTP username
 * @param string $password CARFAX FTP password
 * @param ?string $type Data Report Type ("HIST" or "PROD")
 * @param ?DateTime $date Data Report Date
 * @throws None
 * @author Alec M.
 */
public function __construct(string $partner_name, string $username, string $password, string $type = "PROD", \DateTime $date = null);
```

___

### write
#### Usage
Write a single record to the export file.

```PHP
$success = $wrapper->write([
  /* See class HEADER_FIELDS for a list of fields that are REQUIRED */
]);
```

#### PHPDoc
```PHP
/**
 * Write a single Repair Order to the file
 *
 * @param array $data Repair Order data
 * @param ?resource $handle File handle
 * @return bool
 * @throws FileUploadedException
 * @author Alec M.
 */
public function write(array $data, $handle = null) : bool;
```

___

### writeAll
#### Usage
Write an array of repair orders to the report file.

```PHP
$successes = $wrapper->writeAll(
  [
    [
      /* Repair order record */
    ],
    [
      /* Repair order record */
    ],

    // ...
  ]
);
```

#### PHPDoc
```PHP
/**
 * Write all Repair Orders to the file
 *
 * @param array $data An array of Repair Orders
 * @return int number of Repair Orders written
 * @throws FileUploadedException
 * @author Alec M.
 */
public function writeAll(array $data) : int;
```

___

### upload
#### Usage
Submit the generated record to the CARFAX FTP endpoint.

```PHP
$wrapper->upload();
```

#### PHPDoc
```PHP
/**
 * Connect to the FTP server and upload the file
 *
 * @param None
 * @return bool
 * @throws FileUploadedException
 * @author Alec M.
 */
public function upload() : bool
```

___

## ServiceHistory
This is a entirely static class used to fetch repair history data from CARFAX by a vehicle VIN.

### setLocationId
#### Usage
Update the Location ID for the current *instance* of the class. This is provided by CARFAX at the time of account setup.

```PHP
amattu\CARFAX\ServiceHistory::setLocationId("exampleLOC");
```

#### PHPDoc
```PHP
/**
 * A Static function to Update the Location ID
 *
 * @param string $locationId
 * @return void
 * @author Alec M.
 */
public static function setLocationId(string $locationId) : void;
```

___

### setProductDataId
#### Usage
Update the Product Data ID for the current *instance* of the class. It is the equivelent of a API key, and is CARFAX defined at the time of account setup.

```PHP
amattu\CARFAX\ServiceHistory::setProductDataId("exampleProductDataId");
```

#### PHPDoc
```PHP
/**
 * A Static function to Update the Product Data ID
 *
 * @param string $productDataId
 * @return void
 * @author Alec M.
 */
public static function setProductDataId(string $productDataId) : void;
```

___

### get
#### Usage
This is the actual function exposed for fetching the vehicle history by VIN number. If you do not have the locationId or productDataId set, errors will be thrown. Everything else is error safe, including CARFAX API failures. The function will always return an array or throw an error.

If a record (Overview or History) does NOT have a valid:
- Odometer, it will be equal to `0`
- Date, it will be equal to `NULL`

```PHP
$data = amattu\CARFAX\ServiceHistory::get("1G1GCCBX3JX001788");
```

#### PHPDoc
```PHP
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
public static function get(string $VIN) : array;
```

#### Expected Output
*Abbreviated substantially*

```JSON
{
  "Decode": {
    "VIN": "1G1GCCBX4JX001298",
    "Year": "2011",
    "Make": "CADILLAC",
    "Model": "LUXURY",
    "Trim": "",
    "Driveline": ""
  },
  "Overview": [
    {
      "Name": "Tire rotation",
      "Date": "12/24/2013",
      "Odometer": 42185
    },
    {
      "Name": "Emissions test",
      "Date": "04/20/2021",
      "Odometer": 127005
    },
    {
      "Name": "Battery Replacement",
      "Date": "11/21/2019",
      "Odometer": 112682
    },
  ],
  "Records": [
    {
      "Date": "01/12/2011",
      "Odometer": 5,
      "Services": [
        "Vehicle serviced",
        "Pre-delivery inspection completed",
        "Window tint installed",
        "Vehicle washed/detailed",
        "Tire condition and pressure checked",
        "Nitrogen fill tires",
        "Anti-theft/keyless device/alarm installed",
        "Safety inspection performed"
      ],
      "Type": "Service"
    },
    {
      "Date": null,
      "Odometer": 92500,
      "Services": [
        "Title issued or updated",
        "Registration issued or renewed",
        "Passed safety inspection",
        "Vehicle color noted as Brown"
      ],
      "Type": "Service"
    },
    {
      "Date": "06/25/2021",
      "Odometer": 0,
      "Services": [
        "Manufacturer Safety recall issued",
        "NHTSA #21V573",
        "Recall #N213240870",
        "Status: Remedy Available"
      ],
      "Type": "Recall"
    }
  ]
}
```

# Requirements & Dependencies
N/A
