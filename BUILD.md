# Build Instructions for WordPress.org Submission

## Quick Build Commands

### Using Composer (Recommended)
```bash
composer build
```

### Using NPM
```bash  
npm run build
```

## Available Scripts

### Composer Scripts
- `composer build` - Full build process (clean + zip)
- `composer run-script build:clean` - Remove old ZIP files
- `composer run-script build:zip` - Create WordPress.org ZIP
- `composer run-script build:check` - Check PHP syntax
- `composer run-script pre-build` - Run pre-build checks

### NPM Scripts  
- `npm run build` - Full build process (clean + zip)
- `npm run build:clean` - Remove old ZIP files
- `npm run build:zip` - Create WordPress.org ZIP

## What Gets Excluded from ZIP

The build process automatically excludes:
- `.git/` directory and `.gitignore` file
- `node_modules/` directory
- `composer.lock` 
- Development files (`.DS_Store`, `*.log`, `.env`, etc.)
- IDE files (`.vscode/`, `.idea/`)
- Temporary files (`*.tmp`, `*.swp`, etc.)

## Output

The build creates `html-to-image-generator.zip` in the parent directory, ready for WordPress.org submission.

## Before Submitting

1. Run `composer run-script build:check` to verify PHP syntax
2. Run `composer build` to create the ZIP
3. Test the ZIP in a fresh WordPress installation
4. Submit to WordPress.org