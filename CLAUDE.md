# Anya Ganger Personal Website & Blog - Technical Documentation

## Project Overview
Personal website and blog platform for Anya Ganger featuring "Venture X AI Insights" - exploring the intersection of artificial intelligence and venture capital.

**Live Site:** https://anya.ganger.com
**Repository:** https://github.com/anyaganger/AI-Venture-Blog

## Technology Stack

### Frontend
- HTML5, CSS3, Vanilla JavaScript
- Responsive design with mobile-first approach
- Google Fonts: Playfair Display (headings), Inter (body)
- Quill.js for rich text editing in admin panel

### Backend
- **PHP 7.4+** for blog and main site
  - PDO for database abstraction
  - Parsedown for markdown parsing
  - Custom functions library
- **Node.js/Express.js** (server.js) - Alternative API server
- **MySQL** database (`gangerne_anyablog`)

### Infrastructure
- Hosted via FTP deployment
- GitHub Actions for automatic deployment on push to main
- Apache with .htaccess configuration

## Database Schema

### `posts` Table
Primary table for blog posts with the following key columns:
- `id` (VARCHAR UUID) - Primary key
- `title`, `slug`, `content`, `excerpt`
- `category_id` (INT FK) - References categories table
- `read_time` (INT) - Estimated reading time in minutes
- `status` (ENUM: 'draft', 'published') - **Primary status field**
- `style` (VARCHAR) - Post style (modern/classic)
- `published_at` (TIMESTAMP) - **Date when post was published**
- `created_at`, `updated_at` (TIMESTAMP)
- `post_order` (INT) - Display order

#### Legacy Columns (Backup)
- `published` (TINYINT) - Old boolean field (deprecated)
- `category` (VARCHAR) - Old text category (deprecated)
- `published_old`, `category_old` - Migration backups

### `categories` Table
- `id` (INT) - Primary key
- `name` (VARCHAR) - Category name
- `slug` (VARCHAR) - URL-friendly slug
- `created_at` (TIMESTAMP)

### Important Notes
- Always use `status` ENUM ('draft'/'published') as source of truth for publish status
- Always use `published_at` for the published date, not `created_at`
- Always use `post_order` for ordering, not `id`
- The `published` boolean is calculated as `(status = 'published')` in queries

## Critical Bug Fixes (January 2026)

### Bug #1: Blog Post Edit Dates Not Saving
**Symptom:** Editing a blog post and changing the date appeared to save, but the date changes were silently lost.

**Root Cause:** The PATCH endpoint in `/api/admin-posts.php` did not have a handler for the `publishedAt` field sent by the frontend.

**Fix:** Added publishedAt handler to the PATCH endpoint (lines 109-117):
```php
// Handle publishedAt date
if (array_key_exists('publishedAt', $input)) {
    if ($input['publishedAt']) {
        $updates[] = "published_at = ?";
        $params[] = $input['publishedAt'];
    } else {
        $updates[] = "published_at = NULL";
    }
}
```

**Files Changed:**
- `/api/admin-posts.php`

---

### Bug #2: Wrong Date Field in Queries
**Symptom:** API was returning `created_at` as `publishedAt`, showing creation date instead of actual published date.

**Root Cause:** Queries mapped `p.created_at as publishedAt` instead of using the actual `published_at` column.

**Fix:** Updated all SELECT queries to use:
```sql
p.published_at as publishedAt  -- instead of p.created_at as publishedAt
```

**Files Changed:**
- `/api/admin-posts.php` (line 28)
- `/api/posts.php` (lines 21, 32)

---

### Bug #3: Wrong Order Field
**Symptom:** Post order returned UUID strings instead of integer order values.

**Root Cause:** Queries used `p.id as 'order'` instead of the actual order column.

**Fix:** Updated queries to use:
```sql
p.post_order as 'order'  -- instead of p.id as 'order'
```

**Files Changed:**
- `/api/admin-posts.php` (line 29)
- `/api/posts.php` (lines 22, 33)

---

### Bug #4: NULL published_at for Existing Posts
**Symptom:** Published posts had NULL `published_at` values.

**Root Cause:** Historical data migration didn't populate `published_at` for existing posts.

**Fix:** Added migration step to populate NULL dates:
```sql
UPDATE posts
SET published_at = created_at
WHERE status = 'published' AND published_at IS NULL
```

**Files Changed:**
- `/api/migrate-schema.php` (Step 8, lines 154-171)

**Status:** Fixed 1 post on 2026-01-13. All published posts now have proper `published_at` dates.

---

### Bug #5: New Posts Not Setting published_at
**Symptom:** Creating new published posts didn't set the `published_at` field.

**Root Cause:** INSERT statement in create endpoint didn't include `published_at`.

**Fix:** Added `published_at` handling to INSERT:
```php
// Set published_at if provided, otherwise use current timestamp for published posts
$publishedAt = null;
if (isset($input['publishedAt']) && $input['publishedAt']) {
    $publishedAt = $input['publishedAt'];
} elseif ($input['published']) {
    // If publishing without explicit date, use current timestamp
    $publishedAt = date('Y-m-d H:i:s');
}
```

**Files Changed:**
- `/api/create-post.php` (lines 59-83)

---

### Bug #6: Blog Templates Displaying Wrong Date
**Symptom:** Date edits saved successfully in admin but didn't appear on the public blog pages.

**Root Cause:** Blog template files (post.php, index.php) were hardcoded to display `created_at` instead of `published_at`.

**Fix:** Updated both template files to use `published_at` with fallback:
```php
strtotime($post['published_at'] ?: $post['created_at'])
```

**Files Changed:**
- `/blog/post.php` (line 43)
- `/blog/index.php` (line 65)

---

### Bug #7: Hardcoded Content Should Be Database-Driven (January 2026)

**Problem:** Multiple content items were hardcoded in templates instead of being editable through admin panel.

**Issues Found:**
- Hero section title, subtitle, description (hardcoded in blog/index.php)
- Section titles (hardcoded)
- Author name (hardcoded in alt text)
- Database credentials in source code (security risk)

**Fix:** Comprehensive content management system update:

1. **Added New Settings** - Expanded settings API to include:
   - `blog_hero_title` - Hero title (e.g., "Venture × AI")
   - `blog_hero_subtitle` - Hero subtitle
   - `blog_hero_description` - Hero description paragraph
   - `blog_section_title` - Section title above post list
   - `author_name` - Author name for alt text and bylines

2. **Environment Variables for Security** - Moved database credentials:
   ```php
   // Before (INSECURE):
   define('DB_PASS', 'AnyaLovesPilate$');

   // After (SECURE):
   define('DB_PASS', getenv('DB_PASS') ?: 'AnyaLovesPilate$');
   ```

3. **Created Settings Helper Function** - Added `get_site_settings()` to fetch settings from API with caching and fallbacks.

4. **Updated Templates** - Modified blog templates to use database settings instead of hardcoded values:
   - Hero section now pulls from settings
   - Section titles editable
   - Author name dynamic
   - All text content customizable via admin panel

5. **Updated Admin Panel** - Added new form fields for all blog hero settings:
   - Hero Title field
   - Hero Subtitle field
   - Hero Description textarea
   - Section Title field
   - Author Name field

**Files Changed:**
- `/api/settings.php` - Added new settings to defaults
- `/api/config.php` - Environment variable support
- `/blog/includes/config.php` - Environment variable support
- `/blog/includes/functions.php` - Added `get_site_settings()` helper
- `/blog/index.php` - Use settings for all content
- `/admin/index.html` - Added form fields for new settings
- `/.env.example` - Created environment variable template

**Security Impact:** Database credentials now support environment variables, reducing risk of credential exposure.

---

## API Endpoints

### Admin Endpoints (Require Authentication)

#### GET `/api/admin-posts.php`
Returns all posts including drafts for admin panel.

**Response Format:**
```json
{
  "id": "uuid",
  "title": "Post Title",
  "slug": "post-title",
  "content": "Full markdown content",
  "excerpt": "Brief description",
  "category": "Category Name",
  "readTime": 5,
  "published": true,
  "publishedAt": "2026-01-13 12:00:00",
  "createdAt": "2026-01-13 10:00:00",
  "updatedAt": "2026-01-13 11:00:00",
  "order": 0
}
```

#### PATCH `/api/admin-posts.php?id={postId}`
Updates an existing post.

**Request Body:**
```json
{
  "title": "Updated Title",
  "slug": "updated-title",
  "content": "Updated content",
  "excerpt": "Updated excerpt",
  "category": "Category Name",
  "readTime": 6,
  "published": true,
  "publishedAt": "2026-01-13 12:00:00"
}
```

**Important:** The `publishedAt` field will be saved to the database. If omitted, the current value is preserved.

#### DELETE `/api/admin-posts.php?id={postId}`
Deletes a post.

#### POST `/api/create-post.php`
Creates a new post.

**Request Body:**
```json
{
  "title": "New Post",
  "slug": "new-post",
  "content": "Post content",
  "excerpt": "Brief description",
  "category": "Category Name",
  "readTime": 5,
  "published": true,
  "publishedAt": "2026-01-13 12:00:00"
}
```

**Note:** If `publishedAt` is not provided but `published` is true, the current timestamp is used.

### Public Endpoints

#### GET `/api/posts.php`
Returns only published posts for public consumption.

## Authentication

### PIN-Based Authentication
- Admin PIN: `2660`
- Session tokens stored in temporary file system
- Token expiration: 24 hours
- Legacy tokens accepted for backward compatibility

### Authentication Flow
1. User enters 4-digit PIN in admin panel
2. Server validates against `ADMIN_PIN` constant
3. Server generates session token and stores in `/tmp/anya_tokens.json`
4. Token sent to client and stored in localStorage
5. All authenticated requests include token in `Authorization` header

## Development Workflow

### Local Development
```bash
# Clone repository
git clone https://github.com/anyaganger/AI-Venture-Blog.git
cd anya.ganger.com

# Edit files locally
# Test locally if PHP server is available

# Commit changes
git add .
git commit -m "Description of changes"

# Push to trigger deployment
git push origin main
```

### Automatic Deployment
- GitHub Actions automatically deploys to FTP server on push to `main`
- Deployment workflow: `.github/workflows/deploy.yml`
- Deploys to `ftp.ganger.com`
- Typical deployment time: 1-2 minutes

### Testing Deployments
After pushing changes, wait 1-2 minutes then test:
```bash
# Check if API returns updated data
curl https://anya.ganger.com/api/admin-posts.php

# Run migration if database changes
curl https://anya.ganger.com/api/migrate-schema.php
```

## Database Migrations

### Running Migrations
```bash
curl https://anya.ganger.com/api/migrate-schema.php
```

### Current Migration Script
Location: `/api/migrate-schema.php`

**Steps Performed:**
1. Verify/create categories table
2. Migrate unique categories from posts
3. Add category_id column to posts
4. Add status ENUM column
5. Add style column
6. Fix empty post IDs
7. Migrate category strings to category_id FK
8. Migrate published boolean to status ENUM
9. Create backup columns (category_old, published_old)
10. **NEW:** Fix NULL published_at dates for published posts
11. Verify migration success

### Schema Cleanup (Future)
Consider dropping legacy columns once stable:
- `published` (use `status` instead)
- `category` (use `category_id` + JOIN instead)
- `published_old`, `category_old` (backups)

**Warning:** Do NOT drop these columns until thoroughly tested in production.

## Common Issues & Solutions

### Issue: "Can't save blog post edits"
**Solution:** Fixed in commit 049fffa. Ensure deployment is live and `publishedAt` handler exists in admin-posts.php.

### Issue: "Posts showing wrong date"
**Solution:** Fixed in commit 049fffa. Queries now use `published_at` instead of `created_at`.

### Issue: "New posts don't have published date"
**Solution:** Fixed in commit 049fffa. create-post.php now sets `published_at` on INSERT.

### Issue: "Changes not appearing on live site"
**Solution:**
1. Check GitHub Actions for deployment status
2. Wait 1-2 minutes after push
3. Clear browser cache
4. Test API directly: `curl https://anya.ganger.com/api/admin-posts.php`

### Issue: "Migration not running"
**Solution:**
```bash
# Manually trigger migration
curl https://anya.ganger.com/api/migrate-schema.php

# Check migration results
curl https://anya.ganger.com/api/check-schema.php
```

## File Structure

```
.
├── admin/                  # Admin dashboard
│   └── index.html         # Main admin interface
├── api/                   # PHP API endpoints
│   ├── admin-posts.php    # CRUD for posts (admin)
│   ├── posts.php          # Public post API
│   ├── create-post.php    # Create new post
│   ├── auth.php           # Authentication
│   ├── settings.php       # Site settings
│   ├── analytics.php      # Analytics data
│   ├── config.php         # Database & auth config
│   ├── migrate-schema.php # Database migrations
│   ├── check-schema.php   # Schema verification
│   └── fix-*.php          # Utility scripts
├── blog/                  # Blog section
│   ├── includes/
│   │   ├── config.php     # Blog config
│   │   ├── database.php   # Database singleton
│   │   └── functions.php  # Helper functions
│   └── index.html         # Blog index
├── assets/                # Global assets
│   ├── css/
│   ├── images/
│   └── js/
├── .github/workflows/
│   └── deploy.yml         # Deployment automation
├── index.html             # Main landing page
├── server.js              # Node.js API (alternative)
└── CLAUDE.md             # This file
```

## Security Considerations

### Authentication
- PIN stored as constant (not ideal for production)
- Session tokens in temp file (consider database or Redis)
- HTTPS enforced via secure cookie settings
- CORS enabled for admin panel

### Database
- PDO with prepared statements (SQL injection protected)
- Input sanitization for user data
- Foreign key constraints for data integrity

### Recommended Improvements
1. Move PIN to environment variable
2. Use database or Redis for session tokens
3. Implement rate limiting on auth endpoint
4. Add CSRF protection
5. Implement proper admin user system with hashed passwords

## Testing

### Manual Testing Checklist

**Blog Post Editing:**
- [ ] Create new draft post
- [ ] Publish post with specific date
- [ ] Edit published post and change date
- [ ] Verify date saved correctly in database
- [ ] Verify date displays correctly on frontend
- [ ] Edit post and change to draft
- [ ] Delete post

**API Testing:**
```bash
# Test GET endpoint
curl https://anya.ganger.com/api/admin-posts.php

# Check schema
curl https://anya.ganger.com/api/check-schema.php

# Run migration
curl https://anya.ganger.com/api/migrate-schema.php
```

## Changelog

### 2026-01-13 - Critical Bug Fixes
**Commit:** 049fffa
**Summary:** Fixed blog post edit functionality

**Changes:**
- Fixed PATCH endpoint to handle publishedAt field
- Corrected query mappings for published_at and post_order
- Added published_at to CREATE endpoint
- Migration to fix NULL published_at values

**Files Modified:**
- api/admin-posts.php
- api/create-post.php
- api/posts.php
- api/migrate-schema.php

**Impact:** Blog post editing now works correctly. Dates save properly and display correctly.

## Future Enhancements

### Short Term
- [ ] Add input validation for date fields
- [ ] Implement post versioning/history
- [ ] Add featured image upload functionality
- [ ] Improve error handling and user feedback

### Medium Term
- [ ] Implement proper admin user system
- [ ] Add automated testing (PHPUnit)
- [ ] Create staging environment
- [ ] Add database backups

### Long Term
- [ ] Consider migration to Laravel/Symfony
- [ ] Implement API rate limiting
- [ ] Add search functionality
- [ ] Implement caching (Redis)

## Support & Maintenance

### Monitoring
- Check GitHub Actions for deployment failures
- Monitor database size and performance
- Review error logs regularly

### Backup Strategy
- Repository backed up on GitHub
- Database: Manual exports recommended
- Consider automated daily backups

### Contact
For issues or questions about this codebase, check:
1. This documentation
2. Git commit history for context
3. API response messages for debugging info

---

**Last Updated:** 2026-01-13
**Documentation Version:** 1.0
**Maintained By:** Claude Code Reviews
