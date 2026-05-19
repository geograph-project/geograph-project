# Repository Instructions for AI Agents

This document provides context and hard constraints for AI development agents (like Jules) working in this repository.

## Environment & Testing Constraints

- **PHP Target Version:** The site is currently deployed on **PHP 7.4**. All code modifications, features, and refactoring must strictly target and be compatible with PHP 7.4 syntax and features. Do not use PHP 8+ features (e.g., match expressions, named arguments, constructor property promotion, or union types).
- **No Local Runtime Execution:** Do NOT attempt to spin up a local PHP development server or execute automated runtime test suites (e.g., PHPUnit). The sandbox environment lacks the complex system dependencies and specific `php.ini` configurations required to run the application safely.
- **Verification Method:** To verify code changes, rely exclusively on **static analysis and syntax checks**. 
- **Definition of Done:** Run `php -l` (lint) on any modified or newly created PHP files. If the syntax linting passes with zero errors, consider the verification successful and proceed to submit the plan or pull request.

## Code Conventions

- **PHP Short Tags:** Legacy sections of this codebase may contain PHP short open tags (`<?`). Do not attempt to refactor or "fix" these system-wide unless explicitly asked to in the task prompt, as it can break legacy template rendering under certain environmental setups.

