###LISTING_CONTINUED
--- Linter setup
local filetypes = {
  typescript = "eslint",
  typescriptreact = "eslint",
  php = {"phpcs", "psalm"},
}

local linters = {
  phpcs = {
    command = "vendor/bin/phpcs",
    sourceName = "phpcs",
    debounce = 300,
    rootPatterns = {"composer.lock", "vendor", ".git"},
    args = {"--report=emacs", "-s", "-"},
    offsetLine = 0,
    offsetColumn = 0,
    sourceName = "phpcs",
    formatLines = 1,
    formatPattern = {
      "^.*:(\\\\d+):(\\\\d+):\\\\s+(.*)\\\\s+-\\\\s \
      +(.*)(\\\\r|\\\\n)*$",
      { line = 1, column = 2, message = 4, security = 3 }
    },
    securities = { error = "error", warning = "warning", },
    requiredFiles = {"vendor/bin/phpcs"}
  },
  psalm = {
    command = "./vendor/bin/psalm",
    sourceName = "psalm",
    debounce = 100,
    rootPatterns = {"composer.lock", "vendor", ".git"},
    args = {"--output-format=emacs", "--no-progress"},
    offsetLine = 0,
    offsetColumn = 0,
    sourceName = "psalm",
    formatLines = 1,
    formatPattern = {
      "^[^ =]+ =(\\\\d+) =(\\\\d+) =(.*)\\\\s-\\\\s \
      (.*)(\\\\r|\\\\n)*$",
      { line = 1, column = 2, message = 4, security = 3 }
    },
    securities = { error = "error", warning = "warning" },
    requiredFiles = {"vendor/bin/psalm"}
  }
}

nvim_lsp.diagnosticls.setup {
  on_attach = on_attach,
  filetypes = vim.tbl_keys(filetypes),
  init_options = {
    filetypes = filetypes, linters = linters,
  },
}
