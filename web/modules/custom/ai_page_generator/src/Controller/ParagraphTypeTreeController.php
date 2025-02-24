<?php

namespace Drupal\ai_page_generator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ai_page_generator\ParagraphFieldManager;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for displaying the paragraph type tree.
 */
class ParagraphTypeTreeController extends ControllerBase {

  /**
   * The paragraph field manager.
   *
   * @var \Drupal\ai_page_generator\ParagraphFieldManager
   */
  protected $paragraphFieldManager;

  /**
   * Constructs a new ParagraphTypeTreeController object.
   *
   * @param \Drupal\ai_page_generator\ParagraphFieldManager $paragraph_field_manager
   *   The paragraph field manager.
   */
  public function __construct(ParagraphFieldManager $paragraph_field_manager) {
    $this->paragraphFieldManager = $paragraph_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ai_page_generator.paragraph_field_manager')
    );
  }

  /**
   * Displays the paragraph type tree.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the paragraph type tree.
   */
  public function display() {
    $paragraph_tree = $this->paragraphFieldManager->getAllParagraphTypesAndFields();
    return new JsonResponse($paragraph_tree);
  }

}
