# Introduction
This is a CARFAX Vehicle History Reporting, QuickVIN, and Service History Check integration toolkit. It provides the interface to achieve the following results:
- Report your DMS/SMS vehicle repair ticket history
- Perform a QuickVIN search
- Perform a Search History Check

As achieved through proprietary APIs and integration procedures. Using this PHP based toolkit is not possible without an existing CARFAX Service Data Transfer Facilitation Agreement, and it relies on API keys that are not publicly subscribable (You need to work with someone in the CARFAX Partner Development department).

# Usage
## FTP
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

# Notes
N/A

# Requirements & Dependencies
N/A
