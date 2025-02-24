# AutoblogerAI Module

## INTRODUCTION

The AutoblogerAI module is a powerful Drupal 10 module that automates blog post generation using AI technology. It integrates with Together AI for text generation and image creation, providing both manual and automated blog post creation capabilities.

The primary use cases for this module are:

- Automated blog post generation on a configurable schedule
- Manual blog post generation through a user-friendly form
- AI-powered image generation that matches the blog post content
- Multi-language support (English and Polish)

## REQUIREMENTS

This module requires the following:

- Drupal 10
- Together AI API key (used for both text and image generation)
- Drupal AI module (for AI provider integration)
- Node content type configured for blog posts
- Fields for blog post content and featured image

## INSTALLATION

1. Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/node/895232 for further information.

2. Configure your content type and fields:
   - Create a content type for blog posts (or use an existing one)
   - Ensure you have a text field for the blog content (supports full HTML)
   - Add an image field for the generated featured image

## CONFIGURATION

### Basic Configuration
1. Go to `/admin/config/services/autobloger-ai`
2. Enter your Together AI API key
3. Select your blog post content type
4. Map the content and image fields
5. Choose your preferred language (English or Polish)

### AI Provider Configuration
1. Select the AI model for text generation
2. Select the AI model for image generation
3. Configure any additional AI provider settings

### Scheduling Configuration
1. Choose the scheduling type:
   - Daily: Posts every day at a specified hour
   - Weekly: Posts on selected days of the week
   - Monthly: Posts on a specific day of the month
   - Custom: Combination of weekly and monthly schedules

2. Set scheduling parameters:
   - Minimum interval between posts (in hours)
   - Maximum posts per week
   - Preferred posting hour
   - Specific days for weekly/monthly scheduling

3. Optional: Enable "Run on every cron" for maximum frequency

## SERVICES

The module provides two main services that can be used by other modules or custom code:

### AutoblogerAiService

Service ID: `autobloger_ai.service`

This service handles the core functionality of blog post generation and management.

#### Methods:

1. `generateBlogPost($prompt, $text_provider)`
   - Purpose: Generates a complete blog post including content and image
   - Parameters:
     - `$prompt`: (string) The blog post topic or instructions
     - `$text_provider`: (object) AI provider instance
   - Returns: Array with keys:
     - `title`: Generated blog post title
     - `content`: Generated HTML content
     - `image_data`: Base64 encoded image data
   - Throws: Exception on generation failure

2. `generateImage($prompt)`
   - Purpose: Generates an image using Together AI API
   - Parameters:
     - `$prompt`: (string) Image generation prompt
   - Returns: (string) Base64 encoded image data
   - Note: Uses Together AI API key from configuration

3. `createBlogPost($title, $content, $image_data)`
   - Purpose: Creates a Drupal node with the generated content
   - Parameters:
     - `$title`: (string) Blog post title
     - `$content`: (string) HTML content
     - `$image_data`: (string) Base64 encoded image
   - Returns: The created Node object
   - Note: Uses configured content type and fields

4. `extractJsonFromContent(string $content)`
   - Purpose: Extracts JSON from AI response text
   - Parameters:
     - `$content`: (string) Text containing JSON
   - Returns: (string|null) Extracted JSON or null
   - Note: Handles various JSON formats and wrappers

### BlogPostQueueService

Service ID: `autobloger_ai.queue`

This service manages the automated blog post generation queue and scheduling.

#### Methods:

1. `queueBlogPostGeneration($prompt)`
   - Purpose: Adds a blog post to the generation queue
   - Parameters:
     - `$prompt`: (string) Blog post topic/prompt
   - Note: Updates post count tracking

2. `addCronTasks()`
   - Purpose: Adds scheduled blog posts to the queue during cron
   - Note: Checks scheduling rules before queueing

#### Protected Methods (for extending):

1. `shouldGeneratePost()`
   - Purpose: Determines if a post should be generated based on schedule
   - Returns: (bool) TRUE if conditions are met
   - Checks:
     - Minimum interval between posts
     - Maximum posts per week
     - Scheduled hours and days
     - Schedule type rules

2. `getNextSubject()`
   - Purpose: Gets the next unused blog subject from configuration
   - Returns: (string|null) Next subject or null if none available
   - Note: Tracks used subjects to ensure rotation

## USAGE

### Manual Blog Post Generation

1. Navigate to `/admin/content/autobloger-ai/generate`
2. Enter your blog post instructions in the text area
   - Be specific about the topic
   - Include any special requirements or focus areas
3. Click "Generate Blog Post"
4. The module will:
   - Generate the blog post content
   - Create a matching featured image
   - Create a new node with the generated content

### Automated Blog Post Generation

The module uses Drupal's cron system to automatically generate posts based on your scheduling configuration.

1. Configure your blog subjects:
   - Go to the module configuration page
   - Add a list of blog subjects/topics
   - The system will cycle through these subjects

2. The automated process will:
   - Check if it's time to generate a post based on your schedule
   - Select the next unused subject from your list
   - Generate a blog post and image
   - Create and publish the node
   - Track which subjects have been used

3. Monitor the process:
   - Check Recent log messages for any errors
   - View generated content in the content overview
   - Track post generation frequency in the module's state storage

## TROUBLESHOOTING

Common issues and solutions:

1. 401 Unauthorized Error
   - Verify your Together AI API key
   - Check if the key has sufficient permissions
   - Ensure the key is properly saved in configuration

2. Failed Image Generation
   - Check image generation prompts
   - Verify image model configuration
   - Ensure proper file system permissions

3. Scheduling Issues
   - Verify cron is running properly
   - Check scheduling configuration
   - Review Recent log messages for errors

## MAINTAINERS

Current maintainers for Drupal 10:

- Bartek (gearoq) - https://www.drupal.org/u/vince_pl
