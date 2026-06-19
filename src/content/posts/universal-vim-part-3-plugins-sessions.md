---
title: "Universal Vim Part 3 - Plugins, Sessions, and PHP Development"
description: "Complete your Vim transformation with essential plugins for PHP development: vim-plug, Startify, Undotree, vim-fugitive, UltiSnips, and more. Boost your productivity with sessions and IDE-like features."
pubDate: "2022-10-12 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Vim", "Editor", "Plugins", "PHP", "Productivity"]
selected: false
---

In parts one and two, we established a solid vimrc, built up the UI, added text formatting, and integrated FZF with RipGrep for fuzzy searching. Now we complete the transformation. This month we add plugins that turn Vim into an IDE-like experience without losing the lightweight editor we love.

## Startify: A Welcome Screen That Works

When you start Vim with no arguments, it shows an empty buffer. Other IDEs help you get back where you left off — reopening files and panels from your previous session. Vim's `:mksession` command exists, but you have to know about it.

[Startify](https://github.com/mhinz/vim-startify) fills this gap. Instead of automatically reopening everything, it displays a customizable menu. The default layout shows your most recently changed files in the current directory and a separate section for recently edited files in other directories. Each item has a single-character shortcut: type it, or move the cursor and press enter.

The real power is in the session integration. Startify stores sessions in `~/.vim/sessions` (or `$XDG_DATA_HOME/nvim/session` for Neovim). You get dedicated commands:

- `:SLoad` — load a session
- `:SSave` — save a session
- `:SDelete` — delete a session
- `:SClose` — close current session

Items can be opened in horizontal split (`s`), vertical split (`v`), or a tab (`t`). Multiple items can be selected and opened at once. You can further customize what sections appear — some people add git status output or custom project links.

## Undotree: Visual Undo History

Everyone makes mistakes. Sometimes you need to go back further than a few undos. Git only knows about committed changes. PhpStorm has "Local History." Vim actually records every change — it just has no built-in way to browse it.

[Undotree](https://github.com/mbbill/undotree) visualizes your change history as a tree. You can branch, merge, and navigate through every change you've ever made.

`UndotreeToggle` opens the history panel with a diff for each change. Map it to your leader key for quick access. The default layout (option 1) puts the tree on the left, shifting your content right. Layout 4 puts the tree on the right and the diff at the bottom, preventing your content from shifting:

```vim
let g:undotree_WindowLayout = 4
let g:undotree_SplitWidth = 30
let g:undotree_SetFocusWhenToggle = 1
```

The `SplitWidth` of 30 means 30% of the window — a comfortable default. Setting `SetFocusWhenToggle` to 1 ensures the tree gets focus when opened instead of leaving focus on your buffer.

How do branches happen? Say you write for a while, undo three changes with `u`, then make a new edit. Those three undone changes become an alternative branch. With Undotree, you can navigate back to that old branch and recover content.

The only real alternative is [Mundo](https://simnalamburt.github.io/vim-mundo/), a fork of the older Gundo project. Mundo has a nicer UI, but Undotree's consistent development keeps it in the lead.

## Vim Surround: Quote and Tag Management

Whether you're writing prose or code, you handle quotes, parentheses, brackets, braces, and HTML tags. [vim-surround](https://github.com/tpope/vim-surround) by Tim Pope makes editing these effortless.

Start with a sentence:

```
Eric and John bought PHP Architect from Oscar.
```

Put your cursor anywhere on `PHP` and type `ys2aw'`:

```
Eric and John bought 'PHP Architect' from Oscar.
```

Breakdown: `ys` means "you surround," `2aw` means "two around word," and `'` is the character to surround with.

Change single quotes to double quotes with `cs'"`:

```
Eric and John bought "PHP Architect" from Oscar.
```

Remove quotes with `ds"`:

```
Eric and John bought PHP Architect from Oscar.
```

The `cs` command takes a "from" and "to" character. You could change `'` to `*` for markdown bold: `cs'*`. Surround also supports HTML tags. For simple tags like headings, it's perfect. For complex markup, consider Emmet instead.

## UltiSnips: Text Expansion on Steroids

If you do something more than a few times, the computer should do it. [UltiSnips](https://github.com/sirver/UltiSnips) is a text expander for Vim — PhpStorm calls them Live Templates.

Vim's built-in abbreviations handle simple expansions, but multiline snippets with placeholders demand more. UltiSnips triggers expansions with a couple of keystrokes and supports tab-stop placeholders:

```snippets
snippet ifp
if (${1:condition}) {
    ${2:// body}
}
endsnippet
```

Type `ifp`, press Tab, and you get the skeleton. Tab again to jump between placeholders.

UltiSnips requires Python 3. Check `vim --version` for `+python3`. Mac users may need to install Python 3 explicitly. Snippets go in folders within Vim's `runtimepath` — typically `~/.vim/UltiSnips/`. Organize by filetype: `php.snippets`, `markdown.snippets`, etc. For snippets that work everywhere, use `all.snippets`.

The main alternative is [SnipMate](https://github.com/garbas/vim-snipmate), inspired by TextMate's snippet system. SnipMate uses Vundle instead of vim-plug, which adds complexity to the setup.

## Lightline: A Status Bar That Informs

In part one, we built a basic status bar. Plugin-based status bars take it further with fancy formatting, color-coded modes, and git branch information.

[Lightline](https://github.com/itchyny/lightline.vim) is minimalist and configurable. All settings live in the `g:lightline` variable:

```vim
let g:lightline = {
\   'colorscheme': 'default',
\ }
```

Popular color schemes often have matching Lightline variants. The plugin adds git branch display, file encoding, and syntax-aware mode indicators without the bloat of heavier alternatives.

## Honorable Mentions

These plugins didn't make the main list but deserve attention:

- **Vim Multiple Cursors** — brings SublimeText-style multi-cursor editing to Vim
- **Vim CSS Color** — renders hex color codes as their actual color inline
- **Vim Pandoc** — full pandoc integration for writers working with markdown

## Why Not NerdTree?

NerdTree is the most recommended Vim plugin on the internet. I left it out intentionally. When you combine FZF's fuzzy file finding with netrw (Vim's built-in file explorer), you rarely need a file tree. FZF navigates faster. netrw handles the few times you need directory browsing. Removing NerdTree keeps your workflow focused on content instead of sidebar navigation.

## Wrap Up

We covered a lot across this three-part series. Startify gives you a productive starting point. Undotree makes every change recoverable. Surround handles quotes and tags without thought. UltiSnips eliminates repetitive typing. Lightline keeps you informed at a glance.

The best way to learn is to teach someone else. Build on what we've covered. Create your own dotfiles project. This isn't the end — it's the end of the beginning.
