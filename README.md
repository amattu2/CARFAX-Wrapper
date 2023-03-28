# Introduction

This is a CARFAX Vehicle History Reporting, QuickVIN, and Service History Check integration toolkit. It provides the interface to achieve the following results:

- Report your DMS/SMS vehicle repair ticket history
- Perform a QuickVIN search
- Perform a Search History Check

As achieved through proprietary APIs and integration procedures. Using this PHP based toolkit is not possible without an existing CARFAX Service Data Transfer Facilitation Agreement, and it relies on API keys that are not publicly subscribable.

# Usage

## Install & Setup

```bash
composer require amattu2/carfax-wrapper
```

```php
require_once "vendor/autoload.php";

// see examples/..
```

> **Note**: For the examples in [/examples](./examples/), the [config.ini.example](./examples/config.ini.example) must be renamed to `config.ini` and updated with your CARFAX credentials.

___

## FTP

This is a helper class for reporting repair data to the CARFAX VHR system. It substantially eases the load required of a developer to implement CARFAX vehicle history reporting from a proprietary DMS/SMS system.

### constructor

Initialize the class component using the constructor

```PHP
$wrapper = new CARFAX\FTP();
```

```PHP
/**
 * Class Constructor
 *
 * @param string $username CARFAX FTP username
 * @param string $password CARFAX FTP password
 * @param ?string $partner_name CARFAX Partner Name
 * @param ?string $type Data Report Type ("HIST" or "PROD")
 * @param ?DateTime $date Data Report Date
 * @throws None
 * @author Alec M.
 */
public function __construct(string $username, string $password, string $partner_name = "", string $type = "PROD", \DateTime $date = null);
```

___

### write(array $data, $handle = null) : bool

Write a single record to the export file. Please Note: **This function DOES NOT validate field values. It only writes what was provided.** Your implementation of the class will need to validate Repair Order field values. **This ONLY ensures that the field is present in the array.**

```PHP
$success = $wrapper->write([
  /* See class HEADER_FIELDS for a list of fields that are REQUIRED */
]);
```

### writeAll(array $data) : int

Write an array of repair orders to the report file. This is an efficient wrapper to the `write()` method, and maintains a file handle at all times. If you are able to write a multitude of Repair Orders at a single time, use this. Please Note: **This function DOES NOT validate field values. It only writes what was provided.** Your implementation of the class will need to validate Repair Order field values. **This ONLY ensures that the field is present in the array.**

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

### upload() : bool

Submit the generated record to the CARFAX FTP endpoint.

```PHP
$wrapper->upload();
```

### cleanUp() : bool

This is an entirely optional function that will delete the Repair Order file from the local server. It should be called after uploading it to the FTP server.

```PHP
$isCleaned = $wrapper->cleanUp();
```

### getTotalRecords() : int

This returns the total number of repair orders written to the report file. Does not include the header line.

```PHP
$numRecords = $wrapper->getTotalRecords();
```

### getFilePath() : ?string

This returns the fully-qualified path to the report file if the file exists.

### getFileName() : ?string

This returns the filename of the report file if it exists.

___

## ServiceHistory

This is a entirely static class used to fetch repair history data from CARFAX by a vehicle VIN.

### setLocationId(string $locationId) : void

Update the Location ID for the current *instance* of the class. This is provided by CARFAX at the time of account setup.

```PHP
CARFAX\ServiceHistory::setLocationId("exampleLOC");
```

### setProductDataId(string $productDataId) : void

Update the Product Data ID for the current *instance* of the class. It is the equivelent of a API key, and is CARFAX defined at the time of account setup.

```PHP
CARFAX\ServiceHistory::setProductDataId("exampleProductDataId");
```

### get(string $VIN) : array

This is the actual function exposed for fetching the vehicle history by VIN number. If you do not have the locationId or productDataId set, errors will be thrown. Everything else is error safe, including CARFAX API failures. The function will always return an array or throw an error.

If a record (Overview or History) does NOT have a valid:

- Odometer, it will be equal to `0`
- Date, it will be equal to `NULL`

```PHP
$data = CARFAX\ServiceHistory::get("1G1GCCBX3JX001788");
```

<details>
  <summary>Abbreviated example response</summary>

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

</details>

> **Note**: See the examples in [servicehistory-get.php](./examples/servicehistory-get.php)

___

## QuickVIN

The QuickVIN Plus class is a wrapper for the CARFAX QuickVIN Plus decoder API. It turns a license plate + state into a VIN number with a VIN decode. It is a static class, and does not require instantiation.

### setLocationId(string $locationId): void

Update the Location ID for the current *instance* of the class. This is provided by CARFAX at the time of account setup.

```PHP
CARFAX\QuickVIN::setLocationId("exampleLOC");
```

### setProductDataId(string $productDataId): void

Update the Product Data ID for the current *instance* of the class. It is the equivelent of a API key, and is CARFAX defined at the time of account setup.

```PHP
CARFAX\QuickVIN::setProductDataId("exampleProductDataId");
```

### decode(string $plate, string $state, ?string $VIN = null): ?SimpleXMLElement

Perform a plate+state to VIN decode. If the decode is successful, a SimpleXMLElement object will be returned. If the decode is unsuccessful, `NULL` will be returned.

```PHP
$xml = CARFAX\QuickVIN::decode("HELLO", "VA");
```

> **Note**: See the examples in [quickvin-decode.php](./examples/quickvin-decode.php)

# Requirements & Dependencies

- PHP 7.4+
- SimpleXML
- cURL
