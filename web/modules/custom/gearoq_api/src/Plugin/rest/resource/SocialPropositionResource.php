<?php

namespace Drupal\gearoq_api\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides a resource to create social content propositions.
 *
 * @RestResource(
 *   id = "gearoq_api_social_proposition",
 *   label = @Translation("Social Content Proposition Resource"),
 *   uri_paths = {
 *     "create" = "/api/create/social-proposition"
 *   }
 * )
 */
class SocialPropositionResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new SocialPropositionResource object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('gearoq_api'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to POST requests.
   *
   * @param array $data
   *   The data to create the social content proposition.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function post(array $data) {
    // Remove the permission check here, we'll handle it in the route definition
    // Create the node.
    $node = Node::create([
      'type' => 'social_content_proposition',
      'title' => substr($data['body'], 0, 30),
      'body' => [
        'value' => $data['body'],
        'format' => 'plain_text',
      ],
      'field_update_in_airtable' => $data['field_update_in_airtable'] ?? FALSE,
    ]);

    // Set the social media reference.
    $term = $this->getOrCreateSocialMediaTerm($data['field_social_media']);
    if ($term) {
      $node->set('field_social_media', ['target_id' => $term->id()]);
    }

    $node->save();

    return new ResourceResponse(['message' => 'Social content proposition created', 'nid' => $node->id()], 201);
  }

  /**
   * Gets or creates a social media taxonomy term.
   *
   * @param string $name
   *   The name of the social media platform.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The taxonomy term object or null if creation fails.
   */
  protected function getOrCreateSocialMediaTerm($name) {
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'vid' => 'social_media',
        'name' => $name,
      ]);

    if (!empty($terms)) {
      return reset($terms);
    }

    $term = Term::create([
      'vid' => 'social_media',
      'name' => $name,
    ]);
    $term->save();

    return $term;
  }

}
