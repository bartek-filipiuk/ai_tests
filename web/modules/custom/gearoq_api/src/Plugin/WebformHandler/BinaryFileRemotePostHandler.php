<?php

namespace Drupal\gearoq_api\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandler\RemotePostWebformHandler;
use Drupal\file\Entity\File;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Element\WebformMessage;
use GuzzleHttp\RequestOptions;
use Drupal\Core\Url;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Yaml\Yaml;
use GuzzleHttp\Psr7\Utils;

/**
 * Webform submission remote post handler with binary file support.
 *
 * @WebformHandler(
 *   id = "binary_remote_post",
 *   label = @Translation("Binary Remote post"),
 *   category = @Translation("External"),
 *   description = @Translation("Posts webform submissions to a URL with binary file data."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */
class BinaryFileRemotePostHandler extends RemotePostWebformHandler {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    // Always set file_data to TRUE and make it non-configurable
    $config['file_data'] = TRUE;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    
    // Remove the file_data checkbox as we always want to send file data
    if (isset($form['additional']['file_data'])) {
      unset($form['additional']['file_data']);
    }
    
    // Update the message about file uploads
    if ($this->getWebform()->hasManagedFile()) {
      $form['submission_data']['managed_file_message'] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t('Upload files will include the file\'s id, name, uri, and data in raw binary format.'),
        '#message_type' => 'warning',
        '#message_close' => TRUE,
        '#message_id' => 'webform_node.references',
        '#message_storage' => WebformMessage::STORAGE_SESSION,
      ];
      // Remove the no-data message since we always send data
      if (isset($form['submission_data']['managed_file_message_no_data'])) {
        unset($form['submission_data']['managed_file_message_no_data']);
      }
    }
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestFileData($fid, $prefix = '') {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::load($fid);
    if (!$file) {
      return [];
    }

    // Return only metadata for storage
    return [
      $prefix . 'id' => (int) $file->id(),
      $prefix . 'name' => $file->getFilename(),
      $prefix . 'uri' => $file->getFileUri(),
      $prefix . 'mime' => $file->getMimeType(),
      $prefix . 'uuid' => $file->uuid(),
      $prefix . 'size' => (int) $file->getSize(),
    ];
  }

  /**
   * Get the binary content of a file.
   *
   * @param int $fid
   *   The file ID.
   *
   * @return string|null
   *   The binary content of the file, or null if the file cannot be loaded.
   */
  protected function getFileBinaryContent($fid) {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::load($fid);
    if (!$file) {
      return null;
    }

    $uri = $file->getFileUri();
    if (!file_exists($uri)) {
      return null;
    }

    // Get the real file path
    $real_path = \Drupal::service('file_system')->realpath($uri);
    if (!$real_path) {
      return null;
    }

    // Read file in binary mode
    $handle = fopen($real_path, 'rb');
    if ($handle === false) {
      return null;
    }

    // Read the file in chunks
    $chunks = [];
    $chunk_size = 1024 * 1024; // 1MB chunks
    
    while (!feof($handle)) {
      $chunk = fread($handle, $chunk_size);
      if ($chunk === false) {
        fclose($handle);
        return null;
      }
      $chunks[] = $chunk;
    }
    
    fclose($handle);
    
    // Combine all chunks
    $binary_data = implode('', $chunks);
    
    // Verify the size matches
    if (strlen($binary_data) !== $file->getSize()) {
      \Drupal::logger('gearoq_api')->error('File size mismatch for file @fid. Expected: @expected, Got: @actual', [
        '@fid' => $fid,
        '@expected' => $file->getSize(),
        '@actual' => strlen($binary_data),
      ]);
      return null;
    }
    
    return $binary_data;
  }

  /**
   * {@inheritdoc}
   */
  protected function remotePost($state, WebformSubmissionInterface $webform_submission) {
    $state_url = $state . '_url';
    if (empty($this->configuration[$state_url])) {
      return;
    }

    $this->messageManager->setWebformSubmission($webform_submission);

    $request_url = $this->configuration[$state_url];
    $request_url = $this->replaceTokens($request_url, $webform_submission);
    $request_method = (!empty($this->configuration['method'])) ? $this->configuration['method'] : 'POST';

    // Get request options with tokens replaced.
    $request_options = (!empty($this->configuration['custom_options'])) ? Yaml::decode($this->configuration['custom_options']) : [];
    $request_options = $this->replaceTokens($request_options, $webform_submission);

    try {
      if ($request_method === 'GET') {
        // Append data as query string to the request URL.
        $query = $this->getRequestData($state, $webform_submission);
        $request_url = Url::fromUri($request_url, ['query' => $query])->toString();
        $response = $this->httpClient->get($request_url, $request_options);
      }
      else {
        $method = strtolower($request_method);
        
        // Get the request data
        $data = $this->getRequestData($state, $webform_submission);
        
        // Convert data to multipart format
        $multipart = [];
        
        // Process all form fields
        foreach ($data as $key => $value) {
          if (is_array($value) && isset($value['id'])) {
            // This is a file field, get the binary content
            $binary_content = $this->getFileBinaryContent($value['id']);
            if ($binary_content !== null) {
              $multipart[] = [
                'name' => $key,
                'filename' => $value['name'],
                'contents' => $binary_content,
                'headers' => [
                  'Content-Type' => $value['mime'],
                  'Content-Length' => (string) strlen($binary_content),
                ],
              ];
              // Add file metadata as separate fields
              foreach ($value as $meta_key => $meta_value) {
                $multipart[] = [
                  'name' => $key . '_' . $meta_key,
                  'contents' => is_array($meta_value) ? json_encode($meta_value) : (string) $meta_value,
                ];
              }
            }
            else {
              \Drupal::logger('gearoq_api')->error('Failed to read binary content for file @fid', [
                '@fid' => $value['id'],
              ]);
            }
          }
          else {
            // Regular form field
            if (is_array($value)) {
              // For array values, create separate fields for each element
              $this->addArrayFieldsToMultipart($multipart, $key, $value);
            } else {
              $multipart[] = [
                'name' => $key,
                'contents' => (string) $value,
              ];
            }
          }
        }
        
        $request_options[RequestOptions::MULTIPART] = $multipart;
        
        $response = $this->httpClient->$method($request_url, $request_options);
      }
    }
    catch (RequestException $request_exception) {
      $response = $request_exception->getResponse();

      // Encode HTML entities to prevent broken markup from breaking the page.
      $message = $request_exception->getMessage();
      $message = nl2br(htmlentities($message));

      $this->handleError($state, $message, $request_url, $request_method, 'multipart', $request_options, $response);
      return;
    }

    // Display submission exception if response code is not 2xx.
    if ($this->responseHasError($response)) {
      $t_args = ['@status_code' => $this->getStatusCode($response)];
      $message = $this->t('Remote post request return @status_code status code.', $t_args);
      $this->handleError($state, $message, $request_url, $request_method, 'multipart', $request_options, $response);
      return;
    }
    else {
      $this->displayCustomResponseMessage($response, FALSE);
    }

    // If debugging is enabled, display the request and response.
    $this->debug($this->t('Remote post successful!'), $state, $request_url, $request_method, 'multipart', $request_options, $response, 'warning');

    // Replace [webform:handler] tokens in submission data.
    $submission_data = $webform_submission->getData();
    $submission_has_token = (strpos(print_r($submission_data, TRUE), '[webform:handler:' . $this->getHandlerId() . ':') !== FALSE) ? TRUE : FALSE;
    if ($submission_has_token) {
      $response_data = $this->getResponseData($response);
      $token_data = ['webform_handler' => [$this->getHandlerId() => [$state => $response_data]]];
      $submission_data = $this->replaceTokens($submission_data, $webform_submission, $token_data);
      $webform_submission->setData($submission_data);
      // Resave changes to the submission data without invoking any hooks
      // or handlers.
      if ($this->isResultsEnabled()) {
        $webform_submission->resave();
      }
    }
  }

  /**
   * Recursively adds array fields to multipart data.
   *
   * @param array &$multipart
   *   The multipart array to add fields to.
   * @param string $key_prefix
   *   The prefix for the field name.
   * @param array $array_value
   *   The array value to process.
   */
  protected function addArrayFieldsToMultipart(array &$multipart, $key_prefix, array $array_value) {
    foreach ($array_value as $key => $value) {
      $field_key = $key_prefix . '_' . $key;
      if (is_array($value)) {
        // Recursively process nested arrays
        $this->addArrayFieldsToMultipart($multipart, $field_key, $value);
      } else {
        $multipart[] = [
          'name' => $field_key,
          'contents' => (string) $value,
        ];
      }
    }
  }

}
