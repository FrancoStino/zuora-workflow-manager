# Security Policy

## Reporting Security Vulnerabilities

We take security seriously. If you discover a security vulnerability in this project, please report it responsibly by
emailing security@example.com instead of using the public issue tracker.

When reporting a vulnerability, please include:

- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if available)

## Security Guidelines

### Requirements

- All dependencies must be kept up to date
- Code must pass security scanning tools
- Sensitive data should never be committed to the repository

### Secure Coding Practices

- Validate and sanitize all user inputs
- Use parameterized queries to prevent SQL injection
- Implement proper authentication and authorization
- Follow OWASP Top 10 guidelines
- Use HTTPS for all external communications
- Implement rate limiting for API endpoints

### Dependency Management

- Regularly run `composer audit` and `npm audit`
- Address security updates promptly
- Keep Laravel and PHP versions supported and patched

## Supported Versions

| Version | Supported         |
|---------|-------------------|
| 3.x     | ✅ Fully supported |
| 2.x     | ❌ Not supported   |
| 1.x     | ❌ Not supported   |
| 0.x     | ❌ Not supported   |

## Security Headers

Ensure the following headers are configured in your web server:

- `Strict-Transport-Security`
- `X-Content-Type-Options`
- `X-Frame-Options`
- `Content-Security-Policy`

## Contact

For security inquiries, please contact the security team.
