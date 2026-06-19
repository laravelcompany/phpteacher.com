---
title: "Universal Vim Part 2 - Advanced Searching and Navigation"
description: "Take your Vim workflow to the next level with FZF fuzzy finding, ripgrep integration, buffer management, and project navigation techniques."
pubDate: "2022-09-12 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Vim", "Editor", "Navigation", "PHP", "Productivity"]
selected: false
---

In part one of the Universal Vim series, we built the base of a universal `vimrc` configuration — the UI, general settings, and the foundation for an IDE-like experience. This time we focus on search. We will enhance your ability to find files, locate content, and navigate projects using FZF, a performant fuzzy finder that works both inside Vim and on the command line.

FZF was created by Junegunn Choi and is one of those tools that feels fun to use while making you measurably more productive. It ships with support for Bash, ZSH, and Fish. It uses other command-line tools behind the scenes — ripgrep for content search, `fd` for file discovery, and `bat` for previews.

## What Is FZF?

FZF is a fuzzy finder. It takes a list of text and filters it by fuzzy matching each line against your search term. More precise results float to the top, but it is the lack of perfection that makes FZF powerful. You do not need to know the exact file name or the precise string you are looking for. Partial matches, typos, and approximate searches all work.

FZF uses smart case by default — if you type lowercase, the search is case-insensitive; include uppercase letters, and the search becomes case-sensitive. This matches the smart case configuration we set up in the vimrc from part one.

## Setting Up the Tools

Create a `bin` directory under your home directory and add it to your PATH:

```bash
mkdir -p $HOME/bin
```

Add this to your `~/.bash_profile`:

```bash
export PATH=$HOME/bin:$PATH
```

The supporting tools that make FZF effective:

- **ripgrep** (`rg`) — A highly performant replacement for `grep`, written in Rust, respects `.gitignore`
- **fd** — An enhanced replacement for the `find` command with sensible defaults
- **bat** — A `cat` replacement with syntax highlighting and line numbers

Install FZF by cloning the repository:

```bash
git clone --depth 1 https://github.com/junegunn/fzf.git ~/.fzf
~/.fzf/install
```

Say yes to the key bindings and shell integration.

## Command-Line Key Bindings

FZF provides three key bindings that change how you interact with the terminal:

| Binding | Action |
|---|---|
| `CTRL+R` | Search shell history with fuzzy matching |
| `CTRL+T` | Search for files and directories as command arguments |
| `ALT+C` | Fuzzy-find a directory and `cd` into it |

### Searching History

The default `CTRL+R` experience shows one result at a time. FZF replaces this with a full-screen filterable list. Start typing, and matching commands narrow down instantly.

### Selecting Files

`CTRL+T` includes files and directories, supporting multiple selections with the Tab key. Type `vim ` then press `CTRL+T` to fuzzy-search for a file to open. When you start Vim from your project root (which we configured with `set exrc` in part one), the search automatically scopes to the project.

### Changing Directories

`ALT+C` shows a list of all directories. Select one, and the `cd` command is constructed for you. This beats tab-completing through five levels of nesting.

## Command-Line Configuration

Configure FZF with environment variables in your `.bashrc`:

```bash
export FZF_DEFAULT_COMMAND='fd --type f'

FZF_DEFAULT_OPTS=""
FZF_DEFAULT_OPTS+=' --multi'
FZF_DEFAULT_OPTS+=' --tabstop=4'
FZF_DEFAULT_OPTS+=' --border=sharp'
FZF_DEFAULT_OPTS+=' --layout=default'

export FZF_DEFAULT_OPTS
```

The `--preview` option shows file contents as you scroll:

```bash
fzf --preview 'bat --style=numbers --color=always --tabs=4 {}'
```

Create an alias to save typing:

```bash
alias fzfp="fzf --preview 'bat --style=numbers --color=always --tabs=4 {}'"
```

## The rfv Script: RipGrep + FZF Viewer

The FZF author provides a script that combines ripgrep with FZF for searching file contents. Save it as `~/bin/rfv`:

```bash
#!/usr/bin/env bash

# rfv - RipGrep FZF Viewer
# Search file contents with ripgrep, preview with bat, open in Vim

INITIAL_QUERY=""
RG_PREFIX="rg --column --line-number --no-heading --color=always --smart-case "

FZF_DEFAULT_COMMAND="$RG_PREFIX $(printf %q "$INITIAL_QUERY")" \
  fzf --bind "change:reload:$RG_PREFIX {q} || true" \
      --ansi --disabled --query "$INITIAL_QUERY" \
      --height=80% --layout=reverse \
      --preview 'bat --style=numbers --color=always --tabs=4 {}' \
      --preview-window 'right:50%' \
      --bind "enter:become(vim {1} +{2})"
```

Make it executable:

```bash
chmod +x ~/bin/rfv
```

Now you can type `rfv` followed by a search term to search all files, preview matches, and press Enter to open the file at the exact line.

## FZF in Vim: The Plugin

The `fzf.vim` plugin brings this same experience inside Vim. Install Vim-Plug first if you do not have a plugin manager:

```bash
curl -fLo ~/.vim/autoload/plug.vim --create-dirs \
    https://raw.githubusercontent.com/junegunn/vim-plug/master/plug.vim
```

Add to your `~/.vimrc`:

```vim
call plug#begin('~/.vim/plugged')

Plug 'junegunn/fzf', {'dir': '~/.fzf', 'do': './install --all'}
Plug 'junegunn/fzf.vim'

call plug#end()
```

Run `:PlugInstall` to install.

### Key FZF Functions in Vim

| Function | What It Does |
|---|---|
| `:Files` | Fuzzy-search all files in the current directory |
| `:GFiles` | Search only Git-tracked files |
| `:GFiles?` | Show `git status` output |
| `:Buffers` | Switch between open buffers |
| `:Lines` | Search across all open buffers |
| `:BLines` | Search within the current buffer |
| `:Rg` | Search file contents with ripgrep |
| `:Marks` | Browse Vim marks |
| `:Maps` | Browse key mappings |
| `:Colors` | Browse and preview color schemes |

### Window Layout

Configure how the FZF window appears:

```vim
let g:fzf_layout = { 'window': { 'width': 0.9, 'height': 0.9, 'relative': v:true } }
```

### Preview Window

```vim
" Show preview on the right
let g:fzf_preview_window = ['right:50%', 'ctrl/']

" Hide preview by default, toggle with ctrl+/
" let g:fzf_preview_window = ['right:50%:hidden', 'ctrl/']
```

### Key Mappings

Map FZF functions to your leader key (spacebar from part one):

```vim
" Open
nnoremap <Leader>ob :Buffers<CR>
nnoremap <Leader>of :Files<CR>
nnoremap <Leader>og :GFiles<CR>

" Search
nnoremap <Leader>sc :Rg
nnoremap <Leader>sf :Rg!
nnoremap <Leader>sa :Lines<CR>
nnoremap <Leader>sb :BLines<CR>

" Display
nnoremap <Leader>dg :GFiles?<CR>
nnoremap <Leader>db :Marks<CR>
nnoremap <Leader>dm :Maps<CR>
nnoremap <Leader>dt :Colors<CR>
```

The "verb noun" naming convention — `<Leader>ob` for "open buffers", `<Leader>sc` for "search common" — keeps mappings predictable. If you switch from `:Files` to a different tool later, the mapping name still makes sense.

## Action Bindings

When FZF displays results, the default action is to open the file in the current buffer. You can configure additional actions:

```vim
let g:fzf_action = {
    \ 'ctrl-t': 'tab split',
    \ 'ctrl-x': 'split',
    \ 'ctrl-v': 'vsplit'
    \ }
```

`CTRL+X` splits horizontally (top and bottom). `CTRL+V` splits vertically (left and right). `CTRL+T` opens a new tab. Vim's sword-swinging logic: a horizontal swing divides top and bottom, a vertical swing divides left and right.

## Project Navigation Without Netrw

One thing you notice after using FZF is how little you need a file explorer. When you can fuzzy-find any file in under two seconds, browsing a directory tree feels wasteful. FZF does not replace file explorers entirely — you will still want `netrw` or `NERDTree` for quick glances — but for daily navigation, `:Files` is your primary tool.

Buffer switching with `:Buffers` replaces `:ls` and `:bnext`. Content searching with `:Rg` replaces `:vimgrep`. The combination of FZF, ripgrep, and `fd` creates a workflow where you spend less time thinking about file locations and more time writing code.

## Going Further

FZF documentation covers dozens of customization options — custom preview commands, alternate key bindings, color schemes, and integration with Git, brew, and npm. The `FZF_DEFAULT_OPTS` variable accepts any FZF command-line option, so you can tune behavior globally.

The complete universal vimrc is available on GitHub at `github.com/andrewwoods/universal-vim`.

## Conclusion

FZF makes you faster because it removes the friction of finding things. On the command line, the key bindings for history, files, and directories replace multi-step navigation with a single keystroke. Inside Vim, the plugin functions replace file explorers, buffer lists, and grep commands with a consistent fuzzy interface.

Fun and productivity are not adversaries. FZF proves that. Take the time to set it up, learn the key bindings, and watch your muscle memory develop. By the next article, when we cover Git integration from within Vim, FZF will already feel indispensable.
