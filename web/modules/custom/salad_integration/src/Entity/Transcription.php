<?php

namespace Drupal\salad_integration\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Transcription entity.
 *
 * @ContentEntityType(
 *   id = "transcription",
 *   label = @Translation("Transcription"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "transcription",
 *   admin_permission = "administer transcription entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "job_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/transcription/{transcription}",
 *     "add-form" = "/admin/structure/transcription/add",
 *     "edit-form" = "/admin/structure/transcription/{transcription}/edit",
 *     "delete-form" = "/admin/structure/transcription/{transcription}/delete",
 *     "collection" = "/admin/structure/transcription",
 *   },
 * )
 */
class Transcription extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['job_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Job ID'))
      ->setDescription(t('The Salad API job ID.'))
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Status'))
      ->setDescription(t('The status of the transcription job.'))
      ->setRequired(TRUE);

    $fields['result_data'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Result Data'))
      ->setDescription(t('The JSON-encoded result data from the transcription.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the transcription was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the transcription was last edited.'));

    return $fields;
  }
}
