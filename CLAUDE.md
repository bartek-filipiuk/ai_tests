# Drupal 10 Code Review Guidelines

## Overview
Check the code if contains good coding practices for Drupal 10. When reviewing, focus on identifying these anti-patterns and suggesting Drupal 10 best practices instead.


## Key Issues to Identify

### Dependency Injection
Look for direct service access via `\Drupal::service()` instead of proper constructor injection.

### Static Methods
Identify static method usage in services which hinders testability.

### Type Declarations
Flag missing parameter and return type declarations.

### Documentation
Spot incomplete or missing docblocks for classes and methods.

### Input Validation
Identify places where input data is used without proper validation.

### Translation Handling
Look for improper translation handling (global `t()` function or direct translation service calls).

### Code Structure
Identify missing strict type declarations and other PHP 8.3+ features.

### Configuration Files
Check for incomplete or improperly structured YAML files.

### Database Operations
Identify direct SQL queries instead of using the Query Builder API.

## Goal
The purpose of this review is educational - to demonstrate how to identify common Drupal coding issues and suggest better alternatives that follow Drupal coding standards.
