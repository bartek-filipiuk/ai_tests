## Prompts for AI Content Generation

This document outlines prompt templates for generating a blog post with an image using Perplexity AI, OpenAI, and an image generation API like Flux.

### 1. Perplexity API Prompt (Information Gathering)

**Objective:** Retrieve concise, accurate, and up-to-date information on any given subject, suitable for use in a blog post.  Avoid generating a full blog post at this stage.

**Prompt Template:**

```
[Instruction]
Provide a concise and informative summary of the topic: "[User Subject]".

[Context]
I need the most recent and relevant information on this topic to assist in creating a detailed blog post. Focus on key points, statistics, and new developments related to the subject.

[Input]
- Topic: "[User Subject]"

[Keywords]
- Latest trends
- Key insights
- Important data
- Recent news
- Citations from reputable sources

[Output Format]
- Bullet points summarizing the main information
- Include citations with sources for each point
```

**Example using "Top 7 hot current trends in AI space":**

```
[Instruction]
Provide a concise and informative summary of the topic: "Top 7 hot current trends in AI space."

[Context]
I need the most recent and relevant information on this topic to assist in creating a detailed blog post. Focus on key points, statistics, and new developments related to the subject.

[Input]
- Topic: "Top 7 hot current trends in AI space."

[Keywords]
- Latest trends
- Key insights
- Important data
- Recent news
- Citations from reputable sources

[Output Format]
Output the information as bullet points summarizing the main points, and include citations with sources for each point.

```


### 2. OpenAI API Prompt (Blog Post Generation)

**Objective:** Generate a comprehensive and engaging blog post using information from Perplexity and the model's own knowledge.

**Prompt Template:**

```
You are a professional writer tasked with creating a high-quality blog post on the topic: "[User Subject]".

[Context]
Utilize the following up-to-date information to enrich the blog post:

[Perplexity Information]
[Insert bullet points and citations from Perplexity here.]

[Instructions]
- Combine the provided information with your existing knowledge.
- Write an engaging and informative blog post suitable for a knowledgeable audience.
- Include detailed explanations, examples, and insights.
- Organize the content with clear headings and subheadings.
- Ensure the content reflects the latest developments and is accurate.
- Maintain a formal and informative tone.
- Conclude with a summary and potential future perspectives.

[Output Format]
Your response must be a valid JSON array with exactly this structure:
[{
  "title": "The blog post title",
  "content": "The main blog post content",
  "links": []
}]

- Structured blog post with an introduction, body, and conclusion.
- Use markdown formatting for headings, subheadings, and lists.
- Cite the sources provided where appropriate.
```

**Example incorporating Perplexity Information:**

```
You are a professional writer tasked with creating a high-quality blog post on the topic: "Top 7 hot current trends in AI space."

[Context]
Utilize the following up-to-date information to enrich the blog post:

[Perplexity Information]
- **1. AI in Healthcare:** AI is revolutionizing diagnostics and treatment plans. [Source: Journal of Medical AI, 2023]
- **2. Natural Language Processing (NLP) Advances:** Significant improvements in language models enabling better human-computer interaction. [Source: NLP Conference Proceedings, 2023]
- **3. AI Ethics and Regulation:** Growing focus on ethical AI and governmental regulations. [Source: Tech Policy Review, 2023]
- *(Continue with points 4-7 from Perplexity output.)*

[Instructions and Output Format as above.]
```


### 3. Flux API Prompt (Image Generation)


**Objective:** Generate a realistic image relevant to the blog post's subject.

**Prompt Template:**

```
Generate an ultra-realistic image that represents the theme: "[User Subject]".

[Instructions]
- Focus on visual elements symbolizing the main concepts without including human figures.
- Avoid sci-fi or futuristic aesthetics; aim for a realistic and contemporary look.
- Incorporate relevant objects, environments, or symbols associated with the topic.
- Ensure the image is high-quality and visually engaging.

[Specifications]
- Style: Photorealistic
- Exclude: Sci-fi elements, human figures
- Emphasize: [Insert specific elements related to the subject]
```

**Example for "Top 7 hot current trends in AI space.":**

```
Generate an ultra-realistic image that represents the theme: "Top 7 hot current trends in AI space."

[Instructions]
- Focus on visual elements symbolizing artificial intelligence advancements, such as neural networks, data streams, machine learning algorithms, and robotics components.
- Avoid sci-fi or futuristic aesthetics; aim for a realistic and contemporary look.
- Do not include human figures in the image.
- Incorporate elements like modern servers, circuit boards, or code overlays to represent technological progress.

[Specifications]
- Style: Photorealistic
- Exclude: Sci-fi elements, human figures
- Emphasize: Neural networks, data visualization, AI technology
```


This refined formatting clarifies the structure and purpose of each prompt, making them easier to understand and use.  The code blocks are now correctly formatted, and the examples are more illustrative. The unnecessary language tags (csharp, vbnet, diff) have been removed. This improved version is more suitable for use as a reference document.
