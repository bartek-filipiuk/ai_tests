<?php

namespace Drupal\receipt\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a block for uploading receipts.
 *
 * @Block(
 *   id = "receipt_upload_block",
 *   admin_label = @Translation("Receipt Upload Block"),
 *   category = @Translation("Receipt")
 * )
 */
class ReceiptUploadBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    
    // Attach our library for JS handling
    $build["#attached"]["library"][] = "receipt/receipt-upload";
    
    // Return the template with upload form
    $build["#theme"] = "receipt_upload_block";
    
    return $build;
  }

}
