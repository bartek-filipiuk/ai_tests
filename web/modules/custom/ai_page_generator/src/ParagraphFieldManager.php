<?php

namespace Drupal\ai_page_generator;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\paragraphs\Entity\ParagraphsType;

/**
 * Service for managing paragraph fields.
 */
class ParagraphFieldManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new ParagraphFieldManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Gets field information for a paragraph type.
   *
   * @param string $paragraph_type
   *   The paragraph type.
   *
   * @return array
   *   An array of field information categorized by field type.
   */
  public function getParagraphFieldInfo($paragraph_type) {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('paragraph', $paragraph_type);

    $field_info = [
      'text' => [],
      'media' => [],
      'paragraph_reference' => [],
    ];

    foreach ($field_definitions as $field_name => $field_definition) {
      // Skip fields starting with 'parent_'
      if (strpos($field_name, 'parent_') === 0) {
        continue;
      }

      $field_type = $field_definition->getType();

      if (in_array($field_type, ['text', 'text_long', 'text_with_summary', 'string', 'string_long', 'list_string'])) {
        $field_info['text'][$field_name] = $field_definition;
      }
      elseif ($field_type === 'entity_reference' && $field_definition->getSetting('target_type') === 'media') {
        $field_info['media'][$field_name] = $field_definition;
      }
      elseif ($field_type === 'entity_reference_revisions' && $field_definition->getSetting('target_type') === 'paragraph') {
        $field_info['paragraph_reference'][$field_name] = $field_definition;
      }
    }

    return $field_info;
  }

  /**
   * Gets all paragraph types and their fields, including nested structures.
   *
   * @return array
   *   An array of paragraph types and their fields, including nested structures.
   */
  public function getAllParagraphTypesAndFields() {
    $paragraph_types = $this->entityTypeManager->getStorage('paragraphs_type')->loadMultiple();
    $paragraph_tree = [];

    foreach ($paragraph_types as $paragraph_type) {
      $type_id = $paragraph_type->id();
      $paragraph_tree[$type_id] = $this->buildParagraphTypeTree($type_id);
    }

    return $paragraph_tree;
  }

  /**
   * Builds a tree structure for a paragraph type, including nested paragraphs.
   *
   * @param string $type_id
   *   The paragraph type ID.
   * @param array $processed_types
   *   An array of already processed paragraph types to prevent infinite recursion.
   *
   * @return array
   *   A tree structure representing the paragraph type and its fields.
   */
  protected function buildParagraphTypeTree($type_id, array $processed_types = []) {
    if (in_array($type_id, $processed_types)) {
      return ['name' => $type_id . ' (recursive reference)'];
    }

    $processed_types[] = $type_id;
    $paragraph_type = ParagraphsType::load($type_id);
    $field_info = $this->getParagraphFieldInfo($type_id);

    $tree = [
      'name' => $paragraph_type->label(),
      'description' => $paragraph_type->getDescription(),
      'fields' => [
        'text' => array_keys($field_info['text']),
        'media' => array_keys($field_info['media']),
      ],
    ];

    if (!empty($field_info['paragraph_reference'])) {
      $tree['nested_paragraphs'] = [];
      foreach ($field_info['paragraph_reference'] as $field_name => $field_definition) {
        $handler_settings = $field_definition->getSetting('handler_settings');
        if (isset($handler_settings['target_bundles'])) {
          $tree['nested_paragraphs'][$field_name] = [];
          foreach ($handler_settings['target_bundles'] as $target_bundle) {
            $tree['nested_paragraphs'][$field_name][$target_bundle] = $this->buildParagraphTypeTree($target_bundle, $processed_types);
          }
        }
      }
    }

    return $tree;
  }

}
