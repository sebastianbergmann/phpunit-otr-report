# Changes in phpunit/otr-report

All notable changes in phpunit/otr-report are documented in this file using the [Keep a CHANGELOG](https://keepachangelog.com/) principles.

## [1.0.2] - 2026-11-02

### Changed

* Make PHAR build reproducible across filesystems (files are now added to the PHAR sorted by their path instead of in the order in which the filesystem lists them)
* The Software Bill of Materials (SBOM) embedded in the PHAR now uses CycloneDX 1.7 and provides the NTIA minimum elements as well as the data fields that BSI TR-03183-2 (version 2.1.0) requires for logical and identified components, including component creators, original, distribution and effective licences, source code URIs, and the PHP runtime and its extensions as external components
* A signed SBOM that includes the filename and the SHA-512 hash of the PHAR is now published alongside the PHAR as `otr-report-X.Y.Z.phar.cdx.xml`

## [1.0.1] - 2026-06-02

### Fixed

* Columns in "slowest" output are not correctly aligned

## [1.0.0] - 2026-06-02

Initial release

[1.0.2]: https://github.com/sebastianbergmann/phpunit-otr-report/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/sebastianbergmann/phpunit-otr-report/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/sebastianbergmann/phpunit-otr-report/compare/72e207ff51ca0e5f174d4cc1971d50edfe59b721...1.0.0
