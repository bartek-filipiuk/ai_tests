## INTRODUCTION

The AI page generator module is a Drupal 10 module that allows administrators to generate multiple pages based on JSON input. It provides a form where users can input JSON data describing the pages to be created, and then uses a batch process to create those pages efficiently.

The primary use cases for this module are:

- Bulk creation of pages with structured content
- Automated page generation based on external data sources
- Quick prototyping of site structures

## REQUIREMENTS

This module requires the following modules:

- Node (Core)
- Media (Core)
- Paragraphs

## INSTALLATION

Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/node/895232 for further information.

## CONFIGURATION

1. Navigate to Administration » Configuration » Content » AI Page Generator
2. Enter the JSON data for the pages you want to create
3. Click "Generate Pages" to start the batch process

## PARAGRAPH TYPE TREE

To view a JSON-formatted tree of all paragraph types and their text and media fields:

1. Navigate to Administration » Configuration » Content » AI Page Generator » Paragraph Type Tree
2. You will see a JSON representation of all paragraph types and their relevant fields

## JSON STRUCTURE

The module accepts JSON input in the following format:
