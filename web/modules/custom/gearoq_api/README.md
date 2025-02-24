## INTRODUCTION

The Gearoq API module provides a custom JSON:API endpoint for creating social content propositions in Drupal 10 using PHP 8.3.

The primary use case for this module is:

- Creating social content propositions via an external API
- Testing the API endpoint through a custom form
- Allowing the 'api' user to create content through the API
- Handling binary file uploads through webforms

## REQUIREMENTS

This module requires the following modules:

- REST (core)
- Basic Auth (core)
- Webform

## INSTALLATION

1. Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/node/895232 for further information.
2. Enable the Gearoq API module.

## CONFIGURATION

1. Create a user with the username 'api' and set its password to '123'.
2. Grant the necessary permissions to the 'api' user role.
3. Enable the JSON:API module and configure it to accept all operations.
4. Enable the Basic Auth module.

## USAGE

To test the API endpoint, visit /admin/config/services/gearoq-api/test and use the provided form.

To use the API programmatically, send a POST request to /api/create/social-proposition with the following JSON structure:

## WEBHOOK TESTING

The module includes a webhook testing form that allows you to:
- Specify a webhook URL endpoint
- Send test messages with different statuses
- Monitor the webhook response

To access the webhook test form, visit `/admin/config/services/gearoq-api/webhook-test`.

## BINARY FILE UPLOAD HANDLER

The module includes a custom webform handler (`BinaryFileRemotePostHandler`) that enables sending file attachments in raw binary format to remote endpoints.

### Features

- Sends files in their original binary format without base64 encoding
- Supports large files through chunk-based reading
- Verifies file integrity through size checks
- Sends file metadata as separate fields
- Handles complex form data by splitting arrays into individual fields

### Usage

1. Create a webform with file upload fields
2. Add the "Binary Remote post" handler to your webform
3. Configure the handler with your endpoint URL and other settings

### Handler Configuration

The handler supports the following settings:
- Remote endpoint URL
- HTTP method (POST, PUT, etc.)
- Custom headers and options

### Data Format

The handler sends data in the following format:

1. File Fields:
   ```
   field_name: [binary content]
   field_name_id: [file ID]
   field_name_name: [filename]
   field_name_mime: [MIME type]
   field_name_size: [file size]
   field_name_uri: [file URI]
   field_name_uuid: [file UUID]
   ```

2. Array/Compound Fields:
   Instead of sending JSON, arrays are split into separate fields:
   ```
   contact_name: John
   contact_email: john@example.com
   address_street: 123 Main St
   address_city_name: New York
   ```

### Error Handling

The handler includes comprehensive error logging:
- File reading errors
- Size mismatch warnings
- Transmission failures
- HTTP response errors

Errors are logged to Drupal's system log and can be viewed at `/admin/reports/dblog`.

### Security Considerations

- Files are read securely using Drupal's file system service
- Binary data is transmitted using multipart/form-data
- File integrity is verified before transmission
- All data is properly sanitized and validated

### Example Configuration

1. Add the handler to your webform:
   - Go to your webform's settings
   - Click on "Emails / Handlers"
   - Click "Add handler"
   - Select "Binary Remote post"

2. Configure the handler:
   - Set the endpoint URL
   - Choose the HTTP method
   - Add any required headers
   - Save the handler

3. Test the form:
   - Submit the form with a file attachment
   - Check the logs for any errors
   - Verify the file was received correctly at the endpoint

Example webhook payload:
