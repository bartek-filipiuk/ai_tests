<?php

namespace Drupal\bad_module;

class BadModuleService {

  public function saveData($data) {
    \Drupal::configFactory()
      ->getEditable('bad_module.settings')
      ->set('saved_data', $data)
      ->save();

    \Drupal::logger('bad_module')->notice('Zapisano dane: @data', ['@data' => $data]);

    \Drupal::messenger()->addStatus(\Drupal::translation()->translate('Dane zostały zapisane.'));

    return \Drupal::translation()->translate('Operacja zakończona sukcesem!');
  }

  public static function getData() {
    $data = \Drupal::config('bad_module.settings')->get('saved_data');

    return $data ? [$data] : [];
  }

  function processData($input) {
    $processed = $input . ' - przetworzono';

    $cache = \Drupal::cache();
    $cache->set('bad_module_last_processed', $processed, \Drupal::time()->getRequestTime() + 3600);

    return $processed;
  }

}
