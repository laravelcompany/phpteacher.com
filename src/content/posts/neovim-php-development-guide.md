---
title: "NeoVim for PHP Development: A Grumpy Programmer's Setup Guide"
description: "Set up NeoVim as a PHP IDE. Complete guide covering LSP, autocompletion, linting, PHPCS, Psalm, Telescope, and debugging configuration for modern PHP development."
pubDate: "2023-04-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["NeoVim", "PHP", "Vim", "IDE", "LSP", "PHPCS", "Psalm", "EditorConfig", "Developer Tools", "Productivity"]
selected: false
---

You might know Chris Hartjes from his years of advocating for PHP testing, his entertaining conference talks, or his firmly held opinions about tools. In this Workshop column, he tackles an editor that inspires fervent devotion and equally fervent frustration: NeoVim.

NeoVim is more than Vim with a facelift. It is a hyperextensible text editor with a modern plugin architecture based on Lua, a well-documented API, and a passionate community building everything from language servers to debugging interfaces. For PHP developers willing to invest the setup time, it can rival PhpStorm in functionality while delivering the keyboard-centric workflow that modal editing provides.

This guide walks through a complete NeoVim setup for PHP development: Language Server Protocol integration, autocompletion, linting with PHPCS and Psalm, fuzzy file finding, and the philosophy of building your own IDE.

## What You'll Learn

- How NeoVim differs from Vim and why Lua matters for plugins
- How to configure Language Server Protocol for PHP with Intelephense
- How to set up PHPCS and Psalm as linters inside NeoVim
- How to use Telescope for fuzzy file and code searching
- How to configure autocompletion with nvim-cmp
- The philosophy of building a custom IDE through plugin composition

## What Is NeoVim?

NeoVim describes itself as a hyperextensible Vim-based text editor. To understand what that means, you need to understand its progenitor.

Vim (Vi Improved) is a highly configurable text editor built to make creating and changing text efficient. It is a modal editor, meaning its keys behave differently depending on the current mode — normal mode for navigation, insert mode for typing, visual mode for selection, and command mode for operations. This modal nature is the source of both Vim's power and its learning curve.

NeoVim started as a project to clean up Vim's codebase. The original Vim code had accumulated decades of technical debt. NeoVim rewrote the editor using modern languages and design patterns, reducing bloat and making the codebase accessible to contributors. The big selling point was replacing Vimscript with Lua as the primary language for configuration and plugins.

Lua is a lightweight, fast scripting language that is dramatically more pleasant to work with than Vimscript. Plugin authors flocked to NeoVim, and the ecosystem exploded. Today, NeoVim has a stable API, an active community, and plugin support that covers every major programming language — including PHP.

### Why Not Just Use PhpStorm?

Chris Hartjes is upfront: he pays for PhpStorm because he believes in supporting the PHP ecosystem's tools. PhpStorm is an excellent IDE. But he prefers NeoVim's modal editing, keyboard-centric workflow, and the ability to build exactly the environment he wants.

The honest answer is that NeoVim requires more setup. PhpStorm works out of the box. NeoVim requires configuring Language Servers, autocompletion engines, linters, and formatters manually. The trade-off is total control. Your editor behaves exactly the way you want, triggers the keybindings you define, and includes only the features you use.

## Building Your Own IDE With Plugins

Chris's NeoVim setup focuses on four goals:

1. Use Language Server Protocol tools to analyze PHP code
2. Make it easy to find files in projects
3. Make it easy to find code within files
4. Continuously evaluate tools and remove anything that does not work

The plugin list evolves constantly. Here is the current configuration, along with explanations of what each plugin does and why it matters for PHP development.

### Plugin Management With Packer

Packer is the package manager for NeoVim plugins. It integrates natively with Lua, handles dependencies, and supports lazy loading:

```lua
return require('packer').startup(function()
    use 'wbthomason/packer.nvim'
    -- Additional plugins go here
end)
```

Packer automatically installs, updates, and configures plugins. Running `:PackerSync` installs new plugins and updates existing ones. The `use` function accepts plugin specifications from GitHub, GitLab, or any other source.

### Language Server Protocol Configuration

The Language Server Protocol (LSP), created by Microsoft, provides a standardized interface between editors and language-specific code analysis tools. An LSP server for PHP understands your code's structure, types, and references. The NeoVim LSP client communicates with the server to provide:

- Go-to-definition and go-to-implementation
- Find references
- Hover type information
- Autocompletion
- Code actions (quick fixes, refactoring)
- Signature help

Here is the LSP configuration file for PHP development:

```lua
local nvim_lsp = require("lspconfig")

local opts = { noremap = true, silent = true }
vim.api.nvim_set_keymap('n', '<space>e', '<cmd>lua vim.diagnostic.open_float()<CR>', opts)
vim.api.nvim_set_keymap('n', '[d', '<cmd>lua vim.diagnostic.goto_prev()<CR>', opts)
vim.api.nvim_set_keymap('n', ']d', '<cmd>lua vim.diagnostic.goto_next()<CR>', opts)
vim.api.nvim_set_keymap('n', '<space>q', '<cmd>lua vim.diagnostic.setloclist()<CR>', opts)
vim.api.nvim_set_keymap('n', '<space>f', '<cmd>lua vim.lsp.buf.formatting()<CR>', opts)

local on_attach = function(client, bufnr)
    vim.api.nvim_buf_set_option(bufnr, 'omnifunc', 'v:lua.vim.lsp.omnifunc')

    vim.api.nvim_buf_set_keymap(bufnr, 'n', 'gD', '<cmd>lua vim.lsp.buf.declaration()<CR>', opts)
    vim.api.nvim_buf_set_keymap(bufnr, 'n', 'gd', '<cmd>lua vim.lsp.buf.definition()<CR>', opts)
    vim.api.nvim_buf_set_keymap(bufnr, 'n', 'K', '<cmd>lua vim.lsp.buf.hover()<CR>', opts)
    vim.api.nvim_buf_set_keymap(bufnr, 'n', 'gi', '<cmd>lua vim.lsp.buf.implementation()<CR>', opts)
    vim.api.nvim_buf_set_keymap(bufnr, 'n', '<C-k>', '<cmd>lua vim.lsp.buf.signature_help()<CR>', opts)
    vim.api.nvim_buf_set_keymap(bufnr, 'n', '<space>D', '<cmd>lua vim.lsp.buf.type_definition()<CR>', opts)
    vim.api.nvim_buf_set_keymap(bufnr, 'n', '<space>rn', '<cmd>lua vim.lsp.buf.rename()<CR>', opts)
    vim.api.nvim_buf_set_keymap(bufnr, 'n', '<space>ca', '<cmd>lua vim.lsp.buf.code_action()<CR>', opts)
    vim.api.nvim_buf_set_keymap(bufnr, 'n', 'gr', '<cmd>lua vim.lsp.buf.references()<CR>', opts)
end

-- PHP Language Server: Intelephense
nvim_lsp.intelephense.setup {
    cmd = { "intelephense", "--stdio" },
    filetypes = { "php" },
    on_attach = on_attach,
}
```

Intelephense is the most capable PHP language server. The free version provides basic autocompletion and navigation. The paid version adds advanced features like rename, code actions, and detailed type analysis. It runs via Node.js, so install it with `npm install -g intelephense`.

The keybindings deserve special attention:

- `gd`: Go to definition — jumps to where a class, method, or variable is defined
- `K`: Hover — shows type information and docblock content
- `gi`: Go to implementation — shows concrete implementations of an interface method
- `gr`: Find references — lists all locations where a symbol is used
- `<space>rn`: Rename symbol — renames a variable, method, or class across the entire project
- `<space>ca`: Code action — triggers quick fixes suggested by the language server

### Linting With PHPCS and Psalm

Static analysis is essential for PHP quality. PHPCS enforces coding standards. Psalm detects type errors and potential bugs. The diagnostic-languageserver plugin connects these tools to NeoVim's diagnostic system:

```lua
local filetypes = {
    php = { "phpcs", "psalm" },
}

local linters = {
    phpcs = {
        command = "vendor/bin/phpcs",
        sourceName = "phpcs",
        debounce = 300,
        rootPatterns = { "composer.lock", "vendor", ".git" },
        args = { "--report=emacs", "-s", "-" },
        formatLines = 1,
        formatPattern = {
            "^.*:(\\d+):(\\d+):\\s+(.*)\\s+-\\s+(.*)",
            { line = 1, column = 2, message = 4, security = 3 },
        },
        securities = { error = "error", warning = "warning" },
        requiredFiles = { "vendor/bin/phpcs" },
    },
    psalm = {
        command = "./vendor/bin/psalm",
        sourceName = "psalm",
        debounce = 100,
        rootPatterns = { "composer.lock", "vendor", ".git" },
        args = { "--output-format=emacs", "--no-progress" },
        formatLines = 1,
        formatPattern = {
            "^[^ =]+ =(\\d+) =(\\d+) =(.*)\\s-\\s(.*)",
            { line = 1, column = 2, message = 4, security = 3 },
        },
        securities = { error = "error", warning = "warning" },
        requiredFiles = { "vendor/bin/psalm" },
    },
}

nvim_lsp.diagnosticls.setup {
    on_attach = on_attach,
    filetypes = vim.tbl_keys(filetypes),
    init_options = {
        filetypes = filetypes,
        linters = linters,
    },
}
```

Both linters run automatically when you save a PHP file. Errors appear inline in the buffer, in the location list, and in the gutter. The `debounce` setting prevents re-running the linter on every keystroke — 300ms for PHPCS, 100ms for Psalm — balancing responsiveness with thoroughness.

### Fuzzy Finding With Telescope

Telescope provides fuzzy finding over files, buffers, help tags, grep results, and more. It uses `fd` (a faster alternative to `find`) for file searching:

```lua
use 'nvim-lua/plenary.nvim'
use 'nvim-telescope/telescope.nvim'
use 'sharkdp/fd'
use { 'nvim-telescope/telescope-fzf-native.nvim', run = 'make' }
```

The keybindings are intuitive:

- `<C-p>`: Find files in the project directory
- `<C-g>`: Live grep across all project files
- `<C-b>`: Switch between open buffers

Telescope is the first tool Chris configures on any new machine. It replaces the mental overhead of remembering file paths with instant fuzzy matching.

### Autocompletion With nvim-cmp

Modern Vim autocompletion no longer requires the arcane configuration it once did. nvim-cmp provides a fast, LSP-integrated completion engine:

```lua
use 'hrsh7th/nvim-cmp'
use 'hrsh7th/cmp-nvim-lsp'
```

nvim-cmp hooks into your LSP server and presents completions as you type. It integrates with snippets, buffers, paths, and more through its source system. The LSP source provides type-aware completions from Intelephense.

### Visual Enhancements

Beyond functionality, NeoVim supports a rich visual ecosystem:

```lua
use 'dracula/vim'           -- Colorscheme
use 'nvim-treesitter/nvim-treesitter'  -- Improved syntax highlighting
use 'onsails/lspkind-nvim'  -- Icons for LSP completions
```

Tree-sitter provides syntax-aware highlighting that understands code structure rather than relying on regex patterns. This means PHP code is highlighted based on actual parsing — variables, methods, and keywords are colored correctly even in complex expressions.

### Version Control Integration

```lua
use 'airblade/vim-gitgutter'
```

GitGutter shows the git status of each line in the gutter column. Added lines show a green `+`, modified lines show a yellow `~`, and deleted lines show a red `-`. This gives instant feedback about what has changed since the last commit without leaving the editor.

### PHP-Specific Plugins

```lua
use 'tpope/vim-dispatch'          -- Async command execution
use 'StanAngeloff/php.vim'        -- PHP syntax improvements
use 'stephpy/vim-php-cs-fixer'    -- PHP CS Fixer integration
use 'noahfrederick/vim-composer'  -- Composer integration
```

vim-dispatch runs PHPUnit tests asynchronously — the test runs in the background while you continue editing. php.vim improves syntax highlighting for modern PHP features. vim-composer provides commands like `:ComposerInstall` and `:ComposerRequire` from within the editor.

### Coding Style Enforcement

```lua
use 'editorconfig/editorconfig-vim'
```

EditorConfig ensures your editor respects the project's coding style automatically. When you open a PHP file, the plugin reads the `.editorconfig` file and applies indentation style, line endings, and character encoding. This eliminates debates about tabs versus spaces — the file's settings apply without manual intervention.

## What Is Next?

Chris's to-do list for NeoVim includes three items:

1. **An Xdebug solution**: Debugging PHP inside NeoVim requires configuring a debug adapter protocol (DAP) client. Plugins like `mfussenegger/nvim-dap` provide the integration, but setup varies by PHP version and Xdebug configuration.

2. **GitHub Copilot support**: AI-assisted coding is becoming essential. Copilot has a NeoVim plugin that provides GPT-powered completions within the editor.

3. **In-editor test execution**: Running PHPUnit tests from inside NeoVim without leaving the editor, with results displayed in a quickfix window.

## Real-World Use Cases

### Full-Time PHP Development

A full-time PHP developer using NeoVim with this configuration can navigate a 100,000-line codebase as efficiently as they could with PhpStorm. LSP provides jump-to-definition, find references, and rename across the entire project.

### Pair Programming

NeoVim supports pair programming through `:terminal` splits and tmux integration. Two developers sharing a tmux session can each control their own NeoVim instance, or one can drive while the other watches in a mirrored split.

### Remote Development

NeoVim runs over SSH with zero latency. A PHP developer connecting to a remote server can have the same editing experience they have locally — no GUI forwarding required.

## Best Practices

- **Start with a minimal configuration**: Only add plugins you actually need. Each plugin is a dependency that can break or slow down startup.
- **Learn the shortcuts before adding plugins**: NeoVim's built-in keybindings are powerful. Master navigation, searching, and window management before adding complexity.
- **Use EditorConfig**: It eliminates an entire class of formatting disagreements and enforces project standards automatically.
- **Keep configuration in version control**: Your NeoVim configuration should live in a Git repository. This makes it easy to sync across machines and roll back changes.
- **Invest in muscle memory**: The real productivity gain comes from not thinking about keybindings. Practice until the motions are automatic.

## Common Mistakes to Avoid

- **Installing too many plugins at once**: Add plugins one at a time and learn each one before moving to the next.
- **Ignoring built-in features**: NeoVim's built-in LSP client, diagnostics, and terminal are powerful. Do not add plugin equivalents unless the built-in version is genuinely insufficient.
- **Copying configurations without understanding them**: Chris's configuration works for Chris. Your needs may differ. Understand each line before adding it to your config.
- **Not reading plugin documentation**: Most plugins have excellent `:help` pages. Reading them saves hours of trial and error.
- **Forgetting to update plugins**: Run `:PackerSync` regularly. PHP tools change frequently, and updates often improve performance or add features.

## Frequently Asked Questions

**Is NeoVim better than PhpStorm for PHP development?**
It depends on your priorities. PhpStorm provides a polished, integrated experience out of the box. NeoVim offers total customizability and a keyboard-centric workflow. Neither is objectively better — use what fits your working style.

**How long does it take to learn NeoVim?**
Basic proficiency takes a few days of consistent use. True fluency — where keybindings become muscle memory — takes weeks to months. The investment pays off over a career.

**Can NeoVim match PhpStorm's refactoring capabilities?**
For most common refactorings (rename, extract method, change signature), yes. Intelephense provides code actions that cover the majority of refactoring needs. Some advanced refactorings require additional configuration or manual work.

**Do I need the paid version of Intelephense?**
The free version covers basic autocompletion and navigation. The paid version adds rename, code actions, and advanced type analysis. For professional PHP development, the paid license is worth the cost.

**How do I debug PHP with NeoVim?**
Use nvim-dap with the PHP debug adapter. Configure Xdebug to connect to the DAP client, set breakpoints in NeoVim, and step through code in the editor. The setup requires more configuration than PhpStorm's zero-configuration debugging but works well once configured.

**Can I use Laravel with NeoVim?**
Yes. Laravel-specific plugins provide Artisan command integration, Blade template syntax highlighting, and route list navigation. The general LSP and autocompletion setup works with any PHP framework.

**Does NeoVim support PHP 8.x features?**
Yes, both Intelephense and Tree-sitter support PHP 8.x features including enums, readonly properties, named arguments, and match expressions.

**What about WordPress development?**
Intelephense supports WordPress code patterns. The main challenge is the WordPress codebase's inconsistent use of modern PHP practices. Static analysis tools like Psalm help catch issues that Intelephense might miss.

## Conclusion

NeoVim is not the right editor for everyone. It requires setup, configuration, and a willingness to invest in learning. For developers who value a keyboard-centric workflow, total control over their environment, and the ability to build exactly the tools they need, it is unmatched.

Chris Hartjes's setup demonstrates that NeoVim can handle professional PHP development. LSP integration provides IDE-level code intelligence. Telescope eliminates the friction of file navigation. Linters catch errors before they reach production. And the entire environment runs over SSH, in a terminal, without a GUI.

Start with a minimal configuration. Add plugins one at a time. Learn the keybindings until they become automatic. Your editor is the tool you spend the most time with — investing in making it work the way you think is one of the highest-leverage activities in a developer's career.

Now if you will excuse Chris, he needs to find the keybinding to exit NeoVim. It is around here somewhere. `:wq`.
