#!/bin/bash

# MCP Server Local Installer for Project IDX
# Install MCP servers locally in project (no global permission needed)

set -e

echo "🚀 Installing MCP servers locally..."
echo ""

# Create local bin directory
mkdir -p "$PWD/.mcp-servers"

# Install each server locally
cd "$PWD/.mcp-servers"

npm init -y 2>/dev/null || true

echo "📦 Installing @modelcontextprotocol/server-filesystem..."
npm install @modelcontextprotocol/server-filesystem

echo "📦 Installing @modelcontextprotocol/server-sequential-thinking..."
npm install @modelcontextprotocol/server-sequential-thinking

echo "📦 Installing @gannonh/mcp-command-runner..."
npm install @gannonh/mcp-command-runner

echo "📦 Installing @modelcontextprotocol/server-brave-search..."
npm install @modelcontextprotocol/server-brave-search

echo "📦 Installing @modelcontextprotocol/server-github..."
npm install @modelcontextprotocol/server-github

echo "📦 Installing @modelcontextprotocol/server-puppeteer..."
npm install @modelcontextprotocol/server-puppeteer

echo ""
echo "✅ Installation complete!"
echo ""
echo "📍 Servers installed at: $PWD/.mcp-servers/node_modules/.bin/"
echo ""
