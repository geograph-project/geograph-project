# Audit Report: PHP 8.4 Upgrade Assessment

## Executive Summary

This report provides a comprehensive code audit and technical assessment for upgrading the **Geograph** site from PHP 7.4 (currently running on Debian via `tigercomputing/debian-php-fpm:php7.4`) to **PHP 8.4**.

Upgrading across two major PHP releases (7.4 &rarr; 8.0 &rarr; 8.1 &rarr; 8.2 &rarr; 8.3 &rarr; 8.4) involves addressing several breaking changes, removed language constructs, deprecated built-in functions, and outdated third-party vendor dependencies.

### Primary Audit Summary Findings
* **Fatal Syntax Errors in PHP 8+**: Multiple files fail parsing under PHP 8+ due to removed syntax (curly brace string/array offset access `$var{0}`) and removed special functions (e.g., `__autoload()`).
* **Incompatible 3rd-Party Libraries**:
  * `libs/3rdparty/S3.php`: Uses curly brace indexing, deprecated `openssl_free_key()`, implicit nullable parameters, and mixed static state.
  * `libs/smarty/` (Smarty 2.6.19): Incompatible with PHP 8 (PHP 4 constructors, `var` keywords, `each()`, dynamic properties).
  * `libs/adodb/` (ADOdb 5.00): Incompatible with PHP 8 (uses `each()`, PHP 4 constructors, `get_magic_quotes_gpc()`).
  * `libs/3rdparty/JSON.php`, `PorterStemmer.class.php`, `class.phpmailer.php`, `sphinxapi.php`.
* **Deprecated/Removed PHP Functions**:
  * `utf8_encode()` / `utf8_decode()` (deprecated in 8.2, removed in 8.4; 40+ occurrences).
  * `each()` (removed in 8.0).
  * `__autoload()` (removed in 8.0).
  * `get_magic_quotes_gpc()` / `get_magic_quotes_runtime()` / `set_magic_quotes_runtime()` (removed in 8.0).
  * `strftime()` / `gmstrftime()` (deprecated in 8.1).
  * Implicitly nullable parameter types (deprecated in PHP 8.4).
* **Docker / Infrastructure Upgrade**: Transitioning `system/docker/geograph/Dockerfile` from PHP 7.4 to a PHP 8.4 FPM container image (e.g., `php:8.4-fpm-bookworm` or Debian 12 PHP 8.4 packages).

---

## Priority Analysis: `libs/3rdparty/S3.php`

`libs/3rdparty/S3.php` is a core utility used for Amazon S3 object storage interactions across Geograph (image uploads, tile caching, vector storage, etc.).

### Findings & Specific Issues in `S3.php`

1. **Fatal Syntax Error (Curly Brace String Offset Access)**:
   * **Location**: Line 2608 in `S3Request::__responseHeaderCallback()`
   * **Code**: `$this->response->headers['hash'] = $value{0} == '"' ? substr($value, 1, -1) : $value;`
   * **Impact**: **Fatal Error in PHP 8.0+**. In PHP 8.0, array and string offset access using curly braces (`$value{0}`) was completely removed.
   * **Fix**: Replace `$value{0}` with array square bracket notation `$value[0]`.

2. **Static vs. Instance State Mix**:
   * **Issue**: `S3` relies on global static state (`S3::$__accessKey`, `S3::$__secretKey`, `S3::$useSSL`, `S3::$endpoint`, `S3::$region`, `S3::$proxy`, `S3::$securityToken`, etc.) while also exposing instance constructors (`$s3 = new S3($key, $secret)`).
   * **Impact**: In PHP 8.x, static method calls on non-static methods or inconsistent static state initialization will throw `E_DEPRECATED` or `TypeError` if properties are uninitialized or typed strictly.
   * **Fix**: Retain static setter methods (`S3::setAuth()`, `S3::setSSL()`, `S3::setEndpoint()`) for backward compatibility, but ensure default nullability/type safety on static properties.

3. **Deprecated Resource Functions (`openssl_free_key`)**:
   * **Location**: Line 351 in `S3::freeSigningKey()`
   * **Code**: `openssl_free_key(self::$__signingKeyResource);`
   * **Impact**: `openssl_free_key()` is deprecated in PHP 8.0 as OpenSSL key resources were migrated to `OpenSSLAsymmetricKey` objects which free memory automatically upon destruction.
   * **Fix**: Wrap call in `if (PHP_VERSION_ID < 80000) { openssl_free_key(...); }` or remove.

4. **Implicitly Nullable Parameter Types (PHP 8.4 Deprecation)**:
   * **Issue**: Parameters with type hints or default values before non-default parameters cause deprecation notices in PHP 8.4.
   * **Fix**: Explicitly define nullable types (e.g., `?string $accessKey = null`) where type declaration is used.

5. **Resource/Handle Types (`curl_init`, `fopen`)**:
   * **Issue**: In PHP 8.0, cURL resources (`resource`) were changed to `CurlHandle` objects. Code checking `is_resource($curl)` needs to account for object handles.

---

## 3rd-Party Library Inventory & Upgrade Requirements

| Library / File | Current Version | PHP 8.4 Compatibility Status | Recommended Action |
| :--- | :--- | :--- | :--- |
| **ADOdb** (`libs/adodb/`) | `5.00` (2007) | **CRITICAL FAILURE**: Uses `each()`, PHP 4 constructors, dynamic properties, `get_magic_quotes_gpc()`. | Upgrade to **ADOdb 5.22.x+** or replace deprecated syntax. |
| **Smarty** (`libs/smarty/`) | `2.6.19` (2008) | **CRITICAL FAILURE**: Incompatible with PHP 8 (PHP 4 constructors `function Smarty()`, `var`, `each()`, `eval()`). | Upgrade to **Smarty 3/4/5** or apply a compatibility shim / patch layer. |
| **S3** (`libs/3rdparty/S3.php`) | `0.5.1` (Custom) | **FATAL SYNTAX ERROR**: Curly brace indexing `$value{0}`, `openssl_free_key()`. | Refactor inline fixes: `$value[0]`, update OpenSSL calls, maintain static state compatibility. |
| **JSON.php** (`libs/3rdparty/JSON.php`) | Services_JSON 1.3.1 | **FATAL SYNTAX ERROR**: Uses `$str{1}` curly brace indexing. | Replace usages with native `json_encode()` / `json_decode()` or fix syntax. |
| **PorterStemmer** (`libs/3rdparty/PorterStemmer.class.php`) | Legacy | **FATAL SYNTAX ERROR**: Uses `$str{$i}` curly brace indexing. | Update syntax to `$str[$i]`. |
| **PHPMailer** (`libs/3rdparty/class.phpmailer.php`) | Legacy | **HIGH RISK**: Uses `get_magic_quotes_runtime()`, `set_magic_quotes_runtime()`, PHP 4 constructors. | Upgrade to modern PHPMailer or patch magic quotes logic. |
| **Sphinx API** (`libs/3rdparty/sphinxapi.php`) | Legacy | **MEDIUM RISK**: Uses curly brace syntax and legacy socket options. | Refactor curly brace syntax to square brackets. |

---

## Core Codebase Compatibility Issues (PHP 8.0 &ndash; PHP 8.4)

### 1. Array and String Curly Brace Offset Access (`$var{0}`)
In PHP 8.0, accessing string/array offsets via curly braces was removed.
* **Impact**: `Fatal error: Array and string offset access syntax with curly braces is no longer supported`
* **Affected Files**:
  * `libs/3rdparty/JSON.php` (line 167)
  * `libs/3rdparty/S3.php` (line 2608)
  * `libs/3rdparty/PorterStemmer.class.php` (line 388)
  * `libs/geograph/uploadmanager.class.php` (line 543)
  * `libs/geograph/b2evo_captcha.class.php` (line 637)
  * `libs/geograph/kmlfile2.class.php` (line 173)
  * `libs/geograph/sphinxwrapper.class.php` (line 722)
  * `scripts/links-3-check-links.php` (line 310)

### 2. Removal of `__autoload()`
In PHP 8.0, the global `__autoload()` function was removed.
* **Impact**: `Fatal error: __autoload() is no longer supported, use spl_autoload_register() instead`
* **Affected File**: `libs/geograph/global.inc.php` (line 677)
* **Remediation**:
  ```php
  // Replace:
  function __autoload($class_name) { ... }

  // With:
  spl_autoload_register(function ($class_name) { ... });
  ```

### 3. Duplicate Static Variable Declarations
PHP 8.0 strictly forbids declaring the same static variable twice in the same scope.
* **Impact**: `Fatal error: Duplicate declaration of static variable $starts`
* **Affected File**: `libs/geograph/functions.inc.php` (lines 92 & 116)
* **Remediation**: Remove the redundant `static $starts;` declaration.

### 4. Removal of `utf8_encode()` and `utf8_decode()`
`utf8_encode()` and `utf8_decode()` were deprecated in PHP 8.2 and **removed in PHP 8.4**.
* **Impact**: `Fatal error: Uncaught Error: Call to undefined function utf8_encode()`
* **Occurrences**: Over 40 calls across `libs/geograph/global.inc.php`, `kmlfile.class.php`, `feedcreator.class.php`, `public_html/api-facetql.php`, `public_html/api-facetql-vector.php`, `scripts/build_sitemap.php`, etc.
* **Remediation**: Replace with `mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1')` or `iconv('ISO-8859-1', 'UTF-8', $str)`.

### 5. Removal of `each()`
In PHP 8.0, the `each()` function was removed.
* **Impact**: `Fatal error: Uncaught Error: Call to undefined function each()`
* **Affected Files**:
  * `libs/geograph/feedcreator.class.php` (`while(list(, $line) = each($lines))`)
  * `public_html/showcase/includes/feedcreator.class.php`
  * `public_html/finder/refine.php` (`list(, $first) = each($sorted);`)
  * `public_html/discuss/bb_admin.php` (`while (list($key) = each($banip))`)
* **Remediation**: Replace with `foreach` loops or `key()`/`current()`/`next()`.

### 6. Deprecation of `strftime()` and `gmstrftime()`
Deprecated in PHP 8.1, scheduled for removal in PHP 9.0.
* **Occurrences**:
  * `public_html/project/systemtask.php`
  * `public_html/geotrips/geotrip_submit.php`
  * `public_html/geotrips/geotrip_edit.php`
  * `public_html/stuff/nohotlink.php`
  * `public_html/photoset/view.php`
  * `scripts/build_sitemap.php` and other sitemap builders
* **Remediation**: Replace with `date()`, `gmdate()`, or `DateTime::format()`.

### 7. Removed Magic Quotes Functions
`get_magic_quotes_gpc()`, `get_magic_quotes_runtime()`, and `set_magic_quotes_runtime()` were removed in PHP 7.4/8.0.
* **Occurrences**: `libs/adodb/adodb.inc.php`, `libs/adodb/adodb-perf.inc.php`, `libs/3rdparty/class.phpmailer.php`.
* **Remediation**: Replace function calls with static `false` checks.

### 8. Implicitly Nullable Parameter Types (PHP 8.4 Deprecation)
In PHP 8.4, implicitly marking a parameter as nullable (e.g., `function foo(string $param = null)`) generates an `E_DEPRECATED` warning.
* **Remediation**: Use explicit nullable syntax: `function foo(?string $param = null)`.

### 9. Dynamic Property Deprecation (PHP 8.2)
In PHP 8.2, creating dynamic properties on classes is deprecated unless the class uses the `#[AllowDynamicProperties]` attribute or extends `stdClass`.
* **Impact**: Emits `E_DEPRECATED` on custom classes like `GridImage`, `User`, `SearchEngine`, etc.
* **Remediation**: Explicitly declare class properties or add `#[AllowDynamicProperties]` attribute to legacy classes.

### 10. Short Open Tags (`<?`)
* **Finding**: Many legacy templates and scripts in `public_html/` and `libs/` use short open tags (`<?` and `<?=`).
* **Requirement**: Ensure `short_open_tag = On` is configured in `php.ini` for the PHP 8.4 FPM container environment, or run a migration script to convert `<?` to `<?php`.

---

## Docker Environment & Infrastructure Assessment

The container configuration is located in `system/docker/geograph/Dockerfile`.

### Current Container Setup
```dockerfile
FROM tigercomputing/debian-php-fpm:php7.4

RUN apt-get install -y --no-install-recommends \
    php7.4-bz2 \
    php7.4-memcache \
    php7.4-redis \
    ...
```

### Upgrading to PHP 8.4
1. **Base Image Options**:
   * **Option A**: Use official PHP 8.4 FPM image based on Debian Bookworm: `php:8.4-fpm-bookworm`.
   * **Option B**: Update Debian repository sources to Debian 12 (Bookworm) or Sury PHP repository (`ppa:ondrej/php` or `packages.sury.org/php/`) for Debian.
2. **PHP Package Extensions for PHP 8.4**:
   * `php7.4-bz2` &rarr; `php8.4-bz2`
   * `php7.4-memcache` &rarr; `php8.4-memcached` (or PECL `memcache` / `memcached`)
   * `php7.4-redis` &rarr; `php8.4-redis`
3. **Nginx & FPM Configuration**:
   * Update FastCGI socket/host pass directives in `/etc/nginx/` if PHP-FPM service/socket paths change from `php7.4-fpm.sock` to `php8.4-fpm.sock` or `127.0.0.1:9000`.

---

## Migration Roadmap & Effort Estimation

### Phase 1: Preparation & Infrastructure Updates
* Update `system/docker/geograph/Dockerfile` to PHP 8.4 base image.
* Configure `php.ini` with `short_open_tag = On` and appropriate error reporting levels (`E_ALL & ~E_DEPRECATED`).

### Phase 2: Core Syntax & Function Fixes
1. Replace all string/array curly brace offset accesses (`$str{0}` &rarr; `$str[0]`).
2. Replace `__autoload()` in `libs/geograph/global.inc.php` with `spl_autoload_register()`.
3. Fix duplicate static variable `$starts` in `libs/geograph/functions.inc.php`.
4. Replace `utf8_encode()` / `utf8_decode()` calls with `mb_convert_encoding()`.
5. Replace `each()` calls with `foreach` or array functions.
6. Replace `strftime()` calls with `date()` / `gmdate()`.

### Phase 3: Library Remediation
1. **`S3.php`**: Apply offset fix, update `openssl_free_key()` logic, verify static vs. instance variable initialization.
2. **ADOdb**: Upgrade to 5.22.x+ or patch PHP 8 compatibility issues.
3. **Smarty**: Upgrade to Smarty 3/4/5 or apply compatibility layer for PHP 8 template compilation.
4. **JSON / PHPMailer / SphinxAPI**: Apply syntax fixes and patch deprecated function calls.

### Phase 4: Testing & Verification
1. Run syntax linting (`/usr/bin/php -d short_open_tag=On -l`) across all `.php` and `.inc.php` files.
2. Test image uploads, S3 storage operations, database query execution, search indexing, and template rendering.
