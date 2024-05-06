# PHDE
### PHDE is a PHP Development Environment utility class.
PHDE provides methods for managing PHP error reporting, setting HTTP response headers, and handling file downloads.

## Usage
### Creating PHDE Instance
To create a PHDE instance and manage error reporting:
```
$phde = new PHDE();
$phde->yes(); // Enable error reporting
$phde->no(); // Disable error reporting
```
You can also use static methods:
```
PHDE::yes(); // Enable error reporting
PHDE::no(); // Disable error reporting
```

### Setting HTTP Response Headers
To set HTTP response headers for API responses or file downloads:
```
PHDE::api(); // Set headers for API responses
```
```
PHDE::file('example.txt', 100); // Set headers for file downloads
```
