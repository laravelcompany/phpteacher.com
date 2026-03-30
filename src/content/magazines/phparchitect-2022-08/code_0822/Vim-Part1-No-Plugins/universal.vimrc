"
" Universal Vim Configuraiton
"
" An attempt to provide a solid configuration file that can be
" used between standard Vim and Neovim. Also, this is meant to
" applicable to any type of Vim user - not just deveclopers. 
"
" Developed using Vim 8.2 and Neovim 0.7.0
"



"
" ==== Settings ================================================
"

set nocompatible

"
" **** User Interface ***************************
"

" Show real line numbers. 
set number

" Number the lines, relative to where you cursor is
set norelativenumber

" Control left edge of window
set numberwidth=6

" Always display the status bar
set laststatus=2


" Custom status line, comment this out when you use a plugin
if has("statusline")
    " Reset to empty string
    set statusline=
    " Display the buffer number
    set statusline+=\<%n\>
    " Base filename
    set statusline+=\ %t
    " Various flags
    set statusline+=\ %m%r%h%w

    " Separate left side from right side
    set statusline+=%=

    " File format/type
    set statusline+=(%{&ff})
    " current line number / total lines
    set statusline+=\ line:%l\/%L
    " Percentage within file
    set statusline+=\ (%p%%)
    " Column number
    set statusline+=\ col:%c
endif

"
" ~~~~ Display guide ~~~~~~~~~~
"

" Remember to add 1 to your desired maximum line length
set colorcolumn=65
" 235 is a very dark grey, good for dark backgrounds
hi ColorColumn ctermbg=235



"
" **** User Experience *************************
"

syntax on

" Look for project specific config file
set exrc

" Ability to change buffers without saving first
set hidden

" Errer settings
set noerrorbells
set novisualbell

" Briefly jump to a matching bracket, then come back
set showmatch

" Useful when you split your current buffer
set splitbelow
set splitright

"
" ~~~~ Content Settings ~~~~~~~
"

" The number of spaces/columns a tab character represents
set tabstop=4
" How many spaces should Vim indent
set shiftwidth=4
set autoindent
set smartindent
set nosmarttab

" Convert tabs to spaces
set expandtab

set nojoinspaces
set textwidth=0
set wrapmargin=0
set wrap
set linebreak


" Minimal number of screen lines to keep above and below the cursor
set scrolloff=8

set sidescroll=12
set sidescrolloff=12

" Determine how you want special characters to be displayed
set listchars=""
set listchars+=tab:>¬
set listchars+=eol:¶
set listchars+=trail:∙
set listchars+=extends:»
set listchars+=precedes:«
set listchars+=nbsp:¤


"
" ~~~~ Search Settings ~~~~~~~~~
"

" Go to a search term as you type it
" Use these key strokes to navigate the matches
" previous match: CTRL-T, next match: CTRL-G
set incsearch

"  Highlight all the the search matches.
set nohlsearch

" Allow search to go back to the top/bottom
set wrapscan

" the case of normal letters is ignored
set ignorecase

" When your term has uppercase letters, they'll be honored
set smartcase


"
" **** Backup Settings *************************
"

"set completeopt=menuone,noinsert,noselect
set noswapfile
set nobackup
set nowritebackup



"
" ==== Plugins ================================================
"



"
" ==== Variables ===============================================
"

let g:netrw_banner = 0
let g:netrw_winsize = 25


"
" ==== Remaps ==================================================
"



"
" **** UX Mappings *****************************
"

" Provides breakpoints so undo doesn't go too far
inoremap , ,<c-g>u
inoremap . .<c-g>u
inoremap ! !<c-g>u
inoremap ? ?<c-g>u

" Keep things vertically centered
nnoremap n nzzzv
nnoremap N Nzzzv
nnoremap J mzJ`z

" Make yank more intuitive
nnoremap Y y$

" Move highlighted selections
vnoremap J :m '>+1<CR>gv=gv
vnoremap K :m '<-2<CR>gv=gv


"
" **** Leader Key Mappings *********************
"
let mapleader=" "

nnoremap <Leader>fe :Lexplore!<CR>
nnoremap <Leader>th :terminal<CR>
nnoremap <Leader>tv :vertical terminal<CR>


"
" ==== Auto Commands ===========================================
"


