# Architecture: rector

## Purpose

An automated PHP upgrade and refactoring tool. Rector applies configurable rule sets (rectors) to PHP ASTs to automatically modernise code — upgrading syntax, fixing patterns, adding type declarations, and removing dead code.

## Directory Structure

```
src/
  Application/              - File processing pipeline (parse → apply rectors → print)
  Autoloading/              - Bootstrap file inclusion and custom autoloader setup
  BetterPhpDocParser/       - Enhanced PHPDoc parser that preserves formatting
  Bootstrap/                - Config resolution and extension loading
  Bridge/                   - Set provider and rector collection utilities
  Caching/                  - Change detection and file-level caching to skip unchanged files
  ChangesReporting/         - Output formatters (console, JSON, JUnit, GitLab, GitHub)
  Comments/                 - Comment-preserving node traversal and PHPDoc manipulation
  Config/                   - RectorConfig: the fluent API for rector.php configuration files
  Configuration/            - Runtime configuration value objects
  Console/                  - Symfony Console commands (process, list-rectors, etc.)
  Contract/                 - Core interfaces (Rector_Interface, Node visitors, etc.)
  DependencyInjection/      - Symfony DI container setup and compiler passes
  FileSystem/               - File discovery, reader, and writer abstractions
  NodeAnalyzer/             - Helpers for querying AST node properties
  NodeDecorator/            - Attaches metadata to nodes before rector processing
  NodeManipulator/          - Helpers for mutating AST nodes
  NodeNameResolver/         - Resolves node names to fully-qualified forms
  NodeRemover/              - Safely removes nodes and their surrounding context
  NodeTypeResolver/         - Resolves Psalm/PHPStan types from AST nodes
  PhpParser/                - PHP-Parser integration: parsing, printing, and comparison
  PostRector/               - Post-processing pass: class renaming, use statement cleanup
  Rector/                   - Abstract base classes for rector rules
  StaticReflection/         - Static class/interface/trait reflection without autoloading
  TypeDeclaration/           - Helpers specific to adding/modifying type declarations
  ValueObject/              - Immutable data objects: File, Rector change, etc.
rules/                      - Rule sets (CodeQuality, DeadCode, Php80, Php81, etc.)
config/                     - Default DI service definitions
```

## Key Design Decisions

- **AST-based transformation**: Rector works at the PHP-Parser AST level, never via text manipulation, ensuring structurally correct output.
- **Rule isolation**: Each rector rule is a single-responsibility class implementing `Rector_Interface` with a declared `get_node_types()` list — only applicable nodes are visited.
- **File-level caching**: `Changed_Files_Detector` uses file content hashes to skip files where no change is possible, dramatically reducing runtime on large codebases.
- **Symfony DI**: The entire tool is wired via Symfony's dependency injection container, enabling extensions and custom rule sets to be registered without modifying core code.
- **BetterPhpDocParser**: A custom PHPDoc parser that preserves original whitespace and formatting, preventing Rector from inadvertently reformatting doc blocks.

## Extension Points

- Write a custom rector by extending `Abstract_Rector` and implementing `get_node_types()` and `refactor()`.
- Register custom rule sets via `RectorConfig::sets()` in `rector.php`.
- Add custom report formats by implementing `Output_Formatter_Interface`.

## Dependency Flow

```
Console Command (process)
  └─> Application_File_Processor (iterates files)
        └─> File_Processor (per file)
              └─> PHP-Parser (parse)
              └─> NodeTraverser (apply rectors)
                    └─> each Rector_Interface rule
              └─> PHP-Parser Printer (print modified AST)
              └─> Changed_Files_Detector (cache result)
```
