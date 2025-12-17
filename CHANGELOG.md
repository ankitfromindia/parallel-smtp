# Changelog

All notable changes to `parallel-smtp` will be documented in this file.

## 1.1.0 - 2025-12-17

### Added
- Min/max batch size validation for bulk operations
- Single email send method (`send()`) for individual emails
- Enhanced constructor with min/max batch size parameters
- Better error handling for batch size limits

### Changed
- Constructor now accepts `minBatchSize` and `maxBatchSize` parameters
- Improved bulk sending with proper batch size validation
- Enhanced error messages for batch size violations

### Fixed
- Proper handling of single vs bulk email operations
- Better resource management for different batch sizes

## 1.0.0 - 2025-12-17

### Added
- Initial release
- Parallel SMTP connections (up to 10 concurrent)
- Connection pooling and reuse (100 messages per connection)
- SMTP pipelining support
- Automatic resource management
- Laravel auto-discovery
- Comprehensive error handling
- Support for CC/BCC recipients
- HTML and text email content
- Enterprise-grade performance optimization
