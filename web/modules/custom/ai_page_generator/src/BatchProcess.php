<?php

namespace Drupal\ai_page_generator;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\media\Entity\Media;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides methods for batch processing of page generation.
 */
class BatchProcess implements ContainerInjectionInterface {

  use DependencySerializationTrait;

  /**
   * The paragraph field manager.
   *
   * @var \Drupal\ai_page_generator\ParagraphFieldManager
   */
  protected $paragraphFieldManager;

  /**
   * Constructs a new BatchProcess object.
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
   * Creates a single page based on the provided data.
   *
   * @param array $page_data
   *   The data for the page to be created.
   * @param array $context
   *   The batch context array.
   */
  public static function createPage(array $page_data, array &$context) {
    $container = \Drupal::getContainer();
    $batch_process = static::create($container);
    $batch_process->doCreatePage($page_data, $context);
  }

  /**
   * Actually creates the page.
   *
   * @param array $page_data
   *   The data for the page to be created.
   * @param array $context
   *   The batch context array.
   */
  protected function doCreatePage(array $page_data, array &$context) {
    $node = Node::create([
      'type' => $page_data['content_type'],
      'title' => $page_data['title'],
      'path' => ['alias' => $page_data['url']],
    ]);

    if (isset($page_data['fields']['field_page_section'])) {
      $paragraphs = [];
      foreach ($page_data['fields']['field_page_section'] as $section) {
        $paragraph = $this->createParagraph($section);
        if ($paragraph) {
          $paragraphs[] = $paragraph;
        }
      }
      $node->set('field_page_section', $paragraphs);
    }

    $node->save();

    $context['results'][] = $node->id();
    $context['message'] = t('Created page "@title"', ['@title' => $node->getTitle()]);
  }

  /**
   * Creates a paragraph based on the provided data.
   *
   * @param array $section
   *   The data for the paragraph to be created.
   *
   * @return \Drupal\paragraphs\Entity\Paragraph|null
   *   The created paragraph entity or null if creation failed.
   */
  protected function createParagraph(array $section) {
    $paragraph_type = $section['type'];
    $field_info = $this->paragraphFieldManager->getParagraphFieldInfo($paragraph_type);

    $paragraph_data = [
      'type' => $paragraph_type,
    ];

    foreach ($section['fields'] as $field_name => $field_value) {
      if (isset($field_info['text'][$field_name])) {
        $paragraph_data[$field_name] = $field_value;
      }
      elseif (isset($field_info['media'][$field_name])) {
        if (is_array($field_value)) {
          $media_items = [];
          foreach ($field_value as $media_id) {
            $media = Media::load($media_id);
            if ($media) {
              $media_items[] = $media;
            }
          }
          $paragraph_data[$field_name] = $media_items;
        }
        else {
          $media = Media::load($field_value);
          if ($media) {
            $paragraph_data[$field_name] = $media;
          }
        }
      }
    }

    $paragraph = Paragraph::create($paragraph_data);

    // Handle nested paragraphs
    if (isset($section['nested_paragraphs'])) {
      foreach ($section['nested_paragraphs'] as $field_name => $nested_paragraphs) {
        $nested_paragraph_items = [];
        foreach ($nested_paragraphs as $nested_paragraph_data) {
          $nested_paragraph = $this->createParagraph($nested_paragraph_data);
          if ($nested_paragraph) {
            $nested_paragraph_items[] = $nested_paragraph;
          }
        }
        $paragraph->set($field_name, $nested_paragraph_items);
      }
    }

    $paragraph->save();

    return $paragraph;
  }

  /**
   * Finish callback for the batch process.
   *
   * @param bool $success
   *   Indicates whether the batch process was successful.
   * @param array $results
   *   An array of results from the batch operations.
   * @param array $operations
   *   An array of operations that remained unprocessed.
   */
  public static function finished($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One page processed.', '@count pages processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    \Drupal::messenger()->addStatus($message);

    // Display the used JSON
    if (isset($operations['raw_json'])) {
      \Drupal::messenger()->addStatus(t('Used JSON: @json', ['@json' => $operations['raw_json']]));
    }
  }

}
