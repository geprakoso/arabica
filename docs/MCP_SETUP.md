# MCP (Model Context Protocol) Setup Guide for Arabica

## 📋 Overview

This guide explains how to setup MCP (Model Context Protocol) servers for the Arabica project to enhance AI-assisted development.

## 🖥️ Supported Editors

| Editor | Config Location | Extension Required |
|--------|----------------|-------------------|
| **Cursor** | `.cursor/mcp.json` | Built-in MCP support |
| **VS Code** | `.vscode/mcp.json` | [Claude for VS Code](https://marketplace.visualstudio.com/items?itemName=anthropic.claude) atau [GitHub Copilot](https://marketplace.visualstudio.com/items?itemName=GitHub.copilot) |
| **Claude Desktop** | `~/Library/Application Support/Claude/claude_desktop_config.json` | Built-in |

> **Catatan**: VS Code memerlukan extension AI yang support MCP (seperti Claude for VS Code atau Copilot Chat).

## 🚀 Quick Start

### 1. Copy Template Configuration

#### Untuk Cursor:
```bash
cp .cursor/mcp.json.example .cursor/mcp.json
```

#### Untuk VS Code:
```bash
cp .vscode/mcp.json.example .vscode/mcp.json
```

### 2. Install Required MCP Servers

#### Option A: Using Install Script (Recommended)
```bash
# Jalankan script installer
./install-mcp.sh

# Uninstall (jika diperlukan)
./install-mcp.sh --uninstall
```

#### Option B: Manual Install
```bash
# Core MCP servers (wajib)
npm install -g @modelcontextprotocol/server-filesystem
npm install -g @modelcontextprotocol/server-sequential-thinking

# Recommended
npm install -g @gannonh/mcp-command-runner

# Optional - sesuai kebutuhan
npm install -g @modelcontextprotocol/server-brave-search
npm install -g @modelcontextprotocol/server-github
npm install -g @modelcontextprotocol/server-puppeteer

# Skip: database-mysql (DB di local MAMP, tidak bisa diakses dari cloud)
# npm install -g @modelcontextprotocol/server-mysql
```

### 3. Configure Credentials

Edit file config sesuai editor Anda:
- **Cursor**: `.cursor/mcp.json`
- **VS Code**: `.vscode/mcp.json`

Isi credential yang diperlukan:

#### Web Search (Brave)
```json
"env": {
  "BRAVE_API_KEY": "your-brave-api-key"
}
```
Dapatkan API key di: https://brave.com/search/api/

#### GitHub
```json
"env": {
  "GITHUB_PERSONAL_ACCESS_TOKEN": "ghp_your_token"
}
```
Buat token di: GitHub Settings > Developer settings > Personal access tokens

#### Database (MySQL) - SKIP
> ⚠️ **Di-skip untuk Project IDX** karena database berada di local MAMP.
> 
> Alternatif: Gunakan terminal IDX + `php artisan tinker` untuk query database.

```json
"env": {
  "MYSQL_HOST": "localhost",
  "MYSQL_PORT": "3306",
  "MYSQL_USER": "your_db_user",
  "MYSQL_PASSWORD": "your_db_password",
  "MYSQL_DATABASE": "arabica"
}
```

## 🛠️ Available MCP Servers

### Core (Wajib)
| Server | Purpose | Use Case |
|--------|---------|----------|
| `filesystem` | File system access | Read/write Laravel files, configs |
| `sequential-thinking` | Problem solving | Complex feature planning, debugging |

### Development Tools
| Server | Purpose | Use Case | Required |
|--------|---------|----------|----------|
| `web-search` | Internet research | Laravel/Filament docs, best practices | Optional |
| `github` | GitHub integration | Create PRs, check issues | Optional |
| `database-mysql` | Database queries | Check transactions, validate data | **Skip** (DB local) |
| `puppeteer` | Browser automation | Testing web features, screenshots | Optional |
| `command-runner` | Execute shell commands | Run artisan, npm, composer, pest | Recommended |

## 🔒 Security Notice

**IMPORTANT:** `.cursor/mcp.json` contains sensitive credentials and is **IGNORED** by git.

- ✅ **Safe to push**: `.cursor/mcp.json.example`, `.vscode/mcp.json.example`, this documentation
- ❌ **NEVER push**: `.cursor/mcp.json`, `.vscode/mcp.json`, files with API keys/passwords

## 📝 Usage Examples

### Dengan Filesystem MCP
```
"Read the User model file"
"Update the migration for products table"
```

### Dengan Sequential Thinking MCP
```
"Plan the implementation for tukar-tambah feature step by step"
"Debug why the WooCommerce sync is failing"
```

### Dengan Web Search MCP
```
"Search for Filament 3 best practices for resource organization"
"Find latest Laravel 12 changelog"
```

### Dengan GitHub MCP
```
"Create a PR for the hotfix branch"
"Check open issues related to inventory"
```

### Dengan Puppeteer MCP
```
"Take a screenshot of the login page"
"Test the checkout flow in browser"
"Verify the PDF export is working correctly"
```

### Dengan Command Runner MCP
```
"Run php artisan migrate to apply latest migrations"
"Execute pest tests for the Inventory module"
"Run npm run build untuk production build"
"Check Laravel routes with php artisan route:list"
"Clear cache dengan php artisan optimize:clear"
```

## 🔧 Troubleshooting

### MCP Server tidak berjalan
```bash
# Check if npx is available
which npx

# Reinstall MCP server
npm install -g @modelcontextprotocol/server-filesystem
```

### Permission denied
```bash
# Fix permissions
chmod +x ~/.npx/*/bin/*
```

### Path issues
Pastikan path di `mcp.json` sesuai dengan lokasi project:
```json
"/Applications/MAMP/htdocs/arabica"
```

## 📚 Additional Resources

- [MCP Documentation](https://modelcontextprotocol.io/)
- [Laravel Documentation](https://laravel.com/docs)
- [Filament Documentation](https://filamentphp.com/docs)

---

**Last Updated**: 2025-01-15
