// Settings menu functionality
document.addEventListener('DOMContentLoaded', function () {
    const settingsToggle = document.getElementById('settingsToggle');
    const settingsDropdown = document.getElementById('settingsDropdown');

    if (settingsToggle && settingsDropdown) {
        settingsToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            settingsDropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', function (e) {
            if (!settingsToggle.contains(e.target) && !settingsDropdown.contains(e.target)) {
                settingsDropdown.classList.add('hidden');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !settingsDropdown.classList.contains('hidden')) {
                settingsDropdown.classList.add('hidden');
            }
        });
    }
});

// Mobile sidebar enhancement
document.addEventListener('DOMContentLoaded', function () {
    // Find all possible sidebar toggle elements
    const menuToggle = document.getElementById('menuToggle') ||
        document.querySelector('.navbar-toggler') ||
        document.querySelector('[class*="menu"]') ||
        document.querySelector('nav button') ||
        document.querySelector('header button');

    // Find all possible sidebar elements
    const sidebar = document.querySelector('.sidebar') ||
        document.querySelector('.navbar-collapse') ||
        document.querySelector('nav > ul') ||
        document.querySelector('[class*="side"]');

    // Find app title/logo
    const appTitle = document.querySelector('.navbar-brand') ||
        document.querySelector('h1') ||
        document.querySelector('header a') ||
        document.querySelector('nav a:first-child');

    if (!menuToggle) {
        const possibleToggle = document.querySelector('i[class*="fa"]') || document.querySelector('svg');
        if (possibleToggle) {
            const toggleParent = possibleToggle.closest('button') || possibleToggle.parentElement;
            if (toggleParent) menuToggle = toggleParent;
        }
    }

    // Exit if essential elements are missing
    if (!menuToggle || !sidebar) {
        console.error('Missing required elements for sidebar toggle. Toggle:', menuToggle, 'Sidebar:', sidebar);
        return;
    }

    // Create overlay if it doesn't exist
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    // Function to ensure all sidebar navigation items are visible
    function ensureSidebarItemsVisible() {
        // Find all navigation items, icons, and labels in the sidebar
        const navItems = sidebar.querySelectorAll('a, li, button, .nav-item, [class*="nav"], [class*="menu"], [class*="list"]');
        const icons = sidebar.querySelectorAll('i, svg, img, [class*="icon"]');
        const labels = sidebar.querySelectorAll('span, label, p, h1, h2, h3, h4, h5, h6');

        // Make all navigation items visible and properly displayed
        navItems.forEach(item => {
            item.style.display = 'block';
            item.style.visibility = 'visible';
            item.style.opacity = '1';
            item.style.position = 'relative';
            item.style.overflow = 'visible';
            item.style.height = 'auto';
            item.style.minHeight = '24px';
            item.style.margin = '8px 0';
            item.style.padding = '4px 0';
            item.style.whiteSpace = 'normal';
        });

        // Make all icons visible
        icons.forEach(icon => {
            icon.style.display = 'inline-block';
            icon.style.visibility = 'visible';
            icon.style.opacity = '1';
            icon.style.verticalAlign = 'middle';
            icon.style.marginRight = '8px';
        });

        // Make all text labels visible
        labels.forEach(label => {
            label.style.display = 'inline-block';
            label.style.visibility = 'visible';
            label.style.opacity = '1';
            label.style.verticalAlign = 'middle';
            label.style.whiteSpace = 'normal';
            label.style.overflow = 'visible';
            label.style.textOverflow = 'clip';
        });

        // Additional specific classes often used in navigation
        const specificItems = [
            '.all-notes', '.shared', '.pinned', '.labels', '.nav-link',
            '.dropdown', '.dropdown-menu', '.dropdown-item', '.nav-pills',
            '[class*="note"]', '[class*="share"]', '[class*="pin"]', '[class*="label"]'
        ];

        specificItems.forEach(selector => {
            const elements = sidebar.querySelectorAll(selector);
            elements.forEach(el => {
                el.style.display = 'block';
                el.style.visibility = 'visible';
                el.style.opacity = '1';
            });
        });
    }

    // Apply mobile-specific styles
    function applyMobileStyles() {
        const viewportWidth = window.innerWidth;
        const isMobile = viewportWidth <= 768;

        // Remove any existing padding or margin from body
        document.body.style.margin = '0';
        document.body.style.padding = '0';
        document.body.style.paddingTop = '0';
        document.body.style.overflow = 'auto';

        // Adjust main content container if it exists
        const mainContent = document.querySelector('.main-content') ||
            document.querySelector('main') ||
            document.querySelector('.container');
        if (mainContent) {
            mainContent.style.margin = '0';
            mainContent.style.padding = '0';
            mainContent.style.marginTop = '0';
            mainContent.style.paddingTop = '0';
        }

        if (isMobile) {
            // Get the navbar element
            const navbar = menuToggle.closest('nav') || document.querySelector('nav');
            const navbarHeight = 40;
            const sidebarElements = sidebar.querySelectorAll('*');
            sidebarElements.forEach(element => {
                element.style.display = '';
                element.style.visibility = 'visible';
                element.style.opacity = '1';
                if (element.textContent && element.textContent.trim().length > 0) {
                    element.style.overflow = 'visible';
                    element.style.whiteSpace = 'normal';
                    element.style.textOverflow = 'clip';
                }
            });

            const sidebarWidth = viewportWidth < 400 ? '85%' : '250px';

            sidebar.style.position = 'fixed';
            sidebar.style.top = '0';
            sidebar.style.left = `-${sidebarWidth}`;
            sidebar.style.height = '100%';
            sidebar.style.width = sidebarWidth;
            sidebar.style.maxWidth = '85%';
            sidebar.style.zIndex = '9980';
            sidebar.style.transition = 'left 0.3s ease-in-out, transform 0.3s ease-in-out';
            sidebar.style.backgroundColor = '#ffffff';
            sidebar.style.boxShadow = '2px 0 10px rgba(0,0,0,0.2)';
            sidebar.style.overflowY = 'auto';
            sidebar.style.overflowX = 'hidden';
            sidebar.style.display = 'block';
            sidebar.style.padding = '10px';
            sidebar.style.paddingTop = `${navbarHeight}px`;

            // Make sure navigation parent is positioned correctly
            const sidebarParent = sidebar.parentElement;
            if (sidebarParent && sidebarParent !== document.body) {
                sidebarParent.style.position = 'static';
                sidebarParent.style.overflow = 'visible';
                sidebarParent.style.margin = '0';
                sidebarParent.style.padding = '0';
            }

            if (navbar) {
                navbar.style.position = 'fixed';
                navbar.style.top = '0';
                navbar.style.left = '0';
                navbar.style.width = '100%';
                navbar.style.height = `${navbarHeight}px`;
                navbar.style.lineHeight = `${navbarHeight}px`;
                navbar.style.margin = '0';
                navbar.style.padding = '0';
                navbar.style.zIndex = '9990';
                navbar.style.backgroundColor = '#ffffff';
                navbar.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';

                const navbarItems = navbar.querySelectorAll('a, button, img, i, svg');
                navbarItems.forEach(item => {
                    item.style.height = 'auto';
                    item.style.maxHeight = `${navbarHeight - 10}px`;
                    item.style.padding = '5px';
                    item.style.margin = '0 5px';
                    item.style.lineHeight = 'normal';
                    item.style.verticalAlign = 'middle';
                });
            }

            if (appTitle) {
                appTitle.style.zIndex = '9991';
                appTitle.style.position = 'relative';
                appTitle.style.display = 'inline-block';
                appTitle.style.fontSize = '1rem';
                appTitle.style.padding = '0 5px';
                appTitle.style.margin = '0';
                appTitle.style.lineHeight = `${navbarHeight}px`;
                appTitle.style.verticalAlign = 'middle';
            }

            if (menuToggle) {
                menuToggle.style.zIndex = '9992';
                menuToggle.style.position = 'relative';
                menuToggle.style.display = 'inline-block';
                menuToggle.style.cursor = 'pointer';
                menuToggle.style.height = `${navbarHeight - 10}px`;
                menuToggle.style.width = `${navbarHeight - 10}px`;
                menuToggle.style.padding = '5px';
                menuToggle.style.margin = '0';
                menuToggle.style.verticalAlign = 'middle';
            }

            // Overlay styles
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.right = '0';
            overlay.style.bottom = '0';
            overlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
            overlay.style.zIndex = '9979';
            overlay.style.display = 'none';

            const header = document.querySelector('header');
            if (header) {
                header.style.margin = '0';
                header.style.padding = '0';
                header.style.height = 'auto';
            }

            ensureSidebarItemsVisible();

            const wrappers = document.querySelectorAll('.wrapper, .container-fluid, .page-wrapper');
            wrappers.forEach(wrapper => {
                wrapper.style.margin = '0';
                wrapper.style.padding = '0';
                wrapper.style.paddingTop = '0';
                wrapper.style.marginTop = '0';
            });

            document.documentElement.style.margin = '0';
            document.documentElement.style.padding = '0';
        } else {
            // Reset styles for larger screens
            sidebar.style = '';
            overlay.style.display = 'none';

            // Reset all sidebar elements
            const sidebarElements = sidebar.querySelectorAll('*');
            sidebarElements.forEach(element => {
                element.style = '';
            });

            // Reset navbar
            const navbar = menuToggle.closest('nav') || document.querySelector('nav');
            if (navbar) {
                navbar.style = '';

                // Reset navbar items
                const navbarItems = navbar.querySelectorAll('*');
                navbarItems.forEach(item => {
                    item.style = '';
                });
            }

            // Reset title and toggle
            if (appTitle) {
                appTitle.style = '';
            }

            if (menuToggle) {
                menuToggle.style = '';
            }

            // Reset sidebar parent
            const sidebarParent = sidebar.parentElement;
            if (sidebarParent && sidebarParent !== document.body) {
                sidebarParent.style = '';
            }
        }
    }

    applyMobileStyles();

    function toggleSidebar() {
        const viewportWidth = window.innerWidth;
        const sidebarWidth = viewportWidth < 400 ? '85%' : '250px';
        const currentLeft = sidebar.style.left;

        if (currentLeft === '0px') {
            // Hide sidebar
            sidebar.style.left = `-${sidebarWidth}`;
            overlay.style.display = 'none';
            document.body.style.overflow = 'auto';
        } else {
            // Show sidebar
            sidebar.style.left = '0px';
            sidebar.style.display = 'block';
            overlay.style.display = 'block';
            document.body.style.overflow = 'hidden';

            ensureSidebarItemsVisible();

            setTimeout(() => {
                ensureSidebarItemsVisible();
            }, 50);
        }
    }

    ['click', 'touchend'].forEach(eventType => {
        menuToggle.addEventListener(eventType, function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });

        overlay.addEventListener(eventType, function (e) {
            e.preventDefault();
            toggleSidebar();
        });
    });

    // Close sidebar on escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar.style.left === '0px') {
            toggleSidebar();
        }
    });

    // Re-apply styles on window resize
    window.addEventListener('resize', function () {
        applyMobileStyles();

        // Close sidebar if screen becomes large
        if (window.innerWidth > 768 && sidebar.style.left === '0px') {
            toggleSidebar();
        }
    });
});

