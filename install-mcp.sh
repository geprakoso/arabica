#!/bin/bash

# ============================================================================
# MCP Server Installer for Arabica Project
# ============================================================================
# Script ini akan menginstall semua MCP servers yang dibutuhkan
# untuk development Arabica dengan AI assistance
# ============================================================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_DIR="/Applications/MAMP/htdocs/arabica"
MCP_DIR="$PROJECT_DIR/.cursor"

# MCP Servers list
# Note: database-mysql skipped for Project IDX (local MAMP database)
MCP_SERVERS=(
    "@modelcontextprotocol/server-filesystem"
    "@modelcontextprotocol/server-sequential-thinking"
    "@modelcontextprotocol/server-brave-search"
    "@modelcontextprotocol/server-github"
    "@modelcontextprotocol/server-puppeteer"
    "@gannonh/mcp-command-runner"
)

# ============================================================================
# Helper Functions
# ============================================================================

print_header() {
    echo -e "${BLUE}"
    echo "╔════════════════════════════════════════════════════════════════╗"
    echo "║        MCP Server Installer for Arabica Project               ║"
    echo "╚════════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# ============================================================================
# Check Prerequisites
# ============================================================================

check_prerequisites() {
    print_info "Checking prerequisites..."
    
    # Check Node.js
    if ! command -v node &> /dev/null; then
        print_error "Node.js is not installed!"
        echo "Please install Node.js first: https://nodejs.org/"
        exit 1
    fi
    
    NODE_VERSION=$(node --version | cut -d'v' -f2 | cut -d'.' -f1)
    if [ "$NODE_VERSION" -lt 18 ]; then
        print_warning "Node.js version $(node --version) detected. Recommended: v18+"
    else
        print_success "Node.js $(node --version)"
    fi
    
    # Check npm
    if ! command -v npm &> /dev/null; then
        print_error "npm is not installed!"
        exit 1
    fi
    print_success "npm $(npm --version)"
    
    # Check npx
    if ! command -v npx &> /dev/null; then
        print_error "npx is not installed!"
        exit 1
    fi
    print_success "npx is available"
    
    # Check if we're in the right directory
    if [ ! -f "$PROJECT_DIR/composer.json" ]; then
        print_warning "Arabica project not found at $PROJECT_DIR"
        read -p "Continue anyway? (y/n) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
    
    print_success "All prerequisites met!"
    echo
}

# ============================================================================
# Install MCP Servers
# ============================================================================

install_mcp_servers() {
    print_info "Installing MCP servers..."
    echo "This may take a few minutes..."
    echo
    
    local failed=()
    local installed=()
    
    for server in "${MCP_SERVERS[@]}"; do
        echo -e "${BLUE}Installing $server...${NC}"
        
        if npm install -g "$server" 2>/dev/null; then
            print_success "$server installed"
            installed+=("$server")
        else
            print_error "Failed to install $server"
            failed+=("$server")
        fi
        echo
    done
    
    # Summary
    echo
    print_header
    echo -e "${GREEN}Successfully installed: ${#installed[@]} servers${NC}"
    for srv in "${installed[@]}"; do
        echo "  ✓ $srv"
    done
    
    if [ ${#failed[@]} -gt 0 ]; then
        echo
        print_error "Failed to install: ${#failed[@]} servers"
        for srv in "${failed[@]}"; do
            echo "  ✗ $srv"
        done
    fi
    
    echo
}

# ============================================================================
# Setup Configuration
# ============================================================================

setup_config() {
    print_info "Setting up MCP configuration..."
    
    # Create .cursor directory if not exists
    if [ ! -d "$MCP_DIR" ]; then
        mkdir -p "$MCP_DIR"
        print_success "Created .cursor directory"
    fi
    
    # Copy config template if mcp.json doesn't exist
    if [ ! -f "$MCP_DIR/mcp.json" ]; then
        if [ -f "$MCP_DIR/mcp.json.example" ]; then
            cp "$MCP_DIR/mcp.json.example" "$MCP_DIR/mcp.json"
            print_success "Copied mcp.json.example to mcp.json"
        else
            print_warning "mcp.json.example not found. Please create mcp.json manually."
        fi
    else
        print_warning "mcp.json already exists. Skipping..."
    fi
    
    echo
}

# ============================================================================
# Post-Install Instructions
# ============================================================================

show_post_install_instructions() {
    print_header
    
    echo -e "${GREEN}🎉 MCP Server Installation Complete!${NC}"
    echo
    
    echo -e "${YELLOW}Next Steps:${NC}"
    echo "─────────────────────────────────────────────────────────────"
    echo
    
    echo "1. ${BLUE}Configure Credentials${NC}"
    echo "   Edit: $MCP_DIR/mcp.json"
    echo
    echo "   Add these API keys (if needed):"
    echo "   • BRAVE_API_KEY         - for web search"
    echo "   • GITHUB_PERSONAL_ACCESS_TOKEN - for GitHub integration"
    echo "   • MySQL credentials     - for database access"
    echo
    
    echo "2. ${BLUE}Get API Keys:${NC}"
    echo "   • Brave Search:  https://brave.com/search/api/"
    echo "   • GitHub Token:  Settings → Developer settings → Personal access tokens"
    echo
    
    echo "3. ${BLUE}Restart Your IDE${NC}"
    echo "   Restart Cursor IDE to load MCP configuration"
    echo
    
    echo "4. ${BLUE}Test Installation${NC}"
    echo "   Try asking the AI:"
    echo "   • 'Read the User model file'"
    echo "   • 'Plan the tukar-tambah feature'"
    echo "   • 'Take a screenshot of the login page'"
    echo
    
    echo -e "${YELLOW}Documentation:${NC}"
    echo "   See: $PROJECT_DIR/docs/MCP_SETUP.md"
    echo
    
    echo -e "${GREEN}Happy Coding! 🚀${NC}"
    echo
}

# ============================================================================
# Uninstall Function
# ============================================================================

uninstall_mcp_servers() {
    print_warning "Uninstalling all MCP servers..."
    
    for server in "${MCP_SERVERS[@]}"; do
        echo -e "${BLUE}Uninstalling $server...${NC}"
        npm uninstall -g "$server" 2>/dev/null || true
    done
    
    print_success "All MCP servers uninstalled"
    echo
}

# ============================================================================
# Main
# ============================================================================

main() {
    print_header
    
    # Check for uninstall flag
    if [ "$1" == "--uninstall" ] || [ "$1" == "-u" ]; then
        uninstall_mcp_servers
        exit 0
    fi
    
    # Check for help flag
    if [ "$1" == "--help" ] || [ "$1" == "-h" ]; then
        echo "Usage: ./install-mcp.sh [OPTIONS]"
        echo
        echo "Options:"
        echo "  -h, --help       Show this help message"
        echo "  -u, --uninstall  Uninstall all MCP servers"
        echo
        echo "This script will install all MCP servers required for Arabica development."
        exit 0
    fi
    
    # Run installation
    check_prerequisites
    install_mcp_servers
    setup_config
    show_post_install_instructions
}

# Run main function
main "$@"
