# Parse Voyager Page Map

This is an independent Composer example for parsing a `VPM/1` document with
[`emanuelecoppola/phpeg`](https://packagist.org/packages/emanuelecoppola/phpeg).
It has no dependency on the parent Voyager Page Map library.

The CleanPeg grammar is stored in `voyager-page-map.peg`. By default the runner
parses the included `turin-weekend-guide.vpm` fixture.

```bash
composer install
composer parse
```

Pass another VPM file to parse it instead:

```bash
php parse-voyager-page-map.php /path/to/document.vpm
```
