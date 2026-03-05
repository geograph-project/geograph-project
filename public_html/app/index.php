<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PMA - Personal Management Application</title>
    <link rel="stylesheet" href="/app/assets/css/style.css">
</head>
<body>
    <header id="main-header">
        <div class="nav-left">
            <button id="btn-home" class="nav-btn" data-route="/app/">
                <span class="icon-container"></span>
            </button>
        </div>
        <div class="nav-center">
            <h1 id="page-title">PMA</h1>
        </div>
        <div class="nav-right">
            <button id="btn-search" class="nav-btn" data-route="/app/search">
                <span class="icon-container"></span>
            </button>
            <button id="btn-profile" class="nav-btn" data-route="/app/profile">
                <span class="icon-container"></span>
            </button>
        </div>
    </header>

    <main id="app-container">
        <!-- Dynamic content goes here -->
    </main>

    <div id="iframe-container">
        <!-- Persistent iframes go here -->
    </div>

    <footer id="main-footer">
        <div class="nav-left">
             <button id="btn-upload" class="nav-btn" data-route="/app/upload">
                <span class="icon-container"></span>
            </button>
            <button id="btn-map" class="nav-btn" data-route="/app/map">
                <span class="icon-container"></span>
            </button>
        </div>
        <div class="nav-right">
            <button id="btn-menu" class="nav-btn">
                <span class="icon-container"></span>
            </button>
        </div>
    </footer>

    <div id="menu-overlay" class="hidden">
        <nav id="drawer-menu">
            <ul>
                <li><a href="/app/settings" data-route="/app/settings">Settings</a></li>
                <li><a href="/app/recent" data-route="/app/recent">Recent Submissions</a></li>
                <li><a href="/app/help" data-route="/app/help">App Help</a></li>
                <li><a href="/app/contact" data-route="/app/contact">Contact Us</a></li>
                <li><a href="/app/tos" data-route="/app/tos">Terms of Service</a></li>
            </ul>
        </nav>
    </div>

    <script type="module" src="/app/js/main.js"></script>
</body>
</html>
