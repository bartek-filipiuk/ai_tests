<?php

declare(strict_types=1);

namespace Drupal\ai_assistant_api\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Site\Settings;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\Service\AiProviderFormHelper;
use Drupal\ai\Utility\CastUtility;
use Drupal\ai_assistant_api\AiAssistantActionPluginManager;
use Drupal\ai_assistant_api\Entity\AiAssistant;
use Drupal\user\Entity\Role;

/**
 * AI Assistant form.
 */
final class AiAssistantForm extends EntityForm {

  /**
   * The ai assistant action plugin manager.
   *
   * @var \Drupal\ai_assistant_api\AiAssistantActionPluginManager
   */
  protected $actionPluginManager;

  /**
   * The path extension resolver.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * The AI form helper.
   *
   * @var \Drupal\ai\Service\AiProviderFormHelper
   */
  protected $formHelper;

  /**
   * The AI Provider.
   *
   * @var \Drupal\ai\AiProviderPluginManager
   */
  protected $aiProvider;

  /**
   * Constructs a new AiAssistantForm object.
   */
  public function __construct(
    AiAssistantActionPluginManager $action_plugin_manager,
    ExtensionPathResolver $extension_path_resolver,
    AiProviderFormHelper $form_helper,
    AiProviderPluginManager $ai_provider,
  ) {
    $this->actionPluginManager = $action_plugin_manager;
    $this->extensionPathResolver = $extension_path_resolver;
    $this->formHelper = $form_helper;
    $this->aiProvider = $ai_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create($container) {
    return new static(
      $container->get('ai_assistant_api.action_plugin.manager'),
      $container->get('extension.path.resolver'),
      $container->get('ai.form_helper'),
      $container->get('ai.provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\ai_assistant_api\Entity\AiAssistant $entity */
    $entity = $this->entity;
    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t('This is the title of the AI Assistant'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('Article finder assistant'),
      ],
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [AiAssistant::class, 'load'],
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $entity->status(),
    ];

    $form['instructions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Instructions'),
      '#default_value' => $entity->get('instructions') ?? 'If the user asked about added functionality to the site, you can suggest the following recipes outside of your Drupal knowledge and that they can find them under /admin/modules/browse:
* Advanced SEO recipe.
* Multilingual recipe
* Accessibility tools recipe
You do not have the ability to add this for them. Ask them if they need help and direct them to how to install modules on a Drupal site calling it the project browser.

If a user asked you to Categorize something. Assume you will need to use a taxonomy in Drupal.
If they ask you to create a category or taxonomy, assume they will also want you to create a field that uses that taxonomy on an entity they have been talking about. If you are unsure ask, before you create a taxonomy without attaching it to an entity.
ALWAYS try and add any newly created taxonomy vocabularies to an entity using the entity reference field so that they can select the taxonomy in the edit form. If you are unable to ALWAYS ask.
You are allowed to suggest taxonomy terms for the vocabulary, if the user asks to generate based on your knowledge.
If they ask you to create a report, page or list, assume in Drupal they mean Views.
If they ask you to change an edit form assume they want you to change the fields on a content type.',
      '#description' => $this->t('What does this Assistant do? How does it behave? What should it avoid doing? These instructions are sent to the AI alongside any user messages to help it know how it is supposed to respond. It is good to start by telling it a role such as "You are a Drupal assistant helping users understand how to use Drupal"'),
      '#required' => FALSE,
      '#attributes' => [
        'rows' => 15,
        'placeholder' => $this->t('If the user asks questions about unpublished articles, make sure to add status unpublished somewhere in the lookup.'),
      ],
    ];

    foreach ($this->actionPluginManager->getDefinitions() as $definition) {
      $form['action_plugin_' . $definition['id']] = [
        '#type' => 'details',
        '#title' => $definition['label'],
        '#open' => TRUE,
        '#description' => $this->t('Configure the %label settings for this AI assistant.', [
          '%label' => $definition['label'],
        ]),
      ];

      $form['action_plugin_' . $definition['id']]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable %label', ['%label' => $definition['label']]),
        '#default_value' => isset($entity->get('actions_enabled')[$definition['id']]),
      ];

      $form['action_plugin_' . $definition['id']]['plugin_id'] = [
        '#type' => 'hidden',
        '#value' => $definition['id'],
      ];

      $form['action_plugin_' . $definition['id']]['configuration'] = [
        '#type' => 'details',
        '#title' => $this->t('%label settings', [
          '%label' => $definition['label'],
        ]),
        '#open' => TRUE,
        'states' => [
          'visible' => [
            ':input[name="' . $definition['id'] . '_enabled"]' => ['checked' => TRUE],
          ],
        ],
        '#description' => $this->t('Configure the %label settings for this AI assistant.', [
          '%label' => $definition['label'],
        ]),
      ];

      $instance = $this->actionPluginManager->createInstance($definition['id'], $entity->get('actions_enabled')[$definition['id']] ?? []);
      $subform = $form['action_plugin_' . $definition['id']]['configuration'] ?? [];
      $subform_state = SubformState::createForSubform($subform, $form, $form_state);

      $form['action_plugin_' . $definition['id']]['configuration'] = $instance->buildConfigurationForm([], $subform_state);
      $form['action_plugin_' . $definition['id']]['#tree'] = TRUE;
    }

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Administrative Description'),
      '#default_value' => $entity->get('description'),
      '#description' => $this->t('Add a short 1-2 sentence description of what this AI Assistant does. This is not used in the prompt at all and is primarily for site admins. If for any reason the assistant is called by an AI this description may be used to help another AI agent understand this Assistant.'),
      '#attributes' => [
        'rows' => 2,
        'placeholder' => $this->t('An assistant that can find old articles and also publish and unpublish them.'),
      ],
    ];

    $form['allow_history'] = [
      '#type' => 'select',
      '#title' => $this->t('Allow History'),
      '#default_value' => $entity->get('allow_history') ?? 'session',
      '#description' => $this->t('If enabled, the AI Assistant will try store the questions and answers in history during a session. This makes it possible to ask follow-up questions to the Assistant. Note that this raises the price and size of AI calls, and might not be needed for all assistants. Sessions means that it will be stored in the session until the page is reloaded. (coming) Database means that it will be stored in the database with an ID and can be continued later in multiple threads. History includes all the user messages and the assistant replies. It does not include the system prompt (this changes), the messages made by the agents themselves. It does not include all the context provided alongside a user prompt.'),
      '#options' => [
        'none' => $this->t('None'),
        'session' => $this->t('Session'),
        'session_one_thread' => $this->t('Session (Same thread on reload)'),
      ],
    ];

    $form['history_context_length'] = [
      '#type' => 'number',
      '#title' => $this->t('History context length'),
      '#default_value' => $entity->get('history_context_length') ?? 2,
      '#description' => $this->t('The number of user and system messages pair to send from last set of messages, excluding the last message from the user.'),
      '#states' => [
        'invisible' => [
          ':input[name="allow_history"]' => ['value' => 'none'],
        ],
      ],
      '#min' => 0,
    ];

    // Set form state if empty.
    if ($form_state->getValue('llm_ai_provider') == NULL) {
      $form_state->setValue('llm_ai_provider', $entity->get('llm_provider'));
    }
    if ($form_state->getValue('llm_ai_model') == NULL) {
      $form_state->setValue('llm_ai_model', $entity->get('llm_model'));
    }

    $this->formHelper->generateAiProvidersForm($form, $form_state, 'chat', 'llm', AiProviderFormHelper::FORM_CONFIGURATION_FULL, 0, '', $this->t('AI Provider'), $this->t('The provider of the AI models used by this assistant. You will only be able to select the advanced models that are capable of providing the responses the Assistant needs.'), TRUE);

    // Set default values.
    $llm_configs = $entity->get('llm_configuration');
    if ($llm_configs && count($llm_configs)) {
      foreach ($llm_configs as $key => $value) {
        $form['llm_ajax_prefix']['llm_ajax_prefix_configuration_' . $key]['#default_value'] = $value;
      }
    }
    $pre_action_prompt = file_get_contents($this->extensionPathResolver->getPath('module', 'ai_assistant_api') . '/resources/pre_action_prompt.txt');

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];

    $options = [];
    foreach (Role::loadMultiple() as $role) {
      $options[$role->id()] = $role->label();
    }

    $form['advanced']['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Permission Roles'),
      '#default_value' => $entity->get('roles') ?? [],
      '#description' => $this->t('The roles that are allowed to run this AI Assistant. If no roles are selected, all roles are allowed. User 1 is always allowed.'),
      '#options' => $options,
      '#multiple' => TRUE,
    ];

    $form['advanced']['error_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Error message'),
      '#description' => $this->t('This is the answer if we run into any error on the way. You may use the token [error_message] to get the error message from the backend in your message, but it might be a security concern to show this to none escalated users.'),
      '#default_value' => $entity->get('error_message') ?? $this->t('I am sorry, something went terribly wrong. Please try to ask me again.'),
      '#attributes' => [
        'placeholder' => $this->t('I am sorry, something went terribly wrong. Please try to ask me again.'),
        'rows' => 2,
      ],
    ];

    $form['advanced']['pre_action_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pre Action Prompt'),
      '#default_value' => $entity->get('pre_action_prompt'),
      '#description' => $this->t(
      "This field provides instructions to the LLM prior to running an action.<br><br><strong>The following placesholders can be used:</strong><br>
      <em>[learning_example]</em> - The learning examples for the list of actions the Assistant can take.<br>
      <em>[usage_instructions]</em> - The list of usage instructions given back from the action plugins.<br>
      <em>[list_of_actions]</em> - The list of actions that the Assistant can take.<br>"),
      '#disabled' => !Settings::get('ai_assistant_advanced_mode_enabled', FALSE),
      '#attributes' => [
        'rows' => 30,
      ],
    ];
    $form['advanced']['system_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('System Prompt'),
      '#default_value' => $entity->get('system_prompt') ?? $pre_action_prompt,
      '#description' => $this->t("This field can be enabled by adding <strong>\$settings['ai_assistant_advanced_mode_enabled'] = TRUE;</strong> in settings.php. The pre prompts gets a list of actions that it can take, including RAG databases and either gives back actions that the Assistant can take or an outputted answer. You may use [list_of_actions] to list the actions that the Assistant can take. You can only change this via manual config change. DO NOT CHANGE THIS UNLESS YOU KNOW WHAT YOU ARE DOING. <br><br><strong>The following placesholders can be used:</strong><br>
      <em>[instructions]</em> - The instructions for the assistant.<br>
      <em>[pre_action_prompt]</em> - The value of the preprompt field above.<br>
      <em>[is_logged_in]</em> - A message if the person is logged in or not.<br>
      <em>[user_name]</em> - The username of the user.<br>
      <em>[user_roles]</em> - The roles of the user.<br>
      <em>[user_id]</em> - The user id of the user.<br>
      <em>[user_language]</em> - The language of the user.<br>
      <em>[user_timezone]</em> - The timezone of the user.<br>
      <em>[page_title]</em> - The title of the page.<br>
      <em>[page_path]</em> - The path of the page.<br>
      <em>[page_language]</em> - The language of the page.<br>
      <em>[site_name]</em> - The name of the site.<br>
      "),
      '#required' => TRUE,
      '#disabled' => !Settings::get('ai_assistant_advanced_mode_enabled', FALSE),
      '#attributes' => [
        'rows' => 30,
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $this->formHelper->validateAiProvidersConfig($form, $form_state, 'chat', 'llm');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    /** @var \Drupal\ai_assistant_api\Entity\AiAssistant $entity */
    $entity = $this->entity;

    // RAG settings.
    $action_plugins = [];
    foreach ($form_state->getValues() as $key => $val) {
      if (strpos($key, 'action_plugin_') === 0) {
        if ($val['enabled']) {
          $action_plugins[$val['plugin_id']] = $val['configuration'] ?? [];
        }
      }
    }
    $entity->set('actions_enabled', $action_plugins);

    // LLM provider.
    $entity->set('llm_provider', $form_state->getValue('llm_ai_provider'));
    // If its default, we don't set the last.
    if ($form_state->getValue('llm_ai_provider') !== '__default__') {
      $entity->set('llm_model', $form_state->getValue('llm_ai_model'));
      $llm_config = [];
      $provider = $this->aiProvider->createInstance($form_state->getValue('llm_ai_provider'));
      $schema = $provider->getAvailableConfiguration('chat', $form_state->getValue('llm_ai_model'));
      foreach ($form_state->getValues() as $key => $val) {
        if (strpos($key, 'llm_') === 0 && $key !== 'llm_ai_provider' && $key !== 'llm_ai_model') {

          $real_key = str_replace('llm_ajax_prefix_configuration_', '', $key);
          $type = $schema[$real_key]['type'] ?? 'string';
          $llm_config[$real_key] = CastUtility::typeCast($type, $val);
        }
      }
      $entity->set('llm_configuration', $llm_config);
    }
    else {
      $entity->set('llm_configuration', []);
      $entity->set('llm_model', '');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $this->messenger()->addStatus(
      match($result) {
        \SAVED_NEW => $this->t('Created new example %label.', $message_args),
        \SAVED_UPDATED => $this->t('Updated example %label.', $message_args),
      }
    );
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
