<?php

namespace Drupal\autobloger_ai\Service;

use Drupal\ai\Plugin\ProviderProxy;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\Chat\ChatInput;

/**
 * Service for AutoblogerAI functionality.
 *
 * Provides methods for generating and managing AI-powered blog posts,
 * including content generation, image creation, and node creation.
 */
class AutoblogerAiService {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructs a new AutoblogerAiService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ClientInterface $http_client,
    EntityTypeManagerInterface $entity_type_manager,
    FileSystemInterface $file_system,
    AccountProxyInterface $current_user
  ) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->currentUser = $current_user;
  }

  /**
   * Generates a blog post using AI.
   *
   * Uses the provided text provider to generate a structured blog post with
   * a title and content. The content is generated in the configured language
   * and follows specific formatting requirements.
   *
   * @param string $prompt
   *   The prompt for generating the blog post.
   * @param \Drupal\ai\Plugin\ProviderProxy $text_provider
   *   The text provider instance.
   * @param \Drupal\ai\Plugin\ProviderProxy|null $perplexity_provider
   *   Optional perplexity provider instance for web search.
   *
   * @return array
   *   An array containing:
   *   - title: The generated blog post title.
   *   - content: The HTML-formatted blog post content.
   *   - image_data: Base64-encoded image data.
   *   - links: An array of links extracted from the content.
   *
   * @throws \Exception
   *   When the AI response is invalid or missing required fields.
   */
  public function generateBlogPost(
    string $prompt,
    ProviderProxy $text_provider,
    ?ProviderProxy $perplexity_provider = null
  ): array {
    // Configure longer timeout for OpenAI requests
    if (method_exists($text_provider, 'getClient')) {
      $guzzleClient = new \GuzzleHttp\Client([
        'timeout' => 120, // Increase timeout to 120 seconds
        'connect_timeout' => 10, // Increase connect timeout to 10 seconds
      ]);

      $text_provider->getClient()->withHttpClient($guzzleClient);
    }

    $config = $this->configFactory->get('autobloger_ai.settings');
    $model = $config->get('text_ai_model');
    $use_perplexity_search = $config->get('use_perplexity_search') ?? FALSE;

    // Get perplexity data if enabled
    $perplexity_data = '';
    if ($use_perplexity_search && !empty($perplexity_provider)) {
      $perplexity_model = $config->get('perplexity_model');

      // Get and process perplexity prompt
      $perplexity_prompt = $this->getPromptTemplate('perplexity', [
        '[User Subject]' => $prompt,
      ]);

      // Save perplexity prompt
      $this->savePrompt($perplexity_prompt, $perplexity_model);

      $message = new ChatMessage(
        'user',
        $perplexity_prompt
      );
      $perplexity_input = new ChatInput([$message]);

      // Set perplexity-specific configuration
      $perplexity_config = [
        'temperature' => 0.2,
        'max_tokens' => 2048,
      ];
      $perplexity_provider->setConfiguration($perplexity_config);

      $perplexity_response = $perplexity_provider->chat($perplexity_input, $perplexity_model);
      $perplexity_data = $perplexity_response->getNormalized()->getText();
    }

    // Get language-specific instructions
    $language_instructions = $this->getLanguageInstructions();

    // Generate blog post content with structured format
    $system_content = 'You are a professional blog post writer. Create engaging, well-structured content. ' . $language_instructions;

    // Get and process OpenAI prompt
    $openai_prompt = $this->getPromptTemplate('openai', [
      '[User Subject]' => $prompt,
      '[Perplexity Information]' => $perplexity_data,
    ]);

    // Save OpenAI prompt
    $this->savePrompt($openai_prompt, 'openai');

    $system_content .= ' ' . $openai_prompt;

    $messages = [
      [
        'role' => 'system',
        'content' => $system_content,
      ],
      [
        'role' => 'user',
        'content' => $prompt,
      ],
    ];

    // Start writing a blog post text.
    $blog_response = $text_provider->chat($messages, $model);
    $blog_text = $blog_response->getNormalized()->getText();

    // Parse the JSON response
    $json_content = $this->extractJsonFromContent($blog_text);
    if (!$json_content) {
      throw new \Exception('Could not find valid JSON in the AI response');
    }

    $blog_data = json_decode($json_content, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \Exception('Invalid JSON format: ' . json_last_error_msg());
    }

    // Handle both array and single object responses
    if (is_array($blog_data) && isset($blog_data[0])) {
      $blog_data = $blog_data[0];
    }

    if (!isset($blog_data['title']) || !isset($blog_data['content']) || !isset($blog_data['links'])) {
      throw new \Exception('Response is missing required fields (title, content, or links)');
    }

    // Generate image prompt
    $image_prompt = $this->generateImagePrompt($blog_data);

    // Generate image
    $image_data = $this->generateImage($image_prompt);

    return [
      'title' => $blog_data['title'],
      'title_english' => $blog_data['title_english'],
      'content' => $blog_data['content'],
      'image_data' => $image_data,
      'links' => $blog_data['links'],
      'description' => $blog_data['description'],
      'description_english' => $blog_data['description_english'],
    ];
  }

  /**
   * Generates a prompt for image generation.
   *
   * @param array $blog_data
   *   The blog post data containing title and title_english.
   *
   * @return string
   *   The generated image prompt.
   */
  private function generateImagePrompt(array $blog_data): string {
    // Use English title if available, otherwise use the original title
    $title_for_image = !empty($blog_data['title_english']) ? $blog_data['title_english'] : $blog_data['title'];

    $prompt = $this->getPromptTemplate('image', [
      '[User Subject]' => $title_for_image,
      '[Title English]' => $blog_data['title_english'],
      '[Title]' => $blog_data['title'],
      '[Description English]' => $blog_data['description_english'],
    ]);

    // Save image generation prompt
    $this->savePrompt($prompt, 'image');

    return $prompt;
  }

  /**
   * Generates an image using the FLUX API.
   *
   * @param string $prompt
   *   The image generation prompt.
   *
   * @return string
   *   Base64-encoded image data.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   When the API request fails.
   */
  public function generateImage(string $prompt): string {
    $config = $this->configFactory->get('autobloger_ai.settings');
    $model = $config->get('image_model');
    $together_api_key = $config->get('together_api_key');

    $response = $this->httpClient->post('https://api.together.xyz/v1/images/generations', [
      'headers' => [
        'Authorization' => 'Bearer ' . $together_api_key,
        'Content-Type' => 'application/json',
      ],
      'json' => [
        'prompt' => $prompt,
        'model' => $model,
        'width' => 1440,
        'height' => 832,
        'steps' => 4,
        'n' => 1,
        'response_format' => 'b64_json',
      ],
    ]);

    $result = json_decode($response->getBody(), TRUE);
    return $result['data'][0]['b64_json'];
  }

  /**
   * Creates a blog post node with the generated content and image.
   *
   * Saves the generated image as a managed file and creates a new node
   * of the configured content type with the blog post content.
   *
   * @param string $title
   *   The blog post title.
   * @param string $content
   *   The HTML-formatted blog post content.
   * @param string $image_data
   *   Base64-encoded image data.
   * @param array $links
   *   An array of links extracted from the content.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   When the node or file cannot be saved.
   */
  public function createBlogPost(
    string $title,
    string $content,
    string $image_data,
    array $links
  ): \Drupal\node\NodeInterface {
    $config = $this->configFactory->get('autobloger_ai.settings');
    $content_type = $config->get('content_type');
    $image_field = $config->get('image_field');
    $text_field = $config->get('text_field');

    // Decode and save the image.
    $image_data = base64_decode($image_data);
    $directory = 'public://generated_images';
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

    $file_path = $directory . '/image_' . time() . '.png';
    $this->fileSystem->saveData($image_data, $file_path, FileSystemInterface::EXISTS_REPLACE);

    $file = File::create([
      'uri' => $file_path,
      'uid' => 3,
      'status' => 1,
    ]);
    $file->save();

    // Create the node.
    $node = Node::create([
      'type' => $content_type,
      'title' => $title,
      $text_field => [
        'value' => $content,
        'format' => 'full_html',
      ],
      $image_field => [
        'target_id' => $file->id(),
        'alt' => 'Generated blog post image',
      ],
      'uid' => $this->currentUser->id(),
      'status' => 1,
    ]);

    // Add links to the node.
    $node->field_links = $links;

    $node->save();
    return $node;
  }

  /**
   * Extracts JSON from the content if it's wrapped in other text.
   *
   * Attempts to find valid JSON in the content by:
   * 1. Checking if the entire content is valid JSON
   * 2. Looking for JSON in code blocks
   * 3. Looking for JSON between curly braces
   *
   * @param string $content
   *   The content that may contain JSON.
   *
   * @return string|null
   *   The extracted JSON string, or NULL if no valid JSON is found.
   */
  public function extractJsonFromContent(string $content): ?string {
    // First, check if the entire content is already valid JSON
    $json = json_decode($content, true);
    if (json_last_error() === JSON_ERROR_NONE) {
      return $content;
    }

    // If not, try to find JSON wrapped in code blocks
    if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $content, $matches)) {
      return $matches[1];
    }

    // If no code blocks, try to find JSON between curly braces
    if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
      return $matches[0];
    }

    // If no JSON-like structure is found, return null
    return null;
  }

  /**
   * Gets a prompt template and replaces placeholders with actual values.
   *
   * @param string $type
   *   The type of prompt to get ('perplexity', 'openai', or 'image').
   * @param array $replacements
   *   Array of replacements where key is the placeholder and value is the replacement.
   *
   * @return string
   *   The processed prompt with placeholders replaced.
   */
  private function getPromptTemplate(string $type, array $replacements = []): string {
    $config = $this->configFactory->get('autobloger_ai.settings');

    // Get the appropriate prompt template
    $template = match ($type) {
      'perplexity' => $config->get('perplexity_prompt'),
      'openai' => $config->get('openai_prompt'),
      'image' => $config->get('image_prompt'),
      default => throw new \InvalidArgumentException("Unknown prompt type: $type"),
    };

    // Replace placeholders with actual values
    foreach ($replacements as $placeholder => $value) {
      $template = str_replace($placeholder, $value, $template);
    }

    return $template;
  }

  /**
   * Saves a prompt as a 'prompts' content type node.
   *
   * @param string $prompt
   *   The prompt text to save.
   * @param string $model
   *   The model used (perplexity, openai, or image).
   *
   * @return EntityInterface|null
   *   The created node or null if creation failed.
   */
  private function savePrompt(string $prompt, string $model): ?EntityInterface {
    try {
      // Create a new node object
      $node = $this->entityTypeManager->getStorage('node')->create([
        'type' => 'prompts',
        'title' => substr($prompt, 0, 80) . '...', // First 80 chars as title
        'body' => [
          'value' => $prompt,
          'format' => 'full_html',
        ],
        'field_model' => $model,
        'status' => 0,
      ]);

      $node->save();
      return $node;
    }
    catch (\Exception $e) {
      \Drupal::logger('autobloger_ai')->error('Failed to save prompt: @error', ['@error' => $e->getMessage()]);
      return null;
    }
  }

  /**
   * Gets language-specific instructions.
   *
   * @return string
   *   Language-specific instructions.
   */
  private function getLanguageInstructions(): string {
    $blog_language = $this->configFactory->get('autobloger_ai.settings')->get('blog_language') ?? 'en';
    $language_instructions = $blog_language === 'pl'
      ? 'Write the blog post in Polish. Use proper Polish grammar, punctuation, and formatting.'
      : 'Write the blog post in English. Use proper English grammar, punctuation, and formatting.';
    return $language_instructions;
  }

}
