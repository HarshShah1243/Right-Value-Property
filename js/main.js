/**
 * This script handles the dynamic loading of shared components like the header and footer.
 * It waits for the entire window to load to avoid conflicts with other page scripts.
 */

// Use the window.onload event to ensure all other scripts and resources have finished loading.
window.onload = function() {

    console.log("Main script started after page load.");

    /**
     * Fetches data from a specified collection.
     * @param {string} collectionName - The name of the data collection to fetch.
     * @returns {Promise<Object|Array>} - The fetched data.
     */
    const fetchData = async (collectionName) => {
        try {
            const response = await fetch(`api/api.php?collection=${collectionName}&t=${new Date().getTime()}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();
            console.log(`Successfully fetched data for: ${collectionName}`, data);
            return data;
        } catch (error) {
            console.error(`Failed to fetch ${collectionName}:`, error);
            return collectionName.startsWith('page_') || collectionName === 'settings' ? {} : [];
        }
    };

    /**
     * Loads an HTML component into a placeholder element.
     * @param {string} placeholderId - The ID of the element to load the HTML into.
     * @param {string} url - The URL of the HTML file to load.
     */
    const loadComponent = async (placeholderId, url) => {
        const placeholder = document.getElementById(placeholderId);
        if (placeholder) {
            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error(`Could not load ${url}`);
                placeholder.innerHTML = await response.text();
                console.log(`Successfully loaded component: ${url}`);
            } catch (error) {
                console.error(`Error loading component from ${url}:`, error);
                placeholder.innerHTML = `<p class="text-center text-red-500">Error: Could not load ${placeholderId.replace('-placeholder', '')}.</p>`;
            }
        } else {
            console.error(`Placeholder element not found: #${placeholderId}`);
        }
    };

    /**
     * Initializes all interactive scripts for the header.
     */
    const initializeHeaderScripts = () => {
        const header = document.querySelector('header');
        if (!header) {
            console.error("Header element not found for initialization.");
            return;
        }
        console.log("Initializing header scripts...");

        // Mobile Menu, Active Links, Cursor, Scroll animations...
        const mobileMenuButton = header.querySelector('#mobile-menu-button');
        const mobileMenu = header.querySelector('#mobile-menu');
        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
        }

        const currentPage = window.location.pathname.split('/').pop() || 'index.html';
        header.querySelectorAll('a.nav-link').forEach(link => {
            if ((link.getAttribute('href').split('/').pop() || 'index.html') === currentPage) {
                link.classList.add('active', 'text-[var(--primary-red)]', 'font-semibold');
            }
        });

        if (!document.querySelector('.cursor-dot')) {
            const cursorDot = document.createElement('div');
            cursorDot.className = 'cursor-dot';
            document.body.appendChild(cursorDot);
            const cursorOutline = document.createElement('div');
            cursorOutline.className = 'cursor-outline';
            document.body.appendChild(cursorOutline);
            window.addEventListener('mousemove', (e) => {
                cursorDot.style.left = `${e.clientX}px`;
                cursorDot.style.top = `${e.clientY}px`;
                cursorOutline.animate({ left: `${e.clientX}px`, top: `${e.clientY}px` }, { duration: 500, fill: "forwards" });
            });
        }
        
        document.querySelectorAll('a, button, .cursor-link').forEach(link => {
            link.addEventListener('mouseenter', () => document.body.classList.add('cursor-link-hover'));
            link.addEventListener('mouseleave', () => document.body.classList.remove('cursor-link-hover'));
        });
        
        let lastScrollTop = 0;
        window.addEventListener('scroll', () => {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            header.classList.toggle('scrolled', scrollTop > 50);
            header.classList.toggle('header-hidden', scrollTop > lastScrollTop && scrollTop > header.offsetHeight);
            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        }, false);
    };

    /**
     * Populates the footer with dynamic data and initializes its scripts.
     * @param {Object} settings - The site-wide settings data.
     */
    const initializeFooter = (settings) => {
        if (!document.getElementById('footer-placeholder')) {
             console.error("Footer placeholder not found.");
             return;
        }
        console.log("Initializing footer with settings:", settings);

        // --- 1. Populate Footer Content ---
        const footerContactInfo = document.getElementById('footer-contact-info');
        if (footerContactInfo) {
            footerContactInfo.innerHTML = `
                <p class="flex items-start"><i class="fa-solid fa-location-dot w-4 mr-3 mt-1 text-[var(--primary-red)]"></i><span>${settings.address || 'Address not set.'}</span></p>
                <p class="flex items-start"><i class="fa-solid fa-envelope w-4 mr-3 mt-1 text-[var(--primary-red)]"></i><a href="mailto:${settings.email || '#'}" class="hover:text-[var(--primary-red)] cursor-link">${settings.email || 'Email not set.'}</a></p>
                <p class="flex items-start"><i class="fa-solid fa-phone w-4 mr-3 mt-1 text-[var(--primary-red)]"></i><a href="tel:${settings.phone || '#'}" class="hover:text-[var(--primary-red)] cursor-link">${settings.phone || 'Phone not set.'}</a></p>
            `;
        }

        const socialLinksContainer = document.getElementById('footer-social-links');
        if (socialLinksContainer) {
            let linksHtml = '';
            // Ensure the URL is valid before creating the link
            if (settings.facebook_url && settings.facebook_url.startsWith('http')) {
                 linksHtml += `<a href="${settings.facebook_url}" target="_blank" aria-label="Facebook" class="text-gray-500 hover:text-[var(--primary-red)] transition-colors cursor-link"><i class="fa-brands fa-facebook-f fa-lg"></i></a>`;
            }
            if (settings.instagram_url && settings.instagram_url.startsWith('http')) {
                linksHtml += `<a href="${settings.instagram_url}" target="_blank" aria-label="Instagram" class="text-gray-500 hover:text-[var(--primary-red)] transition-colors cursor-link"><i class="fa-brands fa-instagram fa-lg"></i></a>`;
            }
            if (settings.twitter_url && settings.twitter_url.startsWith('http')) {
                linksHtml += `<a href="${settings.twitter_url}" target="_blank" aria-label="Twitter" class="text-gray-500 hover:text-[var(--primary-red)] transition-colors cursor-link"><i class="fa-brands fa-x-twitter fa-lg"></i></a>`;
            }
            if (settings.linkedin_url && settings.linkedin_url.startsWith('http')) {
                linksHtml += `<a href="${settings.linkedin_url}" target="_blank" aria-label="LinkedIn" class="text-gray-500 hover:text-[var(--primary-red)] transition-colors cursor-link"><i class="fa-brands fa-linkedin-in fa-lg"></i></a>`;
            }
            
            if(linksHtml === '') {
                socialLinksContainer.innerHTML = `<p class="text-xs text-gray-400">Social links not configured.</p>`;
            } else {
                socialLinksContainer.innerHTML = linksHtml;
            }
        }

        const reraEl = document.getElementById('footer-rera');
        if (reraEl) reraEl.textContent = `RERA: ${settings.rera_number || 'Not Available'}`;

        const copyrightEl = document.getElementById('footer-copyright');
        if (copyrightEl) copyrightEl.textContent = settings.copyright_text || `© ${new Date().getFullYear()} RIGHT VALUE PROPERTY. All Rights Reserved.`;

        // --- 2. Initialize Footer Interactive Scripts ---
        const statusElement = document.getElementById('office-status');
        const timeElement = document.getElementById('current-time');
        if (statusElement && timeElement) {
            const updateOfficeStatus = () => {
                const openHour = parseInt(settings.opening_hour, 10) || 10;
                const closeHour = parseInt(settings.closing_hour, 10) || 19;
                const closedDays = (settings.closed_days || 'Sun').split(',').map(d => d.trim());

                const now = new Date();
                const options = { timeZone: 'Asia/Kolkata', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
                const timeString = now.toLocaleTimeString('en-IN', options);
                const day = now.toLocaleString('en-US', { timeZone: 'Asia/Kolkata', weekday: 'short' });
                const hour = parseInt(now.toLocaleString('en-US', { timeZone: 'Asia/Kolkata', hour: '2-digit', hour12: false }));
                
                const isOpen = !closedDays.includes(day) && hour >= openHour && hour < closeHour;

                timeElement.textContent = `${timeString} (IST)`;
                statusElement.textContent = isOpen ? 'Open Now' : 'Closed';
                statusElement.classList.toggle('open', isOpen);
                statusElement.classList.toggle('closed', !isOpen);
            };
            updateOfficeStatus();
            setInterval(updateOfficeStatus, 1000);
            console.log("Office status clock initialized.");
        } else {
            console.error("Office status elements not found in footer.");
        }

        const scrollToTopBtn = document.getElementById('scroll-to-top');
        if (scrollToTopBtn) {
            window.addEventListener('scroll', () => {
                scrollToTopBtn.classList.toggle('show', window.pageYOffset > 300);
            });
            scrollToTopBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
        }
    };

    /**
     * Main function to initialize the page.
     */
    const initializePage = async () => {
        // Load HTML components first
        await Promise.all([
            loadComponent('header-placeholder', 'header.html'),
            loadComponent('footer-placeholder', 'footer.html')
        ]);
        
        // Fetch settings data
        const siteSettings = await fetchData('settings');
        
        // Initialize components with data
        initializeHeaderScripts();
        initializeFooter(siteSettings);

        // Refresh AOS if it was loaded by the main page
        if (typeof AOS !== 'undefined') {
            AOS.refresh();
            console.log("AOS refreshed.");
        }
    };

    // Run the initialization function
    initializePage();
};
