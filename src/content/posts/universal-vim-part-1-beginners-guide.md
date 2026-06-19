---
title: "Universal Vim - Part 1: Getting Started With the Editor"
description: "Start your Vim journey with this beginner-friendly guide covering installation, modes, basic navigation, editing commands, and vimrc configuration for PHP development."
pubDate: "2022-08-12 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Vim", "Editor", "PHP", "Development", "Productivity"]
selected: false
---

Vim is the editor whose name is on the lips of every developer—whether they love it or hate it. It's the most popular descendant of Vi, and its modern fork Neovim has brought renewed energy to the ecosystem. This article covers Vim and Neovim together, with a single configuration file that works for both.

This is Part One: no plugins required. You won't need to worry about package managers or plugin registries. Everything here is built into Vim out of the box.

## Why Vim?

Vim's modal editing is its superpower. Instead of holding modifiers to perform actions, you switch modes. Normal mode lets you navigate and manipulate text. Insert mode lets you type. Visual mode lets you select. Each mode optimizes for a different task, and the result is an editing experience that, once learned, feels faster than any other approach.

For PHP developers specifically, Vim offers:
- Blazing fast file navigation without touching the mouse
- Powerful search and replace with regex support
- Built-in file explorer and terminal
- Syntax highlighting for PHP and dozens of other languages
- Extensive customization through `.vimrc`

## Installation

### Linux

```bash
sudo apt install vim        # Debian/Ubuntu
sudo dnf install vim        # Fedora
sudo pacman -S vim          # Arch
```

### macOS

```bash
brew install vim
brew install neovim
```

### Windows

Download the installer from vim.org or use Chocolatey:

```bash
choco install vim
```

## Understanding Vim Modes

Vim has three primary modes you need to know:

### Normal Mode

This is the default mode when Vim starts. Every key is a command, not a character to insert. You navigate, copy, delete, and paste from here. Press `Escape` from any other mode to return here.

### Insert Mode

Press `i` in Normal mode to enter Insert mode. Now your keystrokes become text. Press `Escape` to leave.

### Visual Mode

Press `v` in Normal mode to enter Visual mode. You can select text with movement keys, then act on the selection (delete, copy, replace).

## Basic Navigation

The home row gives you four movement keys:

| Key | Movement |
|-----|----------|
| `h` | Left one character |
| `j` | Down one line |
| `k` | Up one line |
| `l` | Right one character |

Word-level navigation speeds things up considerably:

| Key | Movement |
|-----|----------|
| `w` | Forward to start of next word |
| `b` | Backward to start of word |
| `e` | Forward to end of word |
| `W` | Forward to next WORD (whitespace-separated) |

File-level jumps:

| Key | Movement |
|-----|----------|
| `gg` | Go to first line |
| `G` | Go to last line |
| `:42` | Go to line 42 |
| `Ctrl-d` | Page down half screen |
| `Ctrl-u` | Page up half screen |
| `%` | Jump to matching bracket |

## Editing Commands

### Insertion

| Command | Action |
|---------|--------|
| `i` | Insert before cursor |
| `a` | Append after cursor |
| `o` | Open new line below |
| `O` | Open new line above |
| `I` | Insert at beginning of line |
| `A` | Append at end of line |

### Deletion

| Command | Action |
|---------|--------|
| `x` | Delete character under cursor |
| `dd` | Delete current line |
| `dw` | Delete word |
| `d$` | Delete to end of line |
| `d3j` | Delete 3 lines down |
| `D` | Delete to end of line |

### Copy and Paste

| Command | Action |
|---------|--------|
| `yy` | Yank (copy) current line |
| `yw` | Yank word |
| `p` | Paste after cursor |
| `P` | Paste before cursor |
| `dd` then `p` | Cut and paste (move line) |

All deletion commands also yank the deleted text. Deleting a line with `dd` lets you paste it elsewhere with `p`.

### Undo and Redo

| Command | Action |
|---------|--------|
| `u` | Undo |
| `Ctrl-r` | Redo |
| `.` | Repeat last change |

## Building Your vimrc

The vimrc file controls Vim's behavior. Neovim uses `~/.config/nvim/init.vim`, and Vim uses `~/.vimrc`. The configuration below works for both.

Structure the vimrc into six sections:

```vim
" ==== Settings ====================
set nocompatible

" **** User Interface *************

" **** User Experience ************

" ==== Plugins ====================

" ==== Variables ==================

" ==== Remaps =====================

" ==== Auto Commands ==============
```

### User Interface Settings

```vim
" Show real line numbers
set number

" Relative line numbers (toggle with :set relativenumber!)
set norelativenumber

" Control the left edge of the window
set numberwidth=6

" Always display the status bar
set laststatus=2

" Custom status line
if has("statusline")
    set statusline=
    set statusline+=\<%n\>
    set statusline+=\ %t
    set statusline+=\ %m%r%h%w
    set statusline+=%=
    set statusline+=(%{&ff})
    set statusline+=\ line:%l\/%L
    set statusline+=\ (%p%%)
    set statusline+=\ col:%c
endif

" Display guide at column 81
set colorcolumn=81
hi ColorColumn ctermbg=235
```

Relative line numbers take practice. With `relativenumber` enabled, every line is numbered by its distance from the cursor. This makes commands like `6j` or `5dd` intuitive since the target line number matches the count you need.

### User Experience Settings

```vim
syntax on

colorscheme default

" Look for project-specific config file
set exrc

" Change buffers without saving first
set hidden

" Error settings
set noerrorbells
set novisualbell

" Briefly jump to matching bracket
set showmatch

" Window splitting behavior
set splitbelow
set splitright
```

The `hidden` setting is particularly useful. It allows you to switch buffers without saving first, reducing friction when jumping between files.

### Content Settings for PHP Development

PSR-12 is the PHP coding standard, and these settings align with it:

```vim
set tabstop=4
set softtabstop=4
set shiftwidth=4
set smartindent

" Convert tabs to spaces
set expandtab

set nojoinspaces
set textwidth=0
set wrapmargin=0
set nowrap
set linebreak

" Minimum screen lines above/below cursor
set scrolloff=8
set sidescroll=12
set sidescrolloff=12

" Display invisible characters
set listchars=""
set listchars+=tab:>¬
set listchars+=eol:¶
set listchars+=trail:∙
set listchars+=extends:»
set listchars+=precedes:«
set listchars+=nbsp:¤
```

PHP-FIG recommends 4 spaces for indentation. Setting `expandtab` ensures tabs are converted to spaces, keeping your code consistent with PSR-12.

### Search Settings

```vim
" Highlight search results
set hlsearch
set incsearch

" Case-insensitive search unless capital letters used
set ignorecase
set smartcase

" Wrap around file when searching
set wrapscan
```

The `smartcase` setting is invaluable. Type your search term in lowercase and Vim matches case-insensitively. Include a capital letter and Vim switches to case-sensitive mode.

## File Explorer

Vim's built-in file explorer (`netrw`) gives you IDE-style file navigation without plugins:

```vim
" Leader key is space
let mapleader=" "

" Toggle file explorer
nnoremap <Leader>fe :Lexplore<CR>

" netrw settings
let g:netrw_banner = 0
let g:netrw_winsize = 25
```

The `Lexplore` command opens the file explorer in a vertical split on the left side of the window. The `winsize` setting (25) keeps the explorer compact while giving your content most of the screen.

## Terminal Integration

Vim includes a built-in terminal. No need to switch windows:

```vim
nnoremap <Leader>th :terminal<CR>
nnoremap <Leader>tv :vertical terminal<CR>
```

`<Leader>th` opens a horizontal terminal split. `<Leader>tv` opens a vertical one. This keeps you in Vim for quick commands without leaving your editing context.

## PHP-Specific Tips

### Syntax Checking

Vim's `syntax on` enables PHP highlighting. For deeper checks, run PHP's lint from within Vim:

```vim
" Check PHP syntax
command! PhpCheck !php -l %
nnoremap <Leader>pc :PhpCheck<CR>
```

### Navigating PHP Files

Use these motions to navigate PHP code:

- `[[` — Go to previous function/class
- `]]` — Go to next function/class
- `gd` — Go to local definition (variable under cursor)
- `K` — Look up PHP manual for keyword under cursor

### Quick File Switching

```vim
" Switch between .php and matching test file
nnoremap <Leader>st :e %:r:r.test.php<CR>
```

## The Complete vimrc

The full configuration file is available on GitHub at `github.com/andrewwoods/universal-vim`. Build yours incrementally: start with a few settings, use Vim for a week, then add more. The goal is a configuration that feels natural to you, not a copy of someone else's setup.

## Conclusion

Vim's modal editing, composable commands, and extensive built-in features make it a powerful editor for PHP development without a single plugin. Start with basic navigation (`h/j/k/l`), add editing commands (`i`, `a`, `o`, `dd`, `yy`, `p`), and incrementally build your vimrc as you discover what you need.

Part Two will introduce plugins that turn Vim into a full PHP IDE. For now, master the fundamentals. Every keystroke saved is a microsecond of flow preserved.
