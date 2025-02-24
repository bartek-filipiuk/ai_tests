# Perplexity AI Provider for Drupal AI module

This module provides [Perplexity AI](https://www.perplexity.ai/) integration for Drupal's AI module. It allows you to use Perplexity's powerful language models for various AI tasks in your Drupal site.

## Requirements

- Drupal 10.2 || 11
- PHP 8.x
- [AI module](https://www.drupal.org/project/ai)
- [Key module](https://www.drupal.org/project/key)
- Perplexity AI API key

## Installation

1. Install the required modules:
   ```bash
   composer require drupal/ai
   drush en ai key ai_perplexity
   ```

## Configuration

1. First, create an API key at [Perplexity AI Settings](https://www.perplexity.ai/settings/api)

2. Store your API key in the Key module:
   - Go to `/admin/config/system/keys`
   - Click "Add Key"
   - Fill in the following:
     - Key name: `perplexity_api_key` (or any name you prefer)
     - Key type: `Authentication`
     - Key provider: `Configuration`
     - Key input: Paste your Perplexity API key

3. Configure the Perplexity AI Provider:
   - Go to `/admin/config/ai/perplexity`
   - Select your API key from the dropdown
   - Choose your default model
   - Configure model settings:
     - Temperature (0-2)
     - Top P (0-1)
     - Max Tokens (1-4096)

## Available Models

| Model Name | Size | Context Window | Description |
|------------|------|----------------|-------------|
| llama-3.1-sonar-small-128k-online | 8B | 127,072 | Fast, efficient model for general use |
| llama-3.1-sonar-large-128k-online | 70B | 127,072 | More powerful model for complex tasks |
| llama-3.1-sonar-huge-128k-online | 405B | 127,072 | Most capable model for demanding applications |

## Features

- Seamless integration with Drupal's AI module
- Support for all Perplexity Sonar models
- Configurable model parameters
- Citation support in responses
- Rate limit handling
- Secure API key management through Key module

## Usage Example

```php
// Get the AI provider plugin manager
$ai_provider_manager = \Drupal::service('ai.provider');

// Get the Perplexity provider
$provider = $ai_provider_manager->createInstance('perplexity');

// Create a chat message
$input = new ChatInput();
$input->addMessage(new ChatMessage('user', 'What is quantum computing?'));

// Get the response
$output = $provider->chat($input, 'llama-3.1-sonar-small-128k-online');

// Access the response text
$response_text = $output->getMessage()->getText();

// Access citations if available
$citations = $output->getMetadata()['citations'] ?? [];
```

## Response Format

The module provides responses in two parts:
1. Main response text
2. Citations (when available)

Example response structure:
```php
$output = [
  'message' => [
    'role' => 'assistant',
    'content' => 'The response text...'
  ],
  'metadata' => [
    'citations' => [
      // Citation information
    ]
  ]
];
```

## Troubleshooting

1. **API Key Issues**
   - Verify your API key is correctly stored in the Key module
   - Check if the key has proper permissions

2. **Rate Limiting**
   - The module handles rate limits automatically
   - If you encounter rate limits, try reducing request frequency

3. **Model Availability**
   - Ensure you're using a supported model
   - Check Perplexity AI status if models are unavailable

## Contributing

Contributions are welcome! Please follow Drupal's coding standards and submit pull requests.

## License

This module is licensed under GPL-2.0+. See the LICENSE.txt file for details.

