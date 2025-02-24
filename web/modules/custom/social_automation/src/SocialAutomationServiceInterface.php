<?php

namespace Drupal\social_automation;

/**
 * Interface for social automation service.
 */
interface SocialAutomationServiceInterface {

  /**
   * Triggers the webhook with the given social media and datetime.
   *
   * @param string $social_media
   *   The social media platform (twitter or linkedin).
   * @param string $datetime
   *   The current datetime in 'Y-m-d H:i:s' format.
   *
   * @return bool
   *   TRUE if the webhook was triggered successfully, FALSE otherwise.
   */
  public function triggerWebhook($social_media, $datetime);

}