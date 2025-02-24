<?php

namespace Drupal\receipt\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;

/**
 * Controller for handling receipt processing.
 */
class ReceiptController extends ControllerBase {

  /**
   * Processes receipt data from make.com webhook.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function processReceipt(Request $request) {
    $content = json_decode($request->getContent(), TRUE);
    
    // Validate incoming data
    if (!$this->validateReceiptData($content)) {
      return new JsonResponse([
        "error" => "Invalid receipt data"
      ], 400);
    }
    
    try {
      // Create new receipt node
      $node = Node::create([
        "type" => "receipt",
        "title" => "Paragon z " . $content["date"],
        "field_date" => $content["date"],
        "field_total_amount" => $content["amount"],
        "field_products" => $content["products"],
        "uid" => \Drupal::currentUser()->id(),
      ]);
      
      $node->save();
      
      return new JsonResponse([
        "message" => "Receipt processed successfully",
        "nid" => $node->id()
      ]);
      
    } catch (\Exception $e) {
      \Drupal::logger("receipt")->error("Error processing receipt: @error", [
        "@error" => $e->getMessage()
      ]);
      
      return new JsonResponse([
        "error" => "Internal server error"
      ], 500);
    }
  }
  
  /**
   * Validates the receipt data from make.com.
   *
   * @param array|null $data
   *   The data to validate.
   *
   * @return bool
   *   Whether the data is valid.
   */
  private function validateReceiptData($data) {
    if (!is_array($data)) {
      return FALSE;
    }
    
    // Check required fields
    $required = ["date", "amount", "products"];
    foreach ($required as $field) {
      if (!isset($data[$field])) {
        return FALSE;
      }
    }
    
    // Validate date format
    if (!\DateTime::createFromFormat("Y-m-d", $data["date"])) {
      return FALSE;
    }
    
    // Validate amount
    if (!is_numeric($data["amount"]) || $data["amount"] <= 0) {
      return FALSE;
    }
    
    // Validate products array
    if (!is_array($data["products"]) || empty($data["products"])) {
      return FALSE;
    }
    
    return TRUE;
  }

}
