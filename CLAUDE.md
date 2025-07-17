# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

This is a PHP-based file transfer system that allows secure file sharing between admin users and customers through session-based links. The system uses a file-based storage approach with JSON metadata and implements device detection for responsive interfaces.

## Architecture

### Core Components

- **Entry Point (`index.php`)**: Device detection and routing logic that directs desktop users to admin interface and mobile users to customer interface
- **Session Management (`functions.php`)**: Core session creation, file upload/download, and metadata management
- **Admin Interface (`admin.php`)**: Desktop-optimized dashboard for creating sessions, managing files, and monitoring transfers
- **Customer Interface (`customer.php`)**: Mobile-optimized interface for downloading files from shared sessions
- **Configuration (`config.php`)**: Security settings, file restrictions, and system limits

### Data Storage

- **Session Storage**: File-based in `sessions/` directory with JSON metadata
- **File Storage**: Physical files stored in session directories alongside metadata
- **Session Structure**: Each session has a unique token (format: `SES-YYYYMMDD-XXXXXX`) with dedicated folder containing:
  - `session.json` - Session metadata and settings
  - `files.json` - File metadata and tracking
  - Actual uploaded files

### Security Features

- File extension validation with blocked executable types
- MIME type verification using PHP's fileinfo
- Session token validation with expiration (30 days default)
- Filename sanitization to prevent directory traversal
- File size limits (40MB default)
- Input sanitization throughout

## Development Commands

This is a PHP application running on XAMPP. No build process or package management is required.

### Running the Application

1. Ensure XAMPP is running with Apache and PHP enabled
2. Place code in `/Applications/XAMPP/xamppfiles/htdocs/filetransfer/`
3. Access via `http://localhost/filetransfer/`

### Development Workflow

- **Start Development**: Ensure XAMPP Apache server is running
- **File Permissions**: Ensure `sessions/` and `temp/` directories are writable (755 permissions)
- **Testing**: Manual testing through browser interfaces
- **Logs**: Check Apache error logs for PHP errors

## Key Configuration

### File Restrictions (config.php:15-27)

Allowed extensions include common office documents, images, archives, and media files. Blocked extensions include executable files and server-side scripts for security.

### Session Management (functions.php:13-63)

Sessions are created with unique tokens, customer names, and optional notes. The system automatically handles session expiration and cleanup.

### Device Detection (index.php:45-71)

User-Agent parsing determines whether to show admin (desktop) or customer (mobile) interface, with mobile being the default fallback.

## Styling System

The application uses Anthropic's design system implemented in `anthropic-style.css`:
- Deep blue primary colors (#3182ce, #2c5aa0)
- Muted warm gray palette
- Styrene-inspired typography for headings
- Tiempos-inspired serif fonts for body text
- Responsive design tokens for all interfaces

## Common Operations

### Creating a Session
1. Access admin dashboard
2. Enter customer name and optional notes
3. System generates unique token and shareable URL
4. QR code can be generated for easy mobile access

### File Upload Process
Files are validated for extension, MIME type, and size before being stored in the session directory with metadata tracking.

### Download Tracking
System tracks download counts and session activity for monitoring file exchange usage.

## Security Considerations

- Never modify the blocked extensions list without careful security review
- Session tokens should be treated as sensitive data
- Regular cleanup of expired sessions is handled automatically
- File uploads are restricted to prevent code execution vulnerabilities