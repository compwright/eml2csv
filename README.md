# eml2csv

A script to extract contact information from a directory of .eml files

Extracts the name, email, address, and phone number from the email from header, subject, and signature at the end of the body.

## Requirements

Requires [Composer](https://getcomposer.org/). Once installed, run `composer install` to download the project dependencies.

## Usage

```
$ php eml2csv.php /path/to/directory > output.csv
```

## License

MIT License